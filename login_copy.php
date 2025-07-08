<?php
session_start(); // Memulai sesi untuk memeriksa status login

require 'config.php'; // Pastikan file config.php ada dan berisi koneksi database
require 'send_email.php'; // Digunakan jika ada logic kirim ulang OTP di sini (meskipun sudah dihapus)

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = ''; // Variabel untuk menyimpan pesan error jika login gagal
$message = ''; // Variabel untuk pesan sukses atau informasi

// Batasan percobaan login
define('MAX_LOGIN_ATTEMPTS', 5); // Maksimal percobaan gagal sebelum dikunci
define('LOCKOUT_TIME', 300); // Waktu penguncian dalam detik (misal: 300 detik = 5 menit)

// --- Kunci reCAPTCHA v2 Anda ---
// Pastikan ini adalah SECRET KEY reCAPTCHA v2 ASLI Anda
define('RECAPTCHA_SECRET_KEY', '6LeKJlUrAAAAAKQys-7RLQJg5IDUE7_sjxa2O5ts'); // <-- GANTI DENGAN SECRET KEY ASLI!

/**
 * Mengambil jumlah percobaan login gagal untuk username dan IP tertentu.
 * Menghapus percobaan yang sudah kadaluarsa.
 * @param mysqli $conn Koneksi database.
 * @param string $username Username yang dicoba login.
 * @param string $ip_address Alamat IP pengguna.
 * @return int Jumlah percobaan login gagal.
 */
function getLoginAttempts($conn, $username, $ip_address) {
    // Hapus percobaan yang sudah kadaluarsa untuk menjaga tabel tetap bersih
    $conn->query("DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL " . LOCKOUT_TIME . " SECOND"); // Menggunakan tabel produksi

    // Jika username disediakan, cek berdasarkan username dan IP
    $sql = "SELECT COUNT(*) FROM login_attempts WHERE (username = ? OR username = ?) AND ip_address = ?"; // Menggunakan tabel produksi
    $stmt = $conn->prepare($sql);
    $unknown_username = 'unknown_ip_' . $ip_address; // Identifier unik untuk IP jika username tidak ditemukan
    $stmt->bind_param("sss", $username, $unknown_username, $ip_address);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count;
}

/**
 * Menambahkan catatan percobaan login gagal.
 * @param mysqli $conn Koneksi database.
 * @param string $username Username yang dicoba login (atau identifier unik jika username tidak ditemukan).
 * @param string $ip_address Alamat IP pengguna.
 */
function addLoginAttempt($conn, $username, $ip_address) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, attempt_time) VALUES (?, ?, NOW())"); // Menggunakan tabel produksi
    $stmt->bind_param("ss", $ip_address, $username);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR']; // Ambil IP pengguna

    // --- Verifikasi reCAPTCHA v2 (checkbox) ---
    if (isset($_POST['g-recaptcha-response'])) {
        $recaptcha_response = $_POST['g-recaptcha-response'];
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
            goto end_form_process;
        }
    } else {
        $error = "Silakan centang verifikasi reCAPTCHA.";
        goto end_form_process;
    }

    // --- Pembatasan Percobaan Login ---
    $attempts = getLoginAttempts($conn, $username, $ip_address);
    if ($attempts >= MAX_LOGIN_ATTEMPTS) {
        $error = "Terlalu banyak percobaan login gagal dari IP Anda. Silakan coba lagi dalam " . (LOCKOUT_TIME / 60) . " menit.";
        goto end_form_process;
    }

    // Mengambil password hash dan status verifikasi dari database berdasarkan username
    $stmt = $conn->prepare("SELECT id, password, email, is_verified FROM admin_users WHERE username = ?"); // Menggunakan tabel produksi
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    // Jika username ditemukan dalam database
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $hash, $email, $is_verified);
        $stmt->fetch();

        // Verifikasi password menggunakan password_verify
        if (password_verify($password, $hash)) {
            // Cek apakah akun sudah terverifikasi
            if ($is_verified) {
                // Login berhasil, hapus semua percobaan gagal
                $conn->query("DELETE FROM login_attempts WHERE username = '" . $conn->real_escape_string($username) . "' OR ip_address = '" . $conn->real_escape_string($ip_address) . "'");
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $user_id; // <-- TAMBAHAN BARIS INI
                $_SESSION['username'] = $username; // <-- TAMBAHAN BARIS INI
                header('Location: dashboard.php');
                exit;
            } else {
                // Akun belum terverifikasi, minta verifikasi
                $error = "Akun Anda belum terverifikasi. Silakan lengkapi verifikasi email Anda.";
                $_SESSION['temp_email_for_otp'] = $email; // Simpan email di sesi untuk halaman verifikasi
            }
        } else {
            // Password salah, tambahkan percobaan gagal
            addLoginAttempt($conn, $username, $ip_address);
            $error = "Username atau password salah.";
        }
    } else {
        // Username tidak ditemukan, tambahkan percobaan gagal (untuk IP saja)
        addLoginAttempt($conn, 'unknown_ip_' . $ip_address, $ip_address);
        $error = "Username atau password salah.";
    }

    $stmt->close();
    end_form_process: // Label untuk goto, akan dieksekusi jika ada goto
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | GreenTrek</title>
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

    .login-container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        text-align: center;
        box-sizing: border-box;
    }

    .login-logo {
        margin-bottom: 25px;
        color: #4CAF50;
        font-size: 3.5rem;
    }

    .login-container h2 {
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

    .text-info { /* New style for informational messages */
        color: #2196F3;
        font-weight: 600;
        margin-bottom: 15px;
        font-size: 0.95rem;
    }

    .register-link {
        display: inline-block;
        margin-top: 25px;
        padding: 10px 20px;
        background-color: #2196F3;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }

    .register-link:hover {
        background-color: #1976D2;
    }

    .forgot-password-link { /* New style for forgot password link */
        display: block;
        text-align: right;
        margin-top: -10px; /* Adjust margin to pull it up closer to input */
        margin-bottom: 15px; /* Space between link and button */
        color: #2196F3;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: color 0.3s ease;
    }
    .forgot-password-link:hover {
        color: #1976D2;
    }

    .recaptcha-container {
        display: flex;
        justify-content: center;
        margin-bottom: 1.25rem;
    }

    /* Responsivitas Mobile */
    @media (max-width: 600px) {
        .login-container {
            padding: 25px;
            max-width: 95%;
        }
        .login-container h2 {
            font-size: 1.8rem;
        }
        .login-logo {
            font-size: 3rem;
        }
        .btn-primary, .register-link {
            font-size: 1rem;
            padding: 10px 15px;
        }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-logo">
      <i class="fas fa-seedling"></i> </div>
    <h2>Selamat Datang</h2>

    <?php if ($error): ?>
      <div class="text-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="text-info"><?= htmlspecialchars($message) ?></div>
        <?php if (isset($_SESSION['temp_email_for_otp'])): ?>
            <p class="text-gray-600 text-sm mt-3">
                Silakan <a href="verify_otp.php" class="text-blue-600 hover:underline">klik di sini</a> untuk melanjutkan verifikasi akun Anda.
            </p>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required autocomplete="username"
               placeholder="Masukkan username Anda" />
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password"
               placeholder="Masukkan password Anda" />
      </div>
      
      <a href="forgot_password.php" class="forgot-password-link">Lupa Password?</a> <div class="recaptcha-container">
        <div class="g-recaptcha" data-sitekey="6LeKJlUrAAAAACD3_iklrQpL_l_KrYDt5NkVXg5L"></div> 
      </div>
      
      <button type="submit" class="btn-primary">Login</button>
    </form>

    <div class="mt-6">
      <a href="register.php" class="register-link">Buat Akun Baru</a>
    </div>
  </div>
</body>
</html>