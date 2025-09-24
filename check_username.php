<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

header('Content-Type: application/json');

$response = ['isTaken' => false];

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);

    if (empty($username)) {
        echo json_encode($response); // Return not taken if empty
        exit();
    }

    try {
        $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?"); // Menggunakan tabel produksi
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $response['isTaken'] = true;
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        // Log error, but don't expose sensitive info to frontend
        error_log("Database error in check_username.php: " . $e->getMessage());
        // In case of database error, treat as taken to prevent enumeration if error occurs
        $response['isTaken'] = true; 
    }
}

$conn->close();
echo json_encode($response);
?>