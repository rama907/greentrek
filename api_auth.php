<?php
// Guard untuk endpoint JSON yang hanya boleh dibaca pengguna yang sudah login.
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}