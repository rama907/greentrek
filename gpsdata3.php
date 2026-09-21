<?php
require 'config.php';
require __DIR__ . '/device_auth.php';

// Pastikan data POST ada
if (!isset($_POST['lat']) || !isset($_POST['lng'])) {
    http_response_code(400);
    exit('Parameter lat dan lng wajib diisi.');
}

$lat = $_POST['lat'];
$lng = $_POST['lng'];

if (!is_numeric($lat) || !is_numeric($lng)) {
    http_response_code(400);
    exit('Parameter lat dan lng harus numerik.');
}

$created_date = date("Y-m-d H:i:s");

$speed = isset($_POST['speed']) && is_numeric($_POST['speed']) ? $_POST['speed'] : 0;

// Simpan ke database dengan prepared statement
$stmt = $conn->prepare("INSERT INTO tbl_gps (lat, lng, speed, created_date) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    error_log('DB error: ' . $conn->error);
    exit('Terjadi kesalahan pada server.');
}

$stmt->bind_param("ddds", $lat, $lng, $speed, $created_date);

if ($stmt->execute()) {
    echo "Data berhasil disimpan, ID: " . $stmt->insert_id;
} else {
    http_response_code(500);
    error_log('DB error: ' . $stmt->error);
    echo 'Terjadi kesalahan pada server.';
}

$stmt->close();
$conn->close();
