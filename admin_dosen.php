<?php
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$role = strtolower(trim($_SESSION['role']));
if (!in_array($role, ['admin', 'kepala_departemen'])) {
    die("<div class='alert alert-danger m-3'>Akses Ditolak! Modul CRUD Data Dosen ini khusus untuk Admin atau Kepala Departemen.</div>");
}

$message = '';
$edit_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_dosen'])) {
        $type = $_POST['action_dosen'];
        $username = trim($_POST['username']);
        $nama_lengkap = trim($_POST['nama_lengkap']);
        $nomor_induk = strtoupper(trim($_POST['nomor_induk']));
        $target_role = $_POST['role'];

        if ($type === 'create') {
            $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, nama_lengkap, nomor_induk, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$username, $password, $nama_lengkap, $nomor_induk, $target_role]);
                $message = "Data pengajar baru berhasil didaftarkan ke sistem!";
            } catch (PDOException $e) {
                $message = "Error: Username atau NIDN sudah terdaftar.";
            }
        } elseif ($type === 'update') {
            $id = $_POST['id'];
            try {
                if (!empty($_POST['password'])) {
                    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, nama_lengkap = ?, nomor_induk = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $password, $nama_lengkap, $nomor_induk, $target_role, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, nama_lengkap = ?, nomor_induk = ?, role = ? WHERE id = ?");
                    $stmt->execute([$username, $nama_lengkap, $nomor_induk, $target_role, $id]);
                }
                $message = "Data dosen/kadep berhasil diperbarui!";
            } catch (PDOException $e) {
                $message = "Error: Gagal memperbarui data dosen.";
            }
        }
    }

    if (isset($_POST['delete_dosen'])) {
        $id = $_POST['delete_dosen'];
        if (intval($id) === intval($_SESSION['user_id'])) {
            $message = "Error: Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "Data dosen berhasil dihapus dari sistem.";
        }
    }
}

if (isset($_GET['edit'])) {
    $id_edit = $_GET['edit'];
    $stmt_edit = $pdo->prepare("SELECT id, username, nama_lengkap, nomor_induk, role FROM users WHERE id = ? AND role IN ('dosen', 'kepala_departemen')");
    $stmt_edit->execute([$id_edit]);
    $edit_data = $stmt_edit->fetch();
}

$dosen_all = $pdo->query("SELECT id, username, nama_lengkap, nomor_induk, role FROM users WHERE role IN ('dosen', 'kepala_departemen') ORDER BY nama_lengkap ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CRUD Data Dosen - SIM_OBE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .cursor-help { cursor: help; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded">
        <div>
            <h4 class="text-primary mb-0">Manajemen Pengguna Dosen & Kepala Departemen</h4>
            <small class="text-muted">Kelola data master kredensial login, nama, dan NIDN pengajar</small>
        </div>
        <a href="dashboard.php" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
    </div>

    <?php if($message): ?> 
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
        </div> 
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-bold">
                    <?= $edit_data ? 'Form Edit Data Dosen' : 'Form Tambah Dosen Baru' ?>
                </div>
                <div class="card-body">
                    <form method="POST" action="admin_dosen.php">
                        <input type="hidden" name="action_dosen" value="<?= $edit_data ? 'update' : 'create' ?>">
                        <?php if($edit_data): ?>
                            <input type="hidden" name="id" value="<?= htmlspecialchars($edit_data['id']) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Nama Lengkap beserta Gelar</label>
                            <input type="text" name="nama_lengkap" class="form-control form-control-sm" placeholder="Contoh: Endang Sulistiyani, S.Kom., M.T." value="<?= $edit_data ? htmlspecialchars($edit_data['nama_lengkap']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">NIDN / Nomor Induk Dosen</label>
                            <input type="text" name="nomor_induk" class="form-control form-control-sm" placeholder="Contoh: NIDN-002" value="<?= $edit_data ? htmlspecialchars($edit_data['nomor_induk']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username Akses Login</label>
                            <input type="text" name="username" class="form-control form-control-sm" placeholder="Contoh: endang" value="<?= $edit_data ? htmlspecialchars($edit_data['username']) : '' ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Akun</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="<?= $edit_data ? 'Kosongkan jika tidak ingin diubah' : 'Masukkan password' ?>" <?= $edit_data ? '' : 'required' ?>>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Peran Kewenangan (Role)</label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="dosen" <?= ($edit_data && $edit_data['role'] === 'dosen') ? 'selected' : '' ?>>Dosen Pengampu</option>
                                <option value="kepala_departemen" <?= ($edit_data && $edit_data['role'] === 'kepala_departemen') ? 'selected' : '' ?>>Kepala Departemen</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn <?= $edit_data ? 'btn-warning' : 'btn-primary' ?> w-100 btn-sm text-white">
                                <?= $edit_data ? 'Perbarui Akun' : 'Daftarkan Akun' ?>
                            </button>
                            <?php if($edit_data): ?>
                                <a href="admin_dosen.php" class="btn btn-secondary w-100 btn-sm">Batal</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white fw-bold">Daftar Dosen & Kepala Departemen Terregistrasi</div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-striped align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th>NIDN</th>
                                    <th>Nama Pengajar</th>
                                    <th>Username</th>
                                    <th>Role Sistem</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($dosen_all)): ?>
                                    <tr><td colspan="5" class="text-center text-muted small">Belum ada data dosen terdaftar.</td></tr>
                                <?php else: foreach($dosen_all as $d): ?>
                                <tr>
                                    <td class="text-center"><strong><?= htmlspecialchars($d['nomor_induk']) ?></strong></td>
                                    <td><span class="cursor-help" data-bs-toggle="tooltip" data-bs-placement="top" title="Nama Resmi: <?= htmlspecialchars($d['nama_lengkap']) ?>"><?= htmlspecialchars($d['nama_lengkap']) ?></span></td>
                                    <td class="text-center">`<?= htmlspecialchars($d['username']) ?>`</td>
                                    <td class="text-center">
                                        <span class="badge <?= $d['role'] === 'kepala_departemen' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= $d['role'] === 'kepala_departemen' ? 'KADEP' : 'DOSEN' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="admin_dosen.php?edit=<?= htmlspecialchars($d['id']) ?>" class="btn btn-warning p-1 py-0 small text-white" style="font-size: 11px;">Edit</a>
                                            <form method="POST" onsubmit="return confirm('Hapus pengguna dosen ini? Seluruh riwayat plotting kelas yang bersangkutan di sistem akan ikut terhapus.')" style="display:inline;">
                                                <button type="submit" name="delete_dosen" value="<?= htmlspecialchars($d['id']) ?>" class="btn btn-danger p-1 py-0 small" style="font-size: 11px;">Hapus</button>
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