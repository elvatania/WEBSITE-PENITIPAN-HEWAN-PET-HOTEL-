<?php
$judulHalaman = $judulHalaman ?? 'Dashboard Admin';
$menuAktif = $menuAktif ?? '';

$baseUrl = '/WEBSITE-PENITIPAN-HEWAN-PET-HOTEL-';
$basePath = $basePath ?? '../'; // Fallback aman jika $basePath belum diset

$menuSidebar = [
    ['dashboard.php', 'fa-house', 'Dashboard'], // Diubah langsung ke dashboard.php
    ['pelanggan/index.php', 'fa-user', 'Pelanggan'], // Kalau pelanggan punya folder sendiri
    ['hewan/index.php', 'fa-paw', 'Hewan'], // Kalau hewan punya folder sendiri
    ['reservasi/index.php', 'fa-calendar-check', 'Reservasi'], // Langsung ke reservasi.php sesuai file kamu
    ['kamar/index.php', 'fa-bed', 'Kamar'], // Kalau kamar punya folder sendiri
    ['layanan.php', 'fa-scissors', 'Layanan'], // Langsung ke layanan.php
    ['pembayaran.php', 'fa-wallet', 'Pembayaran'], // Langsung ke pembayaran.php
    ['laporan.php', 'fa-file-lines', 'Laporan'], // Langsung ke laporan.php
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judulHalaman); ?> - Pet Heaven</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        .sidebar-wrapper {
            background-color: var(--sidebar-blue);
            min-height: 100vh;
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

        @media print {
            .sidebar-wrapper,
            .topbar-header {
                display: none !important;
            }
            #mainContent {
                width: 100% !important;
                flex: 0 0 100% !important;
                max-width: 100% !important;
            }
        }
    </style>

    <?php if (!empty($pakaiDataTables)): ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
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
        .btn-tambah {
            background-color: var(--sidebar-active);
            border-color: var(--sidebar-active);
            color: #ffffff;
        }
        .btn-tambah:hover {
            background-color: #6b95b5;
            border-color: #6b95b5;
            color: #ffffff;
        }
        .dataTables_wrapper { padding: 1.5rem !important; }
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
        .dataTables_wrapper .dataTables_length select:focus,
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 0.2rem rgba(83, 130, 166, 0.25);
            outline: none;
        }
    </style>
    <?php endif; ?>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar-wrapper p-3" id="sidebar">
            <div class="text-center py-3 mb-3 border-bottom border-light-subtle">
                <img src="<?= $basePath; ?>config/assets/images/logo.png" alt="Logo" width="180" class="mb-2 d-block mx-auto img-fluid"
                    onerror="this.onerror=null; ">
                <div class="sidebar-brand">Pet Heaven</div>
            </div>

            <nav class="nav flex-column">
                <?php foreach ($menuSidebar as $m): ?>
                    <a href="<?= $baseUrl; ?>/admin/<?= $m[0]; ?>"
                       class="nav-link <?= ($menuAktif === $m[0]) ? 'active' : ''; ?>">
                        <i class="fas <?= $m[1]; ?> me-2"></i>
                        <?= $m[2]; ?>
                    </a>
                <?php endforeach; ?>

                <a href="<?= $baseUrl; ?>/logout.php"
                   class="nav-link text-danger mt-4">
                    <i class="fas fa-right-from-bracket me-2"></i>
                    Logout
                </a>
            </nav>
        </div> 
        <div class="col-md-9 col-lg-10 p-4" id="mainContent">
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-toggle-sidebar me-3" id="menuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($judulHalaman); ?></h5>
                </div>
                <div class="d-flex align-items-center fw-semibold">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span>Admin</span>
                </div>
            </div>