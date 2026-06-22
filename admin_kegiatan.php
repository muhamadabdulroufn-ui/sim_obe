<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];
$user_id = $_SESSION['user_id'];

if (!in_array($role, ['admin', 'kepala_departemen'])) {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul Dokumen Kegiatan khusus Admin dan Kadep.</div>");
}

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$message = '';
$upload_dir = 'uploads/kegiatan/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_kegiatan'])) {
        $action = $_POST['action_kegiatan'];
        $nama_kegiatan = trim($_POST['nama_kegiatan']);
        $tanggal_kegiatan = $_POST['tanggal_kegiatan'];
        $deskripsi = trim($_POST['deskripsi']);
        $tindak_lanjut_list = $_POST['tindak_lanjut'] ?? [];

        $stmt = $pdo->prepare("INSERT INTO kegiatan_obe (nama_kegiatan, tanggal_kegiatan, deskripsi, dibuat_oleh) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama_kegiatan, $tanggal_kegiatan, $deskripsi, $user_id]);
        $kegiatan_id = $pdo->lastInsertId();

        if (!empty($tindak_lanjut_list)) {
            foreach ($tindak_lanjut_list as $tl_text) {
                $tl_text = trim($tl_text);
                if ($tl_text !== '') {
                    $stmt_tl = $pdo->prepare("INSERT INTO tindak_lanjut_kegiatan (kegiatan_id, deskripsi_tindak_lanjut) VALUES (?, ?)");
                    $stmt_tl->execute([$kegiatan_id, $tl_text]);
                }
            }
        }

        if (!empty($_FILES['lampiran']['name'][0])) {
            foreach ($_FILES['lampiran']['name'] as $key => $name) {
                $tmp_name = $_FILES['lampiran']['tmp_name'][$key];
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

                if (in_array($ext, $allowed)) {
                    $new_filename = uniqid('KEG_', true) . '.' . $ext;
                    $target_file = $upload_dir . $new_filename;

                    if (move_uploaded_file($tmp_name, $target_file)) {
                        $stmt_file = $pdo->prepare("INSERT INTO lampiran_kegiatan (kegiatan_id, nama_file, tipe_file) VALUES (?, ?, ?)");
                        $stmt_file->execute([$kegiatan_id, $new_filename, $ext]);
                    }
                }
            }
        }
        $message = "Kegiatan, berkas lampiran, dan rencana tindak lanjut berhasil didokumentasikan!";
    }

    if (isset($_POST['delete_kegiatan'])) {
        $id = intval($_POST['delete_kegiatan']);
        
        $stmt_files = $pdo->prepare("SELECT nama_file FROM lampiran_kegiatan WHERE kegiatan_id = ?");
        $stmt_files->execute([$id]);
        $files = $stmt_files->fetchAll();
        foreach ($files as $f) {
            $file_path = $upload_dir . $f['nama_file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM kegiatan_obe WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Dokumentasi kegiatan berhasil dihapus dari sistem.";
    }
}

$kegiatan_all = $pdo->query("SELECT k.*, u.nama_lengkap FROM kegiatan_obe k JOIN users u ON k.dibuat_oleh = u.id ORDER BY k.tanggal_kegiatan DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dokumentasi Kegiatan - SIOBE</title>
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
        .action-icon-btn {
            background: none;
            border: none;
            color: #a0aec0;
            font-size: 14px;
            padding: 4px 8px;
            transition: color 0.2s;
            cursor: pointer;
        }
        .action-icon-btn:hover {
            color: #e53e3e;
        }
        .tl-item-box {
            display: flex;
            gap: 6px;
            margin-bottom: 6px;
        }
        .badge-tl {
            display: block;
            background-color: #f0f4f8;
            color: #2b6cb0;
            border-left: 3px solid #3182ce;
            padding: 4px 8px;
            border-radius: 0 4px 4px 0;
            font-size: 11px;
            margin-top: 4px;
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
            <a href="admin_mk.php" class="nav-item-link">
                <i class="fa-solid fa-book"></i> Mata Kuliah
            </a>
            <a href="admin_crud.php" class="nav-item-link">
                <i class="fa-solid fa-pen-to-square"></i> Kelola RPS
            </a>
            <a href="admin_kegiatan.php" class="nav-item-link active">
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
        <h2 class="page-title">Dokumentasi & Berkas Rapat Kegiatan</h2>
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
                    <h3 class="panel-title"><i class="fa-solid fa-folder-plus"></i> Input Kegiatan Baru</h3>
                </div>
                <form method="POST" action="admin_kegiatan.php" enctype="multipart/form-data">
                    <input type="hidden" name="action_kegiatan" value="create">
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Nama Rapat / Kegiatan</label>
                        <input type="text" name="nama_kegiatan" class="form-control form-control-custom" placeholder="Contoh: Rapat Pleno CPL" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Tanggal Pelaksanaan</label>
                        <input type="date" name="tanggal_kegiatan" class="form-control form-control-custom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Deskripsi Singkat</label>
                        <textarea name="deskripsi" class="form-control form-control-custom" rows="2" placeholder="Tulis rincian hasil/agenda kegiatan..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Hasil Evaluasi / Rencana Tindak Lanjut</label>
                        <div id="tl-input-wrapper">
                            <div class="tl-item-box">
                                <input type="text" name="tindak_lanjut[]" class="form-control form-control-custom" placeholder="Butir tindak lanjut 1">
                                <button type="button" class="btn btn-sm btn-success px-2" onclick="addTlField()"><i class="fa-solid fa-plus"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label form-label-custom">Lampiran Berkas / Ambil Foto</label>
                        <input type="file" name="lampiran[]" class="form-control form-control-custom" accept="image/*,application/pdf" capture="environment" multiple required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" style="font-size:13px; border-radius:6px;">Upload Kegiatan</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-card">
                <div class="panel-header">
                    <h3 class="panel-title"><i class="fa-solid fa-list"></i> Log Riwayat Kegiatan Terdata</h3>
                </div>
                <div class="table-responsive">
                    <table class="table-obe">
                        <thead>
                            <tr>
                                <th width="12%">Tanggal</th>
                                <th width="28%">Nama Kegiatan</th>
                                <th width="30%">Butir Evaluasi / Tindak Lanjut</th>
                                <th width="20%">Lampiran Presensi</th>
                                <th width="5%">Oleh</th>
                                <th width="5%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($kegiatan_all)): ?>
                                <tr><td colspan="6" class="text-center text-muted small">Belum ada riwayat berkas kegiatan terdokumentasi.</td></tr>
                            <?php else: foreach($kegiatan_all as $keg): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($keg['tanggal_kegiatan']) ?></span></td>
                                <td>
                                    <strong><?= htmlspecialchars($keg['nama_kegiatan']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($keg['deskripsi']) ?></small>
                                </td>
                                <td>
                                    <div>
                                        <?php
                                        $stmt_tl_get = $pdo->prepare("SELECT * FROM tindak_lanjut_kegiatan WHERE kegiatan_id = ? ORDER BY id ASC");
                                        $stmt_tl_get->execute([$keg['id']]);
                                        $tls = $stmt_tl_get->fetchAll();
                                        if (empty($tls)) {
                                            echo "<small class='text-muted'>-</small>";
                                        } else {
                                            foreach ($tls as $tl_row) {
                                                echo "<span class='badge-tl'><i class='fa-solid fa-arrow-right-long me-1 text-primary' style='font-size:9px;'></i> " . htmlspecialchars($tl_row['deskripsi_tindak_lanjut']) . "</span>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php
                                        $stmt_f = $pdo->prepare("SELECT * FROM lampiran_kegiatan WHERE kegiatan_id = ?");
                                        $stmt_f->execute([$keg['id']]);
                                        $lampiran = $stmt_f->fetchAll();
                                        if(empty($lampiran)) { echo "<small class='text-muted'>-</small>"; }
                                        else {
                                            foreach($lampiran as $l) {
                                                $icon = ($l['tipe_file'] === 'pdf') ? 'fa-file-pdf text-danger' : 'fa-image text-primary';
                                                echo "<a href='uploads/kegiatan/{$l['nama_file']}' target='_blank' class='btn btn-light btn-sm border py-0 px-2' style='font-size:11px;'><i class='fa-solid {$icon} me-1'></i>Lihat</a>";
                                            }
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td><small><?= htmlspecialchars($keg['nama_lengkap']) ?></small></td>
                                <td class="text-center">
                                    <form method="POST" onsubmit="return confirm('Hapus seluruh dokumentasi kegiatan ini beserta file lampirannya?')">
                                        <button type="submit" name="delete_kegiatan" value="<?= $keg['id'] ?>" class="action-icon-btn"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
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
    function addTlField() {
        const wrapper = document.getElementById('tl-input-wrapper');
        const count = wrapper.getElementsByClassName('tl-item-box').length + 1;
        
        const div = document.createElement('div');
        div.className = 'tl-item-box';
        div.innerHTML = `
            <input type="text" name="tindak_lanjut[]" class="form-control form-control-custom" placeholder="Butir tindak lanjut ${count}">
            <button type="button" class="btn btn-sm btn-danger px-2" onclick="removeTlField(this)"><i class="fa-solid fa-minus"></i></button>
        `;
        wrapper.appendChild(div);
    }

    function removeTlField(button) {
        button.closest('.tl-item-box').remove();
    }
</script>
</body>
</html>