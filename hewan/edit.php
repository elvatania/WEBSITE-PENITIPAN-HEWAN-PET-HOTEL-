<?php
session_start();
include "../config/koneksi.php";

$id = $_GET['id'] ?? '';
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM hewan WHERE id_hewan = '$id'"));

if (!$data) {
    header("Location: index.php");
    exit();
}

$queryPelanggan = mysqli_query($conn, "SELECT id_pelanggan, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan ASC");

if (isset($_POST['update'])) {
    $id_pelanggan   = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    $nama_hewan     = mysqli_real_escape_string($conn, $_POST['nama_hewan']);
    $jenis_hewan    = mysqli_real_escape_string($conn, $_POST['jenis_hewan']);
    $ras            = mysqli_real_escape_string($conn, $_POST['ras']);
    $jenis_kelamin  = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $usia_hewan     = mysqli_real_escape_string($conn, $_POST['usia_hewan']);
    $berat_hewan    = mysqli_real_escape_string($conn, $_POST['berat_hewan']);
    $kondisi        = mysqli_real_escape_string($conn, $_POST['kondisi']);
    $catatan        = mysqli_real_escape_string($conn, $_POST['catatan']);

    $update = mysqli_query($conn, "UPDATE hewan SET 
        id_pelanggan='$id_pelanggan', 
        nama_hewan='$nama_hewan', 
        jenis_hewan='$jenis_hewan', 
        ras='$ras', 
        jenis_kelamin='$jenis_kelamin', 
        usia_hewan='$usia_hewan', 
        berat_hewan='$berat_hewan', 
        kondisi='$kondisi',
        catatan='$catatan'
        WHERE id_hewan='$id'");

    if ($update) {
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data Hewan Berhasil Diperbarui.'
        ];
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Gagal!',
            'message' => 'Gagal Memperbarui Data.'
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
    <title>Edit Data Hewan - Pet Heaven</title>
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
                <a href="index.php" class="nav-link active"><i class="fas fa-paw me-2"></i> Hewan</a>
                <a href="../reservasi/index.php" class="nav-link"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="../kamar/index.php" class="nav-link"><i class="fas fa-bed me-2"></i> Kamar</a>
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
                    <h4 class="mb-0 fs-5 fw-bold">Edit Data Hewan</h4>
                </div>
                <div class="d-flex align-items-center topbar-user">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span class="fw-semibold">Admin</span>
                </div>
            </div>

            <!-- CARD FORM -->
            <div class="card card-custom-form shadow-sm border-0 overflow-hidden">
                <div class="card-header card-header-form py-3">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fas fa-pen-to-square me-2"></i>Formulir Edit Hewan</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Pemilik (Pelanggan)</label>
                                <select name="id_pelanggan" class="form-select" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php while ($p = mysqli_fetch_assoc($queryPelanggan)): ?>
                                        <option value="<?= $p['id_pelanggan']; ?>" <?= ($p['id_pelanggan'] == $data['id_pelanggan']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($p['nama_pelanggan']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Nama Hewan</label>
                                <input type="text" name="nama_hewan" class="form-control" value="<?= htmlspecialchars($data['nama_hewan']); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Jenis Hewan</label>
                                <input type="text" name="jenis_hewan" class="form-control" value="<?= htmlspecialchars($data['jenis_hewan']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Ras</label>
                                <input type="text" name="ras" class="form-control" value="<?= htmlspecialchars($data['ras']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Jenis Kelamin</label>
                                <select name="jenis_kelamin" class="form-select" required>
                                    <option value="Jantan" <?= ($data['jenis_kelamin'] == 'Jantan') ? 'selected' : ''; ?>>Jantan</option>
                                    <option value="Betina" <?= ($data['jenis_kelamin'] == 'Betina') ? 'selected' : ''; ?>>Betina</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Usia Hewan (Tahun)</label>
                                <input type="number" name="usia_hewan" class="form-control" value="<?= htmlspecialchars($data['usia_hewan']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Berat Hewan (Kg)</label>
                                <input type="number" step="0.01" name="berat_hewan" class="form-control" value="<?= htmlspecialchars($data['berat_hewan']); ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Kondisi</label>
                                <input type="text" name="kondisi" class="form-control" value="<?= htmlspecialchars($data['kondisi']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catatan / Request Makanan</label>
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Contoh: Makanan basah, alergi ayam, dll"><?= htmlspecialchars($data['catatan'] ?? ''); ?></textarea>
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