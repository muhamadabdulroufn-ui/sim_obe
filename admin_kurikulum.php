<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];

if ($role !== 'admin') {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul ini khusus untuk Admin Kurikulum.</div>");
}

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$message = '';

if (isset($_GET['get_cpl_sn'])) {
    $cpl_id = intval($_GET['get_cpl_sn']);
    $stmt = $pdo->prepare("SELECT sndikti_id FROM map_cpl_sndikti WHERE cpl_id = ?");
    $stmt->execute([$cpl_id]);
    $mapped_sn = $stmt->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: application/json');
    echo json_encode($mapped_sn);
    exit;
}

if (isset($_GET['get_cpmk_bk'])) {
    $cpmk_id = intval($_GET['get_cpmk_bk']);
    $stmt = $pdo->prepare("SELECT bk_id FROM map_cpmk_bk WHERE cpmk_id = ?");
    $stmt->execute([$cpmk_id]);
    $mapped_bk = $stmt->fetchAll(PDO::FETCH_COLUMN);
    header('Content-Type: application/json');
    echo json_encode($mapped_bk);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['master_sndikti'])) {
        $kode_sndikti = strtoupper(trim($_POST['kode_sndikti']));
        $deskripsi_sndikti = trim($_POST['deskripsi_sndikti']);
        try {
            $stmt = $pdo->prepare("INSERT INTO master_sndikti (kode_sndikti, deskripsi_sndikti) VALUES (?, ?)");
            $stmt->execute([$kode_sndikti, $deskripsi_sndikti]);
            $message = "Master Referensi SN-Dikti baru berhasil disimpan!";
        } catch (PDOException $e) {
            $message = "Error: Kode SN-Dikti sudah digunakan.";
        }
    }

    if (isset($_POST['master_bk'])) {
        $kode_bk = strtoupper(trim($_POST['kode_bk']));
        $nama_bk = trim($_POST['nama_bk']);
        $deskripsi = trim($_POST['deskripsi']);
        try {
            $stmt = $pdo->prepare("INSERT INTO bahan_kajian (kode_bk, nama_bk, deskripsi) VALUES (?, ?, ?)");
            $stmt->execute([$kode_bk, $nama_bk, $deskripsi]);
            $message = "Master Bahan Kajian (BK) baru berhasil disimpan!";
        } catch (PDOException $e) {
            $message = "Error: Kode Bahan Kajian sudah digunakan.";
        }
    }

    if (isset($_POST['action_cpl'])) {
        $type = $_POST['action_cpl'];
        $kode_cpl = strtoupper(trim($_POST['kode_cpl']));
        $deskripsi = trim($_POST['deskripsi']);
        $sndikti_ids = $_POST['sndikti_ids'] ?? [];
        
        if ($type === 'create') {
            $stmt = $pdo->prepare("INSERT INTO cpl (kode_cpl, deskripsi) VALUES (?, ?)");
            $stmt->execute([$kode_cpl, $deskripsi]);
            $cpl_id = $pdo->lastInsertId();

            if (!empty($sndikti_ids)) {
                foreach ($sndikti_ids as $s_id) {
                    $pdo->prepare("INSERT INTO map_cpl_sndikti (cpl_id, sndikti_id) VALUES (?, ?)")->execute([$cpl_id, $s_id]);
                }
            }
            $message = "Data CPL baru berhasil ditambahkan!";
        } elseif ($type === 'update') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE cpl SET kode_cpl = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$kode_cpl, $deskripsi, $id]);

            $pdo->prepare("DELETE FROM map_cpl_sndikti WHERE cpl_id = ?")->execute([$id]);
            if (!empty($sndikti_ids)) {
                foreach ($sndikti_ids as $s_id) {
                    $pdo->prepare("INSERT INTO map_cpl_sndikti (cpl_id, sndikti_id) VALUES (?, ?)")->execute([$id, $s_id]);
                }
            }
            $message = "Data CPL dan Referensi SN-Dikti berhasil diperbarui!";
        }
    }
    
    if (isset($_POST['delete_cpl'])) {
        $id = $_POST['delete_cpl'];
        $stmt = $pdo->prepare("DELETE FROM cpl WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Data CPL berhasil dihapus dari sistem.";
    }

    if (isset($_POST['action_cpmk'])) {
        $type = $_POST['action_cpmk'];
        $mk_id = $_POST['mk_id'];
        $kode_cpmk = strtoupper(trim($_POST['kode_cpmk']));
        $deskripsi = trim($_POST['deskripsi']);
        $bk_ids = $_POST['bk_ids'] ?? [];
        
        if ($type === 'create') {
            $stmt = $pdo->prepare("INSERT INTO cpmk (mk_id, kode_cpmk, deskripsi) VALUES (?, ?, ?)");
            $stmt->execute([$mk_id, $kode_cpmk, $deskripsi]);
            $cpmk_id = $pdo->lastInsertId();

            if (!empty($bk_ids)) {
                foreach ($bk_ids as $bk_id) {
                    $stmt_map = $pdo->prepare("INSERT INTO map_cpmk_bk (cpmk_id, bk_id) VALUES (?, ?)");
                    $stmt_map->execute([$cpmk_id, $bk_id]);
                }
            }
            $message = "Data CPMK baru berhasil disimpan!";
        } elseif ($type === 'update') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE cpmk SET mk_id = ?, kode_cpmk = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$mk_id, $kode_cpmk, $deskripsi, $id]);

            $stmt_del = $pdo->prepare("DELETE FROM map_cpmk_bk WHERE cpmk_id = ?");
            $stmt_del->execute([$id]);

            if (!empty($bk_ids)) {
                foreach ($bk_ids as $bk_id) {
                    $stmt_map = $pdo->prepare("INSERT INTO map_cpmk_bk (cpmk_id, bk_id) VALUES (?, ?)");
                    $stmt_map->execute([$id, $bk_id]);
                }
            }
            $message = "Data CPMK dan Pemetaan Bahan Kajian (BK) berhasil diperbarui!";
        }
    }

    if (isset($_POST['delete_cpmk'])) {
        $id = $_POST['delete_cpmk'];
        $stmt = $pdo->prepare("DELETE FROM cpmk WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Data CPMK berhasil dihapus dari sistem.";
    }
}

$sndikti_all = $pdo->query("SELECT * FROM master_sndikti ORDER BY kode_sndikti ASC")->fetchAll();
$cpl_all = $pdo->query("SELECT * FROM cpl ORDER BY kode_cpl ASC")->fetchAll();
$mk_all  = $pdo->query("SELECT * FROM mata_kuliah ORDER BY nama_mk ASC")->fetchAll();
$bk_all  = $pdo->query("SELECT * FROM bahan_kajian ORDER BY kode_bk ASC")->fetchAll();
$cpmk_all = $pdo->query("SELECT c.*, m.nama_mk, m.kode_mk FROM cpmk c JOIN mata_kuliah m ON c.mk_id = m.id ORDER BY m.nama_mk ASC, c.kode_cpmk ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Kurikulum - SIOBE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7fc; font-family: 'Segoe UI', Arial, sans-serif; overflow-x: hidden; }
        .sidebar { width: 260px; height: 100vh; background-color: #ffffff; position: fixed; top: 0; left: 0; border-right: 1px solid #e3e6f0; z-index: 100; }
        .sidebar-brand { padding: 24px; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f1f3f9; }
        .sidebar-logo-box { width: 40px; height: 40px; background-color: #e8f0fe; border-radius: 10px; display: flex; justify-content: center; align-items: center; color: #1a73e8; font-size: 20px; }
        .brand-text h1 { font-size: 16px; font-weight: 700; color: #112d62; margin: 0; }
        .brand-text p { font-size: 11px; color: #6c757d; margin: 0; }
        .sidebar-meta { padding: 12px 24px; font-size: 11px; color: #8c94a0; background-color: #fafbfc; }
        .menu-section-title { padding: 16px 24px 8px 24px; font-size: 11px; font-weight: 700; color: #a0aec0; text-transform: uppercase; letter-spacing: 0.5px; }
        .nav-menu { display: flex; flex-direction: column; gap: 4px; padding: 0 12px; }
        .nav-item-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: #4a5568; text-decoration: none; font-size: 14px; font-weight: 500; border-radius: 8px; transition: all 0.2s ease; }
        .nav-item-link i { font-size: 16px; color: #a0aec0; width: 20px; text-align: center; }
        .nav-item-link:hover { background-color: #f7fafc; color: #1a73e8; }
        .nav-item-link.active { background-color: #e8f0fe; color: #1a73e8; font-weight: 600; }
        .nav-item-link.active i { color: #1a73e8; }
        .sidebar-footer { position: absolute; bottom: 0; left: 0; width: 100%; padding: 16px 24px; border-top: 1px solid #f1f3f9; background-color: #ffffff; display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 36px; height: 36px; background-color: #e2e8f0; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: 600; color: #4a5568; font-size: 13px; }
        .user-info h2 { font-size: 13px; font-weight: 600; color: #2d3748; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
        .user-info p { font-size: 11px; color: #718096; margin: 0; }
        .main-content { margin-left: 260px; padding: 24px 40px; min-height: 100vh; }
        .top-navbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .page-title { font-size: 18px; font-weight: 600; color: #1a202c; margin: 0; }
        .panel-card { background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); padding: 24px; margin-bottom: 24px; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .panel-title { font-size: 15px; font-weight: 600; color: #2d3748; display: flex; align-items: center; gap: 10px; margin: 0; }
        .panel-title i { color: #718096; }
        .table-obe { width: 100%; border-collapse: collapse; }
        .table-obe th { background-color: #f7fafc; color: #718096; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; border-bottom: 1px solid #edf2f7; }
        .table-obe td { padding: 14px 16px; border-bottom: 1px solid #edf2f7; font-size: 13px; color: #2d3748; vertical-align: middle; }
        .badge-cpl-code, .badge-cpmk-code { background-color: #ebf8ff; color: #2b6cb0; font-weight: 600; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-bk-code { background-color: #e6fffa; color: #234e52; font-weight: 500; padding: 2px 6px; border-radius: 4px; font-size: 11px; }
        .action-icon-btn { background: none; border: none; color: #a0aec0; font-size: 14px; padding: 4px 8px; transition: color 0.2s; cursor: pointer; }
        .action-icon-btn:hover.btn-edit { color: #3182ce; }
        .action-icon-btn:hover.btn-delete { color: #e53e3e; }
        .form-label-custom { font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 6px; }
        .form-control-custom, .form-select-custom { font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e0; padding: 8px 12px; }
        .form-control-custom:focus, .form-select-custom:focus { border-color: #3182ce; box-shadow: 0 0 0 1px #3182ce; }
        .btn-submit-custom { font-size: 13px; font-weight: 500; padding: 8px 16px; border-radius: 6px; }
        .bk-scroll-container { max-height: 120px; overflow-y: auto; border: 1px solid #cbd5e0; border-radius: 6px; padding: 10px; background-color: #fafbfc; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-box"><i class="fa-solid fa-graduation-cap"></i></div>
        <div class="brand-text"><h1>SIOBE</h1><p>Outcome-Based Education</p></div>
    </div>
    <div class="sidebar-meta">S1 Sistem Informasi · <?= htmlspecialchars($semester_aktif['tahun_akademik']) ?> <?= htmlspecialchars($semester_aktif['periode']) ?></div>
    <div class="menu-section-title">Menu Utama</div>
    <div class="nav-menu">
        <a href="dashboard.php" class="nav-item-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="kadiper_mapping.php" class="nav-item-link"><i class="fa-solid fa-diagram-project"></i> Pemetaan CPL-PL</a>
        <a href="admin_kurikulum.php" class="nav-item-link active"><i class="fa-solid fa-folder-tree"></i> Master Kurikulum</a>
        <a href="admin_mk.php" class="nav-item-link"><i class="fa-solid fa-book"></i> Mata Kuliah</a>
        <a href="admin_crud.php" class="nav-item-link"><i class="fa-solid fa-pen-to-square"></i> Kelola RPS</a>
        <a href="admin_kegiatan.php" class="nav-item-link"><i class="fa-solid fa-camera"></i> Upload Kegiatan</a>
        <a href="dosen_nilai.php" class="nav-item-link"><i class="fa-solid fa-star"></i> Input Nilai CPMK</a>
        <a href="mahasiswa_view.php" class="nav-item-link"><i class="fa-solid fa-square-poll-vertical"></i> Capaian CPL</a>
    </div>
    <div class="sidebar-footer">
        <div class="user-avatar"><?= $init_avatar ?></div>
        <div class="user-info"><h2><?= htmlspecialchars($nama_user) ?></h2><p><?= strtoupper(str_replace('_', ' ', $role)) ?></p></div>
    </div>
</div>

<div class="main-content">
    <div class="top-navbar">
        <h2 class="page-title">Master Kurikulum</h2>
        <a href="logout.php" class="btn btn-outline-danger btn-logout-top"><i class="fa-solid fa-power-off me-1"></i> Keluar</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="panel-card h-100">
                <div class="panel-header"><h3 class="panel-title"><i class="fa-solid fa-file-invoice"></i> Master SN-Dikti</h3></div>
                <form method="POST" action="admin_kurikulum.php">
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Kode SN-Dikti</label>
                        <input type="text" name="kode_sndikti" class="form-control form-control-custom" placeholder="Contoh: SN-SIKAP-02" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Deskripsi Tolok Ukur SN-Dikti</label>
                        <textarea name="deskripsi_sndikti" class="form-control form-control-custom" rows="3" placeholder="Tulis rincian deskripsi..." required></textarea>
                    </div>
                    <button type="submit" name="master_sndikti" class="btn btn-dark btn-submit-custom w-100">Simpan SN-Dikti</button>
                </form>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel-card h-100">
                <div class="panel-header"><h3 class="panel-title"><i class="fa-solid fa-book-bookmark"></i> Master Bahan Kajian (BK)</h3></div>
                <form method="POST" action="admin_kurikulum.php">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-custom">Kode BK</label>
                            <input type="text" name="kode_bk" class="form-control form-control-custom" placeholder="Contoh: BK-01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-custom">Nama Kelompok Bahan Kajian</label>
                            <input type="text" name="nama_bk" class="form-control form-control-custom" placeholder="Contoh: Software Engineering" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Deskripsi Cakupan Materi</label>
                        <textarea name="deskripsi" class="form-control form-control-custom" rows="3" placeholder="Tulis cakupan pokok bahasan..." required></textarea>
                    </div>
                    <button type="submit" name="master_bk" class="btn btn-dark btn-submit-custom w-100">Simpan Bahan Kajian</button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="panel-card">
                <div class="panel-header"><h3 class="panel-title"><i class="fa-solid fa-square-plus"></i> Form Kelola CPL Prodi</h3></div>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action_cpl" value="create">
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Kode CPL</label>
                        <input type="text" name="kode_cpl" class="form-control form-control-custom" placeholder="Contoh: CPL01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Hubungkan Referensi SN-Dikti</label>
                        <div class="bk-scroll-container" style="max-height: 120px;">
                            <?php foreach($sndikti_all as $sn): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sndikti_ids[]" value="<?= $sn['id'] ?>" id="sn_<?= $sn['id'] ?>">
                                    <label class="form-check-label small" for="sn_<?= $sn['id'] ?>"><strong><?= htmlspecialchars($sn['kode_sndikti']) ?></strong> - <?= htmlspecialchars(substr($sn['deskripsi_sndikti'], 0, 45)) ?>...</label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Deskripsi CPL prodi</label>
                        <textarea name="deskripsi" class="form-control form-control-custom" rows="3" placeholder="Tuliskan rumusan kompetensi lulusan..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-submit-custom w-100">Simpan CPL Baru</button>
                </form>
                <h6 class="border-top pt-3 fw-bold text-secondary mb-3" style="font-size: 13px;">Daftar CPL Terinput</h6>
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table-obe">
                        <thead>
                            <tr>
                                <th width="15%">Kode</th>
                                <th width="45%">Deskripsi</th>
                                <th width="25%">SN-Dikti</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($cpl_all as $cpl_row): ?>
                            <tr>
                                <td><span class="badge-cpl-code" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($cpl_row['deskripsi']) ?>"><?= htmlspecialchars($cpl_row['kode_cpl']) ?></span></td>
                                <td><small style="color: #4a5568;"><?= htmlspecialchars($cpl_row['deskripsi']) ?></small></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php
                                        $stmt_ref = $pdo->prepare("SELECT m.kode_sndikti FROM map_cpl_sndikti map JOIN master_sndikti m ON map.sndikti_id = m.id WHERE map.cpl_id = ?");
                                        $stmt_ref->execute([$cpl_row['id']]);
                                        $refs = $stmt_ref->fetchAll();
                                        foreach ($refs as $ref) { echo "<span class='badge bg-info text-dark font-monospace' style='font-size:10px;'>{$ref['kode_sndikti']}</span>"; }
                                        ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="action-icon-btn btn-edit" data-bs-toggle="modal" data-bs-target="#cplModal" onclick="openEditCpl(<?= $cpl_row['id'] ?>, '<?= htmlspecialchars($cpl_row['kode_cpl']) ?>', '<?= htmlspecialchars(addslashes($cpl_row['deskripsi'])) ?>')"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <form method="POST" onsubmit="return confirm('Hapus data CPL ini?')"><button type="submit" name="delete_cpl" value="<?= htmlspecialchars($cpl_row['id']) ?>" class="action-icon-btn btn-delete"><i class="fa-regular fa-trash-can"></i></button></form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel-card">
                <div class="panel-header"><h3 class="panel-title"><i class="fa-solid fa-square-plus"></i> Form Kelola CPMK Mata Kuliah</h3></div>
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action_cpmk" value="create">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-custom">Pilih Mata Kuliah Target</label>
                            <select name="mk_id" class="form-select form-select-custom" required>
                                <option value="">-- Pilih MK --</option>
                                <?php foreach($mk_all as $mk): ?>
                                    <option value="<?= htmlspecialchars($mk['id']) ?>">[<?= htmlspecialchars($mk['kode_mk']) ?>] <?= htmlspecialchars($mk['nama_mk']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label form-label-custom">Kode CPMK</label>
                            <input type="text" name="kode_cpmk" class="form-control form-control-custom" placeholder="Contoh: CPMK031" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Deskripsi Capaian Kompetensi (CPMK)</label>
                        <textarea name="deskripsi" class="form-control form-control-custom" rows="2" placeholder="Tuliskan sasaran kompetensi perkuliahan..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Petakan Tolak Ukur Bahan Kajian (BK)</label>
                        <div class="bk-scroll-container">
                            <?php if(empty($bk_all)): ?>
                                <span class="text-muted small">Belum ada data Bahan Kajian master di database.</span>
                            <?php else: foreach($bk_all as $bk): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="bk_ids[]" value="<?= $bk['id'] ?>" id="bk_<?= $bk['id'] ?>">
                                    <label class="form-check-label small" for="bk_<?= $bk['id'] ?>"><strong><?= htmlspecialchars($bk['kode_bk']) ?></strong> - <?= htmlspecialchars($bk['nama_bk']) ?></label>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success text-white btn-submit-custom w-100">Simpan CPMK Baru</button>
                </form>
                <h6 class="border-top pt-3 fw-bold text-secondary mb-3" style="font-size: 13px;">Daftar Komponen CPMK per Mata Kuliah</h6>
                <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                    <table class="table-obe">
                        <thead>
                            <tr>
                                <th width="25%">Mata Kuliah</th>
                                <th width="15%">CPMK</th>
                                <th width="30%">Deskripsi Target</th>
                                <th width="20%">Bahan Kajian</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($cpmk_all)): ?>
                                <tr><td colspan="5" class="text-center text-muted small">Belum ada data CPMK terisi.</td></tr>
                            <?php else: foreach($cpmk_all as $cpmk): ?>
                            <tr>
                                <td><small><strong><?= htmlspecialchars($cpmk['nama_mk']) ?></strong></small></td>
                                <td><span class="badge-cpmk-code" data-bs-toggle="tooltip" data-bs-placement="top" title="<?= htmlspecialchars($cpmk['deskripsi']) ?>"><?= htmlspecialchars($cpmk['kode_cpmk']) ?></span></td>
                                <td><small style="color: #718096;"><?= htmlspecialchars($cpmk['deskripsi']) ?></small></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php
                                        $stmt_bk_map = $pdo->prepare("SELECT b.kode_bk FROM map_cpmk_bk m JOIN bahan_kajian b ON m.bk_id = b.id WHERE m.cpmk_id = ?");
                                        $stmt_bk_map->execute([$cpmk['id']]);
                                        $mapped_bk = $stmt_bk_map->fetchAll();
                                        foreach ($mapped_bk as $mbk) { echo "<span class='badge-bk-code'>{$mbk['kode_bk']}</span>"; }
                                        ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="action-icon-btn btn-edit" data-bs-toggle="modal" data-bs-target="#cpmkModal" onclick="openEditCpmk(<?= $cpmk['id'] ?>, '<?= $cpmk['mk_id'] ?>', '<?= htmlspecialchars($cpmk['kode_cpmk']) ?>', '<?= htmlspecialchars(addslashes($cpmk['deskripsi'])) ?>')"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <form method="POST" onsubmit="return confirm('Hapus data CPMK ini?')"><button type="submit" name="delete_cpmk" value="<?= htmlspecialchars($cpmk['id']) ?>" class="action-icon-btn btn-delete"><i class="fa-regular fa-trash-can"></i></button></form>
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

<div class="modal fade" id="cplModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header"><h5 class="modal-title">Edit Data CPL Prodi</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="POST" action="admin_kurikulum.php">
                <div class="modal-body">
                    <input type="hidden" name="action_cpl" value="update"><input type="hidden" name="id" id="editCplId">
                    <div class="mb-3"><label class="form-label small fw-bold">Kode CPL</label><input type="text" name="kode_cpl" id="editCplKode" class="form-control form-control-sm" required></div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Pilih Referensi Master SN-Dikti (Multi-Pilih)</label>
                        <div class="bk-scroll-container" style="max-height: 120px;">
                            <?php foreach($sndikti_all as $sn): ?>
                                <div class="form-check">
                                    <input class="form-check-input modal-sn-checkbox" type="checkbox" name="sndikti_ids[]" value="<?= $sn['id'] ?>" id="modal_sn_<?= $sn['id'] ?>">
                                    <label class="form-check-label small" for="modal_sn_<?= $sn['id'] ?>"><strong><?= htmlspecialchars($sn['kode_sndikti']) ?></strong> - <?= htmlspecialchars(substr($sn['deskripsi_sndikti'], 0, 45)) ?>...</label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-bold">Deskripsi CPL</label><textarea name="deskripsi" id="editCplDeskripsi" class="form-control form-control-sm" rows="4" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary btn-sm">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="cpmkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header"><h5 class="modal-title">Edit Data CPMK Mata Kuliah</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="POST" action="admin_kurikulum.php">
                <div class="modal-body">
                    <input type="hidden" name="action_cpmk" value="update"><input type="hidden" name="id" id="editCpmkId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Mata Kuliah Target</label>
                            <select name="mk_id" id="editCpmkMkId" class="form-select form-select-sm" required>
                                <?php foreach($mk_all as $mk): ?>
                                    <option value="<?= htmlspecialchars($mk['id']) ?>">[<?= htmlspecialchars($mk['kode_mk']) ?>] <?= htmlspecialchars($mk['nama_mk']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label small fw-bold">Kode CPMK</label><input type="text" name="kode_cpmk" id="editCpmkKode" class="form-control form-control-sm" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-bold">Deskripsi Capaian Kompetensi (CPMK)</label><textarea name="deskripsi" id="editCpmkDeskripsi" class="form-control form-control-sm" rows="3" required></textarea></div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Petakan Tolak Ukur Bahan Kajian (BK)</label>
                        <div class="bk-scroll-container">
                            <?php foreach($bk_all as $bk): ?>
                                <div class="form-check">
                                    <input class="form-check-input modal-bk-checkbox" type="checkbox" name="bk_ids[]" value="<?= $bk['id'] ?>" id="modal_bk_<?= $bk['id'] ?>">
                                    <label class="form-check-label small" for="modal_bk_<?= $bk['id'] ?>"><strong><?= htmlspecialchars($bk['kode_bk']) ?></strong> - <?= htmlspecialchars($bk['nama_bk']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success text-white btn-sm">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl) })

    function openEditCpl(id, kode, deskripsi) {
        document.getElementById('editCplId').value = id;
        document.getElementById('editCplKode').value = kode;
        document.getElementById('editCplDeskripsi').value = deskripsi;
        var checkboxes = document.getElementsByClassName('modal-sn-checkbox');
        for (var i = 0; i < checkboxes.length; i++) { checkboxes[i].checked = false; }
        fetch('admin_kurikulum.php?get_cpl_sn=' + id)
            .then(response => response.json())
            .then(data => { data.forEach(snId => { var cb = document.getElementById('modal_sn_' + snId); if (cb) { cb.checked = true; } }); })
            .catch(error => console.error('Error:', error));
    }

    function openEditCpmk(id, mkId, kode, deskripsi) {
        document.getElementById('editCpmkId').value = id;
        document.getElementById('editCpmkMkId').value = mkId;
        document.getElementById('editCpmkKode').value = kode;
        document.getElementById('editCpmkDeskripsi').value = deskripsi;
        var checkboxes = document.getElementsByClassName('modal-bk-checkbox');
        for (var i = 0; i < checkboxes.length; i++) { checkboxes[i].checked = false; }
        fetch('admin_kurikulum.php?get_cpmk_bk=' + id)
            .then(response => response.json())
            .then(data => { data.forEach(bkId => { var cb = document.getElementById('modal_bk_' + bkId); if (cb) { cb.checked = true; } }); })
            .catch(error => console.error('Error:', error));
    }
</script>
</body>
</html>