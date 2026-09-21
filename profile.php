<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null; // Asumsi Anda menyimpan user_id di sesi saat login
$username = '';
$email = '';
$message = '';
$error = '';

// Ambil data profil pengguna dari database
if ($user_id) {
    try {
        $stmt = $conn->prepare("SELECT username, email FROM admin_users WHERE id = ?"); // Menggunakan tabel produksi
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($username, $email);
        $stmt->fetch();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $error = "Gagal mengambil data profil: " . $e->getMessage();
    }
} else {
    $error = "ID pengguna tidak ditemukan di sesi.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengguna | GreenTrek</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding-top: 100px; /* Jarak dari atas untuk topbar yang sticky */
            padding-bottom: 20px;
            min-height: 100vh;
            color: #333;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }
        .container {
            max-width: 800px; /* Sesuaikan lebar container profil */
            margin: auto;
            padding-left: 15px;
            padding-right: 15px;
            flex-grow: 1;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 24px;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-header i {
            font-size: 5rem;
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .profile-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #222;
        }
        .profile-info {
            margin-bottom: 30px;
            background-color: #f0fdf4; /* Warna background lebih lembut */
            border: 1px solid #d1fae5;
            padding: 20px;
            border-radius: 8px;
        }
        .profile-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 1.1rem;
            color: #333;
        }
        .profile-info-item i {
            color: #10B981;
            width: 25px; /* Lebar ikon tetap */
            text-align: center;
        }
        .form-section {
            margin-top: 30px;
        }
        .form-section h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #444;
        }
        .form-group input:not([type="radio"]):not([type="checkbox"]),
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            color: #333;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
        }
        .form-group input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
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
        .text-error {
            color: #e74c3c;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        .text-success {
            color: #28a745;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }
        /* Password Strength / Match Indicators */
        .password-requirements, .password-match-indicator {
            font-size: 0.85rem;
            text-align: left;
            margin-top: 5px;
            margin-left: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .password-requirements li {
            list-style: none;
            padding-left: 1.2em;
            position: relative;
            line-height: 1.5;
        }
        .password-requirements li::before {
            content: '\2718';
            color: #e74c3c;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        .password-requirements li.valid::before {
            content: '\2714';
            color: #28a745;
        }
        .password-match-indicator.match { color: #28a745; }
        .password-match-indicator.no-match { color: #e74c3c; }


        @media (max-width: 768px) {
            body {
                padding-top: 90px;
            }
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            .card {
                padding: 15px;
            }
            .profile-header h2 {
                font-size: 1.8rem;
            }
            .profile-header i {
                font-size: 4rem;
            }
            .form-section h3 {
                font-size: 1.3rem;
            }
            .profile-info-item {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'layouts/topbar.php'; ?>
    <?php include 'layouts/header.php'; ?>

    <div class="container">
        <div class="card">
            <div class="profile-header">
                <i class="fas fa-user-circle"></i>
                <h2>Profil Pengguna</h2>
            </div>

            <?php if ($error): ?>
                <p class="text-error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
            <?php if ($message): ?>
                <p class="text-success"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>

            <div class="profile-info">
                <div class="profile-info-item">
                    <i class="fas fa-user"></i> <span><strong>Username:</strong> <?= htmlspecialchars($username) ?></span>
                </div>
                <div class="profile-info-item">
                    <i class="fas fa-envelope"></i> <span><strong>Email:</strong> <?= htmlspecialchars($email) ?></span>
                </div>
                <div class="profile-info-item">
                    <i class="fas fa-calendar-alt"></i> <span><strong>Tanggal Bergabung:</strong> <?php /* Ambil dari DB jika ada created_at */ echo date('d M Y'); ?></span>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-lock"></i> Ganti Password</h3>
                <form id="change-password-form" method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label for="current_password">Password Saat Ini</label>
                        <input type="password" id="current_password" name="current_password" required placeholder="Masukkan password Anda saat ini">
                    </div>
                    <div class="form-group">
                        <label for="new_password">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Buat password baru">
                        <ul class="password-requirements" id="newPasswordRequirements">
                            <li id="new_length">Minimal 8 karakter</li>
                            <li id="new_uppercase">Satu huruf kapital (A-Z)</li>
                            <li id="new_lowercase">Satu huruf kecil (a-z)</li>
                            <li id="new_number">Satu angka (0-9)</li>
                            <li id="new_symbol">Satu simbol (!@#$%)</li>
                        </ul>
                    </div>
                    <div class="form-group">
                        <label for="confirm_new_password">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required placeholder="Ulangi password baru">
                        <div id="new-password-match-indicator" class="password-match-indicator"></div>
                    </div>
                    <button type="submit" class="btn-submit">Ganti Password</button>
                </form>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-envelope"></i> Ganti Email</h3>
                <form id="change-email-form" method="POST">
                    <input type="hidden" name="action" value="change_email">
                    <div class="form-group">
                        <label for="current_email">Email Saat Ini</label>
                        <input type="email" id="current_email" name="current_email" value="<?= htmlspecialchars($email) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="new_email">Email Baru</label>
                        <input type="email" id="new_email" name="new_email" required placeholder="Masukkan email baru Anda">
                    </div>
                     <div class="form-group">
                        <label for="email_password">Password Anda</label>
                        <input type="password" id="email_password" name="email_password" required placeholder="Masukkan password Anda untuk konfirmasi">
                    </div>
                    <button type="submit" class="btn-submit">Ganti Email</button>
                </form>
            </div>

            </div>
    </div>

    <script>
        // JavaScript untuk validasi password baru (mirip register)
        const newPasswordInput = document.getElementById('new_password');
        const confirmNewPasswordInput = document.getElementById('confirm_new_password');
        const newPasswordRequirements = document.getElementById('newPasswordRequirements');
        const newPasswordMatchIndicator = document.getElementById('new-password-match-indicator');

        const new_length = document.getElementById('new_length');
        const new_uppercase = document.getElementById('new_uppercase');
        const new_lowercase = document.getElementById('new_lowercase');
        const new_number = document.getElementById('new_number');
        const new_symbol = document.getElementById('new_symbol');

        newPasswordInput.addEventListener('keyup', function() {
            const password = newPasswordInput.value;

            if (password.length >= 8) { new_length.classList.add('valid'); } else { new_length.classList.remove('valid'); }
            if (/[A-Z]/.test(password)) { new_uppercase.classList.add('valid'); } else { new_uppercase.classList.remove('valid'); }
            if (/[a-z]/.test(password)) { new_lowercase.classList.add('valid'); } else { new_lowercase.classList.remove('valid'); }
            if (/[0-9]/.test(password)) { new_number.classList.add('valid'); } else { new_number.classList.remove('valid'); }
            if (/[^a-zA-Z0-9]/.test(password)) { new_symbol.classList.add('valid'); } else { new_symbol.classList.remove('valid'); }

            checkNewPasswordMatch();
        });

        confirmNewPasswordInput.addEventListener('keyup', checkNewPasswordMatch);

        function checkNewPasswordMatch() {
            const newPassword = newPasswordInput.value;
            const confirmNewPassword = confirmNewPasswordInput.value;

            newPasswordMatchIndicator.innerHTML = '';
            newPasswordMatchIndicator.classList.remove('match', 'no-match');

            if (confirmNewPassword.length === 0) {
                return;
            }

            if (newPassword === confirmNewPassword) {
                newPasswordMatchIndicator.classList.add('match');
                newPasswordMatchIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok.';
            } else {
                newPasswordMatchIndicator.classList.add('no-match');
                newPasswordMatchIndicator.innerHTML = '<i class="fas fa-times-circle"></i> Password tidak sama.';
            }
        }

        // Handle form submissions for AJAX
        const changePasswordForm = document.getElementById('change-password-form');
        const changeEmailForm = document.getElementById('change-email-form');

        changePasswordForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this); // Mengambil semua data dari form
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmNewPassword = document.getElementById('confirm_new_password').value;

            if (newPassword !== confirmNewPassword) {
                alert("Password baru dan konfirmasi password tidak cocok.");
                return;
            }
            // Tambahkan validasi kekuatan password di sini juga jika diperlukan
            const isNewPasswordStrong = newPassword.length >= 8 &&
                                         /[A-Z]/.test(newPassword) &&
                                         /[a-z]/.test(newPassword) &&
                                         /[0-9]/.test(newPassword) &&
                                         /[^a-zA-Z0-9]/.test(newPassword);
            if (!isNewPasswordStrong) {
                alert("Password baru tidak memenuhi persyaratan keamanan.");
                return;
            }


            try {
                const response = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message); // Tampilkan pesan sukses
                    // Opsional: Bersihkan form password setelah sukses
                    changePasswordForm.reset();
                    newPasswordMatchIndicator.innerHTML = '';
                    newPasswordRequirements.querySelectorAll('li').forEach(li => li.classList.remove('valid'));
                } else {
                    alert("Error: " + result.message); // Tampilkan pesan error
                }
            } catch (error) {
                console.error('Error changing password:', error);
                alert("Terjadi kesalahan saat mengganti password.");
            }
        });

        changeEmailForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            const formData = new FormData(this);
            const newEmail = document.getElementById('new_email').value;
            const emailPassword = document.getElementById('email_password').value;

            if (!newEmail || !emailPassword) {
                alert("Email baru dan password harus diisi.");
                return;
            }
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(newEmail)) {
                alert("Format email baru tidak valid.");
                return;
            }

            try {
                const response = await fetch('update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    alert(result.message); // Tampilkan pesan sukses
                    // Opsional: Bersihkan form email setelah sukses atau update tampilan current email
                    document.getElementById('current_email').value = newEmail; // Update tampilan email saat ini
                    changeEmailForm.reset(); // Reset form
                } else {
                    alert("Error: " + result.message); // Tampilkan pesan error
                }
            } catch (error) {
                console.error('Error changing email:', error);
                alert("Terjadi kesalahan saat mengganti email.");
            }
        });

        // Initial check for password requirements on load
        document.addEventListener('DOMContentLoaded', () => {
            if (newPasswordInput.value) {
                newPasswordInput.dispatchEvent(new Event('keyup'));
            }
            if (confirmNewPasswordInput.value) {
                checkNewPasswordMatch();
            }
        });
    </script>

    <?php include 'layouts/footer.php'; ?>
</body>
</html>