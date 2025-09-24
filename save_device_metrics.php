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

$input = json_decode(file_get_contents('php://input'), true);

// total_distance TIDAK LAGI DIAMBIL DARI INPUT, karena akan dihitung ulang dari tbl_gps
// $total_distance = isset($input['total_distance']) ? floatval($input['total_distance']) : 0;

$usage_duration = isset($input['usage_duration']) ? intval($input['usage_duration']) : 0;
$last_active_timestamp = isset($input['last_active']) ? $input['last_active'] : null;

// Validasi format timestamp
if ($last_active_timestamp) {
    $dateTimeObj = DateTime::createFromFormat(DATE_ISO8601, $last_active_timestamp);
    if ($dateTimeObj === false) {
        $last_active_timestamp = null;
    } else {
        $last_active_timestamp = $dateTimeObj->format('Y-m-d H:i:s');
    }
} else {
    $last_active_timestamp = date('Y-m-d H:i:s');
}

try {
    // Hanya update usage_duration_seconds dan last_active_timestamp
    // total_distance_meters tidak diupdate dari frontend, karena dihitung ulang dari tbl_gps
    $stmt = $conn->prepare("INSERT INTO device_metrics (id, usage_duration_seconds, last_active_timestamp, updated_at) VALUES (1, ?, ?, NOW()) ON DUPLICATE KEY UPDATE usage_duration_seconds = ?, last_active_timestamp = ?, updated_at = NOW()");
    $stmt->bind_param("issds", $usage_duration, $last_active_timestamp, $usage_duration, $last_active_timestamp);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Device metrics (usage duration) saved successfully.';
    } else {
        $response['message'] = 'Failed to save device metrics: ' . $stmt->error;
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>