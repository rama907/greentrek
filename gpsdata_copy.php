<?php
require 'config.php';

// Cek apakah data latitude dan longitude ada di POST
if (!isset($_POST['lat']) || !isset($_POST['lng'])) {
    http_response_code(400);
    exit('Parameter lat dan lng wajib diisi.');
}

$lat = $_POST['lat'];
$lng = $_POST['lng'];
$speed = isset($_POST['speed']) ? $_POST['speed'] : 0;
$loadvoltage = isset($_POST['loadvoltage']) ? $_POST['loadvoltage'] : 0;
$busvoltage = isset($_POST['busvoltage']) ? $_POST['busvoltage'] : 0;
$shuntvoltage = isset($_POST['shuntvoltage']) ? $_POST['shuntvoltage'] : 0;
$created_date = date("Y-m-d H:i:s");

// Validasi numerik
if (!is_numeric($lat) || !is_numeric($lng) || !is_numeric($speed) || !is_numeric($loadvoltage) || !is_numeric($busvoltage) || !is_numeric($shuntvoltage)) {
    http_response_code(400);
    exit('Parameter harus numerik.');
}

// Simpan data GPS ke tabel tbl_gps
$stmt_gps = $conn->prepare("INSERT INTO tbl_gps (lat, lng, speed, created_date) VALUES (?, ?, ?, ?)");
if ($stmt_gps === false) {
    http_response_code(500);
    exit('Prepare statement gagal: ' . $conn->error);
}

$stmt_gps->bind_param("ddds", $lat, $lng, $speed, $created_date);
$stmt_gps->execute();

// Simpan data INA219 ke tabel tbl_ina219
$stmt_ina = $conn->prepare("INSERT INTO tbl_ina219 (bus_voltage, shunt_voltage, load_voltage, created_date) VALUES (?, ?, ?, ?)");
if ($stmt_ina === false) {
    http_response_code(500);
    exit('Prepare statement gagal: ' . $conn->error);
}

$stmt_ina->bind_param("ddds", $busvoltage, $shuntvoltage, $loadvoltage, $created_date);
$stmt_ina->execute();

// Menutup koneksi
$stmt_gps->close();
$stmt_ina->close();
$conn->close();

// Memberikan respon sukses
echo "Data berhasil disimpan.";
?>
