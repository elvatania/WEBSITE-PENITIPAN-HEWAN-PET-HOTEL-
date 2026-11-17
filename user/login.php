<?php
session_start();
if (isset($_SESSION['id_pelanggan'])) {
    header("Location: ../user/riwayat_pesanan.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Pet Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .bg-video {
            position: fixed;
            right: 0;
            bottom: 0;
            min-width: 100%;
            min-height: 100%;
            object-fit: cover;
            z-index: -1;
        }
        .card.glass-card {
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
        }
        .glass-card .form-label, 
        .glass-card h3 {
            color: #f8f9fa;
        }
        .glass-card p, 
        .glass-card a {
            color: #d1d5db;
        }
        .glass-card .form-control {
            background-color: transparent;
            color: #fff;
            border-color: rgba(255, 255, 255, 0.5);
        }
        .glass-card .form-control::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .glass-card .form-control:focus {
            background-color: transparent;
            color: #fff;
            border-color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100 position-relative overflow-hidden">
    <video autoplay muted loop class="bg-video">
        <source src="../config/assets/video/anjing.mp4" type="video/mp4">
        Browser Anda tidak mendukung tag video.
    </video>
    <div class="card glass-card shadow p-4 rounded-4" style="width: 400px; z-index: 1;">
        <h3 class="fw-bold mb-3 text-center">Login Pet Hotel</h3>
        <form action="proses_login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" placeholder="nama@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Masuk</button>
        </form>
        <p class="text-center mt-3 small">Belum punya akun? <a href="register.php" class="text-decoration-underline">Daftar di sini</a></p>
    </div>

</body>
</html>