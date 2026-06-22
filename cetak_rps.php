<?php
require 'config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID Mata Kuliah tidak ditemukan.");
}

$id_mk = intval($_GET['id']);

$stmt = $pdo->prepare("SELECT * FROM mata_kuliah WHERE id = ?");
$stmt->execute([$id_mk]);
$mk = $stmt->fetch();

if (!$mk) {
    die("Mata kuliah tidak ditemukan.");
}

$stmt_cpmk = $pdo->prepare("SELECT * FROM cpmk WHERE mk_id = ? ORDER BY kode_cpmk ASC");
$stmt_cpmk->execute([$id_mk]);
$cpmks = $stmt_cpmk->fetchAll();

$stmt_sub = $pdo->prepare("SELECT s.*, c.kode_cpmk FROM sub_cpmk s JOIN cpmk c ON s.cpmk_id = c.id WHERE c.mk_id = ? ORDER BY s.kode_sub_cpmk ASC");
$stmt_sub->execute([$id_mk]);
$subs = $stmt_sub->fetchAll();

$stmt_dosen = $pdo->prepare("SELECT DISTINCT u.nama_lengkap FROM kelas_kuliah k JOIN users u ON k.dosen_id = u.id WHERE k.mk_id = ?");
$stmt_dosen->execute([$id_mk]);
$dosen_ampu_list = $stmt_dosen->fetchAll(PDO::FETCH_COLUMN);
$dosen_pengampu = !empty($dosen_ampu_list) ? implode(', ', $dosen_ampu_list) : '-';

$stmt_kaprodi = $pdo->query("SELECT nama_lengkap FROM users WHERE role IN ('kepala_departemen', 'kepala_prodi', 'kaprodi') LIMIT 1");
$data_kaprodi = $stmt_kaprodi->fetch();
$nama_kaprodi = $data_kaprodi ? $data_kaprodi['nama_lengkap'] : '-';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>RPS - <?= htmlspecialchars($mk['nama_mk']) ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 11px; color: #000; padding: 20px; line-height: 1.3; }
        table { width: 100%; border-collapse: collapse; margin-bottom: -1px; }
        td, th { border: 1px solid #000000; padding: 6px; vertical-align: top; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        .bg-gray { background-color: #f2f2f2; }
        .header-title { font-size: 12px; font-weight: bold; text-transform: uppercase; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>

<table style="margin-bottom: 15px;">
    <tr>
        <td width="15%" class="text-center" style="vertical-align: middle;">
            <div class="fw-bold" style="font-size: 14px; padding: 15px 0;">LOGO</div>
        </td>
        <td width="65%" class="text-center" style="vertical-align: middle;">
            <div class="header-title">UNIVERSITAS NAHDLATUL ULAMA SURABAYA</div>
            <div class="header-title">FAKULTAS EKONOMI BISNIS DAN TEKNOLOGI DIGITAL</div>
            <div class="header-title" style="font-size: 11px;">PROGRAM STUDI S1 SISTEM INFORMASI</div>
        </td>
        <td width="20%" style="vertical-align: middle;">
            <div class="fw-bold">KODE DOKUMEN</div>
            <div style="margin-top: 5px; font-family: monospace;">RPS-SI-<?= htmlspecialchars($mk['kode_mk']) ?></div>
        </td>
    </tr>
    <tr>
        <td colspan="3" class="text-center bg-gray fw-bold" style="font-size: 12px; padding: 5px;">RENCANA PEMBELAJARAN SEMESTER</td>
    </tr>
</table>

<table>
    <tr class="bg-gray text-center fw-bold">
        <td width="25%">MATA KULIAH (MK)</td>
        <td width="15%">Kode</td>
        <td width="20%">Bahan Kajian (BK)</td>
        <td width="15%">BOBOT (sks)</td>
        <td width="12%">SEMESTER</td>
        <td width="13%">Tanggal Penyusunan</td>
    </tr>
    <tr>
        <td class="fw-bold"><?= htmlspecialchars($mk['nama_mk']) ?></td>
        <td class="text-center font-monospace"><?= htmlspecialchars($mk['kode_mk']) ?></td>
        <td>
            <?php
            $bk_unique = [];
            foreach ($cpmks as $cp) {
                $stmt_bk = $pdo->prepare("SELECT b.kode_bk FROM map_cpmk_bk m JOIN bahan_kajian b ON m.bk_id = b.id WHERE m.cpmk_id = ?");
                $stmt_bk->execute([$cp['id']]);
                foreach ($stmt_bk->fetchAll(PDO::FETCH_COLUMN) as $kbk) {
                    $bk_unique[] = $kbk;
                }
            }
            echo htmlspecialchars(implode(', ', array_unique($bk_unique)));
            ?>
        </td>
        <td>
            <div>T (Teori) = <?= htmlspecialchars($mk['sks']) ?></div>
            <div>P (Praktikum) = 0</div>
        </td>
        <td class="text-center"><?= htmlspecialchars($mk['semester_target']) ?></td>
        <td class="text-center"><?= date('d F Y') ?></td>
    </tr>
    <tr class="bg-gray fw-bold">
        <td class="text-center" style="vertical-align: middle;">PENGESAHAN</td>
        <td>Dosen Pengembang RPS</td>
        <td colspan="2">Koordinator BK (jika ada)</td>
        <td colspan="2">Ka Prodi</td>
    </tr>
    <tr style="height: 45px;">
        <td class="bg-gray"></td>
        <td class="text-center" style="vertical-align: bottom;"><small><?= htmlspecialchars($dosen_pengampu) ?></small></td>
        <td colspan="2" class="text-center" style="vertical-align: bottom;"><small>TTD Koordinator BK</small></td>
        <td colspan="2" class="text-center" style="vertical-align: bottom;"><small><?= htmlspecialchars($nama_kaprodi) ?></small></td>
    </tr>
</table>

<table style="margin-top: 15px;">
    <tr>
        <td width="15%" rowspan="4" class="bg-gray fw-bold text-center" style="vertical-align: middle;">Capaian Pembelajaran</td>
        <td colspan="2" class="bg-gray fw-bold">CPL-PRODI yang dibebankan pada MK</td>
    </tr>
    <tr>
        <td colspan="2">
            <?php
            $cpl_unique = [];
            foreach($cpmks as $cp) {
                $stmt_cpl = $pdo->prepare("SELECT c.kode_cpl, c.deskripsi FROM map_cpmk_cpl mc JOIN cpl c ON mc.cpl_id = c.id WHERE mc.cpmk_id = ?");
                $stmt_cpl->execute([$cp['id']]);
                foreach($stmt_cpl->fetchAll() as $cpl_r) {
                    $cpl_unique[$cpl_r['kode_cpl']] = $cpl_r['deskripsi'];
                }
            }
            if(empty($cpl_unique)) {
                echo "<small class='text-muted'>Data referensi pemetaan CPL Prodi belum tersedia.</small>";
            } else {
                foreach($cpl_unique as $k_cpl => $d_cpl) {
                    echo "<div><strong>" . htmlspecialchars($k_cpl) . "</strong>: " . htmlspecialchars($d_cpl) . "</div>";
                }
            }
            ?>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="bg-gray fw-bold">Capaian Pembelajaran Mata Kuliah (CPMK)</td>
    </tr>
    <tr>
        <td colspan="2">
            <?php if(empty($cpmks)): ?>
                <small class='text-muted'>Belum ada data CPMK.</small>
            <?php else: foreach($cpmks as $cpmk): ?>
                <div><strong><?= htmlspecialchars($cpmk['kode_cpmk']) ?></strong>: <?= htmlspecialchars($cpmk['deskripsi']) ?></div>
            <?php endforeach; endif; ?>
        </td>
    </tr>
    <tr>
        <td class="bg-gray fw-bold text-center" style="vertical-align: middle;">Kemampuan Akhir tiap tahapan belajar (Sub-CPMK)</td>
        <td colspan="2">
            <?php if(empty($subs)): ?>
                <small class='text-muted'>Belum ada data Sub-CPMK.</small>
            <?php else: foreach($subs as $sub): ?>
                <div><strong><?= htmlspecialchars($sub['kode_sub_cpmk']) ?></strong>: <?= htmlspecialchars($sub['deskripsi']) ?></div>
            <?php endforeach; endif; ?>
        </td>
    </tr>
    <tr>
        <td class="bg-gray fw-bold text-center" style="vertical-align: middle;">Korelasi CPMK terhadap Sub-CPMK</td>
        <td colspan="2" style="padding: 0;">
            <table style="border: none; width: 100%;">
                <thead>
                    <tr class="bg-gray text-center fw-bold">
                        <td style="border-top: none; border-left: none;"></td>
                        <?php foreach($subs as $sub): ?>
                            <td style="border-top: none; font-size: 9px;"><?= htmlspecialchars($sub['kode_sub_cpmk']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cpmks as $cpmk): ?>
                    <tr>
                        <td class="fw-bold text-center" style="border-left: none;"><?= htmlspecialchars($cpmk['kode_cpmk']) ?></td>
                        <?php foreach($subs as $sub): 
                            $is_relasi = ($sub['cpmk_id'] == $cpmk['id']) ? 'V' : '';
                        ?>
                            <td class="text-center"><?= $is_relasi ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td class="bg-gray fw-bold text-center" style="vertical-align: middle;">Deskripsi singkat MK</td>
        <td colspan="2">Mata kuliah ini memberikan pengalaman kepada mahasiswa untuk menyelaraskan capaian luaran pembelajaran berdasarkan standar kompetensi OBE prodi S1 Sistem Informasi Universitas Nahdlatul Ulama Surabaya.</td>
    </tr>
    <tr>
        <td class="bg-gray fw-bold text-center" style="vertical-align: middle;">Bahan Kajian: Materi Pembelajaran</td>
        <td colspan="2">
            <?php
            foreach($cpmks as $cp) {
                $stmt_bk = $pdo->prepare("SELECT b.kode_bk, b.nama_bk, b.deskripsi FROM map_cpmk_bk m JOIN bahan_kajian b ON m.bk_id = b.id WHERE m.cpmk_id = ?");
                $stmt_bk->execute([$cp['id']]);
                $bks = $stmt_bk->fetchAll();
                foreach($bks as $bk) {
                    echo "<div><strong>[" . htmlspecialchars($bk['kode_bk']) . "] " . htmlspecialchars($bk['nama_bk']) . "</strong>: " . htmlspecialchars($bk['deskripsi']) . "</div>";
                }
            }
            ?>
        </td>
    </tr>
    <tr>
        <td rowspan="2" class="bg-gray fw-bold text-center" style="vertical-align: middle;">Pustaka</td>
        <td width="15%" class="bg-gray fw-bold">Utama:</td>
        <td>1. Buku Ajar Utama Pengembangan Sistem Informasi Terintegrasi S1 UNUSA.</td>
    </tr>
    <tr>
        <td class="bg-gray fw-bold">Pendukung:</td>
        <td>2. Jurnal Ilmiah Internasional Terindeks Rekayasa Perangkat Lunak dan Jaringan Kerja Tata Kelola IT.</td>
    </tr>
    <tr>
        <td class="bg-gray fw-bold text-center" style="vertical-align: middle;">Dosen Pengampu</td>
        <td colspan="2"><?= htmlspecialchars($dosen_pengampu) ?></td>
    </tr>
    <tr>
        <td class="bg-gray fw-bold text-center" style="vertical-align: middle;">Mata Kuliah Prasyarat</td>
        <td colspan="2">-</td>
    </tr>
</table>

<table style="margin-top: 15px;">
    <thead>
        <tr class="bg-gray text-center fw-bold">
            <td width="8%">Minggu ke-</td>
            <td width="17%">Sub CPMK</td>
            <td width="20%">Induk CPMK</td>
            <td width="15%">Jenis Penilaian</td>
            <td width="30%">Deskripsi Kemampuan</td>
            <td width="10%">Bobot Penilaian (%)</td>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($subs)): ?>
            <tr><td colspan="6" class="text-center text-muted small">Belum ada rincian tahapan indikator penilaian belajar.</td></tr>
        <?php else: $i = 1; foreach($subs as $sub): ?>
            <tr>
                <td class="text-center"><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($sub['kode_sub_cpmk']) ?></strong></td>
                <td class="text-center"><span class="badge bg-light text-dark border"><?= htmlspecialchars($sub['kode_cpmk']) ?></span></td>
                <td class="text-center"><?= isset($sub['jenis_penilaian']) ? htmlspecialchars($sub['jenis_penilaian']) : 'Teori' ?></td>
                <td><?= htmlspecialchars($sub['deskripsi']) ?></td>
                <td class="text-center fw-bold">
                    <?php if(isset($sub['jenis_penilaian']) && $sub['jenis_penilaian'] === 'Praktikum'): ?>
                        <div>T: <?= htmlspecialchars($sub['bobot_persen']) ?>%</div>
                        <div>P: <?= htmlspecialchars($sub['bobot_praktikum']) ?>%</div>
                    <?php else: ?>
                        <?= htmlspecialchars($sub['bobot_persen']) ?>%
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<div class="no-print" style="margin-top: 20px; text-align: right;">
    <button onclick="window.print()" style="padding: 8px 20px; font-weight: bold; background-color: #000; color: #ffffff; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">Cetak Dokumen RPS</button>
</div>

<script>
    window.onload = function() { window.print(); }
</script>
</body>
</html>