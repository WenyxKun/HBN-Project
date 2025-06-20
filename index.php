<?php
session_start();

// Redirect jika belum login
if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php'); // Gunakan absolute path
    exit;
}

// Set header keamanan
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Cek session timeout
if (isset($_SESSION['user']['last_activity'])) {  // Ditambahkan tanda kurung tutup setelah 'last_activity'
    $timeout = 1800; // 30 menit
    if (time() - $_SESSION['user']['last_activity'] > $timeout) {
        session_unset();
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Update last activity
$_SESSION['user']['last_activity'] = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HBN Production</title>
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="Assets/css/style.css">
    <link rel="stylesheet" href="Assets/css/pricing.css">

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
        
        /* Tambahan untuk dropdown */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
        }
        
        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .dropdown-content a:hover {
            background-color: #f1f1f1;
            border-radius: 5px;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
</head>
<body>

 <!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow bg-light">
    <a class="navbar-brand ps-5" href="#">
        <img src="Assets\Img\Gambar1.jpg" width="50" height="50" alt="">
    </a>
    <button
        class="navbar-toggler"
        type="button"
        data-toggle="collapse"
        data-target="#navbarNav"
        aria-controls="navbarNav"
        aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto pe-3 gap-4">
            <li class="nav-item">
                <a class="nav-link position-relative px-2" href="#portfolio">Portofolio</a>
            </li>
            <li class="nav-item">
                <a class="nav-link position-relative px-2" href="#about">Tentang Kami</a>
            </li>
            <li class="nav-item">
                <a class="nav-link position-relative px-2" href="#contact">Kontak</a>
            </li>
            
            <?php if (isset($_SESSION['user'])): ?>
                <!-- Dropdown Profile untuk user yang sudah login -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (isset($_SESSION['user_avatar'])): ?>
                            <img src="Assets/Img/<?= htmlspecialchars($_SESSION['user_avatar']) ?>" class="rounded-circle" width="30" height="30" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
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
            <?php else: ?>
                <!-- Tampilkan tombol login/register jika belum login -->
                <li class="nav-item">
                    <a class="nav-link position-relative px-2" href="login.php">Masuk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative px-2" href="register.php">Daftar</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

        <style>
            /* Custom hover effect */
            .navbar-nav .nav-link {
                color: #333;
                transition: all 0.3s ease;
                padding-bottom: 5px;
            }

            .navbar-nav .nav-link:hover {
                color: #0dcaf0;
                /* Warna cyan-500 */
            }

            /* Garis bawah animasi */
            .navbar-nav .nav-link::after {
                content: '';
                position: absolute;
                width: 0;
                height: 2px;
                bottom: 0;
                left: 50%;
                transform: translateX(-50%);
                background-color: #0dcaf0;
                /* Warna cyan-500 */
                transition: width 0.3s ease, left 0.3s ease;
            }

            .navbar-nav .nav-link:hover::after {
                width: 80%;
                left: 50%;
            }

            /* Efek tambahan saat aktif */
            .navbar-nav .nav-link.active {
                color: #0dcaf0;
                font-weight: 500;
            }

            .navbar-nav .nav-link.active::after {
                width: 80%;
                left: 50%;
            }
        </style>

        <style>
            .jumbotron {
                background: linear-gradient(to right, #e8e7e7, #cecccc);
                background-size: cover;
                color: rgb(27, 54, 21);
                height: 40vh;
                /* Increased from 15vh to 40vh */
                min-height: 300px;
                /* Ensures minimum height on mobile */
                display: flex;
                flex-direction: column;
                justify-content: center;
                padding: 4rem 0;
                /* Added padding for top and bottom spacing */
                margin-bottom: 2rem;
                /* Space below the jumbotron */
            }

            /* Additional styling for better text presentation */
            .jumbotron h1 {
                font-size: 4.5rem;
                margin-bottom: 1.5rem;
            }

            .jumbotron p {
                font-size: 1.2rem;
                margin-bottom: 1rem;
                max-width: 800px;
                margin-left: auto;
                margin-right: auto;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .jumbotron {
                    height: auto;
                    padding: 3rem 1rem;
                }

                .jumbotron h1 {
                    font-size: 2rem;
                }

                .jumbotron p {
                    font-size: 1rem;
                }
            }
        </style>

        <!-- Hero Section -->
        <header class="jumbotron text-center">
            <div class="container">
                <h1 class="display-4">Selamat Datang di HBN Production</h1>
                <p class="lead">We believe that every brand has a story to tell.We specialize in
                    crafting distinctive logos for your brand.From idea to identity, we're here to
                    bring your ideas to life and help your business stand out in a crowded market.</p>
                <p class="lead"></p>
                <p class="lead mb-4">Ready to elevate your brand? Let's get started today.</p>
            </div>
        </header>

        <style>
            /* Custom Carousel Controls */
            .carousel-control-next,
            .carousel-control-prev {
                width: 40px;
                /* Lebar tombol */
                height: 40px;
                /* Tinggi tombol */
                background-color: rgba(0, 0, 0, 0.3);
                /* Warna background */
                border-radius: 50%;
                /* Bentuk bulat */
                top: 50%;
                /* Posisi vertikal tengah */
                transform: translateY(-50%);
                opacity: 1;
                /* Selalu terlihat */
                transition: all 0.3s ease;
            }

            .carousel-control-prev {
                left: 15px;
                /* Jarak dari kiri */
            }

            .carousel-control-next {
                right: 15px;
                /* Jarak dari kanan */
            }

            .carousel-control-next:hover,
            .carousel-control-prev:hover {
                background-color: rgba(0, 0, 0, 0.6);
                /* Warna saat hover */
            }

            .carousel-control-next-icon,
            .carousel-control-prev-icon {
                width: 20px;
                /* Ukuran icon */
                height: 20px;
                background-size: 100% 100%;
            }
        </style>

        <!-- Portfolio Section -->
        <section id="portfolio" class="container">
            <h2 class="text-center section-title mb-5 ">Portofolio</h2>
            <!-- Carousel Container -->
            <div id="portfolioCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <!-- Slide 1 (Active) -->
                    <div class="carousel-item active">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <img
                                        src="Assets/Img/IMG-20240516-WA0034.jpg"
                                        class="card-img-top"
                                        alt="Ixora Cosmetic">
                                    <div class="card-body">
                                        <h5 class="card-title">Ixora Cosmetic</h5>
                                        <p class="card-text">Logo untuk klinik kesehatan yang menawarkan layanan medis untuk kecantikan.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <img
                                        src="Assets/Img/IMG-20240516-WA0045.jpg"
                                        class="card-img-top"
                                        alt="Berikabar Warkop">
                                    <div class="card-body">
                                        <h5 class="card-title">Berikabar Warkop</h5>
                                        <p class="card-text">Logo untuk kafe yang menawarkan pengalaman kuliner yang santai dan elegan.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <img
                                        src="Assets/Img/IMG-20240516-WA0025.jpg"
                                        class="card-img-top"
                                        alt="Sobatgn Mountain Gear">
                                    <div class="card-body">
                                        <h5 class="card-title">Sobatgn Mountain Gear</h5>
                                        <p class="card-text">Logo untuk Toko perlatan lengkap untuk para pendaki hebat.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Slide 2 -->
                    <div class="carousel-item">
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <img src="Assets/Img/next-image1.jpg" class="card-img-top" alt="Project 4">
                                    <div class="card-body">
                                        <h5 class="card-title">Project 4</h5>
                                        <p class="card-text">Deskripsi project 4.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <img src="Assets/Img/next-image2.jpg" class="card-img-top" alt="Project 5">
                                    <div class="card-body">
                                        <h5 class="card-title">Project 5</h5>
                                        <p class="card-text">Deskripsi project 5.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <img src="Assets/Img/next-image3.jpg" class="card-img-top" alt="Project 6">
                                    <div class="card-body">
                                        <h5 class="card-title">Project 6</h5>
                                        <p class="card-text">Deskripsi project 6.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Controls -->
                <button
                    class="carousel-control-prev"
                    type="button"
                    data-bs-target="#portfolioCarousel"
                    data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden"></span>
                </button>
                <button
                    class="carousel-control-next"
                    type="button"
                    data-bs-target="#portfolioCarousel"
                    data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden"></span>
                </button>
            </div>
        </section>

        <!-- New Pricing Section -->
        <h2 class="section-title">Paket Layanan Kami</h2>

        <div class="package-container">

            <!-- Tombol Paket Starter -->
            <div class="package-btn starter" onclick="showPackageWizard('starter')">
                <div class="pricing-card">
                    <div class="pricing-header starter">
                        <h3 class="pricing-title">Starter Package</h3>
                        <div class="pricing-timeline">
                            <i class="far fa-clock"></i>
                            5-7 Hari</div>
                    </div>
                    <div class="pricing-features">
                        <ul>
                            <li>Primary Logo</li>
                            <li>Color Palette</li>
                            <li>Typography Pairing</li>
                            <li>3D Mockups</li>
                            <li>A3 Bradboard Guideline</li>

                        </ul>
                    </div>
                    <button
                        id="btnStarter"
                        href="transaction.html"
                        class="pricing-btn btn btn-outline-success"
                        data-paket="starter">Pilih Paket</button>
                </div>
                <!-- Tempat untuk Load Overlay Transaksi <div id="transactionOverlay"
                class="overlay"></div> -->

            </div>

            <!-- Tombol Paket Exclusive -->
            <div class="package-btn exclusive" onclick="showPackageWizard('exclusive')">
                <div class="pricing-card">
                    <div class="pricing-header">
                        <h3 class="pricing-title">Exclusive Plus</h3>
                        <div class="pricing-timeline">
                            <i class="far fa-clock"></i>
                            3-4 Minggu</div>
                    </div>
                    <div class="pricing-features">
                        <ul>
                            <li>Primary Logo</li>
                            <li>Logo Variation</li>
                            <li>Color Palette</li>
                            <li>Typography Pairing</li>
                            <li>3D Mockups</li>
                            <li>Brand Pattern/Element</li>
                            <li>Stationary Design</li>
                            <li>Social Media Kit</li>
                            <li>Brand Guidelines</li>
                        </ul>
                    </div>
                    <button
                        id="btnExclusive"
                        href="transaction.html"
                        class="pricing-btn btn btn-outline-primary"
                        data-paket="exclusive">Pilih Paket</button>
                </div>
            </div>

            <!-- Tombol Paket Premium -->
            <div class="package-btn premium" onclick="showPackageWizard('premium')">
                <div class="pricing-card">
                    <div class="pricing-header premium">
                        <h3 class="pricing-title">Premium Package</h3>
                        <div class="pricing-timeline">
                            <i class="far fa-clock"></i>
                            12 Minggu</div>
                    </div>
                    <div class="pricing-features">
                        <ul>
                            <li>Primary Logo</li>
                            <li>Logo Variation</li>
                            <li>Color Palette</li>
                            <li>Typography Pairing</li>
                            <li>3D Mockups</li>
                            <li>Brand Guidelines</li>
                            <li>Unlimited Revisions</li>
                            <li>Priority Support</li>
                            <li>Dedicated Designer</li>
                        </ul>
                    </div>

                    <button
                        id="btnPremium"
                        href="transaction.html"
                        class="pricing-btn btn btn-outline-warning"
                        data-paket="premium">Pilih Paket</button>
                </div>
            </div>
        </div>

        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Konfigurasi wizard untuk setiap paket
            const packageConfigs = {
                starter: {
                    title: "Starter Package",
                    steps: [
                        {
                            title: "Deskripsi Paket",
                            html: `
                            <p><strong>Order ID:</strong> 2FDFKAM2</p>
                            <p>Layanan dasar dengan timeline 5-7 hari kerja</p>
                            <div class="info-box" style="background:#f8f9fa;padding:15px;border-radius:5px;margin-top:10px">
                                <p>2 interval pembayaran</p>
                                <p>biaya mulai: <strong>Rp 99.500</strong></p>
                                <p>biaya akhir: <strong>Rp 99.500</strong></p>
                                <p>Total biaya: <strong>Rp 199.000</strong></p>
                            </div>
                        `,
                            icon: "info"
                        }, {
                            title: "Deskripsi & Upload Dokumen",
                            html: `
        <div class="form-group">
            <label>Deskripsi Proyek*</label>
            <textarea 
                id="projectDescription" 
                class="form-control" 
                rows="3" 
                placeholder="Jelaskan detail proyek Anda..."
                style="width:100%; margin-bottom:15px; padding:8px; border-radius:5px; border:1px solid #ddd"
                required
            ></textarea>
            <div id="descError" style="color:red; font-size:12px; display:none; margin-top:5px">
                <i class="fas fa-exclamation-circle"></i> Deskripsi wajib diisi
            </div>
        </div>
        
        <div class="form-group">
            <label>Upload Referensi (Maks 3 foto)</label>
            <div id="imagePreview" style="
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 15px;
                min-height: 90px;
                border: 2px dashed #eee;
                padding: 10px;
                border-radius: 5px;
            ">
                <div style="
                    width:100%;
                    text-align:center;
                    color:#6c757d;
                    padding:20px 0;
                ">
                    <i class="fas fa-images" style="font-size:24px"></i>
                    <p style="margin-top:5px">Belum ada foto</p>
                </div>
            </div>
            
            <input 
                type="file" 
                id="imageUpload" 
                accept="image/*" 
                multiple
                style="display:none"
                onchange="handleFileUpload(this)"
            >
            
            <button 
                type="button"
                onclick="document.getElementById('imageUpload').click()" 
                style="
                    width: 100%;
                    padding: 10px;
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: all 0.3s;
                "
                onmouseover="this.style.background='#e9ecef'" 
                onmouseout="this.style.background='#f8f9fa'"
            >
                <i class="fas fa-cloud-upload-alt"></i> Pilih Foto
            </button>
            <small style="display:block; margin-top:5px; color:#6c757d">
                Format: JPG/PNG (Maks 2MB per foto)
            </small>
        </div>
    `,
                            icon: "info",
                            didOpen: () => {
                                // Inisialisasi variabel untuk menyimpan file
                                window.uploadedFiles = [];

                                // Fungsi untuk menampilkan preview
                                window.handleFileUpload = function (input) {
                                    const preview = document.getElementById('imagePreview');
                                    const files = input.files;

                                    // Reset dan batasi maksimal 3 file
                                    if (files.length > 3) {
                                        Swal.showValidationMessage('Maksimal 3 foto yang diupload');
                                        return;
                                    }

                                    // Validasi ukuran file
                                    for (let file of files) {
                                        if (file.size > 2 * 1024 * 1024) {
                                            Swal.showValidationMessage(`File ${file.name} melebihi 2MB`);
                                            return;
                                        }
                                    }

                                    // Simpan file
                                    window.uploadedFiles = Array.from(files);

                                    // Tampilkan preview
                                    preview.innerHTML = '';

                                    if (window.uploadedFiles.length === 0) {
                                        preview.innerHTML = `
                    <div style="
                        width:100%;
                        text-align:center;
                        color:#6c757d;
                        padding:20px 0;
                    ">
                        <i class="fas fa-images" style="font-size:24px"></i>
                        <p style="margin-top:5px">Belum ada foto</p>
                    </div>
                `;
                                    } else {
                                        window
                                            .uploadedFiles
                                            .forEach((file, index) => {
                                                const reader = new FileReader();
                                                reader.onload = function (e) {
                                                    const previewItem = document.createElement('div');
                                                    previewItem.style.position = 'relative';
                                                    previewItem.style.width = '80px';

                                                    // Gambar preview
                                                    const img = document.createElement('img');
                                                    img.src = e.target.result;
                                                    img.style.width = '80px';
                                                    img.style.height = '80px';
                                                    img.style.objectFit = 'cover';
                                                    img.style.borderRadius = '5px';
                                                    img.style.border = '1px solid #ddd';

                                                    // Tombol hapus
                                                    const deleteBtn = document.createElement('button');
                                                    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                                                    deleteBtn.style.position = 'absolute';
                                                    deleteBtn.style.top = '-5px';
                                                    deleteBtn.style.right = '-5px';
                                                    deleteBtn.style.background = 'red';
                                                    deleteBtn.style.color = 'white';
                                                    deleteBtn.style.border = 'none';
                                                    deleteBtn.style.borderRadius = '50%';
                                                    deleteBtn.style.width = '20px';
                                                    deleteBtn.style.height = '20px';
                                                    deleteBtn.style.cursor = 'pointer';
                                                    deleteBtn.style.display = 'flex';
                                                    deleteBtn.style.alignItems = 'center';
                                                    deleteBtn.style.justifyContent = 'center';
                                                    deleteBtn.style.fontSize = '10px';
                                                    deleteBtn.onclick = (event) => {
                                                        event.stopPropagation();
                                                        window
                                                            .uploadedFiles
                                                            .splice(index, 1);
                                                        handleFileUpload({files: window.uploadedFiles});
                                                    };

                                                    previewItem.appendChild(img);
                                                    previewItem.appendChild(deleteBtn);
                                                    preview.appendChild(previewItem);
                                                };
                                                reader.readAsDataURL(file);
                                            });
                                    }
                                };
                            },
                            preConfirm: () => {
                                const desc = document
                                    .getElementById('projectDescription')
                                    .value;
                                const descError = document.getElementById('descError');

                                // Validasi deskripsi
                                if (!desc || desc.trim() === '') {
                                    descError.style.display = 'block';
                                    Swal.showValidationMessage('Deskripsi proyek harus diisi');
                                    return false;
                                } else {
                                    descError.style.display = 'none';
                                }

                                return {
                                    description: desc,
                                    images: window.uploadedFiles || []
                                };
                            }
                        }, 
                        {
                            title: "Konfirmasi Pembayaran",
                            html: `
                    <div class="payment-confirmation">
                        <p>Anda akan memesan <strong>Starter Package</strong></p>
                         <p>Termin 1: <strong>Rp 99.500</strong></p>
                        <p>Termin 2: <strong>Rp 99.500</strong></p>
                        <p>Total: <strong>Rp 199.000</strong></p>
                        
                        <div class="bank-info">
                            <h4>Transfer ke Rekening Bank:</h4>
                            <p><strong>Bank Mandiri</strong></p>
                            <p>No. Rek: 123-456-7890</p>
                            <p>a.n. HBN-Design</p>
                        </div>
                        
                        <div class="form-group">
                            <label>Email untuk konfirmasi*</label>
                            <input type="email" id="confirmEmail" placeholder="your@email.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload Bukti Transfer*</label>
                            <input type="file" id="paymentProof" accept="image/*,.pdf" required>
                            <small>Format: JPG/PNG/PDF (Maks 2MB)</small>
                            <div id="proofPreview" style="margin-top:10px;"></div>
                        </div>
                    </div>
                `,
                            icon: "warning",
                             // TAMBAHKAN INI:
                            confirmButtonText: "Konfirmasi Pembelian",
                            confirmButtonColor: "#4CAF50",
                            buttonsStyling: true,
                            showCancelButton: true,
                            cancelButtonText: "< Kembali",
                            didOpen: () => {
                                const proofInput = document.getElementById('paymentProof');
                                proofInput.addEventListener('change', function (e) {
                                    const file = e
                                        .target
                                        .files[0];
                                    if (!file) 
                                        return;
                                    
                                    // Validasi ukuran file
                                    if (file.size > 2 * 1024 * 1024) {
                                        Swal.showValidationMessage('Ukuran file maksimal 2MB');
                                        return;
                                    }

                                    // Tampilkan preview
                                    const preview = document.getElementById('proofPreview');
                                    if (file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = function (e) {
                                            preview.innerHTML = `
                                    <img src="${e.target.result}" style="max-width:200px; max-height:200px; border:1px solid #ddd;">
                                `;
                                        };
                                        reader.readAsDataURL(file);
                                    } else {
                                        preview.innerHTML = `<p>File: ${file.name}</p>`;
                                    }
                                });
                            },
                            preConfirm: () => {
                                const email = document
                                    .getElementById('confirmEmail')
                                    .value;
                                const proof = document
                                    .getElementById('paymentProof')
                                    .files[0];

                                // Validasi email
                                if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                                    Swal.showValidationMessage('Email tidak valid');
                                    return false;
                                }

                                // Validasi bukti transfer
                                if (!proof) {
                                    Swal.showValidationMessage('Bukti transfer wajib diupload');
                                    return false;
                                }

                                return {email, payment_proof: proof};

                         
                            }
                        }
                    ]
                },


                
                exclusive: {
                    title: "Exclusive Plus",
                    steps: [
                        {
                            title: "Deskripsi Paket",
                            html: `
                            <p><strong>Order ID:</strong> EX-2FDFKAM2</p>
                            <p>Layanan premium dengan timeline 3-4 minggu</p>
                            <div class="info-box" style="background:#f8f9fa;padding:15px;border-radius:5px;margin-top:10px">
                                <p>2 interval pembayaran</p>
                                <p>biaya mulai: <strong>Rp 249.500</strong></p>
                                <p>biaya akhir: <strong>Rp 249.500</strong></p>
                                <p>Total biaya: <strong>Rp 499.000</strong></p>
                                <p>Termasuk revisi 3x</p>
                            </div>
                        `,
                            icon: "info"
                        }, {
                            title: "Deskripsi & Upload Dokumen",
                            html: `
        <div class="form-group">
            <label>Deskripsi Proyek*</label>
            <textarea 
                id="projectDescription" 
                class="form-control" 
                rows="3" 
                placeholder="Jelaskan detail proyek Anda..."
                style="width:100%; margin-bottom:15px; padding:8px; border-radius:5px; border:1px solid #ddd"
                required
            ></textarea>
            <div id="descError" style="color:red; font-size:12px; display:none; margin-top:5px">
                <i class="fas fa-exclamation-circle"></i> Deskripsi wajib diisi
            </div>
        </div>
        
        <div class="form-group">
            <label>Upload Referensi (Maks 3 foto)</label>
            <div id="imagePreview" style="
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 15px;
                min-height: 90px;
                border: 2px dashed #eee;
                padding: 10px;
                border-radius: 5px;
            ">
                <div style="
                    width:100%;
                    text-align:center;
                    color:#6c757d;
                    padding:20px 0;
                ">
                    <i class="fas fa-images" style="font-size:24px"></i>
                    <p style="margin-top:5px">Belum ada foto</p>
                </div>
            </div>
            
            <input 
                type="file" 
                id="imageUpload" 
                accept="image/*" 
                multiple
                style="display:none"
                onchange="handleFileUpload(this)"
            >
            
            <button 
                type="button"
                onclick="document.getElementById('imageUpload').click()" 
                style="
                    width: 100%;
                    padding: 10px;
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: all 0.3s;
                "
                onmouseover="this.style.background='#e9ecef'" 
                onmouseout="this.style.background='#f8f9fa'"
            >
                <i class="fas fa-cloud-upload-alt"></i> Pilih Foto
            </button>
            <small style="display:block; margin-top:5px; color:#6c757d">
                Format: JPG/PNG (Maks 2MB per foto)
            </small>
        </div>
    `,
                            icon: "info",
                            didOpen: () => {
                                // Inisialisasi variabel untuk menyimpan file
                                window.uploadedFiles = [];

                                // Fungsi untuk menampilkan preview
                                window.handleFileUpload = function (input) {
                                    const preview = document.getElementById('imagePreview');
                                    const files = input.files;

                                    // Reset dan batasi maksimal 3 file
                                    if (files.length > 3) {
                                        Swal.showValidationMessage('Maksimal 3 foto yang diupload');
                                        return;
                                    }

                                    // Validasi ukuran file
                                    for (let file of files) {
                                        if (file.size > 2 * 1024 * 1024) {
                                            Swal.showValidationMessage(`File ${file.name} melebihi 2MB`);
                                            return;
                                        }
                                    }

                                    // Simpan file
                                    window.uploadedFiles = Array.from(files);

                                    // Tampilkan preview
                                    preview.innerHTML = '';

                                    if (window.uploadedFiles.length === 0) {
                                        preview.innerHTML = `
                    <div style="
                        width:100%;
                        text-align:center;
                        color:#6c757d;
                        padding:20px 0;
                    ">
                        <i class="fas fa-images" style="font-size:24px"></i>
                        <p style="margin-top:5px">Belum ada foto</p>
                    </div>
                `;
                                    } else {
                                        window
                                            .uploadedFiles
                                            .forEach((file, index) => {
                                                const reader = new FileReader();
                                                reader.onload = function (e) {
                                                    const previewItem = document.createElement('div');
                                                    previewItem.style.position = 'relative';
                                                    previewItem.style.width = '80px';

                                                    // Gambar preview
                                                    const img = document.createElement('img');
                                                    img.src = e.target.result;
                                                    img.style.width = '80px';
                                                    img.style.height = '80px';
                                                    img.style.objectFit = 'cover';
                                                    img.style.borderRadius = '5px';
                                                    img.style.border = '1px solid #ddd';

                                                    // Tombol hapus
                                                    const deleteBtn = document.createElement('button');
                                                    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                                                    deleteBtn.style.position = 'absolute';
                                                    deleteBtn.style.top = '-5px';
                                                    deleteBtn.style.right = '-5px';
                                                    deleteBtn.style.background = 'red';
                                                    deleteBtn.style.color = 'white';
                                                    deleteBtn.style.border = 'none';
                                                    deleteBtn.style.borderRadius = '50%';
                                                    deleteBtn.style.width = '20px';
                                                    deleteBtn.style.height = '20px';
                                                    deleteBtn.style.cursor = 'pointer';
                                                    deleteBtn.style.display = 'flex';
                                                    deleteBtn.style.alignItems = 'center';
                                                    deleteBtn.style.justifyContent = 'center';
                                                    deleteBtn.style.fontSize = '10px';
                                                    deleteBtn.onclick = (event) => {
                                                        event.stopPropagation();
                                                        window
                                                            .uploadedFiles
                                                            .splice(index, 1);
                                                        handleFileUpload({files: window.uploadedFiles});
                                                    };

                                                    previewItem.appendChild(img);
                                                    previewItem.appendChild(deleteBtn);
                                                    preview.appendChild(previewItem);
                                                };
                                                reader.readAsDataURL(file);
                                            });
                                    }
                                };
                            },
                            preConfirm: () => {
                                const desc = document
                                    .getElementById('projectDescription')
                                    .value;
                                const descError = document.getElementById('descError');

                                // Validasi deskripsi
                                if (!desc || desc.trim() === '') {
                                    descError.style.display = 'block';
                                    Swal.showValidationMessage('Deskripsi proyek harus diisi');
                                    return false;
                                } else {
                                    descError.style.display = 'none';
                                }

                                return {
                                    description: desc,
                                    images: window.uploadedFiles || []
                                };
                            }
                        },  {
                            title: "Konfirmasi Pembayaran",
                            html: `
                    <div class="payment-confirmation">
                        <p>Anda akan memesan <strong>Starter Package</strong></p>
                        <p>Termin 1: <strong>Rp 249.500</strong></p>
                        <p>Termin 2: <strong>Rp 249.500</strong></p>
                        <p>Total: <strong>Rp 490.000</strong></p>
                        
                        <div class="bank-info">
                            <h4>Transfer ke Rekening Bank:</h4>
                            <p><strong>Bank Mandiri</strong></p>
                            <p>No. Rek: 123-456-7890</p>
                            <p>a.n. HBN-Design</p>
                        </div>
                        
                        <div class="form-group">
                            <label>Email untuk konfirmasi*</label>
                            <input type="email" id="confirmEmail" placeholder="your@email.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload Bukti Transfer*</label>
                            <input type="file" id="paymentProof" accept="image/*,.pdf" required>
                            <small>Format: JPG/PNG/PDF (Maks 2MB)</small>
                            <div id="proofPreview" style="margin-top:10px;"></div>
                        </div>
                    </div>
                `,
                            icon: "warning",
                             // TAMBAHKAN INI:
                            confirmButtonText: "Konfirmasi Pembelian",
                            confirmButtonColor: "#4CAF50",
                            buttonsStyling: true,
                            showCancelButton: true,
                            cancelButtonText: "< Kembali",
                            didOpen: () => {
                                const proofInput = document.getElementById('paymentProof');
                                proofInput.addEventListener('change', function (e) {
                                    const file = e
                                        .target
                                        .files[0];
                                    if (!file) 
                                        return;
                                    
                                    // Validasi ukuran file
                                    if (file.size > 2 * 1024 * 1024) {
                                        Swal.showValidationMessage('Ukuran file maksimal 2MB');
                                        return;
                                    }

                                    // Tampilkan preview
                                    const preview = document.getElementById('proofPreview');
                                    if (file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = function (e) {
                                            preview.innerHTML = `
                                    <img src="${e.target.result}" style="max-width:200px; max-height:200px; border:1px solid #ddd;">
                                `;
                                        };
                                        reader.readAsDataURL(file);
                                    } else {
                                        preview.innerHTML = `<p>File: ${file.name}</p>`;
                                    }
                                });
                            },
                            preConfirm: () => {
                                const email = document
                                    .getElementById('confirmEmail')
                                    .value;
                                const proof = document
                                    .getElementById('paymentProof')
                                    .files[0];

                                // Validasi email
                                if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                                    Swal.showValidationMessage('Email tidak valid');
                                    return false;
                                }

                                // Validasi bukti transfer
                                if (!proof) {
                                    Swal.showValidationMessage('Bukti transfer wajib diupload');
                                    return false;
                                }

                                return {email, payment_proof: proof};

                         
                            }
                        }
                    ]
                },
                premium: {
                    title: "Premium Package",
                    steps: [
                        {
                            title: "Deskripsi Paket",
                            html: `
                            <p><strong>Order ID:</strong> PM-2FDFKAM2</p>
                            <p>Layanan lengkap dengan timeline 12 minggu</p>
                            <div class="info-box" style="background:#f8f9fa;padding:15px;border-radius:5px;margin-top:10px">
                                    <p>2 interval pembayaran</p>
                                <p>biaya mulai: <strong>Rp 499.500</strong></p>
                                <p>biaya akhir: <strong>Rp 499.500</strong></p>
                                <p>Total biaya: <strong>Rp 999.000</strong></p>
                                <p>Termasuk unlimited revisi</p>
                            </div>
                        `,
                            icon: "info"
                        }, {
                            title: "Deskripsi & Upload Dokumen",
                            html: `
        <div class="form-group">
            <label>Deskripsi Proyek*</label>
            <textarea 
                id="projectDescription" 
                class="form-control" 
                rows="3" 
                placeholder="Jelaskan detail proyek Anda..."
                style="width:100%; margin-bottom:15px; padding:8px; border-radius:5px; border:1px solid #ddd"
                required
            ></textarea>
            <div id="descError" style="color:red; font-size:12px; display:none; margin-top:5px">
                <i class="fas fa-exclamation-circle"></i> Deskripsi wajib diisi
            </div>
        </div>
        
        <div class="form-group">
            <label>Upload Referensi (Maks 3 foto)</label>
            <div id="imagePreview" style="
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 15px;
                min-height: 90px;
                border: 2px dashed #eee;
                padding: 10px;
                border-radius: 5px;
            ">
                <div style="
                    width:100%;
                    text-align:center;
                    color:#6c757d;
                    padding:20px 0;
                ">
                    <i class="fas fa-images" style="font-size:24px"></i>
                    <p style="margin-top:5px">Belum ada foto</p>
                </div>
            </div>
            
            <input 
                type="file" 
                id="imageUpload" 
                accept="image/*" 
                multiple
                style="display:none"
                onchange="handleFileUpload(this)"
            >
            
            <button 
                type="button"
                onclick="document.getElementById('imageUpload').click()" 
                style="
                    width: 100%;
                    padding: 10px;
                    background: #f8f9fa;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: all 0.3s;
                "
                onmouseover="this.style.background='#e9ecef'" 
                onmouseout="this.style.background='#f8f9fa'"
            >
                <i class="fas fa-cloud-upload-alt"></i> Pilih Foto
            </button>
            <small style="display:block; margin-top:5px; color:#6c757d">
                Format: JPG/PNG (Maks 2MB per foto)
            </small>
        </div>
    `,
                            icon: "info",
                            didOpen: () => {
                                // Inisialisasi variabel untuk menyimpan file
                                window.uploadedFiles = [];

                                // Fungsi untuk menampilkan preview
                                window.handleFileUpload = function (input) {
                                    const preview = document.getElementById('imagePreview');
                                    const files = input.files;

                                    // Reset dan batasi maksimal 3 file
                                    if (files.length > 3) {
                                        Swal.showValidationMessage('Maksimal 3 foto yang diupload');
                                        return;
                                    }

                                    // Validasi ukuran file
                                    for (let file of files) {
                                        if (file.size > 2 * 1024 * 1024) {
                                            Swal.showValidationMessage(`File ${file.name} melebihi 2MB`);
                                            return;
                                        }
                                    }

                                    // Simpan file
                                    window.uploadedFiles = Array.from(files);

                                    // Tampilkan preview
                                    preview.innerHTML = '';

                                    if (window.uploadedFiles.length === 0) {
                                        preview.innerHTML = `
                    <div style="
                        width:100%;
                        text-align:center;
                        color:#6c757d;
                        padding:20px 0;
                    ">
                        <i class="fas fa-images" style="font-size:24px"></i>
                        <p style="margin-top:5px">Belum ada foto</p>
                    </div>
                `;
                                    } else {
                                        window
                                            .uploadedFiles
                                            .forEach((file, index) => {
                                                const reader = new FileReader();
                                                reader.onload = function (e) {
                                                    const previewItem = document.createElement('div');
                                                    previewItem.style.position = 'relative';
                                                    previewItem.style.width = '80px';

                                                    // Gambar preview
                                                    const img = document.createElement('img');
                                                    img.src = e.target.result;
                                                    img.style.width = '80px';
                                                    img.style.height = '80px';
                                                    img.style.objectFit = 'cover';
                                                    img.style.borderRadius = '5px';
                                                    img.style.border = '1px solid #ddd';

                                                    // Tombol hapus
                                                    const deleteBtn = document.createElement('button');
                                                    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                                                    deleteBtn.style.position = 'absolute';
                                                    deleteBtn.style.top = '-5px';
                                                    deleteBtn.style.right = '-5px';
                                                    deleteBtn.style.background = 'red';
                                                    deleteBtn.style.color = 'white';
                                                    deleteBtn.style.border = 'none';
                                                    deleteBtn.style.borderRadius = '50%';
                                                    deleteBtn.style.width = '20px';
                                                    deleteBtn.style.height = '20px';
                                                    deleteBtn.style.cursor = 'pointer';
                                                    deleteBtn.style.display = 'flex';
                                                    deleteBtn.style.alignItems = 'center';
                                                    deleteBtn.style.justifyContent = 'center';
                                                    deleteBtn.style.fontSize = '10px';
                                                    deleteBtn.onclick = (event) => {
                                                        event.stopPropagation();
                                                        window
                                                            .uploadedFiles
                                                            .splice(index, 1);
                                                        handleFileUpload({files: window.uploadedFiles});
                                                    };

                                                    previewItem.appendChild(img);
                                                    previewItem.appendChild(deleteBtn);
                                                    preview.appendChild(previewItem);
                                                };
                                                reader.readAsDataURL(file);
                                            });
                                    }
                                };
                            },
                            preConfirm: () => {
                                const desc = document
                                    .getElementById('projectDescription')
                                    .value;
                                const descError = document.getElementById('descError');

                                // Validasi deskripsi
                                if (!desc || desc.trim() === '') {
                                    descError.style.display = 'block';
                                    Swal.showValidationMessage('Deskripsi proyek harus diisi');
                                    return false;
                                } else {
                                    descError.style.display = 'none';
                                }

                                return {
                                    description: desc,
                                    images: window.uploadedFiles || []
                                };
                            }
                        },  {
                            title: "Konfirmasi Pembayaran",
                            html: `
                    <div class="payment-confirmation">
                        <p>Anda akan memesan <strong>Starter Package</strong></p>
                         <p>Termin 1: <strong>Rp 499.500</strong></p>
                        <p>Termin 2: <strong>Rp 499.500</strong></p>
                        <p>Total: <strong>Rp 500.000</strong></p>
                        
                        <div class="bank-info">
                            <h4>Transfer ke Rekening Bank:</h4>
                            <p><strong>Bank Mandiri</strong></p>
                            <p>No. Rek: 123-456-7890</p>
                            <p>a.n. HBN-Design</p>
                        </div>
                        
                        <div class="form-group">
                            <label>Email untuk konfirmasi*</label>
                            <input type="email" id="confirmEmail" placeholder="your@email.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Upload Bukti Transfer*</label>
                            <input type="file" id="paymentProof" accept="image/*,.pdf" required>
                            <small>Format: JPG/PNG/PDF (Maks 2MB)</small>
                            <div id="proofPreview" style="margin-top:10px;"></div>
                        </div>
                    </div>
                `,
                            icon: "warning",
                             // TAMBAHKAN INI:
                            confirmButtonText: "Konfirmasi Pembelian",
                            confirmButtonColor: "#4CAF50",
                            buttonsStyling: true,
                            showCancelButton: true,
                            cancelButtonText: "< Kembali",
                            didOpen: () => {
                                const proofInput = document.getElementById('paymentProof');
                                proofInput.addEventListener('change', function (e) {
                                    const file = e
                                        .target
                                        .files[0];
                                    if (!file) 
                                        return;
                                    
                                    // Validasi ukuran file
                                    if (file.size > 2 * 1024 * 1024) {
                                        Swal.showValidationMessage('Ukuran file maksimal 2MB');
                                        return;
                                    }

                                    // Tampilkan preview
                                    const preview = document.getElementById('proofPreview');
                                    if (file.type.startsWith('image/')) {
                                        const reader = new FileReader();
                                        reader.onload = function (e) {
                                            preview.innerHTML = `
                                    <img src="${e.target.result}" style="max-width:200px; max-height:200px; border:1px solid #ddd;">
                                `;
                                        };
                                        reader.readAsDataURL(file);
                                    } else {
                                        preview.innerHTML = `<p>File: ${file.name}</p>`;
                                    }
                                });
                            },
                            preConfirm: () => {
                                const email = document
                                    .getElementById('confirmEmail')
                                    .value;
                                const proof = document
                                    .getElementById('paymentProof')
                                    .files[0];

                                // Validasi email
                                if (!email || !/^\S+@\S+\.\S+$/.test(email)) {
                                    Swal.showValidationMessage('Email tidak valid');
                                    return false;
                                }

                                // Validasi bukti transfer
                                if (!proof) {
                                    Swal.showValidationMessage('Bukti transfer wajib diupload');
                                    return false;
                                }

                                return {email, payment_proof: proof};

                         
                            }
                        }
                    ]
                }
            };

            // Fungsi untuk menampilkan wizard berdasarkan paket
            async function showPackageWizard(packageType) {
                const config = packageConfigs[packageType];
                const steps = ["1", "2", "3"];

                const Queue = Swal.mixin({
                    progressSteps: steps, allowOutsideClick: false, // Blok klik di luar modal
                    confirmButtonText: 'Selanjutnya >',
                    showCancelButton: true,
                    cancelButtonText: '< Kembali',
                    showClass: {
                        backdrop: 'swal2-noanimation'
                    },
                    hideClass: {
                        backdrop: 'swal2-noanimation'
                    },
                    scrollbarPadding: false,
                    customClass: {
                        container: 'custom-swal-container'
                    }
                });

                // Step 1
                let result = await Queue.fire({
                    title: config.title,
                    html: config
                        .steps[0]
                        .html,
                    icon: config
                        .steps[0]
                        .icon,
                    currentProgressStep: 0
                });

                if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) 
                    return;
                
                // Step 2
                result = await Queue.fire({
                    title: config.title,
                    html: config
                        .steps[1]
                        .html,
                    icon: config
                        .steps[1]
                        .icon,
                    currentProgressStep: 1
                });

                if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                    await showPackageWizard(packageType); // Kembali ke step 1
                    return;
                }

                // Step 3
                result = await Queue.fire({
                    title: config.title,
                    html: config
                        .steps[2]
                        .html,
                    icon: config
                        .steps[2]
                        .icon,
                    confirmButtonText: config
                        .steps[2]
                        .confirmButtonText,
                    currentProgressStep: 2
                });

                if (result.isConfirmed) {
                    Swal.fire(
                        'Berhasil!',
                        `Pemesanan ${config.title} telah dikonfirmasi`,
                        'success'
                    );
                }
            }
        </script>

        <!-- About Section -->
        <section id="about" class="bg-light py-5">
            <div class="container">
                <h2 class="text-center mb-5">Tentang Kami</h2>
                <p>Hi, I'm Hiban Sakif, the creative mind behind HBN Studio Kreatif. With a
                    passion for turning ideas into powerful logos and memorable brand identities,
                    I've dedicated myself to helping businesses of all sizes define who they are and
                    how they're seen by the world. Let's collaborate and make your vision come to
                    life with designs that truly stand out.</p>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="container py-5">
            <h2 class="text-center mb-5">Kontak Kami</h2>
            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <form action="save_contact.php" method="post">
                        <div class="form-group">
                            <label for="name">Nama:</label>
                            <input
                                type="text"
                                class="form-control"
                                id="name"
                                name="name"
                                required="required">
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                required="required">
                        </div>
                        <div class="form-group">
                            <label for="message">Pesan:</label>
                            <textarea
                                class="form-control"
                                id="message"
                                name="message"
                                rows="5"
                                required="required"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Kirim</button>
                    </form>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-dark text-light text-center py-3">
            <p>&copy; 2024 HBN Production. Semua hak cipta dilindungi.</p>
        </footer>

        <!-- Scripts -->

   <!-- Scripts -->
    <script>
    // Cek status login dari sessionStorage
    document.addEventListener('DOMContentLoaded', function() {
        const authData = sessionStorage.getItem('auth');
        
        if (!authData && <?= isset($_SESSION['user']) ? 'false' : 'true' ?>) {
            // Jika tidak ada data auth di client side dan tidak ada session di server side
            window.location.href = 'login.php';
        }
    });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="Assets/js/pricing.js"></script>
    <script src="Assets/js/script.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>