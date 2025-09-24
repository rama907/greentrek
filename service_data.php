<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

// Memeriksa apakah pengguna sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Proses penambahan data servis jika form disubmit
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'add_service') {
        $service_date_time = $_POST['service_date'] . ' ' . $_POST['service_time'] . ':00'; // Gabungkan tanggal dan jam
        $responsible_person = trim($_POST['responsible_person']);
        $mechanic_name = trim($_POST['mechanic_name']);
        $notes = trim($_POST['notes']);

        if (empty($service_date_time) || empty($responsible_person) || empty($mechanic_name) || empty($notes)) {
            $error_message = "Semua field harus diisi.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO service_history (service_date, responsible_person, mechanic_name, notes) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $service_date_time, $responsible_person, $mechanic_name, $notes);

                if ($stmt->execute()) {
                    $success_message = "Data servis berhasil ditambahkan.";
                } else {
                    $error_message = "Gagal menambahkan data servis: " . $stmt->error;
                }
                $stmt->close();
            } catch (mysqli_sql_exception $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_metrics') {
        try {
            // --- HANYA RESET USAGE DURATION DI device_metrics ---
            // Karena total_distance sekarang dihitung dari tbl_gps,
            // mereset total_distance_meters di sini tidak akan berpengaruh langsung
            $stmt_usage = $conn->prepare("UPDATE device_metrics SET usage_duration_seconds = 0, last_active_timestamp = NOW() WHERE id = 1");
            $stmt_usage->execute();
            $stmt_usage->close();

            // --- KOSONGKAN TABEL GPS UNTUK MERESET TOTAL JARAK TEMPUH DARI AWAL ---
            // PERHATIAN: Ini akan MENGHAPUS SEMUA DATA GPS yang pernah disimpan!
            $stmt_truncate_gps = $conn->prepare("TRUNCATE TABLE tbl_gps");
            $stmt_truncate_gps->execute();
            $stmt_truncate_gps->close();


            $success_message = "Total jarak dan durasi penggunaan berhasil direset (data GPS dihapus).";
        } catch (mysqli_sql_exception $e) {
            $error_message = "Database error saat mereset: " . $e->getMessage();
        }
    }
}

// Ambil data riwayat servis dari database
$service_history = [];
try {
    $result = $conn->query("SELECT id, service_date, responsible_person, mechanic_name, notes FROM service_history ORDER BY service_date DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $service_history[] = $row;
        }
        $result->free();
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Gagal mengambil riwayat servis: " . $e->getMessage();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Servis | GreenTrek</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* ... (CSS Anda yang sudah ada, tidak perlu diubah) ... */
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0; /* Hapus padding dari body */
            min-height: 100vh;
            color: #333;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            /* Margin-top untuk jarak dari topbar */
            margin-top: 100px; /* Sesuaikan dengan tinggi topbar + jarak */
            padding: 0 15px;
            flex-grow: 1;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px; /* Jarak antar card */
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #222;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #444;
        }

        .form-group input[type="date"],
        .form-group input[type="time"],
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.95rem;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }

        .btn-submit {
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

        .btn-submit:hover {
            background-color: #45a049;
        }

        .btn-reset { /* Gaya baru untuk tombol reset */
            background-color: #f44336; /* Merah */
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
            margin-top: 15px; /* Jarak dari form di atasnya */
        }

        .btn-reset:hover {
            background-color: #da190b;
        }

        .message-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
        }

        /* Gaya Tabel */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            color: #333;
            white-space: nowrap;
        }
        thead th {
            background-color: #e6f4ea;
            padding: 12px 15px;
            font-weight: 700;
            text-align: left;
            border-bottom: 2px solid #b3d7b0;
        }
        tbody tr:hover {
            background-color: #f1faf2;
        }
        tbody td {
            padding: 10px 15px;
            border-bottom: 1px solid #d6e8d9;
        }

        /* Responsivitas */
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
                margin-top: 90px; /* Sesuaikan dengan tinggi topbar mobile + jarak */
            }
            .card {
                padding: 15px;
            }
            .card-header {
                font-size: 1.2rem;
            }
            .form-group input, .form-group textarea {
                font-size: 0.85rem;
                padding: 8px 10px;
            }
            thead th, tbody td {
                padding: 8px 10px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'layouts/header.php'; ?>
    <?php include 'layouts/topbar.php'; ?>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus-circle"></i> Tambah Data Servis Baru
            </div>

            <?php if ($success_message): ?>
                <div class="message-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="message-error"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="action" value="add_service"> <div class="form-group">
                    <label for="service_date">Tanggal Servis:</label>
                    <input type="date" id="service_date" name="service_date" required>
                </div>
                <div class="form-group">
                    <label for="service_time">Jam Servis:</label>
                    <input type="time" id="service_time" name="service_time" required>
                </div>
                <div class="form-group">
                    <label for="responsible_person">Penanggung Jawab:</label>
                    <input type="text" id="responsible_person" name="responsible_person" placeholder="Nama Penanggung Jawab" required>
                </div>
                <div class="form-group">
                    <label for="mechanic_name">Nama yang Memperbaiki:</label>
                    <input type="text" id="mechanic_name" name="mechanic_name" placeholder="Nama Teknisi/Mekanik" required>
                </div>
                <div class="form-group">
                    <label for="notes">Catatan Servis:</label>
                    <textarea id="notes" name="notes" rows="4" placeholder="Apa saja yang diperbaiki..." required></textarea>
                </div>
                <button type="submit" class="btn-submit">Simpan Data Servis</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-redo-alt"></i> Reset Metrik Alat Tani
            </div>
            <p>Klik tombol di bawah ini untuk mereset total jarak tempuh dan durasi penggunaan alat. Aksi ini tidak dapat dibatalkan.</p>
            <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin mereset total jarak dan durasi penggunaan? Aksi ini tidak dapat dibatalkan.');">
                <input type="hidden" name="action" value="reset_metrics">
                <button type="submit" class="btn-reset"><i class="fas fa-exclamation-triangle"></i> Reset Total Jarak & Durasi</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-history"></i> Riwayat Servis Alat Tani
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal & Jam</th>
                            <th>Penanggung Jawab</th>
                            <th>Mekanik</th>
                            <th>Catatan Servis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($service_history)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Tidak ada riwayat servis.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($service_history as $service): ?>
                                <tr>
                                    <td><?= htmlspecialchars($service['service_date']) ?></td>
                                    <td><?= htmlspecialchars($service['responsible_person']) ?></td>
                                    <td><?= htmlspecialchars($service['mechanic_name']) ?></td>
                                    <td><?= htmlspecialchars($service['notes']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
<?php include 'layouts/footer.php'; ?>
</html>