<?php
session_start();

// Jika belum login, redirect ke login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Jika sudah login, tampilkan halaman
?>
<!DOCTYPE html>
<html>
<head>
    <title>Portfolio - HBN Project</title>
    <style>
        .user-menu {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="user-menu">
        <img src="images/avatars/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" 
             class="avatar" 
             alt="User Avatar"
             onclick="toggleMenu()">
        <div id="dropdownMenu" style="display:none;">
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
    <!-- Konten portfolio lainnya -->

    <script>
        function toggleMenu() {
            const menu = document.getElementById('dropdownMenu');
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>