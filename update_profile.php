<?php
session_start();
require 'config.php'; // Pastikan ini mengarah ke file koneksi database Anda

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    $response['message'] = 'Unauthorized access.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'] ?? null; // Dapatkan user ID dari sesi
if (!$user_id) {
    $response['message'] = 'ID pengguna tidak ditemukan di sesi.';
    echo json_encode($response);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';

    // Ambil password pengguna saat ini untuk verifikasi
    $stmt_get_password = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
    $stmt_get_password->bind_param("i", $user_id);
    $stmt_get_password->execute();
    $stmt_get_password->bind_result($stored_hash);
    $stmt_get_password->fetch();
    $stmt_get_password->close();

    if (!$stored_hash) {
        $response['message'] = 'Pengguna tidak ditemukan.';
        echo json_encode($response);
        exit();
    }

    switch ($action) {
        case 'change_password':
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_new_password = $_POST['confirm_new_password'] ?? '';

            if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
                $response['message'] = 'Semua field password harus diisi.';
                break;
            }
            if (!password_verify($current_password, $stored_hash)) {
                $response['message'] = 'Password saat ini salah.';
                break;
            }
            if ($new_password !== $confirm_new_password) {
                $response['message'] = 'Password baru dan konfirmasi password tidak cocok.';
                break;
            }
            // Tambahkan validasi kekuatan password di sini juga (server-side)
            if (strlen($new_password) < 8 || 
                !preg_match('/[A-Z]/', $new_password) || 
                !preg_match('/[a-z]/', $new_password) || 
                !preg_match('/[0-9]/', $new_password) || 
                !preg_match('/[^a-zA-Z0-9]/', $new_password)) {
                $response['message'] = "Password baru tidak memenuhi persyaratan keamanan.";
                break;
            }

            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_update_password = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
            $stmt_update_password->bind_param("si", $hashed_new_password, $user_id);

            if ($stmt_update_password->execute()) {
                $response['success'] = true;
                $response['message'] = 'Password berhasil diganti.';
            } else {
                $response['message'] = 'Gagal mengganti password: ' . $stmt_update_password->error;
            }
            $stmt_update_password->close();
            break;

        case 'change_email':
            $new_email = $_POST['new_email'] ?? '';
            $email_password = $_POST['email_password'] ?? '';

            if (empty($new_email) || empty($email_password)) {
                $response['message'] = 'Email baru dan password harus diisi.';
                break;
            }
            if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Format email baru tidak valid.';
                break;
            }
            if (!password_verify($email_password, $stored_hash)) {
                $response['message'] = 'Password Anda salah untuk konfirmasi perubahan email.';
                break;
            }

            // Cek apakah email baru sudah digunakan oleh user lain
            $stmt_check_email = $conn->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
            $stmt_check_email->bind_param("si", $new_email, $user_id);
            $stmt_check_email->execute();
            $stmt_check_email->store_result();
            if ($stmt_check_email->num_rows > 0) {
                $response['message'] = 'Email baru sudah digunakan oleh akun lain.';
                $stmt_check_email->close();
                break;
            }
            $stmt_check_email->close();

            $stmt_update_email = $conn->prepare("UPDATE admin_users SET email = ? WHERE id = ?");
            $stmt_update_email->bind_param("si", $new_email, $user_id);

            if ($stmt_update_email->execute()) {
                $response['success'] = true;
                $response['message'] = 'Email berhasil diganti.';
                // Perbarui email di sesi jika disimpan di sana
                $_SESSION['user_email'] = $new_email;
            } else {
                $response['message'] = 'Gagal mengganti email: ' . $stmt_update_email->error;
            }
            $stmt_update_email->close();
            break;

        default:
            $response['message'] = 'Aksi tidak valid.';
            break;
    }
} else {
    $response['message'] = 'Metode request tidak valid.';
}

$conn->close();
echo json_encode($response);
?>