<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard Monitoring Alat Tani</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <script src="https://cdn.tailwindcss.com"></script>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <style>
    /* Reset box-sizing global */
    html {
      box-sizing: border-box;
    }
    *, *::before, *::after {
      box-sizing: inherit;
    }

    /* Styling untuk membuat topbar sticky */
    header {
      position: sticky;
      top: 0;
      z-index: 1000;
      background-color: #ffffff;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      padding: 1rem 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      min-height: 80px; /* Contoh min-height */
    }

    .nav-menu {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      color: #4B5563;
      font-weight: 500;
      transition: all 0.3s ease;
      white-space: nowrap;
    }

    .nav-link:hover {
      background-color: #E6F4EA;
      color: #10B981;
    }

    .nav-link.active {
      background-color: #10B981;
      color: white;
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
    }

    .nav-link.active i {
      color: white;
    }

    .nav-link i {
      color: #4B5563;
      transition: color 0.3s ease;
    }

    /* Gaya untuk tombol hamburger */
    .menu-toggle {
        display: none;
        background: none;
        border: none;
        font-size: 1.8rem;
        color: #333;
        cursor: pointer;
    }

    /* Gaya untuk dropdown profil */
    .profile-dropdown {
        position: relative;
        display: inline-block;
        margin-left: 1rem; /* Jarak dari item menu lain */
    }

    .profile-dropdown-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        background-color: #f0f0f0; /* Warna latar belakang toggle */
        color: #4B5563;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .profile-dropdown-toggle:hover {
        background-color: #e0e0e0;
    }

    .profile-dropdown-toggle img {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    .profile-dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        min-width: 160px;
        box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
        z-index: 1;
        border-radius: 0.5rem;
        right: 0; /* Posisikan dropdown ke kanan */
        top: 100%; /* Di bawah tombol toggle */
        margin-top: 0.5rem; /* Jarak dari tombol toggle */
    }

    .profile-dropdown-content a {
        color: #4B5563;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
        transition: background-color 0.3s ease;
        white-space: nowrap;
    }

    .profile-dropdown-content a:hover {
        background-color: #E6F4EA;
        color: #10B981;
    }

    .profile-dropdown-content a:first-child {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
    }

    .profile-dropdown-content a:last-child {
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
    }

    .profile-dropdown-content.show {
        display: block;
    }

    /* Media Queries untuk Responsivitas Topbar */
    @media (max-width: 768px) {
        header {
            flex-direction: row;
            justify-content: space-between;
            padding: 1rem;
            min-height: 70px;
        }
        .menu-toggle {
            display: block;
        }
        .nav-menu {
            display: none;
            flex-direction: column;
            width: 100%;
            background-color: #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
            margin-top: 1rem;
            border-radius: 8px;
        }
        .nav-menu.open {
            display: flex;
        }
        .nav-link {
            width: 100%;
            justify-content: flex-start;
            padding: 0.75rem 1.5rem;
            border-radius: 0;
        }
        .nav-link:hover, .nav-link.active {
            border-radius: 0;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        /* Penyesuaian dropdown profil di mobile */
        .profile-dropdown {
            width: 100%;
            margin-left: 0;
        }
        .profile-dropdown-toggle {
            width: 100%;
            justify-content: flex-start;
            padding: 0.75rem 1.5rem;
            border-radius: 0;
            background-color: transparent; /* Hilangkan background di mobile */
            color: #4B5563; /* Kembalikan warna teks nav-link */
        }
        .profile-dropdown-toggle:hover {
            background-color: #E6F4EA;
            color: #10B981;
        }
        .profile-dropdown-content {
            position: static; /* Hilangkan posisi absolut */
            box-shadow: none; /* Hilangkan shadow */
            background-color: transparent; /* Hilangkan background */
            min-width: unset;
            width: 100%;
            padding: 0;
            margin-top: 0;
        }
        .profile-dropdown-content a {
            padding-left: 2.5rem; /* Indentasi agar terlihat sebagai sub-menu */
            border-radius: 0;
        }
    }
  </style>
</head>
<body class="bg-green-50 min-h-screen font-sans">
<header>
  <div class="header-content">
    <h1 class="text-2xl font-bold text-green-800 flex items-center gap-2">
      <i class="fas fa-tractor text-green-600"></i> Monitoring Alat Tani | GreenTrek
    </h1>
    <button class="menu-toggle" aria-label="Toggle navigation">
      <i class="fas fa-bars"></i>
    </button>
  </div>
  <nav class="nav-menu">
    <a href="dashboard.php" class="nav-link" id="nav-dashboard">
        <i class="fas fa-home"></i> Dashboard Utama
    </a>
    <a href="map_history.php" class="nav-link" id="nav-map-history">
        <i class="fas fa-route"></i> Riwayat Perjalanan
    </a>
    <a href="gps_data.php" class="nav-link" id="nav-gps">
        <i class="fas fa-map-marked-alt"></i> Data GPS
    </a>
    <a href="ina219_data.php" class="nav-link" id="nav-sensor">
        <i class="fas fa-charging-station"></i> Data Sensor
    </a>
    <a href="service_data.php" class="nav-link" id="nav-service"> <i class="fas fa-tools"></i> Data Servis </a>
    
    <div class="profile-dropdown">
        <div class="profile-dropdown-toggle" id="profileDropdownToggle">
            <i class="fas fa-user-circle text-2xl"></i> 
            <span>Profil</span>
            <i class="fas fa-chevron-down text-xs ml-auto"></i> </div>
        <div class="profile-dropdown-content" id="profileDropdownContent">
            <a href="profile.php" id="nav-profile"><i class="fas fa-user"></i> Profil Saya</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
  </nav>
</header>

<script>
    // Highlight aktif link di topbar
    document.addEventListener('DOMContentLoaded', function() {
        const currentPath = window.location.pathname.split('/').pop();
        const navLinks = document.querySelectorAll('.nav-link');
        const menuToggle = document.querySelector('.menu-toggle');
        const navMenu = document.querySelector('.nav-menu');
        const profileDropdownToggle = document.getElementById('profileDropdownToggle'); // Ambil toggle
        const profileDropdownContent = document.getElementById('profileDropdownContent'); // Ambil konten dropdown

        // Logic untuk highlight link aktif
        navLinks.forEach(link => {
            const linkPath = link.href.split('/').pop();
            // Menyesuaikan untuk menangani kasus dashboard.php sebagai halaman default
            if (linkPath === currentPath || (currentPath === '' && linkPath === 'dashboard.php')) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
        
        // Tambahan: Highlight link profil jika di halaman profil
        if (currentPath === 'profile.php') {
            const profileNavLink = document.getElementById('nav-profile'); // Dapatkan link "Profil Saya" di dropdown
            if (profileNavLink) {
                profileNavLink.classList.add('active');
                // Optional: Tambahkan gaya aktif juga pada toggle utama jika diinginkan
                profileDropdownToggle.classList.add('active');
            }
        }


        // Logic untuk toggle menu di mobile
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            // Ganti ikon hamburger menjadi X dan sebaliknya
            const icon = menuToggle.querySelector('i');
            if (navMenu.classList.contains('open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Logic untuk toggle dropdown profil
        profileDropdownToggle.addEventListener('click', function(event) {
            profileDropdownContent.classList.toggle('show');
            // Mencegah event click menyebar ke window dan menutup dropdown secara instan
            event.stopPropagation(); 
        });

        // Tutup dropdown jika klik di luar dropdown
        window.addEventListener('click', function(event) {
            if (!profileDropdownContent.contains(event.target) && !profileDropdownToggle.contains(event.target)) {
                profileDropdownContent.classList.remove('show');
            }
        });

        // Tutup menu jika link diklik (khusus mobile)
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth <= 768) { // Hanya tutup di layar mobile
                    navMenu.classList.remove('open');
                    menuToggle.querySelector('i').classList.remove('fa-times');
                    menuToggle.querySelector('i').classList.add('fa-bars');
                }
            });
        });

        // Tutup menu jika ukuran layar berubah dari mobile ke desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768 && navMenu.classList.contains('open')) {
                navMenu.classList.remove('open');
                menuToggle.querySelector('i').classList.remove('fa-times');
                menuToggle.querySelector('i').classList.add('fa-bars');
            }
            // Pastikan dropdown profil tertutup saat resize dari mobile ke desktop
            profileDropdownContent.classList.remove('show');
        });
    });
</script>