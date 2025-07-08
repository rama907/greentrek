<?php
require 'config.php';

$result = $conn->query("SELECT * FROM tbl_gps ORDER BY created_date DESC LIMIT 1");
$data = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($data);
?>
