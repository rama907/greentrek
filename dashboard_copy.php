<?php
// Ini adalah bagian PHP yang harus berada di PALING ATAS file.
// Tidak boleh ada spasi, baris kosong, atau karakter lain di atasnya.
ini_set('display_errors', 1); // Aktifkan untuk debugging, matikan di produksi
ini_set('display_startup_errors', 1); // Aktifkan untuk debugging, matikan di produksi
error_reporting(E_ALL); // Tampilkan semua error

session_start(); // Memulai sesi untuk memeriksa status login

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
// Jika sudah login, lanjutkan ke halaman dashboard
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenTrek</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
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
            margin-top: 0;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-top: 0;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            flex-wrap: wrap; /* Izinkan wrapping pada header */
            gap: 10px; /* Jarak antar item di header */
        }

        .map-title {
            font-weight: 600;
            font-size: 1.3rem;
            color: #222;
            user-select: none;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Gaya untuk indikator status (digunakan untuk jam dan status online/offline) */
        .status-indicator {
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            color: #fff; /* Default text color for status */
            user-select: none;
            text-align: center;
            min-width: 100px; /* Lebar minimum untuk "HH:MM:SS | Online/Offline" */
            background-color: #95a5a6; /* Default grey before status is known */
        }

        #map {
            height: 420px;
            border-radius: 10px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.07);
        }

        .map-and-status-wrapper {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .map-container-flex {
            flex: 2;
            min-width: 500px;
        }

        .status-section-flex {
            flex: 1;
            /* Perubahan: Ubah menjadi grid dengan 2 kolom */
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding-top: 20px;
        }

        .status-item {
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #f9f9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 80px;
        }

        .status-item i {
            font-size: 2rem;
            color: #4CAF50;
            margin-bottom: 5px;
        }

        .status-item p {
            font-size: 0.9rem;
            font-weight: 600;
            color: #4CAF50;
            margin: 0;
        }

        .status-item span {
            font-size: 1.2rem;
            font-weight: 700;
            color: #333;
            margin-top: 3px;
        }

        /* Media Queries yang Disesuaikan */
        @media (max-width: 1024px) {
            .map-and-status-wrapper {
                flex-direction: column;
            }
            .map-container-flex, .status-section-flex {
                flex: none;
                width: 100%;
                min-width: unset;
            }
            .status-section-flex {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                padding-top: 0;
            }
            .status-item {
                min-width: 150px;
                height: auto;
            }
            .map-header {
                flex-direction: column; /* Strukturnya bisa vertikal */
                align-items: flex-start; /* Sejajarkan ke kiri */
                gap: 10px; /* Jarak antar elemen */
            }
            .status-indicator {
                width: 100%; /* Lebar penuh */
                box-sizing: border-box; /* Pastikan padding masuk dalam lebar */
                text-align: left; /* Teks sejajar kiri */
            }
        }

        @media (max-width: 768px) {
            body {
                padding-top: 0;
                padding-bottom: 15px;
                padding-left: 15px;
                padding-right: 15px;
            }
            .container {
                padding-top: 90px;
                padding-left: 0;
                padding-right: 0;
            }
            .card {
                padding: 15px;
            }
            .map-title {
                font-size: 1.1rem;
                gap: 5px;
            }
            .status-indicator {
                font-size: 0.8rem;
                padding: 4px 8px;
            }
            #map {
                height: 300px;
            }
            .status-section-flex {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 10px;
            }
            .status-item {
                padding: 8px;
            }
            .status-item i {
                font-size: 1.8rem;
            }
            .status-item p {
                font-size: 0.8rem;
            }
            .status-item span {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .status-section-flex {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            .status-item {
                flex-direction: row;
                justify-content: flex-start;
                gap: 10px;
                text-align: left;
                height: auto;
            }
            .status-item i {
                margin-bottom: 0;
            }
            .map-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <?php include 'layouts/header.php'; ?>
    <?php include 'layouts/topbar.php'; ?>

    <div class="container">
        <div class="dashboard-grid">
            <div class="map-and-status-wrapper">
                <div class="card map-container-flex">
                    <div class="map-header">
                        <div class="map-title"><i class="fas fa-map-marker-alt"></i> Lokasi Real-time</div>
                        <div id="connection-status" class="status-indicator">Memuat...</div>
                    </div>
                    <div id="map"></div>
                </div>

                <div class="card status-section-flex">
                    <div class="status-item">
                        <i class="fas fa-gauge"></i>
                        <p>Kecepatan Saat Ini</p>
                        <span id="current-speed">0 km/h</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <p>Kecepatan Rata-rata</p>
                        <span id="average-speed">0 km/h</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-road"></i>
                        <p>Total Jarak</p>
                        <span id="total-distance">0 km</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-battery-half"></i>
                        <p>Tegangan Bus</p>
                        <span id="bus-voltage">0.00 V</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-chart-line"></i>
                        <p>Kecepatan Maksimum</p>
                        <span id="maximum-speed">0 km/h</span>
                    </div>
                    <div class="status-item">
                        <i class="fas fa-clock"></i>
                        <p>Durasi Penggunaan</p>
                        <span id="usage-duration">00:00:00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let marker;
        const map = L.map('map').setView([-6.970498, 107.64619], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        let previousLat = null;
        let previousLng = null;
        let totalDistance = 0; // Ini akan diinisialisasi dari database (SUM speed)
        let usageDurationSeconds = 0; // Ini akan diinisialisasi dari database
        let lastActivityTimestamp = null; // Ini akan diinisialisasi dari database (waktu terakhir perangkat online)

        let currentDeviceStatus = "Memuat...";
        let currentServerTime = "--:--:--"; // Waktu server lokal (berjalan di browser)

        const OFFLINE_THRESHOLD = 120; // Detik: ambang batas untuk dianggap offline (2 menit untuk data 1 menit)
        const USAGE_RESET_THRESHOLD_SECONDS = 3600; // 1 jam = 3600 detik: durasi offline untuk mereset durasi penggunaan

        // Fungsi untuk memperbarui jam server real-time di UI
        function updateRealTimeServerClock() {
            const now = new Date();
            currentServerTime = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('connection-status').textContent = currentServerTime + " | " + currentDeviceStatus;
        }

        // Fungsi untuk mengonversi detik ke format HH:MM:SS
        function formatDuration(totalSeconds) {
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        // Fungsi untuk memperbarui durasi penggunaan di UI secara real-time
        function updateUsageDurationDisplay() {
            document.getElementById('usage-duration').textContent = formatDuration(usageDurationSeconds);
        }

        // Fungsi untuk menyimpan data metrik ke database (AJAX)
        async function saveDeviceMetrics(usageDur, lastActive) { // totalDist dihapus dari parameter
            try {
                const response = await fetch('save_device_metrics.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        // total_distance: totalDist, // Ini tidak lagi dikirim dari frontend
                        usage_duration: usageDur,
                        last_active: lastActive // Kirim timestamp terakhir aktivitas
                    })
                });
                const result = await response.json();
                if (!result.success) {
                    console.error("Failed to save device metrics:", result.message);
                }
            } catch (error) {
                console.error("Error saving device metrics:", error);
            }
        }

        // Fungsi untuk mengambil data metrik dari database saat inisialisasi
        async function loadDeviceMetrics() {
            try {
                const response = await fetch('get_device_metrics.php?_=' + new Date().getTime());
                const data = await response.json();
                if (data.success && data.metrics) {
                    totalDistance = parseFloat(data.metrics.total_distance_meters || 0); // Diambil dari database (SUM speed)
                    usageDurationSeconds = parseInt(data.metrics.usage_duration_seconds || 0); // Diambil dari database
                    lastActivityTimestamp = data.metrics.last_active_timestamp ? new Date(data.metrics.last_active_timestamp) : null;
                    
                    document.getElementById('total-distance').textContent = (totalDistance / 1000).toFixed(2) + " km";
                    updateUsageDurationDisplay();
                } else {
                    console.warn("No existing device metrics found or error loading:", data.message);
                }
            } catch (error) {
                console.error("Error loading device metrics:", error);
            }
        }

        async function updateDashboardData() {
            try {
                const gpsResponse = await fetch('get_latest_gps.php?_=' + new Date().getTime());
                const gpsData = await gpsResponse.json();

                const sensorResponse = await fetch('get_latest_sensor.php?_=' + new Date().getTime());
                const sensorData = await sensorResponse.json();

                console.log("GPS Data:", gpsData);
                console.log("Sensor Data:", sensorData);

                const connectionStatusElement = document.getElementById('connection-status');
                let latestGpsCreatedDate = null; // Waktu pembuatan data GPS terbaru

                // --- Handle jika tidak ada data GPS yang diterima ---
                if (!gpsData || gpsData.length === 0) {
                    console.warn("No GPS data received.");
                    if (marker) {
                        map.removeLayer(marker);
                        marker = null;
                    }
                    document.getElementById('current-speed').textContent = "N/A";
                    document.getElementById('average-speed').textContent = "N/A";
                    document.getElementById('maximum-speed').textContent = "N/A";

                    const currentTime = new Date();
                    if (lastActivityTimestamp) {
                        const timeSinceLastActivity = (currentTime.getTime() - lastActivityTimestamp.getTime()) / 1000;
                        if (timeSinceLastActivity > USAGE_RESET_THRESHOLD_SECONDS) {
                            console.log("Device offline for more than 1 hour. Resetting usage duration and previous coords.");
                            usageDurationSeconds = 0; // Reset jika offline terlalu lama
                            previousLat = null;
                            previousLng = null;
                            lastActivityTimestamp = null; // Penting: reset juga timestamp aktivitas
                        }
                    } else {
                        // Jika tidak ada lastActivityTimestamp dan tidak ada data GPS, anggap sudah lama offline
                        usageDurationSeconds = 0;
                        previousLat = null;
                        previousLng = null;
                    }
                    
                    currentDeviceStatus = "Offline";
                    connectionStatusElement.style.backgroundColor = "#e74c3c"; // Merah

                } else { // --- Jika ada data GPS yang diterima ---
                    const latestGps = gpsData[0];
                    const lat = parseFloat(latestGps.lat);
                    const lng = parseFloat(latestGps.lng);
                    const currentSpeed = parseFloat(latestGps.speed || 0).toFixed(2);
                    latestGpsCreatedDate = new Date(latestGps.created_date); // Waktu data GPS terbaru

                    // Tentukan status online/offline
                    const currentTime = new Date();
                    const timeDifference = (currentTime.getTime() - latestGpsCreatedDate.getTime()) / 1000; // dalam detik

                    if (timeDifference <= OFFLINE_THRESHOLD) {
                        currentDeviceStatus = "Online";
                        connectionStatusElement.style.backgroundColor = "#27ae60"; // Hijau

                        // Ini adalah KOREKSI AKUMULASI durasi berdasarkan data GPS terbaru
                        // Kita tidak akan menambahkan detik secara langsung di sini setiap 10 detik,
                        // tetapi memastikan nilai usageDurationSeconds sesuai dengan waktu yang seharusnya.
                        if (lastActivityTimestamp && latestGpsCreatedDate.getTime() > lastActivityTimestamp.getTime()) {
                             const timeElapsedSinceLastKnownGPS = (latestGpsCreatedDate.getTime() - lastActivityTimestamp.getTime()) / 1000;
                             usageDurationSeconds += Math.round(timeElapsedSinceLastKnownGPS); // Akumulasi dari GPS
                        } else if (!lastActivityTimestamp) {
                            // Jika ini adalah data GPS pertama yang diterima atau setelah reset
                            usageDurationSeconds = 0; // Mulai dari 0 jika baru aktif
                        }
                        lastActivityTimestamp = latestGpsCreatedDate; // Update lastActivityTimestamp

                    } else { // Jika perangkat dianggap offline dari data GPS terbaru
                        currentDeviceStatus = "Offline";
                        connectionStatusElement.style.backgroundColor = "#e74c3c"; // Merah
                        
                        // Periksa apakah perangkat sudah offline lebih dari 1 jam untuk mereset durasi penggunaan
                        if (lastActivityTimestamp) {
                            const timeSinceLastActivity = (currentTime.getTime() - lastActivityTimestamp.getTime()) / 1000;
                            if (timeSinceLastActivity > USAGE_RESET_THRESHOLD_SECONDS) {
                                console.log("Device offline for more than 1 hour. Resetting usage duration and previous coords.");
                                usageDurationSeconds = 0;
                                previousLat = null;
                                previousLng = null;
                                lastActivityTimestamp = null; // Penting: reset juga timestamp aktivitas
                            }
                        }
                    }

                    // Pembaruan Peta
                    if (!marker) {
                        marker = L.marker([lat, lng]).addTo(map);
                        map.setView([lat, lng], 15);
                    } else {
                        marker.setLatLng([lat, lng]);
                        if (map.getCenter().distanceTo([lat, lng]) > 100) {
                             map.panTo([lat, lng]);
                        }
                    }

                    // Perbarui tampilan metrik
                    document.getElementById('current-speed').textContent = currentSpeed + " km/h";
                    // totalDistance sudah di-load dari backend dan akan diperbarui saat loadMetrics dipanggil lagi
                    // atau saat ada data baru dari backend (tapi ini hanya SUM speed).
                    // Jika Anda ingin total jarak ini juga diperbarui secara dinamis dari frontend, kita harus kembali ke akumulasi frontend.
                    // Untuk menjaga agar tidak membingungkan, biarkan loadDeviceMetrics() yang mengatur nilai awal.
                    document.getElementById('average-speed').textContent = calculateAverageSpeed(gpsData) + " km/h";
                    document.getElementById('maximum-speed').textContent = calculateMaxSpeed(gpsData) + " km/h";
                    updateUsageDurationDisplay(); // Akan diupdate oleh setInterval dan dikoreksi di sini
                }

                connectionStatusElement.textContent = currentServerTime + " | " + currentDeviceStatus;

                // --- Handle data sensor ---
                if (!sensorData || sensorData.length === 0) {
                    console.warn("No sensor data received.");
                    document.getElementById('bus-voltage').textContent = "N/A";
                } else {
                    const latestSensor = sensorData[0];
                    document.getElementById('bus-voltage').textContent = parseFloat(latestSensor.bus_voltage || 0).toFixed(2) + " V";
                }

                // Simpan usage_duration dan last_active_timestamp ke database.
                // totalDistance tidak lagi dikirim karena dihitung ulang di backend (get_device_metrics.php).
                await saveDeviceMetrics(usageDurationSeconds, latestGpsCreatedDate ? latestGpsCreatedDate.toISOString() : null);

            } catch (error) {
                console.error('Error in updateDashboardData:', error);
                currentDeviceStatus = "Offline (Error)";
                document.getElementById('connection-status').style.backgroundColor = "#e74c3c"; // Merah
                document.getElementById('connection-status').textContent = currentServerTime + " | " + currentDeviceStatus;
            }
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3;
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        }

        function calculateAverageSpeed(gpsData) {
            if (!gpsData || gpsData.length === 0) return 0;
            let totalSpeed = 0;
            gpsData.forEach(entry => {
                totalSpeed += parseFloat(entry.speed || 0);
            });
            return (totalSpeed / gpsData.length).toFixed(2);
        }

        function calculateMaxSpeed(gpsData) {
            if (!gpsData || gpsData.length === 0) return 0;
            let maxSpeed = 0;
            gpsData.forEach(entry => {
                const speed = parseFloat(entry.speed || 0);
                if (speed > maxSpeed) {
                    maxSpeed = speed;
                }
            });
            return maxSpeed.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', async () => {
            // Memuat metrik awal dari database
            await loadDeviceMetrics();

            // Memulai pembaruan jam server setiap detik
            updateRealTimeServerClock();
            setInterval(updateRealTimeServerClock, 1000);

            // Memulai pembaruan durasi penggunaan setiap detik (real-time visual)
            // Ini akan terus menambah detik di frontend, kemudian dikoreksi oleh data GPS.
            setInterval(() => {
                // Tentukan apakah perangkat *seharusnya* sedang berjalan atau online berdasarkan lastActivityTimestamp
                const currentTime = new Date();
                let isCurrentlyActive = false;
                if (lastActivityTimestamp) {
                    const timeSinceLastActivity = (currentTime.getTime() - lastActivityTimestamp.getTime()) / 1000;
                    // Jika waktu sejak aktivitas terakhir kurang dari ambang batas offline
                    // Kita asumsikan perangkat masih "aktif" atau akan segera mengirim data lagi
                    if (timeSinceLastActivity <= OFFLINE_THRESHOLD) {
                        isCurrentlyActive = true;
                    }
                }
                
                // Tambahkan detik hanya jika perangkat dianggap "aktif"
                if (isCurrentlyActive) {
                    usageDurationSeconds++;
                    updateUsageDurationDisplay();
                }
            }, 1000);

            // Memulai pembaruan data dashboard dan status perangkat setiap 10 detik
            // Jika data GPS baru diperbarui setiap 1 menit, interval ini bisa lebih panjang.
            // Namun, untuk sensor lain yang mungkin lebih sering, 10 detik masih relevan.
            updateDashboardData();
            setInterval(updateDashboardData, 10000); // Pertahankan ini untuk frekuensi fetch data dari server
        });
    </script>

<?php include 'layouts/footer.php'; ?>
</body>
</html>