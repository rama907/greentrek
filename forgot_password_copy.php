<?php
session_start();
require 'config.php';
require 'send_email.php';

$error = '';
$message = '';

// --- Kunci reCAPTCHA v2 Anda ---
define('RECAPTCHA_SECRET_KEY', '6LeKJlUrAAAAAKQys-7RLQJg5IDUE7_sjxa2O5ts'); // <-- GANTI DENGAN SECRET KEY ASLI!

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // ReCAPTCHA verification
    if (empty($recaptcha_response)) {
        $error = "Silakan centang verifikasi reCAPTCHA.";
        goto end_process;
    }

    $verify_url = "https://www.google.com/recaptcha/api/siteverify";
    $data = [
        'secret' => RECAPTCHA_SECRET_KEY,
        'response' => $recaptcha_response,
        'remoteip' => $ip_address
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verify_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $responseData = json_decode($response);

    if (!$responseData || !$responseData->success) {
        $error = "Verifikasi reCAPTCHA gagal, silakan centang ulang.";
        goto end_process;
    }

    // Find user by email
    try {
        $stmt = $conn->prepare("SELECT id, username FROM admin_users WHERE email = ? AND is_verified = TRUE"); // Cek hanya yang sudah terverifikasi
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($user_id, $username);
            $stmt->fetch();
            $stmt->close();

            // Generate new OTP for password reset
            $otp_code = random_int(100000, 999999);
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Update user's verification_code and expiry for reset purposes
            $stmt_update = $conn->prepare("UPDATE admin_users SET verification_code = ?, verification_expiry = ? WHERE id = ?");
            $stmt_update->bind_param("ssi", $otp_code, $otp_expiry, $user_id);
            
            if ($stmt_update->execute()) {
                // Send email with OTP for password reset
                $subject = "Permintaan Reset Password GreenTrek Anda";
                $body = "Halo " . htmlspecialchars($username) . ",<br><br>"
                        . "Kami menerima permintaan reset password untuk akun Anda. Kode verifikasi Anda adalah:<br><br>"
                        . "<strong>Kode Reset: " . $otp_code . "</strong><br><br>"
                        . "Kode ini berlaku selama 15 menit. Jika Anda tidak meminta reset password, silakan abaikan email ini.<br><br>"
                        . "Setelah Anda memiliki kode ini, kunjungi tautan berikut untuk mengatur ulang password Anda: <br>"
                        . "<a href='" . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?email=" . urlencode($email) . "' target='_blank'>Reset Password Anda</a><br><br>" // Link ke reset_password.php
                        . "Salam Hormat,<br>"
                        . "Tim GreenTrek";

                if (sendEmail($email, $username, $subject, $body)) {
                    $_SESSION['reset_email'] = $email; // Simpan email di sesi untuk halaman reset_password
                    $message = "Kode verifikasi untuk reset password telah dikirim ke email Anda. Silakan cek kotak masuk Anda (termasuk folder spam) dan ikuti instruksinya.";
                    // Redirect to reset_password.php with email parameter
                    header("Location: reset_password.php?email=" . urlencode($email));
                    exit();
                } else {
                    $error = "Gagal mengirim email reset password. Silakan coba lagi.";
                }
            } else {
                $error = "Gagal memperbarui kode reset di database.";
            }
        } else {
            $error = "Email tidak ditemukan atau belum terverifikasi.";
        }
    } catch (mysqli_sql_exception $e) {
        $error = "Database error: " . $e->getMessage();
    }

    end_process:
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>Lupa Password | GreenTrek</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://www.google.com/recaptcha/api.js" async defer></script> 
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
    .forgot-container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        text-align: center;
        box-sizing: border-box;
    }
    .forgot-logo {
        margin-bottom: 25px;
        color: #4CAF50;
        font-size: 3.5rem;
    }
    .forgot-container h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #222;
        margin-bottom: 20px;
    }
    .form-group {
        margin-bottom: 1.25rem;
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
    .recaptcha-container {
        display: flex;
        justify-content: center;
        margin-bottom: 1.25rem;
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
    @media (max-width: 600px) {
        .forgot-container {
            padding: 25px;
            max-width: 95%;
        }
        .forgot-container h2 {
            font-size: 1.8rem;
        }
        .forgot-logo {
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
  <div class="forgot-container">
    <div class="forgot-logo">
      <i class="fas fa-lock"></i> </div>
    <h2>Lupa Password?</h2>
    <p class="text-gray-600 mb-6">
      Masukkan alamat email Anda yang terdaftar untuk menerima kode verifikasi.
    </p>

    <?php if ($error): ?>
      <p class="text-error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($message): ?>
      <p class="text-success"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email"
               placeholder="Masukkan email terdaftar Anda" />
      </div>
      
      <div class="recaptcha-container">
        <div class="g-recaptcha" data-sitekey="6LeKJlUrAAAAACD3_iklrQpL_l_KrYDt5NkVXg5L"></div> 
      </div>
      
      <button type="submit" class="btn-primary">Kirim Kode Verifikasi</button>
    </form>

    <a href="login.php" class="back-to-login">Kembali ke Login</a>
  </div>
</body>
</html>