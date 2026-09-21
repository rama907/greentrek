<?php
// CONTOH konfigurasi. Salin menjadi config.php lalu isi nilai asli. JANGAN commit config.php.
date_default_timezone_set('Asia/Jakarta');
define('DB_HOST', '');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');

$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>

// Email (PHPMailer / SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', ''); // untuk Gmail gunakan App Password
define('MAIL_FROM', '');
define('MAIL_FROM_NAME', 'GreenTrek');

// Google reCAPTCHA v2 (secret key)
define('RECAPTCHA_SECRET_KEY', '');

// Kunci untuk perangkat GPS/sensor (string acak panjang). Harus sama dengan DEVICE_API_KEY di gps2.ino.
define('DEVICE_API_KEY', '');
