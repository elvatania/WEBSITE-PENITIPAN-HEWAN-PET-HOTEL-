<?php
session_start();
include "../config/koneksi.php";

$queryKamar = mysqli_query($conn, "SELECT * FROM kamar ORDER BY id_kamar ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kamar - Pet Heaven</title>
    
    <!-- CDN Bootstrap 5, FontAwesome, Toastr, & DataTables -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    
    <style>
        :root {
            --sidebar-blue: #2c5f85;      /* Biru Sidebar / Header Tabel */
            --sidebar-active: #5382a6;    /* Biru Menu Aktif */
            --header-sage: #c9dede;       /* Hijau Sage Topbar / Subheader */
            --bg-cream: #f7f5ed;          /* Background Utama Cream */
            --text-dark: #203a43;
        }

        body {
            background-color: var(--bg-cream);
            color: #333333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar Styling */
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

        /* Topbar Header */
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

        /* Cards & Table */
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

        /* Tombol Tambah Kamar */
        .btn-custom-add {
            background-color: var(--sidebar-active);
            color: #ffffff;
            border: none;
            transition: 0.2s ease;
        }
        .btn-custom-add:hover {
            background-color: var(--sidebar-blue);
            color: #ffffff;
        }

        /* Status Badge */
        .badge-tersedia { background-color: #10b981; color: #ffffff; }
        .badge-terisi { background-color: #f59e0b; color: #ffffff; }
        .badge-perbaikan { background-color: #ef4444; color: #ffffff; }
        
        .dataTables_wrapper {
            padding: 1.5rem !important;
        }

        .dataTables_wrapper .dataTables_length {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            color: #333;
            font-size: 0.95rem;
        }

        .dataTables_wrapper .dataTables_length select {
            width: 75px !important;
            display: inline-block;
            padding: 4px 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #212529;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 6px;
            text-align: center;
            height: auto !important;
        }

        .dataTables_wrapper .dataTables_length select:focus {
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 0.2rem rgba(83, 130, 166, 0.25);
            outline: none;
        }

        .dataTables_wrapper .dataTables_filter {
            display: inline-block;
            float: right;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            display: inline-block;
            width: auto;
            padding: 4px 12px;
            font-size: 0.9rem;
            border-radius: 6px;
            border: 1px solid #ced4da;
            background-color: #fff;
            margin-left: 8px; 
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 0.2rem rgba(83, 130, 166, 0.25);
            outline: none;
        }
    </style>    
</head>
<body>

<?php
if (isset($_SESSION['toast'])):
?>
<script>
    window.addEventListener('DOMContentLoaded', function() {
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
        <!-- ================= SIDEBAR ================= -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper p-3" id="sidebar">
            <div class="text-center py-3 mb-3 border-bottom border-light-subtle">
                <img src="../config/assets/images/logo.png" alt="Logo" width="180" class="mb-2 d-block mx-auto img-fluid">
                <div class="sidebar-brand">Pet Heaven</div>
            </div>
            <nav class="nav flex-column">
                <a href="../dashboard.php" class="nav-link"><i class="fas fa-house me-2"></i> Dashboard</a>
                <a href="../pelanggan/index.php" class="nav-link"><i class="fas fa-user me-2"></i> Pelanggan</a>
                <a href="../hewan/index.php" class="nav-link"><i class="fas fa-paw me-2"></i> Hewan</a>
                <a href="../reservasi/index.php" class="nav-link"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="index.php" class="nav-link active"><i class="fas fa-bed me-2"></i> Kamar</a>
                <a href="../layanan.php" class="nav-link"><i class="fas fa-scissors me-2"></i> Layanan</a>
                <a href="../pembayaran.php" class="nav-link"><i class="fas fa-wallet me-2"></i> Pembayaran</a>
                <a href="../laporan.php" class="nav-link"><i class="fas fa-file me-2"></i> Laporan</a>
                <a href="../logout.php" class="nav-link text-danger mt-4"><i class="fas fa-right-from-bracket me-2"></i> Logout</a>
            </nav>
        </div>

        <!-- ================= MAIN CONTENT ================= -->
        <div class="col-md-9 col-lg-10 p-4" id="mainContent">
            <!-- TOPBAR -->
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-toggle-sidebar me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h5 class="mb-0 fw-bold">Kelola Kamar</h5>
                </div>
                <div class="d-flex align-items-center fw-semibold">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span>Admin</span>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="card card-custom overflow-hidden">
                <div class="card-header card-header-blue py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fas fa-bed me-2"></i>Daftar Kamar Hewan</h5>
                    <a href="tambah.php" class="btn btn-custom-add btn-sm px-3 shadow-sm">
                        <i class="fas fa-plus me-1"></i> Tambah Kamar
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="myTable">
                            <thead>
                                <tr class="table-sage-header">
                                    <th class="ps-3 py-3">ID</th>
                                    <th class="py-3">Nama Kamar</th>
                                    <th class="py-3">Jenis Kamar</th>
                                    <th class="py-3">Harga / Hari</th>
                                    <th class="py-3 text-center">Kapasitas</th>
                                    <th class="py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($queryKamar && mysqli_num_rows($queryKamar) > 0): ?>
                                    <?php while ($kmr = mysqli_fetch_assoc($queryKamar)): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold" style="color: var(--sidebar-blue);">
                                                KMR<?= str_pad($kmr['id_kamar'], 2, "0", STR_PAD_LEFT); ?>
                                            </td>
                                            <td class="fw-semibold"><?= htmlspecialchars($kmr['nama_kamar']); ?></td>
                                            <td><?= htmlspecialchars($kmr['jenis_kamar']); ?></td>
                                            <td>Rp<?= number_format($kmr['harga_per_hari'], 0, ',', '.'); ?></td>
                                            <td class="text-center fw-semibold">
                                                <?= htmlspecialchars($kmr['kapasitas']); ?> Ekor
                                            </td>
                                            <td class="text-center">
                                                <a href="edit.php?id=<?= $kmr['id_kamar']; ?>" class="btn btn-warning btn-sm text-white shadow-sm me-1" title="Edit">
                                                    <i class="fas fa-pen-to-square"></i>
                                                </a>
                                                <a href="hapus.php?id=<?= $kmr['id_kamar']; ?>" class="btn btn-danger btn-sm shadow-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus kamar ini?')" title="Hapus">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data kamar terdaftar.</td>
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    // Toggle Sidebar
    document.getElementById("menuBtn").addEventListener("click", function () {
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");

        if (sidebar.classList.contains("d-none")) {
            sidebar.classList.remove("d-none");
            mainContent.className = "col-md-9 col-lg-10 p-4";
        } else {
            sidebar.classList.add("d-none");
            mainContent.className = "col-12 p-4";
        }
    });

    $(document).ready(function() {
        $('#myTable').DataTable({
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    });
</script>
</body>
</html>