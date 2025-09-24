<?php
require 'config.php';

$result = $conn->query("SELECT * FROM tbl_ina219 ORDER BY created_date DESC LIMIT 5");
$data = [];
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
