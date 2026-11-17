<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include '../config/koneksi.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'reservasi';

if (!in_array($tab, ['reservasi', 'pembayaran'])) {
    $tab = 'reservasi';
}

$cari = isset($_GET['cari'])
    ? mysqli_real_escape_string($koneksi, $_GET['cari'])
    : '';

$dari = isset($_GET['dari']) &&
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dari'])
    ? $_GET['dari'] : '';

$sampai = isset($_GET['sampai']) &&
          preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['sampai'])
    ? $_GET['sampai'] : '';

function laporanRupiah($angka) {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

function laporanTanggal($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '-';
    }
    return date('d-m-Y', strtotime($tanggal));
}

function laporanStatus($status) {
    $status = strtolower(trim($status ?? ''));

    if (in_array($status, ['lunas', 'berhasil', 'dikonfirmasi'])) {
        return 'badge bg-success rounded-pill px-3 py-2';
    }

    if (in_array($status, ['ditolak', 'dibatalkan'])) {
        return 'badge bg-danger rounded-pill px-3 py-2';
    }

    return 'badge bg-warning text-dark rounded-pill px-3 py-2';
}

// Statistik Pendapatan
$queryPendapatan = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(b.total_bayar), 0) AS total
    FROM pembayaran b
    JOIN reservasi r ON b.id_reservasi = r.id_reservasi
    WHERE LOWER(TRIM(r.status)) = 'lunas'
");
$pendapatan = 0;
if ($queryPendapatan) {
    $dataPendapatan = mysqli_fetch_assoc($queryPendapatan);
    $pendapatan = $dataPendapatan['total'] ?? 0;
}

// Total Reservasi
$queryReservasi = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM reservasi");
$totalReservasi = 0;
if ($queryReservasi) {
    $dataReservasi = mysqli_fetch_assoc($queryReservasi);
    $totalReservasi = $dataReservasi['total'] ?? 0;
}

// Reservasi Lunas
$queryLunas = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM reservasi WHERE LOWER(TRIM(status)) = 'lunas'");
$totalLunas = 0;
if ($queryLunas) {
    $dataLunas = mysqli_fetch_assoc($queryLunas);
    $totalLunas = $dataLunas['total'] ?? 0;
}

// Reservasi Pending
$queryPending = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM reservasi
    WHERE LOWER(TRIM(status)) IN ('pending konfirmasi', 'menunggu konfirmasi')
");
$totalPending = 0;
if ($queryPending) {
    $dataPending = mysqli_fetch_assoc($queryPending);
    $totalPending = $dataPending['total'] ?? 0;
}

// Query Filter Reservasi
$whereReservasi = " WHERE 1=1 ";
if ($cari != '') {
    $whereReservasi .= " AND (p.nama_pelanggan LIKE '%$cari%' OR r.id_reservasi LIKE '%$cari%')";
}
if ($dari != '') {
    $whereReservasi .= " AND r.tgl_reservasi >= '$dari'";
}
if ($sampai != '') {
    $whereReservasi .= " AND r.tgl_reservasi <= '$sampai'";
}

$queryDataReservasi = mysqli_query($koneksi, "
    SELECT
        r.id_reservasi, r.tgl_reservasi, r.check_in, r.check_out, r.lama_hari, r.status,
        p.nama_pelanggan,
        k.nama_kamar,
        COALESCE(
            (SELECT GROUP_CONCAT(CONCAT(h.nama_hewan, ' (', h.jenis_hewan, ')') SEPARATOR ', ')
             FROM detail_reservasi dr
             JOIN hewan h ON dr.id_hewan = h.id_hewan
             WHERE dr.id_reservasi = r.id_reservasi),
            (SELECT CONCAT(h2.nama_hewan, ' (', h2.jenis_hewan, ')')
             FROM hewan h2
             WHERE h2.id_hewan = r.id_hewan)
        ) AS daftar_hewan
    FROM reservasi r
    LEFT JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
    LEFT JOIN kamar k ON r.id_kamar = k.id_kamar
    $whereReservasi
    ORDER BY r.id_reservasi DESC
");
$totalDataReservasi = $queryDataReservasi ? mysqli_num_rows($queryDataReservasi) : 0;

// Query Filter Pembayaran
$wherePembayaran = " WHERE 1=1 ";
if ($cari != '') {
    $wherePembayaran .= " AND (p.nama_pelanggan LIKE '%$cari%' OR b.id_bayar LIKE '%$cari%' OR b.id_reservasi LIKE '%$cari%')";
}
if ($dari != '') {
    $wherePembayaran .= " AND b.tgl_bayar >= '$dari'";
}
if ($sampai != '') {
    $wherePembayaran .= " AND b.tgl_bayar <= '$sampai'";
}

$queryDataPembayaran = mysqli_query($koneksi, "
    SELECT
        b.id_bayar, b.tgl_bayar, b.total_bayar, b.metode_bayar, b.status_bayar,
        r.lama_hari, r.status AS status_reservasi,
        p.nama_pelanggan, p.no_telp
    FROM pembayaran b
    LEFT JOIN reservasi r ON b.id_reservasi = r.id_reservasi
    LEFT JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
    $wherePembayaran
    ORDER BY b.id_bayar DESC
");
$totalDataPembayaran = $queryDataPembayaran ? mysqli_num_rows($queryDataPembayaran) : 0;

$judulHalaman    = 'Laporan';
$menuAktif       = 'laporan.php';
$pakaiDataTables = true;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - Pet Heaven</title>

    <!-- CDN Bootstrap 5 & FontAwesome / Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

        /* Penyesuaian DataTables Layout agar rapi & Search bawaan hilang */
        .dataTables_wrapper {
            padding: 1.5rem !important;
        }
        .dataTables_filter {
            display: none !important; /* Menghilangkan kotak pencarian bawaan DataTables */
        }
        .dataTables_length {
            margin-bottom: 1rem;
            color: #444;
            font-size: 0.95rem;
        }
        .dataTables_length select {
            width: 75px !important;
            display: inline-block;
            padding: 4px 8px;
            font-size: 0.9rem;
            border: 1px solid #ced4da;
            border-radius: 6px;
            text-align: center;
        }

        /* Tombol Tab Navigasi */
        .tombol-laporan {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .tombol-laporan a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: 1px solid var(--sidebar-blue);
            border-radius: 8px;
            color: var(--sidebar-blue);
            text-decoration: none;
            font-weight: 600;
            background: #FFFFFF;
            transition: 0.3s;
        }

        .tombol-laporan a:hover,
        .tombol-laporan a.aktif {
            background: var(--sidebar-blue);
            color: #FFFFFF;
        }

        /* Filter Form */
        .filter-laporan {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
            background: #FFFFFF;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .filter-laporan input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            background: #FFFFFF;
            color: #333;
            outline: none;
        }

        .filter-laporan input:focus {
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 0.2rem rgba(83, 130, 166, 0.25);
        }

        .filter-laporan label {
            font-size: 13px;
            color: #444;
            margin: 0;
            font-weight: 600;
        }

        .btn-filter {
            background: var(--sidebar-blue);
            color: #FFFFFF;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            font-weight: 600;
        }
        .btn-filter:hover {
            background: var(--sidebar-active);
            color: #FFFFFF;
        }

        .btn-reset {
            background: #e2e8f0;
            color: #333;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            font-weight: 600;
            text-decoration: none;
        }
        .btn-reset:hover {
            background: #cbd5e1;
            color: #333;
        }

        .btn-cetak {
            background: #198754;
            color: #FFFFFF;
            border: none;
            border-radius: 6px;
            padding: 8px 15px;
            font-weight: 600;
        }
        .btn-cetak:hover {
            background: #157347;
            color: #FFFFFF;
        }

        @media print {
            .sidebar-wrapper, .topbar-header, .tombol-laporan, .filter-laporan, .dataTables_filter, .dataTables_length, .dataTables_paginate, .dataTables_info {
                display: none !important;
            }
            #mainContent {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body {
                background-color: #FFFFFF !important;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper p-3" id="sidebar">
            <div class="text-center py-3 mb-3 border-bottom border-light-subtle">
                <img src="../config/assets/images/logo.png" alt="Logo" width="180" class="mb-2 d-block mx-auto img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null;">
                <div class="sidebar-brand">Pet Heaven</div>
            </div>
            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-house me-2"></i> Dashboard</a>
                <a href="pelanggan/index.php" class="nav-link"><i class="fas fa-user me-2"></i> Pelanggan</a>
                <a href="hewan/index.php" class="nav-link"><i class="fas fa-paw me-2"></i> Hewan</a>
                <a href="reservasi/index.php" class="nav-link"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="kamar/index.php" class="nav-link"><i class="fas fa-bed me-2"></i> Kamar</a>
                <a href="layanan/layanan.php" class="nav-link"><i class="fas fa-scissors me-2"></i> Layanan</a>
                <a href="pembayaran.php" class="nav-link"><i class="fas fa-wallet me-2"></i> Pembayaran</a>
                <a href="laporan.php" class="nav-link active"><i class="fas fa-file me-2"></i> Laporan</a>
                <a href="logout.php" class="nav-link text-danger mt-4">
                    <i class="fas fa-right-from-bracket me-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-9 col-lg-10 p-4" id="mainContent">
            <!-- TOPBAR -->
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-toggle-sidebar me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h5 class="mb-0 fw-bold">Laporan Sistem</h5>
                </div>
                <div class="d-flex align-items-center fw-semibold">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span><?= htmlspecialchars($_SESSION['nm_admin'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <!-- KOTAK STATISTIK -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card card-custom p-3 border-start border-success border-4">
                        <h6 class="text-muted small fw-semibold">TOTAL PENDAPATAN (LUNAS)</h6>
                        <h4 class="text-success fw-bold mb-0"><?= laporanRupiah($pendapatan); ?></h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 border-start border-primary border-4">
                        <h6 class="text-muted small fw-semibold">TOTAL RESERVASI</h6>
                        <h4 class="text-primary fw-bold mb-0"><?= $totalReservasi; ?> Transaksi</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 border-start border-info border-4">
                        <h6 class="text-muted small fw-semibold">RESERVASI LUNAS</h6>
                        <h4 class="text-info fw-bold mb-0"><?= $totalLunas; ?> Lunas</h4>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-custom p-3 border-start border-warning border-4">
                        <h6 class="text-muted small fw-semibold">MENUNGGU KONFIRMASI</h6>
                        <h4 class="text-warning fw-bold mb-0"><?= $totalPending; ?> Pending</h4>
                    </div>
                </div>
            </div>

            <!-- TOMBOL TAB NAVIGASI -->
            <div class="tombol-laporan">
                <a href="?tab=reservasi" class="<?= $tab == 'reservasi' ? 'aktif' : ''; ?>">
                    <i class="bi bi-calendar-check"></i> Data Reservasi
                </a>
                <a href="?tab=pembayaran" class="<?= $tab == 'pembayaran' ? 'aktif' : ''; ?>">
                    <i class="bi bi-credit-card"></i> Data Pembayaran
                </a>
            </div>

            <!-- FORM FILTER & PENCARIAN -->
            <form method="GET" class="filter-laporan shadow-sm">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab); ?>">
                <input type="text" name="cari" placeholder="Cari data..." value="<?= htmlspecialchars($cari); ?>" class="form-control form-control-sm" style="width: 200px;">
                
                <label>Dari:</label>
                <input type="date" name="dari" value="<?= htmlspecialchars($dari); ?>" class="form-control form-control-sm" style="width: 150px;">
                
                <label>Sampai:</label>
                <input type="date" name="sampai" value="<?= htmlspecialchars($sampai); ?>" class="form-control form-control-sm" style="width: 150px;">
                
                <button type="submit" class="btn btn-filter btn-sm">
                    <i class="bi bi-search me-1"></i> Cari
                </button>
                <a href="laporan.php?tab=<?= htmlspecialchars($tab); ?>" class="btn btn-reset btn-sm">
                    Reset
                </a>
                <button type="button" onclick="window.print()" class="btn btn-cetak btn-sm ms-auto">
                    <i class="bi bi-printer me-1"></i> Cetak
                </button>
            </form>

            <!-- KARTU TABEL KONTEN -->
            <?php if ($tab == 'reservasi') { ?>
                <div class="card card-custom overflow-hidden">
                    <div class="card-header card-header-blue py-3">
                        <h5 class="mb-0 fw-bold fs-6"><i class="bi bi-calendar-check me-2"></i>Laporan Data Reservasi Pelanggan (<?= $totalDataReservasi; ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="myTable">
                                <thead>
                                    <tr class="table-sage-header">
                                        <th class="ps-3 py-3">ID / TGL</th>
                                        <th class="py-3">PELANGGAN</th>
                                        <th class="py-3">HEWAN PELIHARAAN</th>
                                        <th class="py-3">KAMAR</th>
                                        <th class="py-3">DURASI MENGINAP</th>
                                        <th class="py-3">STATUS PEMESANAN</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($queryDataReservasi && mysqli_num_rows($queryDataReservasi) > 0) { ?>
                                        <?php while ($row = mysqli_fetch_assoc($queryDataReservasi)) { ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="fw-bold" style="color: var(--sidebar-blue);">#<?= $row['id_reservasi']; ?></span>
                                                    <div class="text-muted small"><?= laporanTanggal($row['tgl_reservasi']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($row['nama_pelanggan'] ?? '-'); ?></div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($row['daftar_hewan'])) { ?>
                                                        <div class="fw-semibold"><?= htmlspecialchars($row['daftar_hewan']); ?></div>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($row['nama_kamar'] ?? '-'); ?>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?= (int)$row['lama_hari']; ?> Hari</div>
                                                    <small class="text-muted"><?= laporanTanggal($row['check_in']); ?> s/d <?= laporanTanggal($row['check_out']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="<?= laporanStatus($row['status']); ?>">
                                                        <?= htmlspecialchars($row['status'] ?? '-'); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } else { ?>
                <div class="card card-custom overflow-hidden">
                    <div class="card-header card-header-blue py-3">
                        <h5 class="mb-0 fw-bold fs-6"><i class="bi bi-credit-card me-2"></i>Laporan Transaksi Pembayaran (<?= $totalDataPembayaran; ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="myTable">
                                <thead>
                                    <tr class="table-sage-header">
                                        <th class="ps-3 py-3">ID TRANSAKSI</th>
                                        <th class="py-3">PELANGGAN</th>
                                        <th class="py-3">RINCIAN BIAYA</th>
                                        <th class="py-3">TOTAL BAYAR</th>
                                        <th class="py-3">STATUS VERIFIKASI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($queryDataPembayaran && mysqli_num_rows($queryDataPembayaran) > 0) { ?>
                                        <?php while ($row = mysqli_fetch_assoc($queryDataPembayaran)) { ?>
                                            <tr>
                                                <td class="ps-3">
                                                    <span class="fw-bold" style="color: var(--sidebar-blue);">#<?= $row['id_bayar']; ?></span>
                                                    <div class="text-muted small"><?= laporanTanggal($row['tgl_bayar']); ?></div>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold"><?= htmlspecialchars($row['nama_pelanggan'] ?? '-'); ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($row['no_telp'] ?? '-'); ?></small>
                                                </td>
                                                <td>
                                                    Durasi: <span class="fw-semibold"><?= (int)$row['lama_hari']; ?> Hari</span>
                                                    <div class="text-muted small">Metode: <?= htmlspecialchars($row['metode_bayar'] ?? '-'); ?></div>
                                                </td>
                                                <td>
                                                    <span class="fw-bold text-success">
                                                        <?= laporanRupiah($row['total_bayar']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusTampil = $row['status_reservasi'] ?? $row['status_bayar'] ?? '-';
                                                    ?>
                                                    <span class="<?= laporanStatus($statusTampil); ?>">
                                                        <?= htmlspecialchars($statusTampil); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php } ?>

        </div>
    </div>
</div>

<!-- JavaScript Dependencies -->
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