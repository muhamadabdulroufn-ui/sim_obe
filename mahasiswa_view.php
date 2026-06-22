<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

if (!in_array($role, ['admin', 'mahasiswa'])) {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul ini khusus untuk Mahasiswa.</div>");
}

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$mhs_id = $_SESSION['user_id'];

$query = "SELECT m.kode_mk, m.nama_mk, c.kode_cpmk, c.deskripsi as desk_cpmk, n.nilai 
          FROM peserta_kelas p
          JOIN kelas_kuliah k ON p.kelas_id = k.id
          JOIN mata_kuliah m ON k.mk_id = m.id
          JOIN cpmk c ON c.mk_id = m.id
          LEFT JOIN nilai_cpmk n ON n.peserta_id = p.id AND n.cpmk_id = c.id
          WHERE p.mahasiswa_id = ? AND k.semester_id = ?";
          
$stmt = $pdo->prepare($query);
$stmt->execute([$mhs_id, $semester_aktif['id']]);
$nilai_mhs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capaian CPL - SIOBE</title>
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
        .search-container {
            position: relative;
            width: 320px;
        }
        .search-input {
            width: 100%;
            padding: 8px 16px 8px 36px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background-color: #ffffff;
            font-size: 13px;
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 13px;
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
        .panel-card {
            background-color: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            padding: 24px;
            margin-bottom: 24px;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .panel-title {
            font-size: 15px;
            font-weight: 600;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }
        .panel-title i {
            color: #718096;
        }
        .table-obe {
            width: 100%;
            border-collapse: collapse;
        }
        .table-obe th {
            background-color: #f7fafc;
            color: #718096;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 16px;
            border-bottom: 1px solid #edf2f7;
        }
        .table-obe td {
            padding: 14px 16px;
            border-bottom: 1px solid #edf2f7;
            font-size: 13px;
            color: #2d3748;
            vertical-align: middle;
        }
        .badge-cpmk-code {
            background-color: #edf2f7;
            color: #2d3748;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .cursor-help { cursor: help; }
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
        <a href="dashboard.php" class="nav-item-link">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <?php if (in_array($role, ['admin', 'kepala_departemen'])): ?>
            <a href="kadiper_mapping.php" class="nav-item-link">
                <i class="fa-solid fa-diagram-project"></i> Pemetaan CPL-PL
            </a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'dosen'])): ?>
            <a href="admin_kurikulum.php" class="nav-item-link">
                <i class="fa-solid fa-folder-tree"></i> Master Kurikulum
            </a>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
            <a href="admin_mk.php" class="nav-item-link">
                <i class="fa-solid fa-book"></i> Mata Kuliah
            </a>
            <a href="admin_crud.php" class="nav-item-link">
                <i class="fa-solid fa-pen-to-square"></i> Kelola RPS
            </a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'kepala_departemen'])): ?>
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
            <a href="mahasiswa_view.php" class="nav-item-link active">
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
        <h2 class="page-title">Kartu Hasil Evaluasi Capaian Pembelajaran</h2>
        <div class="navbar-actions">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" class="search-input" placeholder="Cari MK, CPL, CPMK...">
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-logout-top">
                <i class="fa-solid fa-power-off me-1"></i> Keluar
            </a>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <h3 class="panel-title">
                <i class="fa-solid fa-graduation-cap"></i> Transkrip Ketercapaian Kompetensi Kurikulum OBE
            </h3>
        </div>
        
        <div class="table-responsive">
            <table class="table-obe">
                <thead>
                    <tr>
                        <th width="15%">Kode MK</th>
                        <th width="25%">Mata Kuliah Teori/Praktikum</th>
                        <th width="12%" class="text-center">Indikator CPMK</th>
                        <th width="28%">Deskripsi Tolok Ukur Ketercapaian Kompetensi</th>
                        <th width="10%" class="text-center">Skor Capaian</th>
                        <th width="10%" class="text-center">Status Kelulusan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($nilai_mhs)): ?>
                        <tr><td colspan="6" class="text-center text-muted small">Belum ada entri pencapaian nilai rilis untuk semester berjalan ini.</td></tr>
                    <?php else: foreach($nilai_mhs as $n): $score = $n['nilai'] ?? 0; ?>
                        <tr>
                            <td><?= htmlspecialchars($n['kode_mk']) ?></td>
                            <td><strong><?= htmlspecialchars($n['nama_mk']) ?></strong></td>
                            <td class="text-center"><span class="badge-cpmk-code cursor-help" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($n['desk_cpmk']) ?>"><?= htmlspecialchars($n['kode_cpmk']) ?></span></td>
                            <td><small class="text-muted"><?= htmlspecialchars($n['desk_cpmk']) ?></small></td>
                            <td class="text-center text-primary fs-5 fw-bold"><strong><?= number_format($score, 2) ?></strong></td>
                            <td class="text-center">
                                <?php if($score >= 70.00): ?>
                                    <span class="badge bg-success px-3" style="border-radius: 12px; font-size: 11px;">TERPENUHI</span>
                                <?php else: ?>
                                    <span class="badge bg-danger px-3" style="border-radius: 12px; font-size: 11px;">BELUM MEMENUHI</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
</body>
</html>