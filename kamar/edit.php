<?php
session_start();
include "../config/koneksi.php";

$id = $_GET['id'] ?? '';
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kamar WHERE id_kamar = '$id'"));

if (!$data) {
    header("Location: index.php");
    exit();
}

if (isset($_POST['update'])) {
    $nama_kamar     = mysqli_real_escape_string($conn, $_POST['nama_kamar']);
    $jenis_kamar    = mysqli_real_escape_string($conn, $_POST['jenis_kamar']);
    $harga_per_hari = mysqli_real_escape_string($conn, $_POST['harga_per_hari']);
    $kapasitas      = mysqli_real_escape_string($conn, $_POST['kapasitas']); // Diubah ke kapasitas

    $update = mysqli_query($conn, "UPDATE kamar SET nama_kamar='$nama_kamar', jenis_kamar='$jenis_kamar', harga_per_hari='$harga_per_hari', kapasitas='$kapasitas' WHERE id_kamar='$id'");

    if ($update) {
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data Kamar Berhasil Diperbarui.'
        ];
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Gagal!',
            'message' => 'Gagal Memperbarui Data Kamar.'
        ];
    }
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kamar - Pet Heaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <style>
        body {
            background-color: #F6F3EB;
            color: #333333;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        .sidebar-wrapper {
            background-color: #27557A;
            transition: all 0.3s ease;
        }
        .sidebar-wrapper .nav-link {
            color: #C5D8D5;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: all 0.25s ease;
        }
        .sidebar-wrapper .nav-link:hover {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.12);
        }
        .sidebar-wrapper .nav-link.active {
            color: #ffffff;
            background-color: #5B8FA8;
        }
        .sidebar-wrapper .border-secondary {
            border-color: rgba(197, 216, 213, 0.25);
        }

        .topbar-header {
            background-color: #C9DCDA;
            border: 1px solid rgba(91, 143, 168, 0.3);
        }
        .topbar-header h4,
        .topbar-header .topbar-user {
            color: #27557A;
        }

        #menuBtn {
            background-color: #ffffff;
            border: 1px solid #5B8FA8;
            color: #27557A;
        }
        #menuBtn:hover {
            background-color: #5B8FA8;
            color: #ffffff;
        }

        .card-custom-form {
            background-color: #ffffff;
            border: 1px solid rgba(91, 143, 168, 0.2);
            border-radius: 12px;
        }
        .card-header-form {
            background-color: #27557A;
            color: #ffffff;
            border-top-left-radius: 11px;
            border-top-right-radius: 11px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #5B8FA8;
            box-shadow: 0 0 0 0.25rem rgba(91, 143, 168, 0.25);
        }
        .form-label {
            color: #27557A;
        }

        .btn-custom-update {
            background-color: #27557A;
            border: 1px solid #27557A;
            color: #ffffff;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-custom-update:hover {
            background-color: #1e4260;
            border-color: #1e4260;
            color: #ffffff;
        }

        .btn-custom-back {
            background-color: #C9DCDA;
            border: 1px solid #b2c8c5;
            color: #27557A;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .btn-custom-back:hover {
            background-color: #b5cecb;
            color: #27557A;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- ================= SIDEBAR ================= -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper min-vh-100 p-3 text-white d-none" id="sidebar">
            <div class="text-center pb-3 mb-3 border-bottom border-secondary">
                <img src="../config/assets/images/logo.png" alt="Logo" width="180" class="mb-2 d-block mx-auto img-fluid">
                <h5 class="fw-bold mb-0">Pet Heaven</h5>
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
        <div class="col-12 p-4" id="mainContent">
            <!-- TOPBAR -->
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 rounded shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm px-3 py-2 me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h4 class="mb-0 fs-5 fw-bold">Edit Kamar</h4>
                </div>
                <div class="d-flex align-items-center topbar-user">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span class="fw-semibold">Admin</span>
                </div>
            </div>

            <!-- CARD FORM -->
            <div class="card card-custom-form shadow-sm border-0 overflow-hidden">
                <div class="card-header card-header-form py-3">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fas fa-pen-to-square me-2"></i>Formulir Edit Kamar</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nama Kamar</label>
                                <input type="text" name="nama_kamar" class="form-control" value="<?= htmlspecialchars($data['nama_kamar']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Jenis Kamar</label>
                                <input type="text" name="jenis_kamar" class="form-control" value="<?= htmlspecialchars($data['jenis_kamar']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Harga per Hari (Rp)</label>
                                <input type="number" name="harga_per_hari" class="form-control" value="<?= htmlspecialchars($data['harga_per_hari']); ?>" required>
                            </div>
                            <!-- INPUT KAPASITAS -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Kapasitas (Ekor)</label>
                                <input type="number" name="kapasitas" class="form-control" value="<?= htmlspecialchars($data['kapasitas']); ?>" min="1" required>
                            </div>
                        </div>
                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" name="update" class="btn btn-custom-update shadow-sm">
                                <i class="fas fa-save me-1"></i> Update Data
                            </button>
                            <a href="index.php" class="btn btn-custom-back shadow-sm">
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
    document.addEventListener("DOMContentLoaded", function () {
        const menuBtn = document.getElementById("menuBtn");
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");

        menuBtn.addEventListener("click", function () {
            if (sidebar.classList.contains("d-none")) {
                sidebar.classList.remove("d-none");
                mainContent.className = "col-md-9 col-lg-10 p-4";
            } else {
                sidebar.classList.add("d-none");
                mainContent.className = "col-12 p-4";
            }
        });
    });
</script>
</body>
</html>