<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp_input = trim($_POST['otp'] ?? '');

    $temp_email = $_SESSION['register_otp_email'] ?? null;
    $temp_otp_code = $_SESSION['register_otp_code'] ?? null;
    $temp_otp_expiry = $_SESSION['register_otp_expiry'] ?? null;
    $temp_username = $_SESSION['register_temp_username'] ?? null;
    $temp_password_hash = $_SESSION['register_temp_password_hash'] ?? null;

    // Penting: Jangan hapus sesi di sini jika verify_otp_ajax akan dipanggil beberapa kali.
    // Hapus sesi setelah sukses final untuk mencegah re-use.
    // Jika ada kegagalan, sesi tetap ada untuk percobaan ulang.

    if (empty($otp_input) || empty($temp_email) || empty($temp_otp_code) || empty($temp_otp_expiry) || empty($temp_username) || empty($temp_password_hash)) {
        $response['message'] = 'Sesi verifikasi tidak valid atau kode OTP kosong. Silakan coba daftar ulang.';
        // Bersihkan sesi jika tidak valid agar tidak bisa di-reuse
        unset($_SESSION['register_otp_email']);
        unset($_SESSION['register_otp_code']);
        unset($_SESSION['register_otp_expiry']);
        unset($_SESSION['register_temp_username']);
        unset($_SESSION['register_temp_password_hash']);
        echo json_encode($response);
        exit();
    }

    if (strtotime($temp_otp_expiry) < time()) {
        $response['message'] = 'Kode OTP sudah kedaluwarsa. Silakan coba daftar ulang untuk mendapatkan kode baru.';
        // Bersihkan sesi
        unset($_SESSION['register_otp_email']);
        unset($_SESSION['register_otp_code']);
        unset($_SESSION['register_otp_expiry']);
        unset($_SESSION['register_temp_username']);
        unset($_SESSION['register_temp_password_hash']);
        echo json_encode($response);
        exit();
    }

    if ($otp_input === $temp_otp_code) {
        try {
            // Cek lagi username dan email untuk duplikasi final di tabel produksi
            $stmt_check_final = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?"); // <-- KEMBALIKAN KE admin_users
            $stmt_check_final->bind_param("ss", $temp_username, $temp_email);
            $stmt_check_final->execute();
            $stmt_check_final->store_result();

            if ($stmt_check_final->num_rows > 0) {
                $response['message'] = 'Username atau email sudah terdaftar saat finalisasi. Silakan coba login atau gunakan data lain.';
                $stmt_check_final->close();
                // Bersihkan sesi
                unset($_SESSION['register_otp_email']);
                unset($_SESSION['register_otp_code']);
                unset($_SESSION['register_otp_expiry']);
                unset($_SESSION['register_temp_username']);
                unset($_SESSION['register_temp_password_hash']);
                echo json_encode($response);
                exit();
            }
            $stmt_check_final->close();

            // Simpan pengguna ke database produksi dengan is_verified TRUE
            $stmt_insert_user = $conn->prepare("INSERT INTO admin_users (username, email, password, is_verified) VALUES (?, ?, ?, TRUE)"); // <-- KEMBALIKAN KE admin_users
            $stmt_insert_user->bind_param("sss", $temp_username, $temp_email, $temp_password_hash);

            if ($stmt_insert_user->execute()) {
                $response['success'] = true;
                $response['message'] = 'Pendaftaran dan verifikasi berhasil! Anda akan diarahkan ke halaman login.';
                // Bersihkan semua sesi verifikasi setelah sukses
                unset($_SESSION['register_otp_email']);
                unset($_SESSION['register_otp_code']);
                unset($_SESSION['register_otp_expiry']);
                unset($_SESSION['register_temp_username']);
                unset($_SESSION['register_temp_password_hash']);
            } else {
                $response['message'] = 'Gagal mendaftar: ' . $stmt_insert_user->error;
            }
            $stmt_insert_user->close();

        } catch (mysqli_sql_exception $e) {
            $response['message'] = 'Database error: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Kode OTP salah. Silakan coba lagi.';
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

$conn->close();
echo json_encode($response);