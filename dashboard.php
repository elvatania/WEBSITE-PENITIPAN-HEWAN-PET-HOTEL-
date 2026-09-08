<?php
include "config/koneksi.php";

// 1. STATISTIK
$totalPelanggan  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pelanggan"))['total'] ?? 0;
$totalHewan      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM hewan"))['total'] ?? 0;
$totalReservasi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservasi"))['total'] ?? 0;
$totalPendapatan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT IFNULL(SUM(total_bayar),0) AS total FROM pembayaran"))['total'] ?? 0;

$queryData = mysqli_query($conn, "
    SELECT reservasi.id_reservasi, pelanggan.nama_pelanggan, hewan.nama_hewan, 
           hewan.jenis_hewan, kamar.jenis_kamar, reservasi.check_in, 
           reservasi.check_out, reservasi.status
    FROM reservasi
    JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan
    JOIN hewan ON reservasi.id_hewan = hewan.id_hewan
    JOIN kamar ON reservasi.id_kamar = kamar.id_kamar
    ORDER BY reservasi.id_reservasi DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
    
    <!-- CDN Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar-wrapper {
            background-color: #1a233a;
            transition: all 0.3s ease;
        }
        .sidebar-wrapper .nav-link {
            color: #a6b0cf;
            padding: 12px 20px;
            border-radius: 6px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        .sidebar-wrapper .nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.08);
        }
        .sidebar-wrapper .nav-link.active {
            color: #ffffff !important;
            background-color: #0d6efd !important;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <!-- ================= SIDEBAR (Sembunyi secara default dengan d-none) ================= -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper min-vh-100 p-3 text-white d-none" id="sidebar">
            <div class="text-center pb-3 mb-3 border-bottom border-secondary">
                <img src="assets/images/logo.png" alt="Logo" width="80" class="mb-2 d-block mx-auto">
                <h5 class="fw-bold mb-0">Pet Heaven</h5>
            </div>

            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-house me-2"></i> Dashboard
                </a>
                <a href="pelanggan/index.php" class="nav-link">
                    <i class="fas fa-user me-2"></i> Pelanggan
                </a>
                <a href="hewan/index.php" class="nav-link">
                    <i class="fas fa-paw me-2"></i> Hewan
                </a>
                <a href="reservasi/index.php" class="nav-link">
                    <i class="fas fa-calendar-check me-2"></i> Reservasi
                </a>
                <a href="kamar.php" class="nav-link">
                    <i class="fas fa-bed me-2"></i> Kamar
                </a>
                <a href="layanan.php" class="nav-link">
                    <i class="fas fa-scissors me-2"></i> Layanan
                </a>
                <a href="pembayaran.php" class="nav-link">
                    <i class="fas fa-wallet me-2"></i> Pembayaran
                </a>
                <a href="laporan.php" class="nav-link">
                    <i class="fas fa-file me-2"></i> Laporan
                </a>
                <a href="logout.php" class="nav-link text-danger mt-4">
                    <i class="fas fa-right-from-bracket me-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- ================= MAIN CONTENT ============== -->
        <div class="col-12 p-4" id="mainContent">

            <!-- HEADER / TOPBAR -->
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <!-- Tombol Garis 3 -->
                    <button class="btn btn-outline-secondary me-3" id="menuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h4 class="mb-0 fs-5 fw-bold">Dashboard Admin</h4>
                </div>
                <div class="d-flex align-items-center text-secondary">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span class="fw-semibold">Admin</span>
                </div>
            </div>

            <!-- STATS CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Total Pelanggan</span>
                                <h3 class="fw-bold mb-0 mt-1"><?= $totalPelanggan; ?></h3>
                            </div>
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Total Hewan</span>
                                <h3 class="fw-bold mb-0 mt-1"><?= $totalHewan; ?></h3>
                            </div>
                            <i class="fas fa-paw fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Total Reservasi</span>
                                <h3 class="fw-bold mb-0 mt-1"><?= $totalReservasi; ?></h3>
                            </div>
                            <i class="fas fa-calendar-check fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <span class="text-muted small">Pendapatan</span>
                                <h3 class="fw-bold mb-0 mt-1">Rp<?= number_format($totalPendapatan, 0, ',', '.'); ?></h3>
                            </div>
                            <i class="fas fa-wallet fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE RESERVASI -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white fw-semibold py-3">
                    <i class="fas fa-clock me-2"></i>Reservasi Terbaru
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Pelanggan</th>
                                    <th>Hewan</th>
                                    <th>Kamar</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($queryData && mysqli_num_rows($queryData) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($queryData)): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold text-secondary">
                                                RSV<?= str_pad($row['id_reservasi'], 3, "0", STR_PAD_LEFT); ?>
                                            </td>
                                            <td><?= htmlspecialchars($row['nama_pelanggan']); ?></td>
                                            <td>
                                                <?= htmlspecialchars($row['nama_hewan']); ?><br>
                                                <small class="text-muted"><?= htmlspecialchars($row['jenis_hewan']); ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($row['jenis_kamar']); ?></td>
                                            <td><?= date('d M Y', strtotime($row['check_in'])); ?></td>
                                            <td><?= date('d M Y', strtotime($row['check_out'])); ?></td>
                                            <td class="text-center">
                                                <?php
                                                $status = $row['status'];
                                                if ($status == "Selesai") {
                                                    echo '<span class="badge bg-success">Selesai</span>';
                                                } elseif ($status == "Proses") {
                                                    echo '<span class="badge bg-warning text-dark">Proses</span>';
                                                } elseif ($status == "Dibatalkan") {
                                                    echo '<span class="badge bg-danger">Dibatalkan</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">Belum ada data reservasi terbaru.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Script JS untuk Muncul/Sembunyi Sidebar -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const menuBtn = document.getElementById("menuBtn");
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");

        menuBtn.addEventListener("click", function () {
            if (sidebar.classList.contains("d-none")) {
                // Munculkan Sidebar
                sidebar.classList.remove("d-none");
                mainContent.className = "col-md-9 col-lg-10 p-4";
            } else {
                // Sembunyikan Sidebar
                sidebar.classList.add("d-none");
                mainContent.className = "col-12 p-4";
            }
        });
    });
</script>

</body>
</html>