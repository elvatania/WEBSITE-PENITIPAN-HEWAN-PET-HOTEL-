<?php
session_start();
include __DIR__ . "/../../config/koneksi.php";

$adaDeskripsi = false;
$cekKolom = mysqli_query($koneksi, "SHOW COLUMNS FROM layanan LIKE 'deskripsi'");
if ($cekKolom && mysqli_num_rows($cekKolom) > 0) {
    $adaDeskripsi = true;
}

$kolomNama = 'nama_layanan';
$daftarKolom = [];
$qKolom = mysqli_query($koneksi, "SHOW COLUMNS FROM layanan");
while ($qKolom && $k = mysqli_fetch_assoc($qKolom)) {
    $daftarKolom[] = $k['Field'];
}
foreach (['nama_layanan', 'jenis_layanan', 'layanan', 'nama'] as $kandidat) {
    if (in_array($kandidat, $daftarKolom)) {
        $kolomNama = $kandidat;
        break;
    }
}

$error = '';
$id = (int)($_GET['id'] ?? $_POST['id_layanan'] ?? 0);

if ($id <= 0) {
    header("Location: layanan.php");
    exit();
}

function bersihkanHarga($nilai) {
    return (int) preg_replace('/\D/', '', $nilai ?? '0');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nama      = trim($_POST['nama_layanan'] ?? '');
    $harga     = bersihkanHarga($_POST['harga'] ?? '0');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') {
        $error = 'Nama layanan wajib diisi.';
    } elseif ($harga <= 0) {
        $error = 'Harga harus lebih dari 0.';
    } else {
        if ($adaDeskripsi) {
            $stmt = mysqli_prepare($koneksi, "UPDATE layanan SET $kolomNama = ?, harga = ?, deskripsi = ? WHERE id_layanan = ?");
            mysqli_stmt_bind_param($stmt, "sisi", $nama, $harga, $deskripsi, $id);
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE layanan SET $kolomNama = ?, harga = ? WHERE id_layanan = ?");
            mysqli_stmt_bind_param($stmt, "sii", $nama, $harga, $id);
        }

        if ($stmt && mysqli_stmt_execute($stmt)) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Data layanan berhasil diperbarui.'
            ];
            header("Location: layanan.php");
            exit();
        } else {
            $error = 'Gagal memperbarui: ' . mysqli_error($koneksi);
        }
    }
}

$stmtCek = mysqli_prepare($koneksi, "SELECT * FROM layanan WHERE id_layanan = ?");
mysqli_stmt_bind_param($stmtCek, "i", $id);
mysqli_stmt_execute($stmtCek);
$resultCek = mysqli_stmt_get_result($stmtCek);
$dataLayanan = mysqli_fetch_assoc($resultCek);

if (!$dataLayanan) {
    header("Location: layanan.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Layanan - Pet Heaven</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

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

        .form-control:focus, .form-select:focus {
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 0.2rem rgba(83, 130, 166, 0.25);
            outline: none;
        }

        .btn-custom-update {
            background-color: var(--sidebar-blue);
            border: none;
            color: #ffffff;
            padding: 8px 20px;
            border-radius: 8px;
            transition: 0.2s ease;
        }
        .btn-custom-update:hover {
            background-color: var(--sidebar-active);
            color: #ffffff;
        }

        .btn-custom-back {
            background-color: #C9DCDA;
            border: 1px solid #b2c8c5;
            color: var(--text-dark);
            padding: 8px 20px;
            border-radius: 8px;
            transition: 0.2s ease;
            text-decoration: none;
        }
        .btn-custom-back:hover {
            background-color: #b5cecb;
            color: var(--text-dark);
        }
    </style>
</head>
<body>

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
                <a href="../reservasi/index.php" class="nav-link"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="../kamar/index.php" class="nav-link"><i class="fas fa-bed me-2"></i> Kamar</a>
                <a href="layanan.php" class="nav-link active"><i class="fas fa-scissors me-2"></i> Layanan</a>
                <a href="../pembayaran.php" class="nav-link"><i class="fas fa-wallet me-2"></i> Pembayaran</a>
                <a href="../laporan.php" class="nav-link"><i class="fas fa-file me-2"></i> Laporan</a>
                <a href="/WEBSITE-PENITIPAN-HEWAN-PET-HOTEL-/admin/logout.php" class="nav-link text-danger mt-4">
                    <i class="fas fa-right-from-bracket me-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-9 col-lg-10 p-4" id="mainContent">
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-toggle-sidebar me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h5 class="mb-0 fw-bold">Edit Layanan (LYN<?= str_pad((int)$id, 2, '0', STR_PAD_LEFT); ?>)</h5>
                </div>
                <div class="d-flex align-items-center fw-semibold">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span><?= htmlspecialchars($_SESSION['nm_admin'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <?php if ($error != ''): ?>
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="fas fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom overflow-hidden">
                <div class="card-header card-header-blue py-3">
                    <h5 class="mb-0 fw-bold fs-6"><i class="fas fa-pen-to-square me-2"></i>Formulir Edit Layanan</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <input type="hidden" name="id_layanan" value="<?= $id; ?>">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Layanan</label>
                            <input type="text" name="nama_layanan" class="form-control" required value="<?= htmlspecialchars($_POST['nama_layanan'] ?? $dataLayanan[$kolomNama] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Harga (Rp)</label>
                            <input type="text" name="harga" class="form-control" inputmode="numeric" required value="<?= htmlspecialchars($_POST['harga'] ?? $dataLayanan['harga'] ?? ''); ?>">
                        </div>

                        <?php if ($adaDeskripsi): ?>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                                <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? $dataLayanan['deskripsi'] ?? ''); ?></textarea>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" name="update" class="btn btn-custom-update shadow-sm">
                                <i class="fas fa-save me-1"></i> Perbarui
                            </button>
                            <a href="layanan.php" class="btn btn-custom-back shadow-sm">
                                <i class="fas fa-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById("menuBtn").addEventListener("click", function () {
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");
        sidebar.classList.toggle("d-none");
        mainContent.className = sidebar.classList.contains("d-none") ? "col-12 p-4" : "col-md-9 col-lg-10 p-4";
    });
</script>
</body>
</html>