<?php
// Jika diunduh manual, sesuaikan jalur ke PHPMailer src/
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';
require 'phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to_email, $to_name, $subject, $body_html) {
    $mail = new PHPMailer(true);

    try {
        // Pengaturan Server SMTP Anda
        // GANTI INI DENGAN DETAIL SERVER SMTP ANDA YANG ASLI!
        // Contoh untuk Gmail, SendGrid, Mailgun, dll.
        $mail->isSMTP();                                            // Kirim menggunakan SMTP
        $mail->Host       = 'smtp.gmail.com';                     // Ganti dengan SMTP host Anda (e.g., smtp.gmail.com)
        $mail->SMTPAuth   = true;                                   // Aktifkan autentikasi SMTP
        $mail->Username   = 'greentrek365@gmail.com';               // Ganti dengan username email Anda
        $mail->Password   = 'lmnc skaf reca mdrk';                  // Ganti dengan password email Anda (atau App Password jika pakai Gmail)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Aktifkan enkripsi TLS (atau ENCRYPTION_SMTPS untuk SSL)
        $mail->Port       = 587;                                    // Port TCP untuk koneksi (587 untuk TLS, 465 untuk SSL)
        $mail->CharSet    = 'UTF-8';                                // Set karakter ke UTF-8

        // Pengaturan Pengirim dan Penerima
        $mail->setFrom('greentrek365@gmail.com', 'GreenTrek'); // Alamat email pengirim dan nama
        $mail->addAddress($to_email, $to_name);                     // Tambahkan penerima

        // Konten Email
        $mail->isHTML(true);                                        // Set format email ke HTML
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = strip_tags($body_html); // Versi plain-text untuk klien email yang tidak mendukung HTML

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email tidak dapat dikirim ke $to_email. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>