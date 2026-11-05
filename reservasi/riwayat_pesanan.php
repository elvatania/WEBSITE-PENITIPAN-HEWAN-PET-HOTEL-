<?php
session_start();
include "../config/koneksi.php";

// Cek apakah user sudah login
if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_pelanggan = $_SESSION['id_pelanggan'];
$nama_user    = $_SESSION['nama_pelanggan'];
$email_user   = $_SESSION['email'];

// Query mengambil riwayat pesanan user
$query_riwayat = mysqli_query($koneksi, "SELECT reservasi.*, kamar.nama_kamar, hewan.nama_hewan 
                                         FROM reservasi 
                                         JOIN kamar ON reservasi.id_kamar = kamar.id_kamar 
                                         JOIN hewan ON reservasi.id_hewan = hewan.id_hewan 
                                         WHERE reservasi.id_pelanggan = '$id_pelanggan' 
                                         ORDER BY reservasi.id_reservasi DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Riwayat Pesanan Saya - Pet Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">

    <!-- Header / Navbar Profil -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-primary" href="index.php">🐾 Pet Hotel</a>
            
            <div class="d-flex align-items-center">
                <!-- Dropdown Pop-up Profil (Sesuai Referensi Gambar) -->
                <div class="dropdown">
                    <button class="btn btn-light border rounded-pill px-3 py-1 dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">
                            <?= strtoupper(substr($nama_user, 0, 1)); ?>
                        </span>
                        <span class="fw-semibold"><?= htmlspecialchars($nama_user); ?></span>
                    </button>

                    <div class="dropdown-menu dropdown-menu-end shadow border-0 p-3 rounded-4" style="width: 280px;">
                        <small class="text-muted d-block mb-2">Saat ini menggunakan</small>
                        
                        <!-- Box Detail Profil User -->
                        <div class="border rounded-3 p-2 d-flex align-items-center gap-3 mb-3 bg-light">
                            <span class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center fs-4 fw-bold" style="width: 48px; height: 48px;">
                                <?= strtoupper(substr($nama_user, 0, 1)); ?>
                            </span>
                            <div class="overflow-hidden">
                                <h6 class="mb-0 fw-bold text-dark text-truncate"><?= htmlspecialchars($nama_user); ?></h6>
                                <small class="text-muted d-block text-truncate"><?= htmlspecialchars($email_user); ?></small>
                            </div>
                        </div>

                        <hr class="dropdown-divider">

                        <!-- Tombol Logout -->
                        <a class="dropdown-item rounded py-2 text-danger fw-semibold d-flex align-items-center gap-2" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right fs-5"></i> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Halaman Riwayat Pesanan -->
    <div class="container py-5">
        <h2 class="h4 fw-bold mb-1">Riwayat Pesanan Saya</h2>
        <p class="text-muted mb-4">Halo, <strong><?= htmlspecialchars($nama_user); ?></strong>. Berikut daftar reservasi anabul Anda.</p>

        <div class="card shadow-sm border-0 rounded-4 p-4 bg-white">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No. Invoice</th>
                            <th>Nama Hewan</th>
                            <th>Kamar & Tanggal Inap</th>
                            <th>Total Biaya</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_riwayat) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                                <tr>
                                    <td class="fw-bold text-primary">RSV<?= str_pad($row['id_reservasi'], 3, "0", STR_PAD_LEFT); ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['nama_hewan']); ?></td>
                                    <td>
                                        <span class="d-block fw-semibold"><?= htmlspecialchars($row['nama_kamar']); ?></span>
                                        <small class="text-muted"><?= $row['check_in']; ?> s/d <?= $row['check_out']; ?> (<?= $row['lama_hari']; ?> Hari)</small>
                                    </td>
                                    <td class="fw-bold text-success">Rp<?= number_format(($row['lama_hari'] * 35000) + $row['total_layanan'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><?= $row['status']; ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">Belum ada riwayat pesanan untuk akun ini.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>