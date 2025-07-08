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
    <title>Data GPS | GreenTrek</title>
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
        #last-update-global {
            font-family: monospace;
            font-weight: 700;
            color: #1b4d1b;
            font-size: 0.9rem;
        }

        /* --- Perubahan untuk Tabel Responsif --- */
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
                <h3><i class="fas fa-satellite"></i> Data GPS Terbaru</h3>
                <div id="last-update-global">Last Update: -</div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Latitude</th>
                            <th>Longitude</th>
                            <th>Speed (km/h)</th>
                        </tr>
                    </thead>
                    <tbody id="gps-data-body"></tbody>
                </table>
            </div>
        </div>

        <div class="card chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-tachometer-alt"></i> Grafik Kecepatan</h3>
            </div>
            <canvas id="speedChart"></canvas>
        </div>
    </div>

    <script>
        // Inisialisasi Chart.js untuk Grafik Kecepatan
        const speedCtx = document.getElementById('speedChart').getContext('2d');
        const speedChart = new Chart(speedCtx, {
            type: 'line',
            data: {
                labels: [], // Label waktu
                datasets: [{
                    label: 'Kecepatan (km/h)',
                    data: [], // Data kecepatan
                    borderColor: 'rgba(54, 162, 235, 1)', // Warna biru
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderWidth: 2,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Kecepatan (km/h)'
                        }
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

        async function updateGpsData() {
            try {
                const gpsResponse = await fetch('get_latest_gps.php?_=' + new Date().getTime());
                const gpsData = await gpsResponse.json();

                // Update GPS Data Table
                const gpsDataBody = document.getElementById("gps-data-body");
                gpsDataBody.innerHTML = '';
                gpsData.forEach((entry) => {
                    gpsDataBody.innerHTML += `
                        <tr>
                            <td>${entry.created_date}</td>
                            <td>${parseFloat(entry.lat).toFixed(6)}</td>
                            <td>${parseFloat(entry.lng).toFixed(6)}</td>
                            <td>${parseFloat(entry.speed).toFixed(2)}</td>
                        </tr>
                    `;
                });

                if (gpsData.length > 0) {
                    const latestGps = gpsData[0];
                    document.getElementById('last-update-global').textContent = "Last Update: " + new Date(latestGps.created_date).toLocaleString('id-ID', { hour12: false });
                }

                // Update Speed Chart Data
                speedChart.data.labels = gpsData.map(entry => {
                    const date = new Date(entry.created_date);
                    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                }).reverse(); // Reverse to show latest on the right

                speedChart.data.datasets[0].data = gpsData.map(entry => parseFloat(entry.speed)).reverse();
                speedChart.update();


            } catch (error) {
                console.error('Error fetching GPS data:', error);
            }
        }

        // Perbarui data setiap 10 detik
        setInterval(updateGpsData, 10000);
        updateGpsData(); // Panggil pertama kali saat halaman dimuat
    </script>
    <?php include 'layouts/footer.php'; ?>
</body>
</html>