<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

if ($role !== 'admin') {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul CRUD Mata Kuliah ini khusus untuk Admin Kurikulum.</div>");
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
    if (isset($_POST['action_mk'])) {
        $type = $_POST['action_mk'];
        $kode_mk = strtoupper(trim($_POST['kode_mk']));
        $nama_mk = trim($_POST['nama_mk']);
        $sks = intval($_POST['sks']);
        $semester_target = intval($_POST['semester_target']);

        if ($type === 'create') {
            try {
                $stmt = $pdo->prepare("INSERT INTO mata_kuliah (kode_mk, nama_mk, sks, semester_target) VALUES (?, ?, ?, ?)");
                $stmt->execute([$kode_mk, $nama_mk, $sks, $semester_target]);
                $message = "Mata Kuliah baru berhasil ditambahkan!";
            } catch (PDOException $e) {
                $message = "Error: Kode MK sudah terdaftar.";
            }
        } elseif ($type === 'update') {
            $id = $_POST['id'];
            try {
                $stmt = $pdo->prepare("UPDATE mata_kuliah SET kode_mk = ?, nama_mk = ?, sks = ?, semester_target = ? WHERE id = ?");
                $stmt->execute([$kode_mk, $nama_mk, $sks, $semester_target, $id]);
                $message = "Data Mata Kuliah berhasil diperbarui!";
            } catch (PDOException $e) {
                $message = "Error: Gagal memperbarui data.";
            }
        }
    }

    if (isset($_POST['delete_mk'])) {
        $id = $_POST['delete_mk'];
        $stmt = $pdo->prepare("DELETE FROM mata_kuliah WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Data Mata Kuliah berhasil dihapus.";
    }
}

if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $stmt_edit = $pdo->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
    $stmt_edit->execute([$id_edit]);
    $edit_data = $stmt_edit->fetch();
}

$mk_all = $pdo->query("SELECT * FROM mata_kuliah ORDER BY semester_target ASC, nama_mk ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Kuliah - SIOBE</title>
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
            <a href="admin_mk.php" class="nav-item-link active">
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
        <h2 class="page-title">Kelola Data Master Mata Kuliah</h2>
        <a href="logout.php" class="btn btn-outline-danger btn-logout-top">
            <i class="fa-solid fa-power-off me-1"></i> Keluar
        </a>
    </div>

    <?php if($message): ?> 
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert" style="border-radius: 8px; font-size: 14px;">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div> 
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="panel-card shadow-sm h-100">
                <div class="panel-header">
                    <h3 class="panel-title">
                        <i class="fa-solid fa-square-plus"></i> <?= $edit_data ? 'Form Edit Kurikulum MK' : 'Form Tambah Mata Kuliah' ?>
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_mk.php">
                        <input type="hidden" name="action_mk" value="<?= $edit_data ? 'update' : 'create' ?>">
                        <?php if($edit_data): ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label form-label-custom">Kode Mata Kuliah</label>
                            <input type="text" name="kode_mk" class="form-control form-control-custom" placeholder="Contoh: SI-203" value="<?= $edit_data ? htmlspecialchars($edit_data['kode_mk']) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-custom">Nama Mata Kuliah</label>
                            <input type="text" name="nama_mk" class="form-control form-control-custom" placeholder="Contoh: Pemrograman Berorientasi Objek" value="<?= $edit_data ? htmlspecialchars($edit_data['nama_mk']) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-custom">Bobot SKS</label>
                            <input type="number" name="sks" class="form-control form-control-custom" placeholder="Contoh: 3" value="<?= $edit_data ? htmlspecialchars($edit_data['sks']) : '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label form-label-custom">Semester Target</label>
                            <input type="number" name="semester_target" class="form-control form-control-custom" placeholder="Contoh: 3" value="<?= $edit_data ? htmlspecialchars($edit_data['semester_target']) : '' ?>" required>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn <?= $edit_data ? 'btn-warning text-white' : 'btn-primary' ?> w-100 btn-submit-custom">
                                <?= $edit_data ? 'Perbarui Data MK' : 'Simpan Data Baru' ?>
                            </button>
                            <?php if($edit_data): ?>
                                <a href="admin_mk.php" class="btn btn-secondary w-100 btn-submit-custom text-center text-decoration-none">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-card shadow-sm h-100">
                <div class="panel-header text-dark fw-bold">Matriks Distribusi Penawaran Mata Kuliah Kurikulum</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table-obe table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Kode MK</th>
                                    <th>Nama Mata Kuliah</th>
                                    <th class="text-center">SKS</th>
                                    <th class="text-center">Semester</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($mk_all)): ?>
                                    <tr><td colspan="5" class="text-center text-muted small">Belum ada mata kuliah kurikulum terdaftar di database.</td></tr>
                                <?php else: foreach($mk_all as $mk): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($mk['kode_mk']) ?></strong></td>
                                    <td><?= htmlspecialchars($mk['nama_mk']) ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?= htmlspecialchars($mk['sks']) ?> SKS</span></td>
                                    <td class="text-center"><span class="badge bg-light text-dark border">Semester <?= htmlspecialchars($mk['semester_target']) ?></span></td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="admin_mk.php?edit=<?= htmlspecialchars($mk['id']) ?>" class="action-icon-btn btn-edit"><i class="fa-regular fa-pen-to-square"></i></a>
                                            <form method="POST" onsubmit="return confirm('Hapus mata kuliah ini? Seluruh data CPMK dan Nilai yang terikat akan ikut terhapus.')" style="display:inline;">
                                                <button type="submit" name="delete_mk" value="<?= htmlspecialchars($mk['id']) ?>" class="action-icon-btn btn-delete"><i class="fa-regular fa-trash-can"></i></button>
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