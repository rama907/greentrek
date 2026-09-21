# GreenTrek — IoT GPS & Sensor Monitoring for Farm Machinery

A web application for monitoring the position and condition of agricultural machinery. An Arduino-based device sends GPS and sensor readings to a PHP/MySQL backend, and the dashboard shows the latest location, trip history and device metrics.

Built as a community service project at the Agricultural Mechanization Development Center (West Java) by students of Telkom University.

[![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=white)](#tech-stack)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?logo=mysql&logoColor=white)](#tech-stack)
[![Arduino](https://img.shields.io/badge/Arduino-00979D?logo=arduino&logoColor=white)](#hardware)
[![Portfolio](https://img.shields.io/badge/Case%20study-ramahrinaldi.id-34d399)](https://ramahrinaldi.id/projects/greentrek/)

**Live demo (simulation with dummy data):** https://ramahrinaldi.id/demo/greentrek/
**Case study, publications and news coverage:** https://ramahrinaldi.id/projects/greentrek/

## Features

- Login, registration, email OTP verification and password reset
- Login-attempt limiting and Google reCAPTCHA v2 on the forms
- Dashboard with the latest GPS position and sensor readings
- Map view and GPS trip history
- Device metrics (voltage / current / power from an INA219 sensor)
- Service history records per machine
- Transactional email through PHPMailer (SMTP)

## Architecture

```
Arduino Mega + GPS + SIM800L + INA219
        │  HTTP (GPRS)
        ▼
PHP endpoints (gpsdata.php, save_device_metrics.php, …)  ──►  MySQL
        ▲
        │  AJAX polling (get_latest_gps.php, get_gps_history.php, …)
Dashboard (dashboard.php, map_history.php)
```

## Hardware

The firmware sketch is in [`gps2.ino`](gps2.ino).

| Part | Role |
|---|---|
| Arduino Mega | Main controller |
| GPS module (TinyGPS++) | Position, speed and time |
| SIM800L | Sends data over GPRS |
| INA219 | Voltage, current and power monitoring |

Libraries: `TinyGPSPlus`, `Adafruit_INA219`, `Wire`.

## Tech stack

PHP · MySQL (mysqli) · PHPMailer · HTML / CSS / JavaScript · Arduino C++

## Getting started

1. Copy `config.example.php` to `config.php` and fill in the database, SMTP, reCAPTCHA and `DEVICE_API_KEY` values.
2. Create a MySQL database and the tables the app uses: `admin_users`, `tbl_gps`, `tbl_ina219`, `device_metrics`, `service_history`, `login_attempts`.
3. Upload the files to a PHP 7.4+ host (Apache/LiteSpeed) or run `php -S localhost:8000`.
4. Register the first account.
5. In `gps2.ino`, set `DEVICE_API_KEY` (same value as in `config.php`) and replace `YOUR_SERVER` with your host, then flash the device.

## Security

What the code does:
- SQL uses prepared statements; passwords are stored with `password_hash()`.
- Login attempts are limited per user and IP, and reCAPTCHA v2 protects the forms.
- The session ID is regenerated after login.
- Device endpoints (`gpsdata.php`, `gpsdata3.php`) require `DEVICE_API_KEY` and fail closed when it is not configured.
- Read endpoints used by the dashboard (`get_latest*.php`) require a logged-in session.
- Database errors are written to the server log, not shown to clients; `display_errors` is off.

What you must do:
- `config.php` is excluded by `.gitignore`; only `config.example.php` is committed. Never commit credentials, API keys or database dumps.
- Use an SMTP App Password, not your account password, and a long random `DEVICE_API_KEY`.
- Serve the site over HTTPS. The SIM800L module only supports plain HTTP for the device link, so put the ingest endpoint behind a server that accepts it and rotate the key periodically.

Known limitations (good next steps): no CSRF tokens on forms, and no rate limiting on the device endpoints.

Found a vulnerability? See [SECURITY.md](SECURITY.md).

## Team and credit

Community service project, Telkom University. Publications, registered copyright (HKI) and news coverage are listed on the [portfolio](https://ramahrinaldi.id/publications/).

## Author

**Ramah Rinaldi Ruslan** — Computer Engineering, Telkom University
Portfolio: https://ramahrinaldi.id · GitHub: [@rama907](https://github.com/rama907)

---

### Bahasa Indonesia

GreenTrek adalah aplikasi web untuk memantau posisi dan kondisi alat/mesin pertanian. Perangkat berbasis Arduino mengirim data GPS dan sensor ke backend PHP/MySQL, lalu dashboard menampilkan posisi terbaru, riwayat perjalanan, dan metrik perangkat. Untuk menjalankannya, salin `config.example.php` menjadi `config.php`, isi nilai database, SMTP, dan reCAPTCHA, lalu unggah ke hosting PHP.
