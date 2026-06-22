<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

if (!in_array($role, ['admin', 'kepala_departemen', 'dosen'])) {
    die("<div class='alert alert-danger m-3'>Akses ilegal! Anda tidak memiliki otoritas menilai.</div>");
}

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$dosen_id = $_SESSION['user_id'];

if (isset($_POST['update_nilai_cpmk'])) {
    foreach ($_POST['skor'] as $peserta_id => $cpmks) {
        foreach ($cpmks as $cpmk_id => $nilai_angka) {
            $stmt = $pdo->prepare("INSERT INTO nilai_cpmk (peserta_id, cpmk_id, nilai) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE nilai = ?");
            $stmt->execute([$peserta_id, $cpmk_id, $nilai_angka, $nilai_angka]);
        }
    }
    $info = "Pembaruan komponen nilai OBE kelas aktif berhasil dipublikasikan!";
}

$query_kelas = "SELECT k.*, m.nama_mk, m.kode_mk 
                FROM kelas_kuliah k 
                JOIN mata_kuliah m ON k.mk_id = m.id 
                WHERE k.semester_id = :sem_aktif";

if ($role === 'dosen') {
    $query_kelas .= " AND k.dosen_id = :dosen_id";
}

$stmt_kls = $pdo->prepare($query_kelas);
$params_kls = ['sem_aktif' => $semester_aktif['id']];
if ($role === 'dosen') { $params_kls['dosen_id'] = $dosen_id; }
$stmt_kls->execute($params_kls);
$daftar_kelas = $stmt_kls->fetchAll();

$kelas_pilihan = $_GET['kelas_id'] ?? null;
$mahasiswa_list = [];
$cpmk_headers = [];

if ($kelas_pilihan) {
    $stmt_h = $pdo->prepare("SELECT c.* FROM cpmk c JOIN kelas_kuliah k ON c.mk_id = k.mk_id WHERE k.id = ?");
    $stmt_h->execute([$kelas_pilihan]);
    $cpmk_headers = $stmt_h->fetchAll();

    $stmt_m = $pdo->prepare("SELECT p.id as peserta_id, u.nama_lengkap, u.nomor_induk FROM peserta_kelas p JOIN users u ON p.mahasiswa_id = u.id WHERE p.kelas_id = ?");
    $stmt_m->execute([$kelas_pilihan]);
    $mahasiswa_list = $stmt_m->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Nilai OBE - SIOBE</title>
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
        .badge-mhs-nim {
            background-color: #ebf8ff;
            color: #2b6cb0;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .form-label-custom {
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }
        .form-select-custom {
            font-size: 13px;
            border-radius: 6px;
            border: 1px solid #cbd5e0;
            padding: 8px 12px;
        }
        .form-select-custom:focus {
            border-color: #3182ce;
            box-shadow: 0 0 0 1px #3182ce;
        }
        .input-skor-custom {
            width: 90px;
            font-weight: 700;
            font-size: 14px;
            padding: 6px;
            border-radius: 6px;
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
            <a href="dosen_nilai.php" class="nav-item-link active">
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
        <h2 class="page-title">Evaluasi & Input Nilai CPMK</h2>
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

    <?php if(isset($info)): ?> 
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 8px; font-size: 14px;">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($info) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div> 
    <?php endif; ?>

    <div class="panel-card">
        <div class="panel-header">
            <h3 class="panel-title">
                <i class="fa-solid fa-filter"></i> Filter Kelas Paralel Pengajaran
            </h3>
        </div>
        <form method="GET" action="dosen_nilai.php">
            <div class="mb-2">
                <label class="form-label form-label-custom">Mata Kuliah Ditawarkan Semester Ini:</label>
                <select name="kelas_id" class="form-select form-select-custom" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas Pengganti Anda --</option>
                    <?php foreach($daftar_kelas as $k): ?>
                        <option value="<?= htmlspecialchars($k['id']) ?>" <?= $kelas_pilihan == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['kode_mk']) ?> - <?= htmlspecialchars($k['nama_mk']) ?> (Kelas <?= htmlspecialchars($k['nama_kelas']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($kelas_pilihan && !empty($mahasiswa_list)): ?>
    <div class="panel-card">
        <div class="panel-header">
            <h3 class="panel-title">
                <i class="fa-solid fa-table"></i> Lembar Penilaian Capaian Belajar Mahasiswa
            </h3>
        </div>
        <form method="POST" action="dosen_nilai.php?kelas_id=<?= htmlspecialchars($kelas_pilihan) ?>">
            <div class="table-responsive">
                <table class="table-obe table-striped table-bordered text-center">
                    <thead>
                        <tr>
                            <th rowspan="2" width="15%" class="text-center">NIM</th>
                            <th rowspan="2" width="35%" class="text-start">Nama Lengkap Mahasiswa</th>
                            <th colspan="<?= count($cpmk_headers) ?>" class="text-center">Skor Unjuk Kerja Ketercapaian Kompetensi (0-100)</th>
                        </tr>
                        <tr>
                            <?php foreach($cpmk_headers as $ch): ?>
                                <th class="cursor-help text-primary text-decoration-underline" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($ch['deskripsi']) ?>"><?= htmlspecialchars($ch['kode_cpmk']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($mahasiswa_list as $m): ?>
                        <tr>
                            <td><span class="badge-mhs-nim"><strong><?= htmlspecialchars($m['nomor_induk']) ?></strong></span></td>
                            <td class="text-start"><strong><?= htmlspecialchars($m['nama_lengkap']) ?></strong></td>
                            <?php foreach($cpmk_headers as $ch): 
                                $stmt_v = $pdo->prepare("SELECT nilai FROM nilai_cpmk WHERE peserta_id = ? AND cpmk_id = ?");
                                $stmt_v->execute([$m['peserta_id'], $ch['id']]);
                                $nilai_saat_ini = $stmt_v->fetchColumn() ?: 0;
                            ?>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <input type="number" step="0.01" min="0" max="100" name="skor[<?= htmlspecialchars($m['peserta_id']) ?>][<?= htmlspecialchars($ch['id']) ?>]" value="<?= htmlspecialchars($nilai_saat_ini) ?>" class="form-control text-center text-success fw-bold input-skor-custom">
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <button type="submit" name="update_nilai_cpmk" class="btn btn-warning text-white px-4 shadow-sm font-weight-bold" style="border-radius: 6px; font-size: 14px;"><i class="fa-solid fa-floppy-disk me-2"></i>Simpan & Publikasi Nilai Kurikulum</button>
            </div>
        </form>
    </div>
    <?php elseif($kelas_pilihan): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center" style="border-radius: 8px;">
            <i class="fa-solid fa-triangle-exclamation me-3 fs-4"></i>
            <div class="small fw-bold">Belum ada mahasiswa terdaftar di kelas paralel yang dipilih untuk semester ini.</div>
        </div>
    <?php endif; ?>
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