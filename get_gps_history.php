<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'history' => []];

// Pastikan pengguna sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

// Ambil tanggal dari parameter GET
$filterDate = $_GET['date'] ?? '';

// Validasi format tanggal (YYYY-MM-DD)
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $filterDate)) {
    $response['message'] = 'Invalid date format. Please use YYYY-MM-DD.';
    echo json_encode($response);
    exit();
}

try {
    // Query untuk mengambil semua data GPS untuk tanggal tertentu, diurutkan berdasarkan waktu
    // Menggunakan created_date LIKE 'YYYY-MM-DD%' untuk mencocokkan tanggal
    $stmt = $conn->prepare("SELECT lat, lng, speed, created_date FROM tbl_gps WHERE created_date LIKE ? ORDER BY created_date ASC");
    $searchDate = $filterDate . '%'; // Tambahkan wildcard untuk mencocokkan tanggal
    $stmt->bind_param("s", $searchDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $historyData = [];
    while ($row = $result->fetch_assoc()) {
        $historyData[] = $row;
    }

    $response['success'] = true;
    $response['history'] = $historyData;

    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>