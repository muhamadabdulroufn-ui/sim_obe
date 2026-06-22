<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

if (!in_array($role, ['admin', 'kepala_departemen'])) {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul ini khusus untuk Kepala Departemen atau Admin.</div>");
}

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$message = '';
$edit_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_plotting'])) {
        $type = $_POST['action_plotting'];
        $mk_id = intval($_POST['mk_id']);
        $dosen_id = intval($_POST['dosen_id']);
        $nama_kelas = strtoupper(trim($_POST['nama_kelas']));
        $semester_id = intval($semester_aktif['id']);

        if ($type === 'create') {
            try {
                $stmt = $pdo->prepare("INSERT INTO kelas_kuliah (mk_id, dosen_id, semester_id, nama_kelas) VALUES (?, ?, ?, ?)");
                $stmt->execute([$mk_id, $dosen_id, $semester_id, $nama_kelas]);
                $message = "Plotting dosen pengampu berhasil ditambahkan!";
            } catch (PDOException $e) {
                $message = "Error: Gagal menambahkan data plotting.";
            }
        } elseif ($type === 'update') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("UPDATE kelas_kuliah SET mk_id = ?, dosen_id = ?, nama_kelas = ? WHERE id = ?");
                $stmt->execute([$mk_id, $dosen_id, $nama_kelas, $id]);
                $message = "Data plotting dosen berhasil diperbarui!";
            } catch (PDOException $e) {
                $message = "Error: Gagal memperbarui data plotting.";
            }
        }
    }

    if (isset($_POST['delete_plotting'])) {
        $id = $_POST['delete_plotting'];
        $stmt = $pdo->prepare("DELETE FROM kelas_kuliah WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Data plotting pengampu berhasil dihapus.";
    }
}

if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $stmt_edit = $pdo->prepare("SELECT * FROM kelas_kuliah WHERE id = ?");
    $stmt_edit->execute([$id_edit]);
    $edit_data = $stmt_edit->fetch();
}

$query_dosen = "SELECT id, nama_lengkap, nomor_induk FROM users WHERE role IN ('dosen', 'kepala_departemen') ORDER BY nama_lengkap ASC";
$dosen_all = $pdo->query($query_dosen)->fetchAll();

$mk_all = $pdo->query("SELECT id, kode_mk, nama_mk FROM mata_kuliah ORDER BY nama_mk ASC")->fetchAll();

$query_plotting = "SELECT k.*, m.nama_mk, m.kode_mk, u.nama_lengkap as nama_dosen, u.nomor_induk 
                   FROM kelas_kuliah k 
                   JOIN mata_kuliah m ON k.mk_id = m.id 
                   JOIN users u ON k.dosen_id = u.id 
                   WHERE k.semester_id = ? 
                   ORDER BY m.nama_mk ASC, k.nama_kelas ASC";
$stmt_p = $pdo->prepare($query_plotting);
$stmt_p->execute([$semester_aktif['id']]);
$plotting_all = $stmt_p->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plotting Dosen - SIOBE</title>
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
        .badge-kelas-name {
            background-color: #edf2f7;
            color: #4a5568;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
        }
        .action-icon-btn {
            background: none;
            border: none;
            color: #a0aec0;
            font-size: 14px;
            padding: 4px 8px;
            transition: color 0.2s;
            cursor: pointer;
        }
        .action-icon-btn:hover.btn-edit {
            color: #3182ce;
        }
        .action-icon-btn:hover.btn-delete {
            color: #e53e3e;
        }
        .form-label-custom {
            font-size: 12px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }
        .form-control-custom, .form-select-custom {
            font-size: 13px;
            border-radius: 6px;
            border: 1px solid #cbd5e0;
            padding: 8px 12px;
        }
        .form-control-custom:focus, .form-select-custom:focus {
            border-color: #3182ce;
            box-shadow: 0 0 0 1px #3182ce;
        }
        .btn-submit-custom {
            font-size: 13px;
            font-weight: 500;
            padding: 8px 16px;
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
                <i class="fa-solid fa-diagram-project"></i> Pemetaan OBE
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
        <h2 class="page-title">Plotting Dosen Pengampu Mata Kuliah</h2>
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

    <?php if($message): ?> 
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 8px; font-size: 14px;">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div> 
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel-card">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class="fa-solid fa-square-plus"></i> <?= $edit_data ? 'Form Edit Plotting' : 'Form Tambah Plotting' ?>
                    </h3>
                </div>
                <form method="POST" action="kadiper_dosen.php">
                    <input type="hidden" name="action_plotting" value="<?= $edit_data ? 'update' : 'create' ?>">
                    <?php if($edit_data): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Pilih Mata Kuliah</label>
                        <select name="mk_id" class="form-select form-select-custom" required>
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php foreach($mk_all as $mk): ?>
                                <option value="<?= htmlspecialchars($mk['id']) ?>" <?= ($edit_data && $edit_data['mk_id'] == $mk['id']) ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($mk['kode_mk']) ?>] <?= htmlspecialchars($mk['nama_mk']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Pilih Dosen Pengampu</label>
                        <select name="dosen_id" class="form-select form-select-custom" required>
                            <option value="">-- Pilih Dosen --</option>
                            <?php foreach($dosen_all as $dsn): ?>
                                <option value="<?= htmlspecialchars($dsn['id']) ?>" <?= ($edit_data && $edit_data['dosen_id'] == $dsn['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dsn['nama_lengkap']) ?> (<?= htmlspecialchars($dsn['nomor_induk']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Nama Kelas / Paralel</label>
                        <input type="text" name="nama_kelas" class="form-control form-control-custom" placeholder="Contoh: A, B, atau MBKM" value="<?= $edit_data ? htmlspecialchars($edit_data['nama_kelas']) : '' ?>" required>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn <?= $edit_data ? 'btn-warning text-white' : 'btn-success text-white' ?> btn-submit-custom w-100">
                            <?= $edit_data ? 'Perbarui Plotting' : 'Simpan Plotting' ?>
                        </button>
                        <?php if($edit_data): ?>
                            <a href="kadiper_dosen.php" class="btn btn-secondary btn-submit-custom w-100 text-center text-decoration-none">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-card">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class="fa-solid fa-list-ol"></i> Matriks Plotting Pengampu Semester Ini
                    </h3>
                </div>
                
                <div class="table-responsive">
                    <table class="table-obe">
                        <thead>
                            <tr>
                                <th width="15%">Kode MK</th>
                                <th width="35%">Mata Kuliah</th>
                                <th width="15%" class="text-center">Kelas</th>
                                <th width="25%">Dosen Pengampu</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($plotting_all)): ?>
                                <tr><td colspan="5" class="text-center text-muted small">Belum ada plotting dosen pengampu untuk semester ini.</td></tr>
                            <?php else: foreach($plotting_all as $p): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($p['kode_mk']) ?></strong></td>
                                <td><span class="cursor-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Mata Kuliah: <?= htmlspecialchars($p['nama_mk']) ?>"><?= htmlspecialchars($p['nama_mk']) ?></span></td>
                                <td class="text-center"><span class="badge-kelas-name">Kelas <?= htmlspecialchars($p['nama_kelas']) ?></span></td>
                                <td><span class="cursor-help text-primary fw-bold" data-bs-toggle="tooltip" data-bs-placement="top" title="NIDN: <?= htmlspecialchars($p['nomor_induk']) ?>"><?= htmlspecialchars($p['nama_dosen']) ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="kadiper_dosen.php?edit=<?= htmlspecialchars($p['id']) ?>" class="action-icon-btn btn-edit"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <form method="POST" onsubmit="return confirm('Hapus plotting pengampu ini? Mahasiswa yang terdaftar di kelas ini tidak akan bisa melihat nilai.')" style="display:inline;">
                                            <button type="submit" name="delete_plotting" value="<?= htmlspecialchars($p['id']) ?>" class="action-icon-btn btn-delete"><i class="fa-regular fa-trash-can"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
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