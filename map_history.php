<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Perjalanan | GreenTrek</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>

    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding-top: 0;
            padding-bottom: 20px;
            min-height: 100vh;
            color: #333;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding-top: 30px;
            padding-left: 15px;
            padding-right: 15px;
            flex-grow: 1;
            margin-top: 0; /* Sesuaikan dengan tinggi topbar jika perlu */
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }
        #map {
            height: 500px; /* Tinggi peta yang cukup */
            border-radius: 10px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.07);
            margin-top: 20px;
        }
        .filter-section {
            display: flex;
            gap: 15px;
            align-items: flex-end; /* Menjaga align tombol dan input */
            margin-bottom: 20px;
            flex-wrap: wrap; /* Untuk responsivitas */
        }
        .filter-section label {
            font-weight: 600;
            color: #444;
            margin-bottom: 5px;
        }
        .filter-section input[type="date"] {
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            box-sizing: border-box;
            flex-grow: 1; /* Agar input bisa memanjang */
            min-width: 150px; /* Lebar minimum input tanggal */
        }
        .filter-section button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .filter-section button:hover {
            background-color: #45a049;
        }
        .info-panel {
            background-color: #f9f9f9;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        .info-panel h4 {
            font-weight: 700;
            font-size: 1.1rem;
            color: #222;
            margin-bottom: 10px;
        }
        .info-item {
            margin-bottom: 8px;
            font-size: 0.95rem;
            color: #555;
        }
        .info-item strong {
            color: #333;
        }

        /* Responsivitas */
        @media (max-width: 768px) {
            .container {
                padding-top: 90px; /* Sesuaikan dengan topbar mobile */
            }
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-section input[type="date"],
            .filter-section button {
                width: 100%;
            }
            #map {
                height: 350px;
            }
        }
    </style>
</head>
<body>
    <?php include 'layouts/header.php'; ?>
    <?php include 'layouts/topbar.php'; ?>

    <div class="container">
        <div class="card">
            <h3 class="text-2xl font-bold text-green-800 mb-4 flex items-center gap-2">
                <i class="fas fa-route text-green-600"></i> Riwayat Perjalanan Alat Tani
            </h3>

            <div class="filter-section">
                <div>
                    <label for="filterDate">Pilih Tanggal:</label>
                    <input type="date" id="filterDate" class="form-input">
                </div>
                <button id="applyFilterBtn">Tampilkan Rute</button>
            </div>

            <div id="map"></div>

            <div class="info-panel">
                <h4>Informasi Rute Terpilih:</h4>
                <div id="route-info">
                    <p class="text-gray-600">Pilih tanggal untuk melihat riwayat perjalanan.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        let map;
        let polyline;
        let markers = []; // Array untuk menyimpan marker penting

        // Inisialisasi peta hanya sekali
        function initMap() {
            if (map) {
                map.remove(); // Hapus instance peta yang lama jika ada
            }
            map = L.map('map').setView([-6.970498, 107.64619], 13); // Koordinat Bandung, zoom 13
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
        }

        // Fungsi untuk menampilkan rute pada peta
        async function loadRouteData(date) {
            if (!date) {
                document.getElementById('route-info').innerHTML = '<p class="text-gray-600">Silakan pilih tanggal untuk melihat riwayat perjalanan.</p>';
                if (polyline) map.removeLayer(polyline);
                markers.forEach(m => map.removeLayer(m));
                markers = [];
                return;
            }

            try {
                // Tampilkan pesan loading
                document.getElementById('route-info').innerHTML = '<p class="text-blue-500"><i class="fas fa-spinner fa-spin"></i> Memuat data...</p>';

                const response = await fetch(`get_gps_history.php?date=${date}&_=${new Date().getTime()}`);
                const data = await response.json();

                if (polyline) {
                    map.removeLayer(polyline);
                }
                markers.forEach(m => map.removeLayer(m));
                markers = [];

                if (data.success && data.history.length > 0) {
                    const latlngs = data.history.map(entry => [parseFloat(entry.lat), parseFloat(entry.lng)]);

                    // Gambar polyline
                    polyline = L.polyline(latlngs, { color: '#4CAF50', weight: 4, opacity: 0.7 }).addTo(map);

                    // Sesuaikan view peta agar mencakup seluruh rute
                    map.fitBounds(polyline.getBounds());

                    // Tambahkan marker awal dan akhir
                    const startMarker = L.marker(latlngs[0]).addTo(map)
                        .bindPopup(`<b>Start:</b><br>${new Date(data.history[0].created_date).toLocaleString('id-ID')}<br>Speed: ${parseFloat(data.history[0].speed).toFixed(2)} km/h`)
                        .openPopup();
                    markers.push(startMarker);

                    const endMarker = L.marker(latlngs[latlngs.length - 1]).addTo(map)
                        .bindPopup(`<b>End:</b><br>${new Date(data.history[data.history.length - 1].created_date).toLocaleString('id-ID')}<br>Speed: ${parseFloat(data.history[data.history.length - 1].speed).toFixed(2)} km/h`)
                        .openPopup();
                    markers.push(endMarker);

                    // Informasi Rute
                    let totalDistance = 0;
                    let minSpeed = Infinity;
                    let maxSpeed = 0;
                    let totalSpeed = 0;

                    for (let i = 0; i < data.history.length; i++) {
                        const current = data.history[i];
                        const speed = parseFloat(current.speed);
                        totalSpeed += speed;

                        if (speed < minSpeed) minSpeed = speed;
                        if (speed > maxSpeed) maxSpeed = speed;

                        if (i > 0) {
                            const prev = data.history[i - 1];
                            const dist = calculateDistance(
                                parseFloat(prev.lat), parseFloat(prev.lng),
                                parseFloat(current.lat), parseFloat(current.lng)
                            );
                            totalDistance += dist;
                        }
                    }

                    const averageSpeed = totalSpeed / data.history.length;
                    
                    // Format durasi penggunaan
                    const firstTimestamp = new Date(data.history[0].created_date);
                    const lastTimestamp = new Date(data.history[data.history.length - 1].created_date);
                    const usageDurationMs = lastTimestamp.getTime() - firstTimestamp.getTime();
                    const usageDurationSeconds = Math.round(usageDurationMs / 1000);

                    const hours = Math.floor(usageDurationSeconds / 3600);
                    const minutes = Math.floor((usageDurationSeconds % 3600) / 60);
                    const seconds = usageDurationSeconds % 60;
                    const formattedDuration = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;


                    document.getElementById('route-info').innerHTML = `
                        <p class="info-item"><i class="fas fa-calendar-alt"></i> <strong>Tanggal:</strong> ${date}</p>
                        <p class="info-item"><i class="fas fa-tachometer-alt"></i> <strong>Jarak Tempuh:</strong> ${(totalDistance / 1000).toFixed(2)} km</p>
                        <p class="info-item"><i class="fas fa-walking"></i> <strong>Kecepatan Rata-rata:</strong> ${averageSpeed.toFixed(2)} km/h</p>
                        <p class="info-item"><i class="fas fa-rocket"></i> <strong>Kecepatan Maksimum:</strong> ${maxSpeed.toFixed(2)} km/h</p>
                        <p class="info-item"><i class="fas fa-stopwatch"></i> <strong>Durasi Perjalanan:</strong> ${formattedDuration}</p>
                        <p class="info-item"><i class="fas fa-play-circle"></i> <strong>Mulai:</strong> ${new Date(data.history[0].created_date).toLocaleString('id-ID')}</p>
                        <p class="info-item"><i class="fas fa-flag-checkered"></i> <strong>Selesai:</strong> ${new Date(data.history[data.history.length - 1].created_date).toLocaleString('id-ID')}</p>
                        <p class="info-item text-xs text-gray-500 mt-3">Klik ikon marker untuk detail waktu dan kecepatan di titik tersebut.</p>
                    `;

                } else {
                    document.getElementById('route-info').innerHTML = '<p class="text-yellow-600"><i class="fas fa-exclamation-triangle"></i> Tidak ada data perjalanan untuk tanggal ini.</p>';
                    map.setView([-6.970498, 107.64619], 13); // Kembali ke lokasi default
                }
            } catch (error) {
                console.error('Error loading route data:', error);
                document.getElementById('route-info').innerHTML = '<p class="text-red-600"><i class="fas fa-times-circle"></i> Gagal memuat data rute. Coba lagi nanti.</p>';
            }
        }

        // Fungsi untuk menghitung jarak antara dua koordinat (Haversine formula)
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // metres
            const φ1 = lat1 * Math.PI / 180; // φ, λ in radians
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                      Math.cos(φ1) * Math.cos(φ2) *
                      Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c; // in metres
        }


        document.addEventListener('DOMContentLoaded', () => {
            initMap();

            const filterDateInput = document.getElementById('filterDate');
            const applyFilterBtn = document.getElementById('applyFilterBtn');

            // Set tanggal default ke hari ini
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0'); // Months start at 0!
            const dd = String(today.getDate()).padStart(2, '0');
            const todayFormatted = `${yyyy}-${mm}-${dd}`;
            filterDateInput.value = todayFormatted;

            // Muat data untuk tanggal hari ini saat halaman pertama kali dimuat
            loadRouteData(todayFormatted);

            applyFilterBtn.addEventListener('click', () => {
                loadRouteData(filterDateInput.value);
            });
        });
    </script>
</body>
</html>