<?php
// Guard untuk endpoint yang menerima data dari perangkat (GPS/sensor).
// Perangkat harus mengirim kunci yang sama dengan DEVICE_API_KEY di config.php (field POST "key" atau header X-API-Key).
// Jika kunci belum dikonfigurasi, permintaan ditolak (aman secara default).
$expected = defined('DEVICE_API_KEY') ? (string)DEVICE_API_KEY : '';
if ($expected === '') {
    http_response_code(503);
    exit('Device API key belum dikonfigurasi.');
}
$provided = $_SERVER['HTTP_X_API_KEY'] ?? ($_POST['key'] ?? '');
if (!is_string($provided) || !hash_equals($expected, $provided)) {
    http_response_code(401);
    exit('Unauthorized');
}