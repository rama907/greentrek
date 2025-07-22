#include <TinyGPS++.h>
#include <Wire.h>
#include <Adafruit_INA219.h>

// Tidak perlu SoftwareSerial dan AltSoftSerial karena Mega punya banyak hardware serial

// Menggunakan Serial1 untuk SIM800L dan Serial2 untuk GPS
#define SIM800L_SERIAL Serial1  // Pin 18 (TX1) dan 19 (RX1)
#define GPS_SERIAL Serial2      // Pin 16 (TX2) dan 17 (RX2)

TinyGPSPlus gps;
Adafruit_INA219 ina219;

unsigned long previousMillis = 0;
const long interval = 30000; // Kirim setiap 60 detik
const int maxRetries = 3;
const unsigned long retryDelay = 5000;

void setup() {
  Serial.begin(115200);      // Serial Monitor
  SIM800L_SERIAL.begin(9600); // SIM800L
  GPS_SERIAL.begin(9600);      // GPS Module

  // Initialize INA219
  if (!ina219.begin()) {
    Serial.println("Failed to find INA219 chip");
    while (1) { delay(10); }
  }
  Serial.println("INA219 initialized successfully"); 

  Serial.println("Initializing...");
  delay(5000);

  sendATcommand("AT", "OK", 3000);
  sendATcommand("AT+CMGF=1", "OK", 3000);
  sendATcommand("AT+CREG?", "0,1", 3000);
  sendATcommand("AT+CGATT?", "1", 3000);
}

void loop() {
  while (SIM800L_SERIAL.available()) Serial.write(SIM800L_SERIAL.read());
  while (Serial.available()) SIM800L_SERIAL.write(Serial.read());

  unsigned long currentMillis = millis();
  if (currentMillis - previousMillis > interval) {
    previousMillis = currentMillis;
    sendGpsWithRetry();
  }
}

void sendGpsWithRetry() {
  boolean newData = false;
  unsigned long start = millis();

  while (millis() - start < 2000) {
    while (GPS_SERIAL.available()) {
      if (gps.encode(GPS_SERIAL.read())) newData = true;
    }
  }

  if (!newData || !gps.location.isValid()) {
    Serial.println("No valid GPS data");
    return;
  }

  String latitude = String(gps.location.lat(), 6);
  String longitude = String(gps.location.lng(), 6);
  String speed = String(gps.speed.kmph(), 2); // km/jam

  // Baca data INA219
  float shuntvoltage = ina219.getShuntVoltage_mV();
  float busvoltage = ina219.getBusVoltage_V();
  float current_mA = ina219.getCurrent_mA();
  float power_mW = ina219.getPower_mW();
  float loadvoltage = busvoltage + (shuntvoltage / 1000);

  Serial.print("Latitude= ");
  Serial.print(latitude);
  Serial.print(" Longitude= ");
  Serial.print(longitude);
  Serial.print(" Speed= ");
  Serial.print(speed);
  Serial.print(" km/h");
  Serial.print(" Shunt Voltage= ");
  Serial.print(shuntvoltage);
  Serial.print("mV Bus Voltage= ");
  Serial.print(busvoltage);
  Serial.print("V Load Voltage= ");
  Serial.print(loadvoltage);
  Serial.print("V Current= ");
  Serial.print(current_mA);
  Serial.print("mA Power= ");
  Serial.print(power_mW);
  Serial.println("mW");

  int attempts = 0;
  bool success = false;

  while (attempts < maxRetries && !success) {
    if (!setupGPRS()) {
      Serial.println("GPRS setup failed");
      attempts++;
      delay(retryDelay);
      continue;
    }

    Serial.println("Mengirim data via HTTP POST...");
    if (sendHttpPost(latitude, longitude, speed, loadvoltage, busvoltage, shuntvoltage)) {
      Serial.println("✅ HTTP POST success");
      success = true;
    } else {
      Serial.println("❌ HTTP POST failed, retrying...");
      attempts++;
      delay(retryDelay);
    }

    sendATcommand("AT+HTTPTERM", "OK", 5000);
    sendATcommand("AT+SAPBR=0,1", "OK", 5000);
  }

  if (!success) {
    Serial.println("❌ Failed to send GPS data after retries");
  }
}

bool setupGPRS() {
  sendATcommand("AT+CFUN=1", "OK", 3000);
  delay(10000);

  sendATcommand("AT+SAPBR=0,1", "OK", 3000);
  sendATcommand("AT+HTTPTERM", "OK", 3000);

  if (!sendATcommand("AT+CGATT=1", "OK", 5000)) return false;
  if (!sendATcommand("AT+SAPBR=3,1,\"CONTYPE\",\"GPRS\"", "OK", 3000)) return false;
  if (!sendATcommand("AT+SAPBR=3,1,\"APN\",\"internet\"", "OK", 3000)) return false;

  delay(3000);

  if (!sendATcommand("AT+SAPBR=1,1", "OK", 7000)) return false;

  SIM800L_SERIAL.println("AT+SAPBR=2,1");
  String resp = readResponse(5000);
  if (resp.indexOf("+SAPBR: 1,1") != -1 && resp.indexOf("0.0.0.0") == -1) {
    Serial.println("Bearer aktif dengan IP: " + resp);
    return true;
  }

  Serial.println("Bearer tidak aktif: " + resp);
  return false;
}

bool sendHttpPost(String latitude, String longitude, String speed, float loadvoltage, float busvoltage, float shuntvoltage) {
  String postData = "lat=" + latitude + "&lng=" + longitude + "&speed=" + speed + 
                   "&loadvoltage=" + String(loadvoltage, 3) + "&busvoltage=" + String(busvoltage, 3) + 
                   "&shuntvoltage=" + String(shuntvoltage, 3);

  sendATcommand("AT+HTTPINIT", "OK", 5000);
  sendATcommand("AT+HTTPPARA=\"CID\",1", "OK", 3000);
  sendATcommand("AT+HTTPPARA=\"URL\",\"http://aditya.globaltech.id/gpsdata.php\"", "OK", 3000);
  sendATcommand("AT+HTTPPARA=\"CONTENT\",\"application/x-www-form-urlencoded\"", "OK", 3000);

  SIM800L_SERIAL.println("AT+HTTPDATA=" + String(postData.length()) + ",10000");
  if (!waitForResponse("DOWNLOAD", 5000)) return false;

  SIM800L_SERIAL.print(postData);
  if (!waitForResponse("OK", 10000)) return false;

  SIM800L_SERIAL.println("AT+HTTPACTION=1");
  String resp = waitForHTTPResponse(10000);
  Serial.println("HTTPACTION POST response: " + resp);

  if (resp.indexOf("+HTTPACTION: 1,200") != -1) return true;

  Serial.println("HTTP POST gagal atau status bukan 200");
  return false;
}

int sendATcommand(String command, String expectedResponse, unsigned long timeout) {
  SIM800L_SERIAL.println(command);
  Serial.print(">> ");
  Serial.println(command);

  unsigned long startTime = millis();
  String response = "";

  while (millis() - startTime < timeout) {
    while (SIM800L_SERIAL.available()) {
      char c = SIM800L_SERIAL.read();
      response += c;
    }
    if (response.indexOf(expectedResponse) != -1) {
      Serial.print("<< ");
      Serial.println(response);
      return 1;
    }
  }
  Serial.print("<< Timeout or unexpected response: ");
  Serial.println(response);
  return 0;
}

bool waitForResponse(String expected, unsigned long timeout) {
  unsigned long start = millis();
  String response = "";
  while (millis() - start < timeout) {
    while (SIM800L_SERIAL.available()) {
      response += (char)SIM800L_SERIAL.read();
    }
    if (response.indexOf(expected) != -1) {
      Serial.print("<< ");
      Serial.println(response);
      return true;
    }
  }
  Serial.print("<< Timeout or unexpected response: ");
  Serial.println(response);
  return false;
}

String waitForHTTPResponse(unsigned long timeout) {
  unsigned long start = millis();
  String response = "";
  while (millis() - start < timeout) {
    while (SIM800L_SERIAL.available()) {
      char c = (char)SIM800L_SERIAL.read();
      response += c;
      int pos = response.indexOf("+HTTPACTION: 1,");
      if (pos != -1 && response.length() >= pos + 21) {
        String code = response.substring(pos + 16, pos + 19);
        Serial.print("HTTP Status Code: ");
        Serial.println(code);
        return response;
      }
    }
  }
  Serial.println("Timeout menunggu HTTPACTION response");
  Serial.println(response);
  return response;
}

String readResponse(unsigned long timeout) {
  unsigned long start = millis();
  String response = "";
  while (millis() - start < timeout) {
    while (SIM800L_SERIAL.available()) {
      response += (char)SIM800L_SERIAL.read();
    }
  }
  return response;
}
