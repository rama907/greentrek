<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Sensor | GreenTrek</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding-top: 0; /* Adjust padding-top later if topbar is sticky */
            padding-bottom: 20px;
            min-height: 100vh;
            color: #333;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .container {
            max-width: 1000px;
            margin: auto;
            padding-top: 100px; /* Sesuaikan dengan tinggi topbar + jarak */
            padding-left: 15px;
            padding-right: 15px;
            flex-grow: 1;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px; /* Jarak antar card */
            overflow: hidden; /* Tambahkan overflow-hidden untuk memastikan konten tidak keluar dari card */
        }
        .table-header, .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .table-header h3, .chart-header h3 {
            font-weight: 600;
            font-size: 1.3rem;
            color: #222;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        #last-update-global-ina {
            font-family: monospace;
            font-weight: 700;
            color: #1b4d1b;
            font-size: 0.9rem;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            color: #1b4d1b;
            white-space: nowrap;
        }
        thead tr {
            background-color: #e6f4ea;
        }
        thead th {
            padding: 12px 15px;
            font-weight: 700;
            text-align: left;
            border-bottom: 2px solid #b3d7b0;
            min-width: 120px;
        }
        tbody tr:hover {
            background-color: #f1faf2;
        }
        tbody td {
            padding: 10px 15px;
            border-bottom: 1px solid #d6e8d9;
        }

        /* Gaya untuk chart */
        .chart-container {
            position: relative;
            height: 400px; /* Tinggi default untuk desktop */
            width: 100%;
            /* margin-top: 24px; <-- Pindah ke card wrapper */
            padding-bottom: 30px; /* Lebih banyak padding di bawah chart */
        }
        /* Override padding-top bawaan card jika tidak ingin terlalu jauh dari margin-top */
        .chart-container.card {
            /* padding-top: 20px; */ /* Biarkan padding card normal */
        }

        @media (max-width: 768px) {
            .container {
                padding-top: 90px; /* Sesuaikan dengan topbar mobile */
            }
            .card {
                padding: 15px;
            }
            thead th, tbody td {
                padding: 8px 10px;
                font-size: 0.85rem;
            }
            thead th:first-child, tbody td:first-child {
                min-width: 150px;
            }
            /* Penyesuaian tinggi chart untuk mobile */
            .chart-container {
                height: 250px; /* Tinggi chart di mobile */
                padding: 15px; /* Kurangi padding card untuk chart di mobile */
                padding-bottom: 30px; /* Pertahankan padding bawah yang cukup */
            }
            .chart-header h3 {
                font-size: 1.1rem; /* Sesuaikan ukuran judul chart */
            }
        }
    </style>
</head>
<body>
    <?php include 'layouts/topbar.php'; ?>
    <?php include 'layouts/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="table-header">
                <h3><i class="fas fa-bolt"></i> Data Sensor INA219 Terbaru</h3>
                <div id="last-update-global-ina">Last Update: -</div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Bus Voltage (V)</th>
                            <th>Shunt Voltage (V)</th>
                            <th>Load Voltage (V)</th>
                        </tr>
                    </thead>
                    <tbody id="ina219-data-body"></tbody>
                </table>
            </div>
        </div>

        <div class="card chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-chart-area"></i> Grafik Data Sensor</h3>
            </div>
            <canvas id="sensorChart"></canvas>
        </div>
    </div>

    <script>
        // Inisialisasi Chart.js untuk Grafik Sensor
        const ctx = document.getElementById('sensorChart').getContext('2d');
        const sensorChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [], // Label waktu
                datasets: [{
                    label: 'Tegangan Bus (V)',
                    data: [], // Data bus voltage
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    fill: true,
                }, {
                    label: 'Tegangan Beban (V)',
                    data: [], // Data load voltage
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderWidth: 2,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    title: {
                        display: false,
                    }
                }
            }
        });

        async function updateIna219Data() {
            try {
                const sensorResponse = await fetch('get_latest_sensor.php?_=' + new Date().getTime());
                const sensorData = await sensorResponse.json();

                // Update INA219 Data Table
                const ina219DataBody = document.getElementById("ina219-data-body");
                ina219DataBody.innerHTML = '';
                sensorData.forEach((entry) => {
                    ina219DataBody.innerHTML += `
                        <tr>
                            <td>${entry.created_date}</td>
                            <td>${parseFloat(entry.bus_voltage).toFixed(2)}</td>
                            <td>${parseFloat(entry.shunt_voltage).toFixed(2)}</td>
                            <td>${parseFloat(entry.load_voltage).toFixed(2)}</td>
                        </tr>
                    `;
                });

                if (sensorData.length > 0) {
                    const latestSensor = sensorData[0];
                    document.getElementById('last-update-global-ina').textContent = "Last Update: " + new Date(latestSensor.created_date).toLocaleString('id-ID', { hour12: false });
                }

                // Update Sensor Chart Data
                sensorChart.data.labels = sensorData.map(entry => {
                    const date = new Date(entry.created_date);
                    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                }).reverse();

                sensorChart.data.datasets[0].data = sensorData.map(entry => parseFloat(entry.bus_voltage)).reverse();
                sensorChart.data.datasets[1].data = sensorData.map(entry => parseFloat(entry.load_voltage)).reverse();
                sensorChart.update();

            } catch (error) {
                console.error('Error fetching INA219 data:', error);
            }
        }

        // Perbarui data setiap 10 detik
        setInterval(updateIna219Data, 10000);
        updateIna219Data(); // Panggil pertama kali saat halaman dimuat
    </script>
    <?php include 'layouts/footer.php'; ?>
</body>
</html>