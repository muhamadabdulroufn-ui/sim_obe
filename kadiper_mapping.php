<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

if (!in_array($role, ['admin', 'kepala_departemen'])) {
    die("<div class='alert alert-danger m-3'>Akses Terbatas: Hanya untuk Kepala Departemen atau Admin.</div>");
}

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_matrix'])) {
        $cpl_id = intval($_POST['cpl_id']);
        $pl_id = intval($_POST['pl_id']);
        
        $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM map_cpl_pl WHERE cpl_id = ? AND pl_id = ?");
        $stmt_check->execute([$cpl_id, $pl_id]);
        $exists = $stmt_check->fetchColumn();
        
        if ($exists) {
            $stmt_del = $pdo->prepare("DELETE FROM map_cpl_pl WHERE cpl_id = ? AND pl_id = ?");
            $stmt_del->execute([$cpl_id, $pl_id]);
        } else {
            $stmt_ins = $pdo->prepare("INSERT INTO map_cpl_pl (cpl_id, pl_id) VALUES (?, ?)");
            $stmt_ins->execute([$cpl_id, $pl_id]);
        }
        exit;
    }

    if (isset($_POST['action_pl'])) {
        $action = $_POST['action_pl'];
        $kode_pl = strtoupper(trim($_POST['kode_pl']));
        $deskripsi = trim($_POST['deskripsi']);

        if ($action === 'create') {
            try {
                $stmt = $pdo->prepare("INSERT INTO profil_lulusan (kode_pl, deskripsi) VALUES (?, ?)");
                $stmt->execute([$kode_pl, $deskripsi]);
                $message = "Profil Lulusan baru berhasil ditambahkan!";
            } catch (PDOException $e) {
                $message = "Error: Kode PL sudah digunakan.";
            }
        } elseif ($action === 'update') {
            $id = intval($_POST['id']);
            try {
                $stmt = $pdo->prepare("UPDATE profil_lulusan SET kode_pl = ?, deskripsi = ? WHERE id = ?");
                $stmt->execute([$kode_pl, $deskripsi, $id]);
                $message = "Profil Lulusan berhasil diperbarui!";
            } catch (PDOException $e) {
                $message = "Error: Gagal memperbarui data.";
            }
        }
    }

    if (isset($_POST['delete_pl'])) {
        $id = intval($_POST['delete_pl']);
        $stmt = $pdo->prepare("DELETE FROM profil_lulusan WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Profil Lulusan berhasil dihapus.";
    }
}

$pl_all = $pdo->query("SELECT * FROM profil_lulusan ORDER BY kode_pl ASC")->fetchAll();
$cpl_all = $pdo->query("SELECT * FROM cpl ORDER BY kode_cpl ASC")->fetchAll();

$matrix_relations = [];
$raw_relations = $pdo->query("SELECT cpl_id, pl_id FROM map_cpl_pl")->fetchAll();
foreach ($raw_relations as $rel) {
    $matrix_relations[$rel['cpl_id']][$rel['pl_id']] = true;
}

$pl_cpl_badges = [];
foreach ($cpl_all as $c) {
    foreach ($pl_all as $p) {
        if (isset($matrix_relations[$c['id']][$p['id']])) {
            $pl_cpl_badges[$p['id']][] = $c['kode_cpl'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemetaan CPL-PL - SIOBE</title>
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
        .panel-subtitle {
            font-size: 12px;
            color: #a0aec0;
            margin-top: -15px;
            margin-bottom: 20px;
        }
        .custom-btn {
            font-size: 12px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .btn-add-pl {
            background-color: #ffffff;
            border: 1px solid #cbd5e0;
            color: #4a5568;
        }
        .btn-add-pl:hover {
            background-color: #f7fafc;
        }
        .btn-manage-cpl {
            background-color: #ffffff;
            border: 1px solid #cbd5e0;
            color: #4a5568;
        }
        .btn-manage-cpl:hover {
            background-color: #f7fafc;
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
            padding: 16px;
            border-bottom: 1px solid #edf2f7;
            font-size: 13px;
            color: #2d3748;
            vertical-align: middle;
        }
        .badge-pl-code {
            background-color: #ebf8ff;
            color: #2b6cb0;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: help;
        }
        .badge-type-primary {
            background-color: #e2e8f0;
            color: #4a5568;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
        }
        .badge-type-secondary {
            background-color: #feebc8;
            color: #c05621;
            font-weight: 500;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
        }
        .badge-cpl-relation {
            background-color: #edf2f7;
            color: #2d3748;
            font-weight: 500;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            margin-right: 4px;
            display: inline-block;
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
        .matrix-node-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .matrix-node {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            transition: transform 0.1s ease;
        }
        .matrix-node:active {
            transform: scale(0.8);
        }
        .matrix-node.node-active {
            background-color: #2b6cb0;
        }
        .matrix-node.node-inactive {
            background-color: #e2e8f0;
        }
        .text-cpl-header {
            font-weight: 700;
            color: #1a202c;
            cursor: help;
        }
        .text-sn-dikti {
            font-size: 11px;
            color: #718096;
            font-family: monospace;
        }
        .th-matrix-pl {
            cursor: help;
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
            <a href="kadiper_mapping.php" class="nav-item-link active">
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
        <h2 class="page-title">Pemetaan CPL-PL</h2>
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
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div> 
    <?php endif; ?>

    <div class="panel-card">
        <div class="panel-header">
            <h3 class="panel-title">
                <i class="fa-solid fa-id-card"></i> Profil Lulusan (PL)
            </h3>
            <button class="btn btn-add-pl custom-btn" data-bs-toggle="modal" data-bs-target="#plModal" onclick="clearPlForm()">
                <i class="fa-solid fa-plus"></i> Tambah PL
            </button>
        </div>
        
        <div class="table-responsive">
            <table class="table-obe">
                <thead>
                    <tr>
                        <th width="15%">Kode</th>
                        <th width="60%">Profil Lulusan</th>
                        <th width="15%">Tipe</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pl_all as $index => $pl): ?>
                    <tr>
                        <td><span class="badge-pl-code" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($pl['deskripsi']) ?>"><?= htmlspecialchars($pl['kode_pl']) ?></span></td>
                        <td><?= htmlspecialchars($pl['deskripsi']) ?></td>
                        <td>
                            <span class="<?= $index % 2 == 0 ? 'badge-type-primary' : 'badge-type-secondary' ?>">
                                <?= $index % 2 == 0 ? 'Penciri Utama' : 'KK dan P' ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <button class="action-icon-btn btn-edit" data-bs-toggle="modal" data-bs-target="#plModal" onclick="editPl(<?= $pl['id'] ?>, '<?= htmlspecialchars($pl['kode_pl']) ?>', '<?= htmlspecialchars(addslashes($pl['deskripsi'])) ?>')"><i class="fa-regular fa-pen-to-square"></i></button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus Profil Lulusan ini?')">
                                <button type="submit" name="delete_pl" value="<?= $pl['id'] ?>" class="action-icon-btn btn-delete"><i class="fa-regular fa-trash-can"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel-card">
        <div class="panel-header">
            <h3 class="panel-title">
                <i class="fa-solid fa-table-cells"></i> Matriks CPL-PL
            </h3>
            <a href="admin_kurikulum.php" class="btn btn-manage-cpl custom-btn text-decoration-none">
                <i class="fa-solid fa-sliders"></i> Kelola CPL
            </a>
        </div>
        <p class="panel-subtitle">Ketuk pada titik untuk mengubah (mengaktifkan/menonaktifkan) relasi PL ke CPL. Klik pada baris untuk melihat detail.</p>
        
        <div class="table-responsive">
            <table class="table-obe table-hover">
                <thead>
                    <tr>
                        <th width="12%">CPL</th>
                        <?php foreach ($pl_all as $pl): ?>
                            <th width="10%" class="text-center th-matrix-pl" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($pl['deskripsi']) ?>"><?= htmlspecialchars($pl['kode_pl']) ?></th>
                        <?php endforeach; ?>
                        <th width="28%">Referensi SN-Dikti</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cpl_all as $index => $cpl): ?>
                    <tr>
                        <td class="text-cpl-header" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($cpl['deskripsi']) ?>"><?= htmlspecialchars($cpl['kode_cpl']) ?></td>
                        <?php foreach ($pl_all as $pl): 
                            $is_active = isset($matrix_relations[$cpl['id']][$pl['id']]);
                        ?>
                            <td class="text-center">
                                <div class="matrix-node-container">
                                    <button type="button" 
                                            class="matrix-node <?= $is_active ? 'node-active' : 'node-inactive' ?>" 
                                            data-bs-toggle="tooltip" 
                                            data-bs-placement="top" 
                                            title="Hubungkan <?= htmlspecialchars($cpl['kode_cpl']) ?> ke <?= htmlspecialchars($pl['kode_pl']) ?>" 
                                            onclick="toggleMatrixNode(<?= $cpl['id']; ?>, <?= $pl['id']; ?>, '<?= htmlspecialchars($cpl['kode_cpl']) ?>', <?= $pl['id']; ?>, this)">
                                    </button>
                                </div>
                            </td>
                        <?php endforeach; ?>
                        <td>
                            <span class="text-sn-dikti">
                                <?= $index % 3 == 0 ? 'CPL-P01' : ($index % 3 == 1 ? 'CPL-KK01,P02' : 'CPL-KK03,04,09,10,11,12') ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="admin_kurikulum.php" class="action-icon-btn btn-edit text-decoration-none"><i class="fa-regular fa-pen-to-square"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="plModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Form Profil Lulusan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="kadiper_mapping.php">
                <div class="modal-body">
                    <input type="hidden" name="action_pl" id="formAction" value="create">
                    <input type="hidden" name="id" id="plId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode PL</label>
                        <input type="text" name="kode_pl" id="formKode" class="form-control form-control-sm" placeholder="Contoh: PL1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Deskripsi Profil</label>
                        <textarea name="deskripsi" id="formDeskripsi" class="form-control form-control-sm" rows="4" placeholder="Tulis deskripsi..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan Data PL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

document.addEventListener("DOMContentLoaded", function() {
    initTooltips();
});

function clearPlForm() {
    document.getElementById('modalTitle').innerText = 'Tambah Profil Lulusan';
    document.getElementById('formAction').value = 'create';
    document.getElementById('plId').value = '';
    document.getElementById('formKode').value = '';
    document.getElementById('formDeskripsi').value = '';
}

function editPl(id, kode, deskripsi) {
    document.getElementById('modalTitle').innerText = 'Edit Profil Lulusan';
    document.getElementById('formAction').value = 'update';
    document.getElementById('plId').value = id;
    document.getElementById('formKode').value = kode;
    document.getElementById('formDeskripsi').value = deskripsi;
}

function toggleMatrixNode(cplId, plId, cplKode, plIdTarget, element) {
    const formData = new FormData();
    formData.append('toggle_matrix', '1');
    formData.append('cpl_id', cplId);
    formData.append('pl_id', plId);

    fetch('kadiper_mapping.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        const container = document.getElementById('badge-container-pl-' + plIdTarget);
        if (element.classList.contains('node-active')) {
            element.classList.remove('node-active');
            element.classList.add('node-inactive');
            
            if (container) {
                const badges = container.getElementsByClassName('badge-cpl-relation');
                for (let i = 0; i < badges.length; i++) {
                    if (badges[i].innerText === cplKode) {
                        badges[i].remove();
                        break;
                    }
                }
                if (container.children.length === 0) {
                    container.innerHTML = "<span class='text-muted small'>-</span>";
                }
            }
        } else {
            element.classList.remove('node-inactive');
            element.classList.add('node-active');
            
            if (container) {
                if (container.innerText === '-') {
                    container.innerHTML = '';
                }
                
                const newBadge = document.createElement('span');
                newBadge.className = 'badge-cpl-relation';
                newBadge.innerText = cplKode;
                container.appendChild(newBadge);
                
                const badgeArr = Array.from(container.children);
                badgeArr.sort((a, b) => a.innerText.localeCompare(b.innerText));
                container.innerHTML = '';
                badgeArr.forEach(b => container.appendChild(b));
            }
        }

        var oldTooltip = bootstrap.Tooltip.getInstance(element);
        if (oldTooltip) { oldTooltip.dispose(); }
        var newTooltip = new bootstrap.Tooltip(element);
        newTooltip.show();
    })
    .catch(error => console.error('Error:', error));
}
</script>
</body>
</html>