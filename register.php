<?php
session_start();
require 'config.php'; // Pastikan file config.php ada dan berisi koneksi database

$error = '';
$success = '';

// --- Kunci reCAPTCHA v2 Anda ---
// Pastikan ini adalah SECRET KEY reCAPTCHA v2 ASLI dari Google Console Anda!
define('RECAPTCHA_SECRET_KEY', '6LeKJlUrAAAAAKQys-7RLQJg5IDUE7_sjxa2O5ts'); // <-- GANTI DENGAN SECRET KEY ASLI!
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
  <title>Daftar | GreenTrek</title>
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

    .register-container {
        background-color: #ffffff;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 450px;
        text-align: center;
        box-sizing: border-box;
    }

    .register-logo {
        margin-bottom: 25px;
        color: #4CAF50;
        font-size: 3.5rem;
    }

    .register-container h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #222;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 1.25rem;
        position: relative; /* Untuk ikon indikator */
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

    .login-link {
        display: inline-block;
        margin-top: 25px;
        padding: 10px 20px;
        background-color: #6C757D;
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        transition: background-color 0.3s ease;
    }

    .login-link:hover {
        background-color: #5A6268;
    }

    /* CSS Khusus untuk memusatkan reCAPTCHA v2 */
    .recaptcha-container {
        display: flex;
        justify-content: center;
        margin-bottom: 1.25rem;
    }

    /* Style untuk indikator password */
    .password-requirements {
        text-align: left;
        font-size: 0.85rem;
        color: #555;
        margin-top: 0.5rem;
        margin-left: 5px; /* Sedikit indentasi agar sejajar dengan label */
    }

    .password-requirements li {
        list-style: none; /* Hapus bullet default */
        padding-left: 1.2em; /* Ruang untuk ikon */
        position: relative;
        line-height: 1.5;
    }

    .password-requirements li::before {
        content: '\2718'; /* Unicode 'X' (belum terpenuhi) */
        color: #e74c3c; /* Merah */
        position: absolute;
        left: 0;
        font-weight: bold;
    }

    .password-requirements li.valid::before {
        content: '\2714'; /* Unicode 'centang' (terpenuhi) */
        color: #28a745; /* Hijau */
    }

    /* Styles for OTP section */
    #otp-section {
        display: none; /* Hidden by default */
    }
    .otp-input {
        text-align: center;
        font-size: 1.5rem;
        letter-spacing: 0.5rem;
        padding: 12px 15px;
    }
    .resend-otp-btn {
        background-color: #2196F3;
        color: white;
        padding: 8px 15px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        margin-top: 10px;
        transition: background-color 0.3s ease;
    }
    .resend-otp-btn:hover {
        background-color: #1976D2;
    }
    .countdown-timer {
        font-size: 0.9rem;
        color: #555;
        margin-top: 10px;
    }

    /* Indikator Konfirmasi Password */
    .password-match-indicator {
        font-size: 0.85rem;
        text-align: left;
        margin-top: 5px;
        margin-left: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .password-match-indicator.match { color: #28a745; }
    .password-match-indicator.no-match { color: #e74c3c; }

    /* Indikator Username */
    .username-status-message {
        font-size: 0.85rem;
        text-align: left;
        margin-top: 5px;
        margin-left: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .username-status-message.available { color: #28a745; }
    .username-status-message.taken { color: #e74c3c; }

    /* Style for eye icon */
    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%; /* Adjust based on label/input positioning */
        transform: translateY(20%); /* Fine-tune vertical alignment */
        cursor: pointer;
        color: #6c757d;
        font-size: 1rem;
        z-index: 10; /* Ensure it's above input */
    }


    /* Responsivitas Mobile */
    @media (max-width: 600px) {
        .register-container {
            padding: 25px;
            max-width: 95%;
        }
        .register-container h2 {
            font-size: 1.8rem;
        }
        .register-logo {
            font-size: 3rem;
        }
        .btn-primary, .login-link {
            font-size: 1rem;
            padding: 10px 15px;
        }
        .toggle-password {
            transform: translateY(10%); /* Adjust for smaller screens */
        }
    }
  </style>
</head>
<body>

  <div class="register-container">
    <div class="register-logo">
      <i class="fas fa-tractor"></i> </div>
    <h2>Daftar Akun Baru</h2>

    <div id="alert-messages">
        <?php if ($error): ?>
          <p class="text-error" id="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
          <p class="text-success" id="success-message"><?= $success ?></p>
        <?php endif; ?>
    </div>

    <form id="register-form" class="space-y-4">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" placeholder="Masukkan username" required autofocus autocomplete="username">
        <div id="username-status"></div> </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" placeholder="Masukkan email Anda" required autocomplete="email">
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" placeholder="Buat password" required autocomplete="new-password">
        <i class="far fa-eye toggle-password" id="togglePasswordRegister"></i>
        <ul class="password-requirements" id="passwordRequirements">
            <li id="length">Minimal 8 karakter</li>
            <li id="uppercase">Satu huruf kapital (A-Z)</li>
            <li id="lowercase">Satu huruf kecil (a-z)</li>
            <li id="number">Satu angka (0-9)</li>
            <li id="symbol">Satu simbol (!@#$%)</li>
        </ul>
      </div>
      <div class="form-group">
        <label for="password_confirm">Konfirmasi Password</label>
        <input type="password" name="password_confirm" id="password_confirm" placeholder="Ulangi password" required autocomplete="new-password">
        <i class="far fa-eye toggle-password" id="togglePasswordConfirm"></i>
        <div id="password-match-indicator" class="password-match-indicator"></div>
      </div>

      <div class="recaptcha-container">
        <div class="g-recaptcha" data-sitekey="6LeKJlUrAAAAACD3_iklrQpL_l_KrYDt5NkVXg5L"></div>
      </div>
      
      <button type="submit" class="btn-primary" id="send-otp-btn">Kirim Kode Verifikasi</button>
    </form>

    <div id="otp-section" class="space-y-4">
        <p class="text-gray-600">Kode verifikasi telah dikirim ke <strong id="otp-email-display"></strong>.</p>
        <div class="form-group">
            <label for="otp">Masukkan Kode Verifikasi (OTP)</label>
            <input type="text" id="otp" name="otp" placeholder="Contoh: 123456" required maxlength="6" class="otp-input">
        </div>
        <button type="submit" class="btn-primary" id="verify-otp-btn">Verifikasi Akun</button>
        <p class="countdown-timer" id="resend-timer">Kirim ulang dalam <span id="timer-value">60</span> detik</p>
        <button class="resend-otp-btn" id="resend-otp-button" disabled>Kirim Ulang Kode</button>
    </div>

    <div class="mt-6">
      <a href="login.php" class="login-link">Kembali ke Login</a>
    </div>
  </div>

  <script>
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');
    const passwordMatchIndicator = document.getElementById('password-match-indicator'); // Changed ID
    const usernameInput = document.getElementById('username');
    const usernameStatusDiv = document.getElementById('username-status');

    // Toggle password visibility for register form
    const togglePasswordRegister = document.querySelector('#togglePasswordRegister');
    const togglePasswordConfirm = document.querySelector('#togglePasswordConfirm');

    togglePasswordRegister.addEventListener('click', function (e) {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
        this.classList.toggle('fa-eye');
    });

    togglePasswordConfirm.addEventListener('click', function (e) {
        const type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordConfirmInput.setAttribute('type', type);
        this.classList.toggle('fa-eye-slash');
        this.classList.toggle('fa-eye');
    });


    const passwordRequirements = document.getElementById('passwordRequirements');
    const registerForm = document.getElementById('register-form');
    const otpSection = document.getElementById('otp-section');
    const sendOtpBtn = document.getElementById('send-otp-btn');
    const verifyOtpBtn = document.getElementById('verify-otp-btn');
    const otpInput = document.getElementById('otp');
    const otpEmailDisplay = document.getElementById('otp-email-display');
    const resendOtpButton = document.getElementById('resend-otp-button');
    const resendTimerDisplay = document.getElementById('resend-timer');
    const timerValueDisplay = document.getElementById('timer-value');
    const alertMessagesContainer = document.getElementById('alert-messages');

    let resendCountdown = 60; // Detik untuk hitung mundur kirim ulang
    let countdownInterval;
    let usernameCheckTimeout; // Timeout for username check

    function displayMessage(type, message) {
        alertMessagesContainer.innerHTML = ''; // Clear previous messages
        const p = document.createElement('p');
        p.classList.add(type === 'success' ? 'text-success' : 'text-error');
        p.innerHTML = message;
        alertMessagesContainer.appendChild(p);
    }

    function startResendTimer() {
        resendCountdown = 60;
        resendOtpButton.disabled = true;
        resendTimerDisplay.style.display = 'block';
        timerValueDisplay.textContent = resendCountdown;

        countdownInterval = setInterval(() => {
            resendCountdown--;
            timerValueDisplay.textContent = resendCountdown;
            if (resendCountdown <= 0) {
                clearInterval(countdownInterval);
                resendOtpButton.disabled = false;
                resendTimerDisplay.style.display = 'none';
            }
        }, 1000);
    }

    // Password requirements validation
    const length = document.getElementById('length');
    const uppercase = document.getElementById('uppercase');
    const lowercase = document.getElementById('lowercase');
    const number = document.getElementById('number');
    const symbol = document.getElementById('symbol');

    passwordInput.addEventListener('keyup', function() {
        const password = passwordInput.value;

        // Validasi Panjang
        if (password.length >= 8) { length.classList.add('valid'); } else { length.classList.remove('valid'); }
        // Validasi Huruf Kapital
        if (/[A-Z]/.test(password)) { uppercase.classList.add('valid'); } else { uppercase.classList.remove('valid'); }
        // Validasi Huruf Kecil
        if (/[a-z]/.test(password)) { lowercase.classList.add('valid'); } else { lowercase.classList.remove('valid'); }
        // Validasi Angka
        if (/[0-9]/.test(password)) { number.classList.add('valid'); } else { number.classList.remove('valid'); }
        // Validasi Simbol (selain huruf dan angka)
        if (/[^a-zA-Z0-9]/.test(password)) { symbol.classList.add('valid'); } else { symbol.classList.remove('valid'); }

        // Trigger password confirm check
        checkPasswordMatch();
    });

    // New: Check Password Match
    passwordConfirmInput.addEventListener('keyup', checkPasswordMatch);
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = passwordConfirmInput.value;

        passwordMatchIndicator.innerHTML = ''; // Clear previous content
        passwordMatchIndicator.classList.remove('match', 'no-match');

        if (confirmPassword.length === 0) {
            // Do nothing if empty
            return;
        }

        if (password === confirmPassword) {
            passwordMatchIndicator.classList.add('match');
            passwordMatchIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok.';
        } else {
            passwordMatchIndicator.classList.add('no-match');
            passwordMatchIndicator.innerHTML = '<i class="fas fa-times-circle"></i> Password tidak sama.';
        }
    }

    // New: Check Username Availability (with debounce)
    usernameInput.addEventListener('keyup', function() {
        clearTimeout(usernameCheckTimeout); // Clear previous timeout
        const username = usernameInput.value;

        if (username.length < 3) { // Only check if username is long enough
            usernameStatusDiv.innerHTML = '';
            return;
        }

        usernameStatusDiv.innerHTML = '<span class="username-status-message text-gray-500"><i class="fas fa-spinner fa-spin"></i> Memeriksa username...</span>';

        usernameCheckTimeout = setTimeout(async () => {
            try {
                const response = await fetch('check_username.php?username=' + encodeURIComponent(username));
                const result = await response.json();

                if (result.isTaken) {
                    usernameStatusDiv.innerHTML = '<span class="username-status-message taken"><i class="fas fa-times-circle"></i> Username sudah digunakan.</span>';
                } else {
                    usernameStatusDiv.innerHTML = '<span class="username-status-message available"><i class="fas fa-check-circle"></i> Username tersedia.</span>';
                }
            } catch (error) {
                console.error('Error checking username:', error);
                usernameStatusDiv.innerHTML = '<span class="username-status-message text-gray-500">Gagal cek username.</span>';
            }
        }, 500); // Debounce for 500ms
    });


    // Handle form submission for sending OTP
    registerForm.addEventListener('submit', async function(event) {
        event.preventDefault(); // Prevent default form submission

        displayMessage('success', '<i class="fas fa-spinner fa-spin"></i> Mengirim kode verifikasi...'); // Show loading message

        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const password_confirm = document.getElementById('password_confirm').value;
        const recaptchaResponse = grecaptcha.getResponse();

        if (!recaptchaResponse) {
            displayMessage('error', 'Silakan centang verifikasi reCAPTCHA.');
            return;
        }
        
        if (password !== password_confirm) {
            displayMessage('error', 'Password dan konfirmasi password tidak sama.');
            grecaptcha.reset(); // Reset reCAPTCHA
            return;
        }

        // Check password strength on client-side before sending OTP
        const isPasswordStrong = passwordInput.value.length >= 8 &&
                                 /[A-Z]/.test(passwordInput.value) &&
                                 /[a-z]/.test(passwordInput.value) &&
                                 /[0-9]/.test(passwordInput.value) &&
                                 /[^a-zA-Z0-9]/.test(passwordInput.value);
        if (!isPasswordStrong) {
            displayMessage('error', 'Password tidak memenuhi semua persyaratan keamanan.');
            grecaptcha.reset();
            return;
        }


        const formData = new FormData();
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password); // Kirim password ke backend untuk hashing sementara
        formData.append('g-recaptcha-response', recaptchaResponse);

        try {
            const response = await fetch('send_otp_ajax.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                displayMessage('success', result.message);
                otpEmailDisplay.textContent = email; // Tampilkan email di bagian OTP
                registerForm.style.display = 'none'; // Sembunyikan form registrasi
                otpSection.style.display = 'block'; // Tampilkan bagian OTP
                startResendTimer(); // Mulai hitung mundur kirim ulang
            } else {
                displayMessage('error', result.message);
                grecaptcha.reset(); // Reset reCAPTCHA on failure
            }
        } catch (error) {
            console.error('Error sending OTP:', error);
            displayMessage('error', 'Terjadi kesalahan saat mengirim kode verifikasi. Coba lagi.');
            grecaptcha.reset(); // Reset reCAPTCHA on network error
        }
    });

    // Handle OTP verification submission
    verifyOtpBtn.addEventListener('click', async function() {
        const otp = otpInput.value;
        if (!otp) {
            displayMessage('error', 'Kode OTP tidak boleh kosong.');
            return;
        }

        displayMessage('success', '<i class="fas fa-spinner fa-spin"></i> Memverifikasi kode...'); // Show loading message

        const formData = new FormData();
        formData.append('otp', otp);

        try {
            const response = await fetch('verify_otp_ajax.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                displayMessage('success', result.message);
                // Redirect to login page after successful verification
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000); // Redirect after 2 seconds
            } else {
                displayMessage('error', result.message);
            }
        } catch (error) {
            console.error('Error verifying OTP:', error);
            displayMessage('error', 'Terjadi kesalahan saat verifikasi kode. Coba lagi.');
        }
    });

    // Handle resend OTP button click
    resendOtpButton.addEventListener('click', async function() {
        resendOtpButton.disabled = true; // Disable to prevent multiple clicks

        displayMessage('success', '<i class="fas fa-spinner fa-spin"></i> Mengirim ulang kode verifikasi...');

        const username = document.getElementById('username').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value; // Password diperlukan untuk re-hashing jika sesi hilang

        const formData = new FormData();
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password); // Password diperlukan untuk re-hashing jika sesi hilang

        try {
            const response = await fetch('send_otp_ajax.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                displayMessage('success', result.message);
                startResendTimer(); // Mulai timer lagi
            } else {
                displayMessage('error', result.message);
                resendOtpButton.disabled = false; // Enable if failed
            }
        } catch (error) {
            console.error('Error resending OTP:', error);
            displayMessage('error', 'Terjadi kesalahan saat mengirim ulang kode. Coba lagi.');
            resendOtpButton.disabled = false; // Enable if failed
        }
    });

    // Initialize password requirements display
    document.addEventListener('DOMContentLoaded', () => {
        // Check initial password strength if there's pre-filled value
        if (passwordInput.value) {
            passwordInput.dispatchEvent(new Event('keyup'));
        }
        // Check password match on load if there's pre-filled value
        if (passwordConfirmInput.value) {
            checkPasswordMatch();
        }
    });
  </script>
</body>
</html>