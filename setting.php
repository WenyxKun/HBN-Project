<?php
// ==================== BAGIAN KONFIGURASI ====================
// Pastikan tidak ada output sebelum session_start()
if (headers_sent()) {
    die("Error: Output sudah dikirim sebelum session dimulai");
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include file konfigurasi dengan pengecekan
$required_files = [
    __DIR__.'/backend/config/session.php',
    __DIR__.'/backend/config/database.php',
    __DIR__.'/backend/config/security.php'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("Error: File konfigurasi tidak ditemukan - " . basename($file));
    }
    require_once $file;
}

// ==================== BAGIAN AUTHENTIKASI ====================
// Auth check yang lebih robust
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

// Get user data dengan prepared statement yang lebih aman
try {
    $user_id = $_SESSION['user']['id'];
    
    // SARAN 1: Gunakan nama tabel yang konsisten (ubah 'users' ke 'pelanggan' jika perlu)
    $stmt = $conn->prepare("SELECT id, nama, email, phone, avatar, is_active, last_login FROM pelanggan WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // SARAN 2: Logout paksa jika user tidak ditemukan
        session_unset();
        session_destroy();
        header('Location: login.php?error=user_not_found');
        exit;
    }

    // SARAN 3: Update session dengan data penting saja
    $_SESSION['user'] = [
        'id' => $user['id'],
        'nama' => htmlspecialchars($user['nama']),
        'email' => filter_var($user['email'], FILTER_SANITIZE_EMAIL),
        'phone' => preg_replace('/[^0-9]/', '', $user['phone'] ?? ''),
        'avatar' => $user['avatar'] ?? 'default-avatar.jpg',
        'is_active' => (int)$user['is_active'] === 1,
        'last_login' => $user['last_login'] ?? null,
        'last_activity' => time() // Tambahan untuk tracking aktivitas
    ];

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Terjadi kesalahan sistem";
    header('Location: error.php');
    exit;
}

// ==================== BAGIAN UPDATE PROFILE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Validasi CSRF yang lebih ketat
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token keamanan tidak valid";
        header('Location: setting.php');
        exit;
    }

    // Validasi input yang lebih menyeluruh
    $nama = trim(htmlspecialchars($_POST['nama'] ?? ''));
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
    
    if (empty($nama) || strlen($nama) > 100) {
        $_SESSION['error'] = "Nama harus diisi (maks 100 karakter)";
        header('Location: setting.php');
        exit;
    }

    if (!$email) {
        $_SESSION['error'] = "Format email tidak valid";
        header('Location: setting.php');
        exit;
    }

    // Handle file upload dengan lebih aman
    $avatar = $_SESSION['user']['avatar'];
    if (!empty($_FILES['avatar']['tmp_name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
        $target_dir = __DIR__."/Assets/Img/avatars/";
        
        // Create directory dengan permission yang aman
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
            file_put_contents($target_dir."index.html", ""); // Prevent directory listing
        }
        
        // Validasi file lebih ketat
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $_FILES['avatar']['tmp_name']);
        finfo_close($file_info);
        
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
        $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        
        if (!in_array($mime_type, $allowed_mimes) || !in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])) {
            $_SESSION['error'] = "Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan";
            header('Location: setting.php');
            exit;
        }
        
        // Generate nama file yang aman
        $avatar = "avatar_".bin2hex(random_bytes(8)).".".$file_ext;
        $target_file = $target_dir . $avatar;
        
        // Resize dan kompres gambar sebelum disimpan
        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
            $_SESSION['error'] = "Gagal menyimpan gambar";
            header('Location: setting.php');
            exit;
        }
        
        // Hapus avatar lama jika perlu
        if ($_SESSION['user']['avatar'] !== 'default-avatar.jpg' && 
            file_exists($target_dir.$_SESSION['user']['avatar'])) {
            unlink($target_dir.$_SESSION['user']['avatar']);
        }
    }

    // Update database dengan transaksi
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("
            UPDATE pelanggan 
            SET 
                nama = :nama, 
                email = :email, 
                phone = :phone, 
                avatar = :avatar, 
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");
        
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt->execute([
            ':nama' => $nama,
            ':email' => $email,
            ':phone' => !empty($phone) ? $phone : null,
            ':avatar' => $avatar,
            ':is_active' => $is_active,
            ':id' => $user_id
        ]);
        
        // Verifikasi perubahan
        if ($stmt->rowCount() === 0) {
            throw new PDOException("Tidak ada data yang berubah");
        }
        
        $conn->commit();
        
        // Update session
        $_SESSION['user'] = [
            'id' => $user_id,
            'nama' => $nama,
            'email' => $email,
            'phone' => !empty($phone) ? $phone : null,
            'avatar' => $avatar,
            'is_active' => (bool)$is_active,
            'last_login' => $_SESSION['user']['last_login'] ?? null
        ];
        
        $_SESSION['success'] = "Profil berhasil diperbarui";
        header('Location: setting.php');
        exit;
        
    } catch (PDOException $e) {
        $conn->rollBack();
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "Gagal memperbarui profil. Silakan coba lagi.";
        header('Location: setting.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1" name="viewport"/>
    <title>Profile - <?= htmlspecialchars($user['nama']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="Assets/css/style.css"/>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Navbar -->
    <div class="shadow bg-white">
        <div class="h-20 mx-auto px-5 flex items-center justify-between">
            <a class="navbar-brand ps-3" href="index.php">
                <img src="Assets\Img\Gambar1.jpg" width="50" height="50" alt="">
            </a>

            <ul class="flex items-center gap-5">
                <li>
                    <a class="hover:text-cyan-500 transition-colors" href="index.php#portfolio">Portofolio</a>
                </li>
                <li>
                    <a class="hover:text-cyan-500 transition-colors" href="index.php#about">Tentang Kami</a>
                </li>
                <li>
                    <a class="hover:text-cyan-500 transition-colors" href="index.php#contact">Kontak</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="Assets/Img/<?= htmlspecialchars($_SESSION['user']['avatar'] ?? 'default-avatar.jpg') ?>" class="rounded-circle" width="30" height="30" alt="Profile">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                        <li>
                            <a class="dropdown-item" href="setting.php">
                                <i class="fas fa-user-cog me-2"></i> Profile & Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="backend/logout.php" method="post">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>

    <div class="max-w-7xl mx-auto mt-8 flex flex-col md:flex-row gap-6 px-4 md:px-5 p-8">
        <!-- Left panel -->
        <div class="bg-white w-full md:w-72 p-8 flex flex-col items-center">
            <img
                alt="User profile"
                class="mb-4 rounded-full w-20 h-20 object-cover"
                src="Assets/Img/<?= htmlspecialchars($user['avatar'] ?? 'default-avatar.jpg') ?>"/>
            <p class="text-sm font-semibold text-gray-800 mb-1">
                <?= htmlspecialchars($user['nama']) ?>
            </p>
            <p class="text-xs text-gray-500">
                <?= htmlspecialchars($user['email']) ?>
            </p>
        </div>

        <!-- Right panel -->
        <div class="bg-white flex-1 p-6">
            <!-- Horizontal Tabs -->
            <nav class="flex space-x-6 border-b border-gray-200 mb-4">
                <button class="tab-btn px-1 py-2 text-sm font-medium text-sky-500 border-b-2 border-sky-500" data-tab="pesanan">
                    Pesanan
                </button>
                <button class="tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="form">
                    Form
                </button>
                <button class="tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300" data-tab="pengaturan">
                    Pengaturan
                </button>
            </nav>

            <!-- Notification Messages -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger mb-4 p-3 bg-red-100 text-red-700 rounded">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success mb-4 p-3 bg-green-100 text-green-700 rounded">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <?php unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <!-- Tab Contents -->
            <div class="tab-contents">
                <!-- Pesanan Tab -->
                <div class="tab-content active" id="pesanan-content">
                    <?php
// Menjadi:
$userId = $_SESSION['user']['id'] ?? null; // Gunakan null coalescing operator
if (!$userId) {
    $_SESSION['error'] = "Sesi tidak valid. Silakan login kembali";
    header('Location: login.php');
    exit;
}
                    $stmt = $conn->prepare("
                        SELECT o.*, p.name as package_name 
                        FROM orders o
                        JOIN packages p ON o.package_id = p.id
                        WHERE o.user_id = ?
                        ORDER BY o.created_at DESC
                    ");
try {
    $stmt = $conn->prepare("
        SELECT o.*, p.name as package_name 
        FROM `orders` o  <!-- Tambahkan backtick untuk nama tabel -->
        JOIN `packages` p ON o.package_id = p.id <!-- Tambahkan backtick -->
        WHERE o.user_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
    
    if (empty($orders)) {
        echo "<p>Anda belum memiliki pesanan</p>";
    }
} catch (PDOException $e) {
    error_log("Order query error: " . $e->getMessage());
    $_SESSION['error'] = "Gagal memuat data pesanan";
    $orders = []; // Set array kosong untuk menghindari error berikutnya
}
                    $orders = $stmt->fetchAll();

                    if (count($orders) > 0): ?>
                        <div class="space-y-4">
                            <?php foreach ($orders as $order): ?>
                                <div class="border rounded-lg p-4 <?= strtolower($order['status']) ?>">
                                    <div class="flex justify-between items-center mb-2">
                                        <h4 class="font-medium"><?= htmlspecialchars($order['package_name']) ?></h4>
                                        <span class="status-badge px-3 py-1 rounded-full text-xs">
                                            <?php switch($order['status']) {
                                                case 'Complete': 
                                                    echo '<i class="fas fa-check-circle text-green-500"></i> Approved';
                                                    break;
                                                case 'Canceled': 
                                                    echo '<i class="fas fa-times-circle text-red-500"></i> Rejected';
                                                    break;
                                                default: 
                                                    echo '<i class="fas fa-spinner fa-spin text-yellow-500"></i> Pending';
                                            } ?>
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-500">Order ID</p>
                                            <p>#<?= htmlspecialchars($order['id']) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Tanggal</p>
                                            <p><?= date('d M Y', strtotime($order['created_at'])) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Status</p>
                                            <p><?= htmlspecialchars($order['status']) ?></p>
                                        </div>
                                    </div>
                                    <?php if ($order['status'] == 'Complete'): ?>
                                        <div class="mt-3 p-3 bg-green-50 rounded-lg">
                                            <p class="text-green-700 text-sm">
                                                <i class="fas fa-check"></i> Paket telah disetujui dan sedang diproses
                                            </p>
                                        </div>
                                    <?php elseif ($order['status'] == 'Canceled'): ?>
                                        <div class="mt-3 p-3 bg-red-50 rounded-lg">
                                            <p class="text-red-700 text-sm">
                                                <i class="fas fa-info-circle"></i> Pesanan ditolak. Silakan hubungi admin.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col items-center justify-center mt-12">
                            <img src="Assets/Img/empty-order.png" alt="Empty orders" class="mb-4" width="150">
                            <p class="text-xs text-gray-600 mb-4">Kamu belum membuat pesanan</p>
                            <a href="index.php#pricing" class="bg-gray-900 text-white text-xs rounded px-4 py-2 hover:bg-gray-800 transition-colors">
                                Pesan Sekarang
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Form Tab -->
                <div class="tab-content hidden" id="form-content">
                    <div class="flex flex-col items-center justify-center mt-12">
                        <img
                            alt="Empty form"
                            class="mb-4"
                            height="150"
                            src="Assets/Img/empty-form-icon.jpg"
                            width="150"/>
                        <p class="text-xs text-gray-600 mb-4">
                            Belum ada form tersedia
                        </p>
                        <button
                            class="bg-gray-900 text-white text-xs rounded px-4 py-2 hover:bg-gray-800 transition-colors"
                            type="button">
                            Buat Form Baru
                        </button>
                    </div>
                </div>

                <!-- Pengaturan Tab -->
                <div class="tab-content hidden" id="pengaturan-content">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                                <input 
                                    type="text" 
                                    name="nama" 
                                    value="<?= htmlspecialchars($user['nama']) ?>" 
                                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input 
                                    type="email" 
                                    name="email" 
                                    value="<?= htmlspecialchars($user['email']) ?>" 
                                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Foto Profil</label>
                                <input 
                                    type="file" 
                                    name="avatar" 
                                    accept="image/jpeg,image/png"
                                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                
                                <?php if (!empty($user['avatar']) && $user['avatar'] != 'default-avatar.jpg'): ?>
                                    <div class="mt-2 flex items-center">
                                        <img src="Assets/Img/<?= htmlspecialchars($user['avatar']) ?>" 
                                             class="rounded-full w-16 h-16 object-cover mr-3">
                                        <span class="text-sm text-gray-600">Foto saat ini</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pt-2">
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Info Kontak -->
            <div class="space-y-2 text-xs md:text-sm">
                <h3 class="font-semibold text-white">Info Kontak</h3>
                <p>
                    <span class="font-semibold">Email</span><br/>
                    hbndesign@gmail.com
                </p>
                <p>
                    <span class="font-semibold">No Telepon / WhatsApp</span><br/>
                    +62 857-2476-5884
                </p>
                <p>
                    <span class="font-semibold">Alamat</span><br/>
                    Cimahpar, Bogor Utara, Kota Bogor, Jawa Barat.
                </p>
                <p>
                    <span class="font-semibold">Temukan Kami</span><br/>
                    <a aria-label="Instagram" class="inline-block text-gray-300 hover:text-white" href="https://www.instagram.com/hbn_design?utm_source=ig_web_button_share_sheet&igsh=ZDNlZDc0MzIxNw==">
                        <i class="fab fa-instagram text-lg"></i>
                    </a>
                </p>
            </div>
            
            <!-- Metode Pembayaran -->
            <div class="space-y-2 text-xs md:text-sm">
                <h3 class="font-semibold text-white mb-2">Metode Pembayaran</h3>
                <div class="grid grid-cols-2 gap-2 max-w-xs">
                    <img alt="BCA bank logo blue and white" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/87108c73-0dea-4f1b-be37-957bbfa4f3a6.jpg" width="80"/>
                    <img alt="BNI bank logo orange and white" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/e212d1f8-2613-4d2a-ccad-1c0be771b57d.jpg" width="80"/>
                    <img alt="Permata Bank logo with colorful diamond shape" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/8d3c78ff-b9c8-4d90-7525-5b4844eccfff.jpg" width="80"/>
                    <img alt="Gopay logo blue and white" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/7d6bf473-0e28-4605-f606-187b6c6d86f9.jpg" width="80"/>
                    <img alt="OVO logo purple and white" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/99324274-1fe3-436f-ea96-8ac8a23e23f0.jpg" width="80"/>
                    <img alt="DANA logo blue and white" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/3a43b817-145a-4377-53d0-88deda248f4c.jpg" width="80"/>
                    <img alt="Shopee Pay logo red and white" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/4d446012-d033-42af-e59c-88d7c89c3ac0.jpg" width="80"/>
                    <img alt="Alfamart logo red and blue with white background" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/cd5b332a-0656-4083-90c5-24d72ea79c92.jpg" width="80"/>
                    <img alt="Mandiri bank logo blue and yellow" class="rounded" height="40" src="https://storage.googleapis.com/a1aa/image/fbff4c38-d5d3-48c1-6b6f-4b3b0791ecad.jpg" width="80"/>
                </div>
            </div>
            
            <!-- Tentang -->
            <div class="space-y-2 text-xs md:text-sm">
                <h3 class="font-semibold text-white">Tentang</h3>
                <p class="font-semibold text-gray-300 text-xs md:text-sm">Official hbn_design</p>
                <p class="text-gray-400 text-xs md:text-sm leading-relaxed">
                    Saya Hiban Sakif, logo & brand identity designer dengan 5+ tahun pengalaman.
                    Telah membantu lebih dari 50 bisnis membangun identitas visual yang kuat dan
                    berkarakter sesuai dengan 'brand value' mereka.
                </p>
                <a class="inline-flex items-center text-gray-400 hover:text-white text-xs md:text-sm" href="index.html">
                    <i class="fas fa-angle-right mr-1"></i>
                    Selengkapnya
                </a>
            </div>
        </div>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 text-gray-500 text-xs text-center">
            Copyright © 2025 YN Merch. All Rights Reserved.
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Tab functionality
    document.querySelectorAll('.tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            const tab = button.dataset.tab;
            
            // Update active tab button
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-sky-500', 'border-sky-500');
                btn.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            });
            button.classList.add('text-sky-500', 'border-sky-500');
            button.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
            
            // Show selected tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.add('hidden');
                content.classList.remove('active');
            });
            document.getElementById(`${tab}-content`).classList.remove('hidden');
            document.getElementById(`${tab}-content`).classList.add('active');
        });
    });

    // Handle form submission with fetch API
    const profileForm = document.querySelector('#pengaturan-content form');
    if (profileForm) {
        profileForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.redirected) {
                    window.location.href = response.url;
                } else {
                    const result = await response.text();
                    console.error('Unexpected response:', result);
                    window.location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Terjadi kesalahan saat menyimpan perubahan');
                window.location.reload();
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
    </script>
</body>
</html>