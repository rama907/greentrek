<?php
// Mulai sesi
session_start();

// Periksa apakah pengguna sudah login
if (!isset($_SESSION['username'])) {
    // Jika belum login, arahkan ke login.php
    header("Location: login.php");
    exit(); // Pastikan tidak ada kode lain yang dijalankan setelah redirect
}
?>
