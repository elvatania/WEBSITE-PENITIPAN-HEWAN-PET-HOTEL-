<?php
session_start();
include __DIR__ . "/../../config/koneksi.php";

/* ================= DATA RESERVASI ================= */
$queryReservasi = mysqli_query($koneksi, "
    SELECT
        reservasi.id_reservasi,
        COALESCE(pelanggan.nama_pelanggan, '-') AS nama_pelanggan,
        COALESCE(kamar.jenis_kamar, 'Standard') AS jenis_kamar,
        reservasi.check_in,
        reservasi.check_out,
        reservasi.status,
        GROUP_CONCAT(hewan.nama_hewan SEPARATOR ', ') AS nama_hewan,
        GROUP_CONCAT(hewan.jenis_hewan SEPARATOR ', ') AS jenis_hewan
    FROM reservasi
    LEFT JOIN pelanggan
        ON reservasi.id_pelanggan = pelanggan.id_pelanggan
    LEFT JOIN detail_reservasi
        ON reservasi.id_reservasi = detail_reservasi.id_reservasi
    LEFT JOIN hewan
        ON detail_reservasi.id_hewan = hewan.id_hewan
    LEFT JOIN kamar
        ON reservasi.id_kamar = kamar.id_kamar
    GROUP BY reservasi.id_reservasi
    ORDER BY reservasi.id_reservasi DESC
");

$totalReservasi = $queryReservasi ? mysqli_num_rows($queryReservasi) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi - Pet Heaven</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --sidebar-blue: #2c5f85;      
            --sidebar-active: #5382a6;    
            --header-sage: #c9dede;       
            --bg-cream: #f7f5ed;          
            --text-dark: #203a43;
        }

        body {
            background-color: var(--bg-cream);
            color: #333333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar-wrapper {
            background-color: var(--sidebar-blue);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            color: #ffffff;
            font-weight: bold;
            font-size: 1.25rem;
        }

        .sidebar-wrapper .nav-link {
            color: #dce7f0;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: all 0.2s ease;
        }

        .sidebar-wrapper .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .sidebar-wrapper .nav-link.active {
            background-color: var(--sidebar-active);
            color: #ffffff;
        }

        .topbar-header {
            background-color: var(--header-sage);
            color: var(--text-dark);
            border-radius: 10px;
        }

        .btn-toggle-sidebar {
            background: transparent;
            border: 1px solid var(--text-dark);
            color: var(--text-dark);
        }

        .card-custom {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card-header-blue {
            background-color: var(--sidebar-blue);
            color: #ffffff;
            font-weight: 600;
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }

        .table-sage-header {
            background-color: var(--header-sage) !important;
            color: var(--text-dark) !important;
        }

        .btn-custom-add {
            background-color: var(--sidebar-active);
            color: #ffffff;
            border: none;
            transition: 0.2s ease;
            text-decoration: none;
        }
        .btn-custom-add:hover {
            background-color: var(--sidebar-blue);
            color: #ffffff;
        }

        .dataTables_wrapper {
            padding: 1.5rem !important;
        }
    </style>
</head>
<body>

<?php
if (isset($_SESSION['toast'])):
?>
<script>
    $(document).ready(function() {
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };
        toastr["<?= $_SESSION['toast']['type']?>"](
            "<?= $_SESSION['toast']['message'] ?>",
            "<?= $_SESSION['toast']['title'] ?>"
        );
    });
</script>
<?php
unset($_SESSION['toast']);
endif;
?>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper p-3" id="sidebar">
            <div class="text-center py-3 mb-3 border-bottom border-light-subtle">
                <img src="../../config/assets/images/logo.png" alt="Logo" width="180" class="mb-2 d-block mx-auto img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null;">
                <div class="sidebar-brand">Pet Heaven</div>
            </div>
            <nav class="nav flex-column">
                <a href="../dashboard.php" class="nav-link"><i class="fas fa-house me-2"></i> Dashboard</a>
                <a href="../pelanggan/index.php" class="nav-link"><i class="fas fa-user me-2"></i> Pelanggan</a>
                <a href="../hewan/index.php" class="nav-link"><i class="fas fa-paw me-2"></i> Hewan</a>
                <a href="index.php" class="nav-link active"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="../kamar/index.php" class="nav-link"><i class="fas fa-bed me-2"></i> Kamar</a>
                <a href="../layanan/layanan.php" class="nav-link"><i class="fas fa-scissors me-2"></i> Layanan</a>
                <a href="../pembayaran.php" class="nav-link"><i class="fas fa-wallet me-2"></i> Pembayaran</a>
                <a href="../laporan.php" class="nav-link"><i class="fas fa-file me-2"></i> Laporan</a>
                <a href="../logout.php" class="nav-link text-danger mt-4">
                    <i class="fas fa-right-from-bracket me-2"></i> Logout
                </a>
            </nav>
            </nav>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-9 col-lg-10 p-4" id="mainContent">
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-toggle-sidebar me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h5 class="mb-0 fw-bold">Reservasi</h5>
                </div>
                <div class="d-flex align-items-center fw-semibold">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span><?= htmlspecialchars($_SESSION['nm_admin'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="card card-custom overflow-hidden">
                <div class="card-header card-header-blue py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold fs-6"><i class="fas fa-calendar-check me-2"></i>Daftar Reservasi (<?= $totalReservasi; ?>)</h5>
                    <a href="tambah.php" class="btn btn-custom-add btn-sm px-3 shadow-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Reservasi
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="myTable">
                            <thead>
                                <tr class="table-sage-header">
                                    <th class="ps-3 py-3">Booking Code</th>
                                    <th class="py-3">Pelanggan</th>
                                    <th class="py-3">Hewan</th>
                                    <th class="py-3">Kamar</th>
                                    <th class="py-3">Check In</th>
                                    <th class="py-3">Check Out</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if ($queryReservasi && $totalReservasi > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($queryReservasi)): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold" style="color: var(--sidebar-blue);">
                                            RSV<?= str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['nama_pelanggan']); ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['nama_hewan']); ?>
                                            <small class="text-muted d-block"><?= htmlspecialchars($row['jenis_hewan']); ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($row['jenis_kamar']); ?></td>
                                        <td><?= date('d M Y', strtotime($row['check_in'])); ?></td>
                                        <td><?= date('d M Y', strtotime($row['check_out'])); ?></td>
                                        <td class="text-center">
                                            <?php if ($row['status'] === 'Selesai'): ?>
                                                <span class="badge bg-success px-3 py-2">Selesai</span>
                                            <?php elseif ($row['status'] === 'Proses'): ?>
                                                <span class="badge bg-warning text-dark px-3 py-2">Proses</span>
                                            <?php elseif ($row['status'] === 'Dibatalkan'): ?>
                                                <span class="badge bg-danger px-3 py-2">Dibatalkan</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary px-3 py-2"><?= htmlspecialchars($row['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="edit.php?id=<?= $row['id_reservasi']; ?>" class="btn btn-warning btn-sm text-white px-2 py-1" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="hapus.php?id=<?= $row['id_reservasi']; ?>" class="btn btn-danger btn-sm px-2 py-1" title="Hapus" onclick="return confirm('Yakin ingin menghapus data reservasi ini?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada data reservasi.</td>
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
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    document.getElementById("menuBtn").addEventListener("click", function () {
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");
        sidebar.classList.toggle("d-none");
        mainContent.className = sidebar.classList.contains("d-none") ? "col-12 p-4" : "col-md-9 col-lg-10 p-4";
    });

    $(document).ready(function() {
        $('#myTable').DataTable({
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data"
            }
        });
    });
</script>
</body>
</html>