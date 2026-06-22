<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
$nama_user = $_SESSION['nama'];
$user_id = $_SESSION['user_id'];

if (!in_array($role, ['admin', 'dosen', 'kepala_departemen'])) {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul ini khusus untuk Dosen Pengampu atau Admin.</div>");
}

$init_avatar = '';
$words = explode(" ", $nama_user);
foreach ($words as $w) {
    $init_avatar .= strtoupper(substr($w, 0, 1));
}
$init_avatar = substr($init_avatar, 0, 2);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_sub'])) {
        $action = $_POST['action_sub'];
        $cpmk_id = intval($_POST['cpmk_id']);
        $kode_sub_cpmk = strtoupper(trim($_POST['kode_sub_cpmk']));
        $deskripsi = trim($_POST['deskripsi']);
        $jenis_penilaian = $_POST['jenis_penilaian'];
        $bobot_persen = floatval($_POST['bobot_persen']);
        $bobot_praktikum = ($jenis_penilaian === 'Praktikum') ? floatval($_POST['bobot_praktikum']) : 0.00;

        if ($role === 'dosen') {
            $stmt_verify = $pdo->prepare("SELECT COUNT(*) FROM cpmk c JOIN mata_kuliah m ON c.mk_id = m.id JOIN kelas_kuliah k ON k.mk_id = m.id WHERE c.id = ? AND k.dosen_id = ?");
            $stmt_verify->execute([$cpmk_id, $user_id]);
            if ($stmt_verify->fetchColumn() == 0) {
                die("<div class='alert alert-danger m-3'>Akses Ilegal: Anda bukan pengampu mata kuliah ini!</div>");
            }
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO sub_cpmk (cpmk_id, kode_sub_cpmk, deskripsi, jenis_penilaian, bobot_persen, bobot_praktikum) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cpmk_id, $kode_sub_cpmk, $deskripsi, $jenis_penilaian, $bobot_persen, $bobot_praktikum]);
            $message = "Sub-CPMK baru berhasil ditambahkan ke RPS!";
        } elseif ($action === 'update') {
            $id = intval($_POST['id']);
            $stmt = $pdo->prepare("UPDATE sub_cpmk SET cpmk_id = ?, kode_sub_cpmk = ?, deskripsi = ?, jenis_penilaian = ?, bobot_persen = ?, bobot_praktikum = ? WHERE id = ?");
            $stmt->execute([$cpmk_id, $kode_sub_cpmk, $deskripsi, $jenis_penilaian, $bobot_persen, $bobot_praktikum, $id]);
            $message = "Data Sub-CPMK berhasil diperbarui!";
        }
    }

    if (isset($_POST['delete_sub'])) {
        $id = intval($_POST['delete_sub']);
        if ($role === 'dosen') {
            $stmt_verify = $pdo->prepare("SELECT COUNT(*) FROM sub_cpmk s JOIN cpmk c ON s.cpmk_id = c.id JOIN kelas_kuliah k ON k.mk_id = c.mk_id WHERE s.id = ? AND k.dosen_id = ?");
            $stmt_verify->execute([$id, $user_id]);
            if ($stmt_verify->fetchColumn() == 0) {
                die("<div class='alert alert-danger m-3'>Akses Ilegal!</div>");
            }
        }
        $stmt = $pdo->prepare("DELETE FROM sub_cpmk WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Sub-CPMK berhasil dihapus dari modul RPS.";
    }
}

if ($role === 'admin' || $role === 'kepala_departemen') {
    $cpmk_all = $pdo->query("SELECT c.*, m.nama_mk, m.kode_mk FROM cpmk c JOIN mata_kuliah m ON c.mk_id = m.id ORDER BY m.nama_mk ASC, c.kode_cpmk ASC")->fetchAll();
    $sub_all = $pdo->query("SELECT s.*, c.kode_cpmk, m.nama_mk FROM sub_cpmk s JOIN cpmk c ON s.cpmk_id = c.id JOIN mata_kuliah m ON c.mk_id = m.id ORDER BY m.nama_mk ASC, s.kode_sub_cpmk ASC")->fetchAll();
    $mk_report = $pdo->query("SELECT DISTINCT m.* FROM mata_kuliah m JOIN cpmk c ON c.mk_id = m.id ORDER BY m.nama_mk ASC")->fetchAll();
} else {
    $stmt_cpmk = $pdo->prepare("SELECT DISTINCT c.*, m.nama_mk, m.kode_mk FROM cpmk c JOIN mata_kuliah m ON c.mk_id = m.id JOIN kelas_kuliah k ON k.mk_id = m.id WHERE k.dosen_id = ? ORDER BY m.nama_mk ASC, c.kode_cpmk ASC");
    $stmt_cpmk->execute([$user_id]);
    $cpmk_all = $stmt_cpmk->fetchAll();

    $stmt_sub = $pdo->prepare("SELECT s.*, c.kode_cpmk, m.nama_mk FROM sub_cpmk s JOIN cpmk c ON s.cpmk_id = c.id JOIN mata_kuliah m ON c.mk_id = m.id JOIN kelas_kuliah k ON k.mk_id = m.id WHERE k.dosen_id = ? ORDER BY m.nama_mk ASC, s.kode_sub_cpmk ASC");
    $stmt_sub->execute([$user_id]);
    $sub_all = $stmt_sub->fetchAll();

    $stmt_mkr = $pdo->prepare("SELECT DISTINCT m.* FROM mata_kuliah m JOIN kelas_kuliah k ON k.mk_id = m.id WHERE k.dosen_id = ? ORDER BY m.nama_mk ASC");
    $stmt_mkr->execute([$user_id]);
    $mk_report = $stmt_mkr->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola RPS - SIOBE</title>
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
        .form-label-custom { font-size: 12px; font-weight: 600; color: #4a5568; margin-bottom: 6px; }
        .form-control-custom, .form-select-custom { font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e0; padding: 8px 12px; }
        .btn-submit-custom { font-size: 13px; font-weight: 500; padding: 8px 16px; border-radius: 6px; }
        .badge-sub-code { background-color: #f7fafc; border: 1px solid #cbd5e0; color: #4a5568; font-weight: 600; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
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
        <?php if (in_array($role, ['admin', 'kepala_departemen'])): ?>
            <a href="kadiper_mapping.php" class="nav-item-link"><i class="fa-solid fa-diagram-project"></i> Pemetaan OBE</a>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
            <a href="admin_kurikulum.php" class="nav-item-link"><i class="fa-solid fa-folder-tree"></i> Master Kurikulum</a>
            <a href="admin_mk.php" class="nav-item-link"><i class="fa-solid fa-book"></i> Mata Kuliah</a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'kepala_departemen', 'dosen'])): ?>
            <a href="admin_crud.php" class="nav-item-link active"><i class="fa-solid fa-pen-to-square"></i> Kelola RPS</a>
            <a href="dosen_nilai.php" class="nav-item-link"><i class="fa-solid fa-star"></i> Input Nilai CPMK</a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'kepala_departemen'])): ?>
            <a href="admin_kegiatan.php" class="nav-item-link"><i class="fa-solid fa-camera"></i> Upload Kegiatan</a>
        <?php endif; ?>
        <?php if (in_array($role, ['admin', 'mahasiswa'])): ?>
            <a href="mahasiswa_view.php" class="nav-item-link"><i class="fa-solid fa-square-poll-vertical"></i> Capaian CPL</a>
        <?php endif; ?>
    </div>
    <div class="sidebar-footer">
        <div class="user-avatar"><?= $init_avatar ?></div>
        <div class="user-info"><h2><?= htmlspecialchars($nama_user) ?></h2><p><?= strtoupper(str_replace('_', ' ', $role)) ?></p></div>
    </div>
</div>

<div class="main-content">
    <div class="top-navbar">
        <h2 class="page-title">Kelola RPS (Sub-CPMK Per Mata Kuliah Pengampu)</h2>
        <a href="logout.php" class="btn btn-outline-danger btn-logout-top"><i class="fa-solid fa-power-off me-1"></i> Keluar</a>
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
                <div class="panel-header"><h3 class="panel-title"><i class="fa-solid fa-square-plus"></i> Tambah Sub-CPMK</h3></div>
                <form method="POST" action="admin_crud.php">
                    <input type="hidden" name="action_sub" value="create">
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Pilih Induk CPMK MK</label>
                        <select name="cpmk_id" class="form-select form-select-custom" required>
                            <option value="">-- Pilih CPMK --</option>
                            <?php foreach($cpmk_all as $cpmk): ?>
                                <option value="<?= $cpmk['id'] ?>">[<?= htmlspecialchars($cpmk['nama_mk']) ?>] - <?= htmlspecialchars($cpmk['kode_cpmk']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Kode Sub-CPMK</label>
                        <input type="text" name="kode_sub_cpmk" class="form-control form-control-custom" placeholder="Contoh: SUB-CPMK1.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Jenis Penilaian</label>
                        <select name="jenis_penilaian" id="jenisPenilaianForm" class="form-select form-select-custom" onchange="toggleBobotFields(this.value, 'boxBobotTeori', 'boxBobotPraktikum', 'labelTeori')" required>
                            <option value="Teori">Teori Saja</option>
                            <option value="Praktikum">Teori & Praktikum</option>
                        </select>
                    </div>
                    <div class="mb-3" id="boxBobotTeori">
                        <label class="form-label form-label-custom" id="labelTeori">Bobot Penilaian (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="bobot_persen" class="form-control form-control-custom" placeholder="Contoh: 15" required>
                    </div>
                    <div class="mb-3 d-none" id="boxBobotPraktikum">
                        <label class="form-label form-label-custom">Bobot Praktikum (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="bobot_praktikum" id="inputBobotPraktikum" class="form-control form-control-custom" placeholder="Contoh: 10">
                    </div>
                    <div class="mb-3">
                        <label class="form-label form-label-custom">Deskripsi Sub-Capaian</label>
                        <textarea name="deskripsi" class="form-control form-control-custom" rows="3" placeholder="Tulis kemampuan akhir yang direncanakan..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-submit-custom w-100">Simpan Sub-CPMK</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="panel-card">
                <div class="panel-header">
                    <h3 class="panel-title"><i class="fa-solid fa-list-ol"></i> Matriks Capaian Pembelajaran RPS (Sub-CPMK)</h3>
                    <button type="button" class="btn btn-outline-danger btn-sm rounded-2 px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#downloadReportModal">
                        <i class="fa-solid fa-print me-1"></i> Cetak Laporan RPS
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table-obe">
                        <thead>
                            <tr>
                                <th width="20%">Mata Kuliah</th>
                                <th width="12%">Induk CPMK</th>
                                <th width="15%">Kode Sub</th>
                                <th width="13%">Jenis</th>
                                <th width="25%">Deskripsi Kemampuan</th>
                                <th width="10%">Bobot</th>
                                <th width="5%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($sub_all)): ?>
                                <tr><td colspan="7" class="text-center text-muted small">Belum ada rincian Sub-CPMK perkuliahan terdata di bawah hak pengampuan Anda.</td></tr>
                            <?php else: foreach($sub_all as $sub): ?>
                            <tr>
                                <td><small><strong><?= htmlspecialchars($sub['nama_mk']) ?></strong></small></td>
                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($sub['kode_cpmk']) ?></span></td>
                                <td><span class="badge-sub-code"><?= htmlspecialchars($sub['kode_sub_cpmk']) ?></span></td>
                                <td><span class="badge <?= (isset($sub['jenis_penilaian']) && $sub['jenis_penilaian'] === 'Praktikum') ? 'bg-purple text-white' : 'bg-secondary text-white' ?> font-size-11"><?= isset($sub['jenis_penilaian']) ? htmlspecialchars($sub['jenis_penilaian']) : 'Teori' ?></span></td>
                                <td><small style="color:#4a5568;"><?= htmlspecialchars($sub['deskripsi']) ?></small></td>
                                <td>
                                    <?php if(isset($sub['jenis_penilaian']) && $sub['jenis_penilaian'] === 'Praktikum'): ?>
                                        <small class="d-block">T: <?= htmlspecialchars($sub['bobot_persen']) ?>%</small>
                                        <small class="d-block">P: <?= htmlspecialchars($sub['bobot_praktikum']) ?>%</small>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($sub['bobot_persen']) ?>%</strong>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="action-icon-btn btn-edit" data-bs-toggle="modal" data-bs-target="#subModal" onclick="openEditSub(<?= $sub['id'] ?>, <?= $sub['cpmk_id'] ?>, '<?= htmlspecialchars($sub['kode_sub_cpmk']) ?>', '<?= isset($sub['jenis_penilaian']) ? $sub['jenis_penilaian'] : 'Teori' ?>', <?= $sub['bobot_persen'] ?>, <?= isset($sub['bobot_praktikum']) ? $sub['bobot_praktikum'] : 0 ?>, '<?= htmlspecialchars(addslashes($sub['deskripsi'])) ?>')"><i class="fa-regular fa-pen-to-square"></i></button>
                                        <form method="POST" onsubmit="return confirm('Hapus data Sub-CPMK ini?')"><button type="submit" name="delete_sub" value="<?= $sub['id'] ?>" class="action-icon-btn btn-delete"><i class="fa-regular fa-trash-can"></i></button></form>
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

<div class="modal fade" id="subModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header"><h5 class="modal-title">Edit Data Sub-CPMK Per perkuliahan</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="POST" action="admin_crud.php">
                <div class="modal-body">
                    <input type="hidden" name="action_sub" value="update"><input type="hidden" name="id" id="editSubId">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">CPMK Target</label>
                        <select name="cpmk_id" id="editSubCpmkId" class="form-select form-select-sm" required>
                            <?php foreach($cpmk_all as $cpmk): ?>
                                <option value="<?= $cpmk['id'] ?>">[<?= htmlspecialchars($cpmk['nama_mk']) ?>] - <?= htmlspecialchars($cpmk['kode_cpmk']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Kode Sub-CPMK</label>
                        <input type="text" name="kode_sub_cpmk" id="editSubKode" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Jenis Penilaian</label>
                        <select name="jenis_penilaian" id="editJenisPenilaian" class="form-select form-select-sm" onchange="toggleBobotFields(this.value, 'modalBoxTeori', 'modalBoxPraktikum', 'modalLabelTeori')" required>
                            <option value="Teori">Teori Saja</option>
                            <option value="Praktikum">Teori & Praktikum</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3" id="modalBoxTeori">
                            <label class="form-label small fw-bold" id="modalLabelTeori">Bobot (%)</label>
                            <input type="number" step="0.01" name="bobot_persen" id="editSubBobot" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-6 mb-3" id="modalBoxPraktikum">
                            <label class="form-label small fw-bold">Bobot Praktikum (%)</label>
                            <input type="number" step="0.01" name="bobot_praktikum" id="editSubBobotPraktikum" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label small fw-bold">Deskripsi Sub-CPMK</label><textarea name="deskripsi" id="editSubDeskripsi" class="form-control form-control-sm" rows="3" required></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-success text-white btn-sm">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="downloadReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-print text-danger me-2"></i>Cetak Dokumen RPS OBE</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="GET" action="cetak_rps.php" target="_blank">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Pilih Mata Kuliah Sasaran Cetak</label>
                        <select name="id" class="form-select form-select-custom" required>
                            <option value="">-- Pilih Mata Kuliah --</option>
                            <?php foreach($mk_report as $mkr): ?>
                                <option value="<?= $mkr['id'] ?>">[<?= htmlspecialchars($mkr['kode_mk']) ?>] <?= htmlspecialchars($mkr['nama_mk']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm font-weight-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm font-weight-bold px-3"><i class="fa-solid fa-print me-1"></i>Buka Halaman Cetak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function toggleBobotFields(val, boxTeoriId, boxPraktikumId, labelTeoriId) {
        const boxTeori = document.getElementById(boxTeoriId);
        const boxPraktikum = document.getElementById(boxPraktikumId);
        const labelTeori = document.getElementById(labelTeoriId);
        
        if(val === 'Praktikum') {
            boxPraktikum.classList.remove('d-none');
            if(boxPraktikumId === 'boxBobotPraktikum') {
                document.getElementById('inputBobotPraktikum').setAttribute('required', 'required');
            } else {
                document.getElementById('editSubBobotPraktikum').setAttribute('required', 'required');
            }
            labelTeori.innerText = "Bobot Teori (%)";
            boxTeori.className = boxTeoriId === 'modalBoxTeori' ? 'col-md-6 mb-3' : 'mb-3';
        } else {
            boxPraktikum.classList.add('d-none');
            if(boxBobotPraktikum === 'boxBobotPraktikum') {
                document.getElementById('inputBobotPraktikum').removeAttribute('required');
            } else {
                document.getElementById('editSubBobotPraktikum').removeAttribute('required');
            }
            labelTeori.innerText = boxTeoriId === 'modalBoxTeori' ? "Bobot (%)" : "Bobot Penilaian (%)";
            boxTeori.className = boxTeoriId === 'modalBoxTeori' ? 'col-md-12 mb-3' : 'mb-3';
        }
    }

    function openEditSub(id, cpmkId, kode, jenis, bobotTeori, bobotPraktikum, deskripsi) {
        document.getElementById('editSubId').value = id;
        document.getElementById('editSubCpmkId').value = cpmkId;
        document.getElementById('editSubKode').value = kode;
        document.getElementById('editJenisPenilaian').value = jenis;
        document.getElementById('editSubBobot').value = bobotTeori;
        document.getElementById('editSubBobotPraktikum').value = bobotPraktikum;
        document.getElementById('editSubDeskripsi').value = deskripsi;
        
        toggleBobotFields(jenis, 'modalBoxTeori', 'modalBoxPraktikum', 'modalLabelTeori');
    }
</script>
</body>
</html>
