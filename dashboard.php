<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) { 
    header("Location: index.php"); 
    exit; 
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$count_mhs = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'mahasiswa'")->fetchColumn();
$count_mk = $pdo->query("SELECT COUNT(*) FROM mata_kuliah")->fetchColumn();
$count_cpl = $pdo->query("SELECT COUNT(*) FROM cpl")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SIOBE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f7fc;
            font-family: 'Segoe UI', Arial, sans-serif;
            overflow-x: hidden;
        }
        .sidebar {
            width: 260px;
            height: 100vh;
            background-color: #ffffff;
            position: fixed;
            top: 0;
            left: 0;
            border-right: 1px solid #e3e6f0;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #f1f3f9;
        }
        .sidebar-logo-box {
            width: 40px;
            height: 40px;
            background-color: #e8f0fe;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #1a73e8;
            font-size: 20px;
        }
        .brand-text h1 {
            font-size: 16px;
            font-weight: 700;
            color: #112d62;
            margin: 0;
        }
        .brand-text p {
            font-size: 11px;
            color: #6c757d;
            margin: 0;
        }
        .sidebar-meta {
            padding: 12px 24px;
            font-size: 11px;
            color: #8c94a0;
            background-color: #fafbfc;
        }
        .menu-section-title {
            padding: 16px 24px 8px 24px;
            font-size: 11px;
            font-weight: 700;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 0 12px;
        }
        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #4a5568;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-item-link i {
            font-size: 16px;
            color: #a0aec0;
            width: 20px;
            text-align: center;
        }
        .nav-item-link:hover {
            background-color: #f7fafc;
            color: #1a73e8;
        }
        .nav-item-link.active {
            background-color: #e8f0fe;
            color: #1a73e8;
            font-weight: 600;
        }
        .nav-item-link.active i {
            color: #1a73e8;
        }
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 16px 24px;
            border-top: 1px solid #f1f3f9;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            background-color: #e2e8f0;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 600;
            color: #4a5568;
            font-size: 13px;
        }
        .user-info h2 {
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }
        .user-info p {
            font-size: 11px;
            color: #718096;
            margin: 0;
        }
        .main-content {
            margin-left: 260px;
            padding: 24px 40px;
            min-height: 100vh;
        }
        .top-navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #1a202c;
            margin: 0;
        }
        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .btn-logout-top {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 20px;
        }
        .welcome-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            margin-bottom: 24px;
        }
        .welcome-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #112d62;
            margin-bottom: 6px;
        }
        .welcome-card p {
            font-size: 14px;
            color: #718096;
            margin-bottom: 0;
        }
        .stat-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }
        .stat-info h4 {
            font-size: 12px;
            font-weight: 700;
            color: #a0aec0;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .stat-info p {
            font-size: 24px;
            font-weight: 700;
            color: #2d3748;
            margin: 0;
        }
        .stat-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
        }
        .icon-blue { background-color: #e8f0fe; color: #1a73e8; }
        .icon-green { background-color: #e6fffa; color: #319795; }
        .icon-purple { background-color: #faf5ff; color: #805ad5; }
        .menu-grid-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .menu-grid-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .menu-card-icon {
            font-size: 24px;
            color: #4a5568;
            margin-bottom: 16px;
        }
        .menu-grid-card h4 {
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }
        .menu-grid-card p {
            font-size: 12px;
            color: #718096;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .btn-action-link {
            font-size: 12px;
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-box">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <div class="brand-text">
            <h1>SIOBE</h1>
            <p>Outcome-Based Education</p>
        </div>
    </div>
    <div class="sidebar-meta">
        S1 Sistem Informasi · <?= htmlspecialchars($semester_aktif['tahun_akademik']) ?> <?= htmlspecialchars($semester_aktif['periode']) ?>
    </div>
    
    <div class="menu-section-title">Menu Utama</div>
    <div class="nav-menu">
        <a href="dashboard.php" class="nav-item-link active">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <?php if (in_array($role, ['admin', 'kepala_departemen'])): ?>
            <a href="kadiper_mapping.php" class="nav-item-link">
                <i class="fa-solid fa-diagram-project"></i> Pemetaan CPL-MK
            </a>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
            <a href="admin_kurikulum.php" class="nav-item-link">
                <i class="fa-solid fa-folder-tree"></i> Master Kurikulum
            </a>
            <a href="admin_mk.php" class="nav-item-link">
                <i class="fa-solid fa-book"></i> Mata Kuliah
            </a>
            <a href="admin_crud.php" class="nav-item-link">
                <i class="fa-solid fa-pen-to-square"></i> Kelola RPS
            </a>
            <a href="admin_kegiatan.php" class="nav-item-link">
                <i class="fa-solid fa-camera"></i> Upload Kegiatan
            </a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'kepala_departemen', 'dosen'])): ?>
            <a href="dosen_nilai.php" class="nav-item-link">
                <i class="fa-solid fa-star"></i> Input Nilai CPMK
            </a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'mahasiswa'])): ?>
            <a href="mahasiswa_view.php" class="nav-item-link">
                <i class="fa-solid fa-square-poll-vertical"></i> Capaian CPL
            </a>
        <?php endif; ?>
    </div>

    <div class="sidebar-footer">
        <div class="user-avatar"><?= $init_avatar ?></div>
        <div class="user-info">
            <h2><?= htmlspecialchars($nama_user) ?></h2>
            <p><?= strtoupper(str_replace('_', ' ', $role)) ?></p>
        </div>
    </div>
</div>

<div class="main-content">
    <div class="top-navbar">
        <h2 class="page-title">Dashboard Utama</h2>
        <div class="navbar-actions">
            <a href="logout.php" class="btn btn-outline-danger btn-logout-top">
                <i class="fa-solid fa-power-off me-1"></i> Keluar
            </a>
        </div>
    </div>

    <div class="welcome-card shadow-sm">
        <h3>Selamat Datang, <?= htmlspecialchars($nama_user) ?>!</h3>
        <p>Anda masuk sebagai <strong><?= strtoupper(str_replace('_', ' ', $role)) ?></strong> di SIOBE Portal prodi S1 Sistem Informasi UNUSA.</p>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="stat-card shadow-sm">
                <div class="stat-info">
                    <h4>Mata Kuliah Kurikulum</h4>
                    <p><?= $count_mk ?></p>
                </div>
                <div class="stat-icon-box icon-blue">
                    <i class="fa-solid fa-book-open"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card shadow-sm">
                <div class="stat-info">
                    <h4>Rumusan CPL Prodi</h4>
                    <p><?= $count_cpl ?></p>
                </div>
                <div class="stat-icon-box icon-purple">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card shadow-sm">
                <div class="stat-info">
                    <h4>Mahasiswa Aktif</h4>
                    <p><?= $count_mhs ?></p>
                </div>
                <div class="stat-icon-box icon-green">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>
            </div>
        </div>
    </div>

    <h3 class="font-size-16 font-weight-600 mb-3 text-dark" style="font-size: 16px; font-weight: 600;">Akses Pintasan Modul</h3>
    <div class="row g-4">
        <?php if ($role === 'admin'): ?>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-primary border-3">
                    <div class="menu-card-icon text-primary"><i class="fa-solid fa-cubes"></i></div>
                    <h4>Master Kurikulum & MK</h4>
                    <p>Kelola data pokok penawaran mata kuliah kurikulum, jumlah SKS, distribusi target semester perkuliahan, dan deskripsi kompetensi lulusan.</p>
                    <a href="admin_mk.php" class="btn btn-primary btn-action-link">Buka Modul MK</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-info border-3">
                    <div class="menu-card-icon text-info"><i class="fa-solid fa-sliders"></i></div>
                    <h4>Rumusan Baku CPL / CPMK</h4>
                    <p>Entri deskripsi butir capaian pembelajaran lulusan tingkat program studi beserta breakdown indikator CPMK terikat pada masing-masing mata kuliah.</p>
                    <a href="admin_kurikulum.php" class="btn btn-info text-white btn-action-link">Kelola Kurikulum</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-secondary border-3">
                    <div class="menu-card-icon text-secondary"><i class="fa-solid fa-address-book"></i></div>
                    <h4>Manajemen Pengguna & KRS</h4>
                    <p>Pendaftaran akun, pengaturan otentikasi dosen, data identitas mahasiswa, serta transaksi plotting pengambilan kelas mahasiswa di tiap semester berjalan.</p>
                    <div class="d-flex gap-2">
                        <a href="admin_mahasiswa.php" class="btn btn-outline-secondary btn-action-link p-1 py-1 px-2">Data Mhs</a>
                        <a href="admin_krs.php" class="btn btn-outline-dark btn-action-link p-1 py-1 px-2">Plot KRS</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'kepala_departemen'])): ?>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-success border-3">
                    <div class="menu-card-icon text-success"><i class="fa-solid fa-diagram-project"></i></div>
                    <h4>Matriks Pemetaan Kompetensi</h4>
                    <p>Fasilitas pemetaan relasi jalinan korelasi antara profil lulusan prodi (PL) menuju capaian pembelajaran lulusan (CPL) secara interaktif.</p>
                    <a href="kadiper_mapping.php" class="btn btn-success text-white btn-action-link">Buka Pemetaan</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-warning border-3">
                    <div class="menu-card-icon text-warning"><i class="fa-solid fa-camera"></i></div>
                    <h4>Upload & Presensi Kegiatan</h4>
                    <p>Gunakan kamera HP atau upload file PDF/Gambar secara langsung untuk mendokumentasikan bukti presensi rapat prodi.</p>
                    <a href="admin_kegiatan.php" class="btn btn-warning text-white btn-action-link">Buka Dokumentasi</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-dark border-3">
                    <div class="menu-card-icon text-dark"><i class="fa-solid fa-user-tie"></i></div>
                    <h4>Plotting Pengampu MK</h4>
                    <p>Penugasan serta penentuan dosen penanggung jawab ruang kelas paralel pengajaran mata kuliah pada periode perkuliahan yang sedang berjalan.</p>
                    <a href="kadiper_dosen.php" class="btn btn-dark btn-action-link">Plot Dosen Pengampu</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'kepala_departemen', 'dosen'])): ?>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-warning border-3">
                    <div class="menu-card-icon text-warning"><i class="fa-solid fa-marker"></i></div>
                    <h4>Penilaian CPMK Mahasiswa</h4>
                    <p>Pengisian lembar evaluasi nilai angka pencapaian per butir komponen indikator kompetensi OBE mahasiswa di kelas paralel yang Anda ampu.</p>
                    <a href="dosen_nilai.php" class="btn btn-warning text-white btn-action-link">Input Nilai Kelas</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'mahasiswa'])): ?>
            <div class="col-md-4">
                <div class="menu-grid-card shadow-sm border-top border-danger border-3">
                    <div class="menu-card-icon text-danger"><i class="fa-solid fa-chart-line"></i></div>
                    <h4>Evaluasi Raport Capaian Pembelajaran</h4>
                    <p>Transkrip hasil kemajuan belajar kelulusan mahasiswa berdasarkan ambang batas ketercapaian target instrumen luaran kurikulum OBE.</p>
                    <a href="mahasiswa_view.php" class="btn btn-danger btn-action-link">Buka Transkrip CPL</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>