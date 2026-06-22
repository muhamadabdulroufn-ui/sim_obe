<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

if ($role !== 'admin') {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul CRUD Mahasiswa ini khusus untuk Admin Kurikulum.</div>");
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
    if (isset($_POST['action_mahasiswa'])) {
        $type = $_POST['action_mahasiswa'];
        $username = trim($_POST['username']);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $nomor_induk = strtoupper(trim($_POST['nomor_induk']));

        if ($type === 'create') {
            $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nomor_induk, role) VALUES (?, ?, ?, ?, 'mahasiswa')");
                $stmt->execute([$username, $password, $nama_lengkap, $nomor_induk]);
                $message = "Data mahasiswa baru berhasil didaftarkan ke sistem!";
            } catch (PDOException $e) {
                $message = "Error: Username atau NIM sudah terdaftar.";
            }
        } elseif ($type === 'update') {
            $id = $_POST['id'];
            try {
                if (!empty($_POST['password'])) {
                    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, nomor_induk = ? WHERE id = ? AND role = 'mahasiswa'");
                    $stmt->execute([$username, $password, $nama_lengkap, $nomor_induk, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, nomor_induk = ? WHERE id = ? AND role = 'mahasiswa'");
                    $stmt->execute([$username, $nama_lengkap, $nomor_induk, $id]);
                }
                $message = "Data profil mahasiswa berhasil diperbarui!";
            } catch (PDOException $e) {
                $message = "Error: Gagal memperbarui data mahasiswa.";
            }
        }
    }

    if (isset($_POST['delete_mahasiswa'])) {
        $id = $_POST['delete_mahasiswa'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'mahasiswa'");
        $stmt->execute([$id]);
        $message = "Data master mahasiswa berhasil dihapus dari sistem.";
    }
}

if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $stmt_edit = $pdo->prepare("SELECT id, username, nama_lengkap, nomor_induk FROM users WHERE id = ? AND role = 'mahasiswa'");
    $stmt_edit->execute([$id_edit]);
    $edit_data = $stmt_edit->fetch();
}

$mahasiswa_all = $pdo->query("SELECT id, username, nama_lengkap, nomor_induk FROM users WHERE role = 'mahasiswa' ORDER BY nomor_induk ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahasiswa - SIOBE</title>
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
        .form-control-custom {
            font-size: 13px;
            border-radius: 6px;
            border: 1px solid #cbd5e0;
            padding: 8px 12px;
        }
        .form-control-custom:focus {
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
        <h2 class="page-title">Manajemen Data Master Mahasiswa</h2>
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
                        <i class="fa-solid fa-user-plus"></i> <?= $edit_data ? 'Form Edit Profil Mahasiswa' : 'Form Registrasi Mahasiswa Baru' ?>
                    </h3>
                </div>
                <form method="POST" action="admin_mahasiswa.php">
                    <input type="hidden" name="action_mahasiswa" value="<?= $edit_data ? 'update' : 'create' ?>">
                    <?php if($edit_data): ?>
                        <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Nomor Induk Mahasiswa (NIM)</label>
                        <input type="text" name="nomor_induk" class="form-control form-control-custom" placeholder="Contoh: 3130024040" value="<?= $edit_data ? htmlspecialchars($edit_data['nomor_induk']) : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Nama Lengkap Mahasiswa</label>
                        <input type="text" name="nama_lengkap" class="form-control form-control-custom" placeholder="Masukkan nama lengkap" value="<?= $edit_data ? htmlspecialchars($edit_data['nama_lengkap']) : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Username Akses Login</label>
                        <input type="text" name="username" class="form-control form-control-custom" placeholder="Contoh: mhs_rouf" value="<?= $edit_data ? htmlspecialchars($edit_data['username']) : '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Password Akun</label>
                        <input type="password" name="password" class="form-control form-control-custom" placeholder="<?= $edit_data ? 'Kosongkan jika tidak diubah' : 'Masukkan password login' ?>" <?= $edit_data ? '' : 'required' ?>>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn <?= $edit_data ? 'btn-warning text-white' : 'btn-primary' ?> btn-submit-custom w-100">
                            <?= $edit_data ? 'Perbarui Profil' : 'Daftarkan Mahasiswa' ?>
                        </button>
                        <?php if($edit_data): ?>
                            <a href="admin_mahasiswa.php" class="btn btn-secondary btn-submit-custom w-100 text-center text-decoration-none">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-card">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class="fa-solid fa-users"></i> Daftar Mahasiswa Terregistrasi Akademik
                    </h3>
                </div>
                
                <div class="table-responsive">
                    <table class="table-obe">
                        <thead>
                            <tr>
                                <th width="25%">NIM</th>
                                <th width="45%">Nama Lengkap Mahasiswa</th>
                                <th width="20%">Username Sistem</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($mahasiswa_all)): ?>
                                <tr><td colspan="4" class="text-center text-muted small">Belum ada data mahasiswa terdaftar di database.</td></tr>
                            <?php else: foreach($mahasiswa_all as $m): ?>
                            <tr>
                                <td><span class="badge-mhs-nim"><strong><?= htmlspecialchars($m['nomor_induk']) ?></strong></span></td>
                                <td><span class="cursor-help font-weight-bold" data-bs-toggle="tooltip" data-bs-placement="top" title="Mahasiswa aktif UNUSA: <?= htmlspecialchars($m['nama_lengkap']) ?>"><?= htmlspecialchars($m['nama_lengkap']) ?></span></td>
                                <td>`<?= htmlspecialchars($m['username']) ?>`</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="admin_mahasiswa.php?edit=<?= htmlspecialchars($m['id']) ?>" class="action-icon-btn btn-edit"><i class="fa-regular fa-pen-to-square"></i></a>
                                        <form method="POST" onsubmit="return confirm('Hapus mahasiswa ini? Seluruh data KRS (Kelas) dan nilai OBE mahasiswa ini akan ikut terhapus secara permanen.')" style="display:inline;">
                                            <button type="submit" name="delete_mahasiswa" value="<?= htmlspecialchars($m['id']) ?>" class="action-icon-btn btn-delete"><i class="fa-regular fa-trash-can"></i></button>
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