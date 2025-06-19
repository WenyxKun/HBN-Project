<?php
session_start();
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/security.php';

// Validasi CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token');
}

$user_id = $_SESSION['user_id'];

// Handle file upload
$avatar_name = $_SESSION['user']['avatar'] ?? 'default-avatar.jpg';
if (!empty($_FILES['avatar']['name'])) {
    $target_dir = "../Assets/Img/avatars/";
    
    // Validasi file
    $allowed_types = ['jpg', 'jpeg', 'png'];
    $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        $_SESSION['error'] = "Hanya file JPG, JPEG, PNG yang diperbolehkan";
        header('Location: ../setting.php');
        exit;
    }
    
    // Generate nama unik
    $avatar_name = "avatar_".$user_id."_".time().".".$file_ext;
    $target_file = $target_dir . $avatar_name;
    
    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
        $_SESSION['error'] = "Gagal mengupload gambar";
        header('Location: ../setting.php');
        exit;
    }
    
    // Hapus avatar lama jika bukan default
    $old_avatar = $_SESSION['user']['avatar'] ?? 'default-avatar.jpg';
    if ($old_avatar != 'default-avatar.jpg' && file_exists($target_dir.$old_avatar)) {
        unlink($target_dir.$old_avatar);
    }
}

// Update database
try {
    $stmt = $conn->prepare("UPDATE pelanggan SET nama = ?, email = ?, avatar = ? WHERE id = ?");
    $stmt->execute([
        $_POST['nama'],
        $_POST['email'],
        $avatar_name,
        $user_id
    ]);
    
    // Update session
    $_SESSION['user']['nama'] = $_POST['nama'];
    $_SESSION['user']['email'] = $_POST['email'];
    $_SESSION['user']['avatar'] = $avatar_name;
    
    $_SESSION['success'] = "Profil berhasil diperbarui";
} catch (PDOException $e) {
    $_SESSION['error'] = "Gagal memperbarui profil: " . $e->getMessage();
}

header('Location: ../setting.php');
exit;
?>