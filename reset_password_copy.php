<?php
session_start();
require 'config.php';

$error = '';
$message = '';
$email = $_GET['email'] ?? ($_SESSION['reset_email'] ?? '');

// Jika tidak ada email, arahkan kembali ke forgot_password
if (empty($email)) {
    header("Location: forgot_password.php");
    exit();
}

// Ensure email is in session for security (prevent direct access via URL param)
if (!isset($_SESSION['reset_email']) || $_SESSION['reset_email'] !== $email) {
    // If email in URL doesn't match session, or no session, redirect
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp_input = trim($_POST['otp']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($otp_input) || empty($new_password) || empty($confirm_password)) {
        $error = "Semua field harus diisi.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Password baru dan konfirmasi password tidak sama.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password harus terdiri minimal 8 karakter.";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = "Password harus mengandung setidaknya satu huruf kapital.";
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = "Password harus mengandung setidaknya satu huruf kecil.";
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = "Password harus mengandung setidaknya satu angka.";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $new_password)) {
        $error = "Password harus mengandung setidaknya satu simbol (contoh: !@#$%^&*).";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, verification_code, verification_expiry FROM admin_users WHERE email = ? AND is_verified = TRUE");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $stored_otp, $stored_expiry);
                $stmt->fetch();
                $stmt->close();

                if (strtotime($stored_expiry) < time()) {
                    $error = "Kode verifikasi sudah kedaluwarsa. Silakan kembali ke halaman lupa password.";
                    // Clear OTP from DB
                    $conn->query("UPDATE admin_users SET verification_code = NULL, verification_expiry = NULL WHERE id = $user_id");
                } elseif ($otp_input === $stored_otp) {
                    // OTP cocok dan belum kedaluwarsa, update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_update = $conn->prepare("UPDATE admin_users SET password = ?, verification_code = NULL, verification_expiry = NULL WHERE id = ?");
                    $stmt_update->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt_update->execute()) {
                        $message = "Password Anda berhasil direset! Silakan <a href='login.php' class='text-blue-600 hover:underline'>login</a>.";
                        unset($_SESSION['reset_email']); // Clear session after successful reset
                    } else {
                        $error = "Gagal mereset password.";
                    }
                    $stmt_update->close();
                } else {
                    $error = "Kode verifikasi salah. Silakan coba lagi.";
                }
            } else {
                $error = "Permintaan reset tidak valid atau email tidak ditemukan.";
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>Reset Password | GreenTrek</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f7fa;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
        padding: 20px;
        color: #333;
    }
    .reset-container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        text-align: center;
        box-sizing: border-box;
    }
    .reset-logo {
        margin-bottom: 25px;
        color: #4CAF50;
        font-size: 3.5rem;
    }
    .reset-container h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #222;
        margin-bottom: 20px;
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
        text-align: left;
    }
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        color: #333;
        transition: border-color 0.3s ease, box-shadow 0.3s ease;
    }
    .form-group input:focus {
        outline: none;
        border-color: #4CAF50;
        box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.2);
    }
    .btn-primary {
        width: 100%;
        padding: 12px 20px;
        background-color: #4CAF50;
        color: white;
        font-weight: 600;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1rem;
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
        margin-top: 25px;
    }
    .btn-primary:hover {
        background-color: #45a049;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
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
    .back-to-login {
        display: block;
        margin-top: 25px;
        color: #6C757D;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.3s ease;
    }
    .back-to-login:hover {
        color: #5A6268;
    }
    /* Password requirements indicator */
    .password-requirements {
        text-align: left;
        font-size: 0.85rem;
        color: #555;
        margin-top: 0.5rem;
        margin-left: 5px;
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
    /* Indikator Konfirmasi Password - New Styling */
    .password-match-indicator { /* Changed class name for clarity */
        font-size: 0.85rem;
        text-align: left;
        margin-top: 5px;
        margin-left: 5px;
        display: flex; /* Use flexbox for icon and text alignment */
        align-items: center;
        gap: 5px; /* Space between icon and text */
    }
    .password-match-indicator.match { color: #28a745; }
    .password-match-indicator.no-match { color: #e74c3c; }


    @media (max-width: 600px) {
        .reset-container {
            padding: 25px;
            max-width: 95%;
        }
        .reset-container h2 {
            font-size: 1.8rem;
        }
        .reset-logo {
            font-size: 3rem;
        }
        .btn-primary {
            font-size: 1rem;
            padding: 10px 15px;
        }
    }
  </style>
</head>
<body>
  <div class="reset-container">
    <div class="reset-logo">
      <i class="fas fa-key"></i> </div>
    <h2>Reset Password</h2>
    <p class="text-gray-600 mb-6">
      Masukkan kode verifikasi yang Anda terima dan password baru.
    </p>

    <?php if ($error): ?>
      <p class="text-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
      <p class="text-success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div class="form-group">
        <label for="otp">Kode Verifikasi (OTP)</label>
        <input type="text" id="otp" name="otp" placeholder="Masukkan kode dari email Anda" required maxlength="6" value="<?= htmlspecialchars($_POST['otp'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="new_password">Password Baru</label>
        <input type="password" id="new_password" name="new_password" placeholder="Buat password baru" required autocomplete="new-password">
        <ul class="password-requirements" id="passwordRequirements">
            <li id="length">Minimal 8 karakter</li>
            <li id="uppercase">Satu huruf kapital (A-Z)</li>
            <li id="lowercase">Satu huruf kecil (a-z)</li>
            <li id="number">Satu angka (0-9)</li>
            <li id="symbol">Satu simbol (!@#$%)</li>
        </ul>
      </div>
      <div class="form-group">
        <label for="confirm_password">Konfirmasi Password Baru</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password baru" required autocomplete="new-password">
        <div id="password-match-indicator" class="password-match-indicator"></div> </div>
      
      <button type="submit" class="btn-primary">Reset Password</button>
    </form>

    <a href="login.php" class="back-to-login">Kembali ke Login</a>
  </div>

  <script>
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordRequirements = document.getElementById('passwordRequirements');
    const passwordMatchIndicator = document.getElementById('password-match-indicator'); // Changed ID

    // Password requirements validation for new password
    const length = document.getElementById('length');
    const uppercase = document.getElementById('uppercase');
    const lowercase = document.getElementById('lowercase');
    const number = document.getElementById('number');
    const symbol = document.getElementById('symbol');

    newPasswordInput.addEventListener('keyup', function() {
        const password = newPasswordInput.value;

        if (password.length >= 8) { length.classList.add('valid'); } else { length.classList.remove('valid'); }
        if (/[A-Z]/.test(password)) { uppercase.classList.add('valid'); } else { uppercase.classList.remove('valid'); }
        if (/[a-z]/.test(password)) { lowercase.classList.add('valid'); } else { lowercase.classList.remove('valid'); }
        if (/[0-9]/.test(password)) { number.classList.add('valid'); } else { number.classList.remove('valid'); }
        if (/[^a-zA-Z0-9]/.test(password)) { symbol.classList.add('valid'); } else { symbol.classList.remove('valid'); }

        checkPasswordMatch();
    });

    confirmPasswordInput.addEventListener('keyup', checkPasswordMatch);

    function checkPasswordMatch() {
        const newPassword = newPasswordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        passwordMatchIndicator.innerHTML = ''; // Clear previous content
        passwordMatchIndicator.classList.remove('match', 'no-match');

        if (confirmPassword.length === 0) {
            // Do nothing if empty
            return;
        }

        if (newPassword === confirmPassword) {
            passwordMatchIndicator.classList.add('match');
            passwordMatchIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok.';
        } else {
            passwordMatchIndicator.classList.add('no-match');
            passwordMatchIndicator.innerHTML = '<i class="fas fa-times-circle"></i> Password tidak sama.';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (newPasswordInput.value) {
            newPasswordInput.dispatchEvent(new Event('keyup'));
        }
        // Also check password match on load if there's pre-filled value
        if (confirmPasswordInput.value) {
            checkPasswordMatch();
        }
    });
  </script>
</body>
</html>