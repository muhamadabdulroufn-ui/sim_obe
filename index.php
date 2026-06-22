<?php
require 'config.php';
$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama_lengkap'];
        $_SESSION['role'] = strtolower(trim($user['role']));
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Username atau password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIOBE PORTAL</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .bg-image {
            background-image: url('dosen_si.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100%;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .bg-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1;
        }
        .login-container {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 40px 35px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
            z-index: 2;
            position: relative;
        }
        .portal-logo {
            max-width: 130px;
            height: auto;
            display: block;
            margin: 0 auto 15px auto;
        }
        .portal-title {
            color: #112d62;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .portal-subtitle {
            color: #6c757d;
            font-size: 13px;
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 600;
            color: #333333;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #112d62;
            box-shadow: 0 0 0 0.25rem rgba(17, 45, 98, 0.25);
        }
        .btn-masuk {
            background-color: #112d62;
            border: none;
            border-radius: 25px;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: background-color 0.2s ease;
            color: #ffffff;
        }
        .btn-masuk:hover {
            background-color: #0b1e43;
            color: #ffffff;
        }
        .footer-text {
            font-size: 13px;
            color: #6c757d;
        }
        .footer-link {
            color: #112d62;
            text-decoration: none;
            font-weight: 600;
        }
        .footer-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="bg-image">
    <div class="bg-overlay"></div>

    <div class="login-container text-center">
        <img src="logo_unusa.png" alt="Logo UNUSA" class="portal-logo">
        
        <h3 class="portal-title">SIOBE PORTAL</h3>
        <p class="portal-subtitle">Fakultas Ekonomi Bisnis & Teknologi Digital</p>

        <?php if($error): ?> 
            <div class="alert alert-danger p-2 small text-start mb-3"><?= htmlspecialchars($error) ?></div> 
        <?php endif; ?>

        <form method="POST" action="index.php" class="text-start">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
            </div>
            <button type="submit" class="btn btn-masuk w-100 mb-4">
                <i class="fa-solid fa-right-to-bracket me-2"></i> MASUK SISTEM
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
