<?php
date_default_timezone_set('Asia/Jakarta');
define('DB_HOST', '127.0.0.1');
define('DB_USERNAME', 'adityafh');
define('DB_PASSWORD', 'D8kmTlO274KzTxDk6YHI');
define('DB_NAME', 'teslok');

$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
