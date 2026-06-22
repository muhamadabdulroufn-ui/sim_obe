<?php
$host = 'localhost';
$db   = 'sim_obe';
$user = 'root';
$pass = ''; 
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     die("Koneksi database gagal: " . $e->getMessage());
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$stmt_sem = $pdo->query("SELECT * FROM semester_akademik WHERE is_aktif = 1 LIMIT 1");
$semester_aktif = $stmt_sem->fetch();
if (!$semester_aktif) {
    die("Sistem Error: Tidak ada Semester Akademik berjalan yang diset AKTIF oleh Admin.");
}
?>