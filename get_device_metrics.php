<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

try {
    // Ambil data metrik lainnya dari tabel device_metrics (kecuali total_distance_meters)
    $stmt_metrics = $conn->prepare("SELECT usage_duration_seconds, last_active_timestamp FROM device_metrics WHERE id = 1 LIMIT 1");
    $stmt_metrics->execute();
    $result_metrics = $stmt_metrics->get_result();
    $metrics_data = $result_metrics->fetch_assoc();
    $stmt_metrics->close();

    // Jika belum ada entri di device_metrics, inisialisasi
    if (!$metrics_data) {
        $metrics_data = [
            'usage_duration_seconds' => 0,
            'last_active_timestamp' => null
        ];
        // Tambahkan record awal jika belum ada
        $insert_stmt = $conn->prepare("INSERT IGNORE INTO device_metrics (id, total_distance_meters, usage_duration_seconds, last_active_timestamp, updated_at) VALUES (1, 0, 0, NOW(), NOW())");
        $insert_stmt->execute();
        $insert_stmt->close();
    }

    // --- HITUNG ULANG TOTAL JARAK DARI SEMUA DATA GPS ---
    // Query untuk menjumlahkan semua kecepatan dari tbl_gps
    // Asumsi: speed dalam km/h dan data dikirim setiap 60 detik (1 menit).
    // Untuk mendapatkan jarak dalam meter dari km/h setiap 60 detik:
    // (speed_kmh / 3600 detik_per_jam) * 60 detik_interval * 1000 meter_per_km
    // = speed_kmh * 60 / 3.6
    // = speed_kmh * 16.666666666666668 (approx)
    $stmt_total_speed = $conn->prepare("SELECT SUM(speed) AS total_speed FROM tbl_gps");
    $stmt_total_speed->execute();
    $result_total_speed = $stmt_total_speed->get_result();
    $speed_data = $result_total_speed->fetch_assoc();
    $stmt_total_speed->close();

    $total_speed_kmh = floatval($speed_data['total_speed'] ?? 0);
    
    // SESUAIKAN INTERVAL INI DENGAN INTERVAL PENGIRIMAN DATA GPS ANDA
    $assumed_interval_seconds = 60; // Diubah dari 10 menjadi 60 detik

    $total_distance_meters_calculated = ($total_speed_kmh / 3600) * $assumed_interval_seconds * 1000;

    $response['success'] = true;
    $response['metrics'] = [
        'total_distance_meters' => $total_distance_meters_calculated,
        'usage_duration_seconds' => $metrics_data['usage_duration_seconds'],
        'last_active_timestamp' => $metrics_data['last_active_timestamp']
    ];

} catch (mysqli_sql_exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>