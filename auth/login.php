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
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow border-0 p-4 rounded-4" style="width: 400px;">
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
        <p class="text-center mt-3 small text-muted">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
    </div>
</body>
</html>