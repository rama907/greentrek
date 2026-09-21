<?php
include 'auth_check.php';
require 'config.php';

$result = $conn->query("SELECT * FROM tbl_gps ORDER BY created_date DESC LIMIT 50");

echo '<table><tr><th>No</th><th>Lat</th><th>Lng</th><th>Speed (km/h)</th><th>Waktu</th></tr>';
$no = 1;
while ($row = $result->fetch_assoc()) {
  echo "<tr><td>$no</td><td>{$row['lat']}</td><td>{$row['lng']}</td><td>{$row['speed']}</td><td>{$row['created_date']}</td></tr>";
  $no++;
}
echo '</table>';
?>
