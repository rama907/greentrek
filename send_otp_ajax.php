<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda
require 'send_email.php'; // Pastikan ini mengarah ke file pengiriman email Anda

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? ''); // Juga terima username untuk email

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Format email tidak valid.';
        echo json_encode($response);
        exit();
    }

    try {
        // Cek apakah username atau email sudah terdaftar (terverifikasi)
        $stmt_check_existing = $conn->prepare("SELECT id, is_verified FROM admin_users WHERE email = ? OR username = ?"); // <-- KEMBALIKAN KE admin_users
        $stmt_check_existing->bind_param("ss", $email, $username);
        $stmt_check_existing->execute();
        $stmt_check_existing->store_result();
        
        if ($stmt_check_existing->num_rows > 0) {
            $stmt_check_existing->bind_result($user_id, $is_verified);
            $stmt_check_existing->fetch();
            $stmt_check_existing->close();
            if ($is_verified) {
                $response['message'] = 'Username atau email ini sudah terdaftar dan terverifikasi. Silakan login.';
                echo json_encode($response);
                exit();
            }
            // Jika terdaftar tapi belum terverifikasi, kita akan kirim ulang OTP untuk email ini
            // Logic di sini akan meng-overwrite OTP lama
        } else {
            $stmt_check_existing->close();
            // Jika username/email benar-benar baru, lanjutkan
        }
        
        // Generate OTP
        $otp_code = random_int(100000, 999999); // 6 digit OTP
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+15 minutes')); // OTP berlaku 15 menit

        // Simpan data pendaftaran sementara di SESSION
        $_SESSION['register_otp_email'] = $email;
        $_SESSION['register_otp_code'] = (string)$otp_code;
        $_SESSION['register_otp_expiry'] = $otp_expiry;
        $_SESSION['register_temp_username'] = $username;
        $_SESSION['register_temp_password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT); // Simpan hash password sementara

        // Kirim email verifikasi
        $subject = "Verifikasi Akun GreenTrek Anda";
        $body = "Halo " . htmlspecialchars($username) . ",<br><br>"
                . "Terima kasih telah mendaftar di GreenTrek. Untuk memverifikasi akun Anda, silakan masukkan kode verifikasi berikut:<br><br>"
                . "<strong>Kode Verifikasi: " . $otp_code . "</strong><br><br>"
                . "Kode ini berlaku selama 15 menit.<br><br>"
                . "Jika Anda tidak mendaftar untuk akun ini, silakan abaikan email ini.<br><br>"
                . "Salam Hormat,<br>"
                . "Tim GreenTrek";

        if (sendEmail($email, $username, $subject, $body)) {
            $response['success'] = true;
            $response['message'] = 'Kode verifikasi telah dikirim ke email Anda. Silakan cek kotak masuk Anda (termasuk folder spam).';
        } else {
            $response['message'] = 'Gagal mengirim email verifikasi. Silakan coba lagi.';
        }

    } catch (mysqli_sql_exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

$conn->close();
echo json_encode($response);