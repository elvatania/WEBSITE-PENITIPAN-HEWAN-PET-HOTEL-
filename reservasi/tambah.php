<?php
include "../config/koneksi.php";

$queryPelanggan = mysqli_query($conn, "SELECT id_pelanggan, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan ASC");
$queryHewan     = mysqli_query($conn, "SELECT id_hewan, nama_hewan, jenis_hewan FROM hewan ORDER BY nama_hewan ASC");
$queryKamar     = mysqli_query($conn, "SELECT * FROM kamar ORDER BY id_kamar ASC");

if (isset($_POST['simpan'])) {
    $id_pelanggan = mysqli_real_escape_string($conn, $_POST['id_pelanggan']);
    $id_hewan     = mysqli_real_escape_string($conn, $_POST['id_hewan']);
    $id_kamar     = mysqli_real_escape_string($conn, $_POST['id_kamar']);
    $status       = mysqli_real_escape_string($conn, $_POST['status']);
    $check_in     = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out    = mysqli_real_escape_string($conn, $_POST['check_out']);

    $insert = mysqli_query($conn, "INSERT INTO reservasi (id_pelanggan, id_hewan, id_kamar, status, check_in, check_out) 
                                   VALUES ('$id_pelanggan', '$id_hewan', '$id_kamar', '$status', '$check_in', '$check_out')");

    if ($insert) {
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Data Reservasi - Pet Heaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar-wrapper { background-color: #1a233a; }
        .sidebar-wrapper .nav-link { color: #a6b0cf; padding: 12px 20px; border-radius: 6px; margin-bottom: 4px; }
        .sidebar-wrapper .nav-link.active { color: #ffffff !important; background-color: #0d6efd !important; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper min-vh-100 p-3 text-white d-none" id="sidebar">
            <div class="text-center pb-3 mb-3 border-bottom border-secondary">
                <img src="../assets/images/logo.png" alt="Logo" width="80" class="mb-2 d-block mx-auto">
                <h5 class="fw-bold mb-0">Pet Heaven</h5>
            </div>
            <nav class="nav flex-column">
                <a href="../dashboard.php" class="nav-link"><i class="fas fa-house me-2"></i> Dashboard</a>
                <a href="../pelanggan/index.php" class="nav-link"><i class="fas fa-user me-2"></i> Pelanggan</a>
                <a href="../hewan/index.php" class="nav-link"><i class="fas fa-paw me-2"></i> Hewan</a>
                <a href="index.php" class="nav-link active"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="../kamar.php" class="nav-link"><i class="fas fa-bed me-2"></i> Kamar</a>
                <a href="../layanan.php" class="nav-link"><i class="fas fa-scissors me-2"></i> Layanan</a>
                <a href="../pembayaran.php" class="nav-link"><i class="fas fa-wallet me-2"></i> Pembayaran</a>
                <a href="../laporan.php" class="nav-link"><i class="fas fa-file me-2"></i> Laporan</a>
                <a href="../logout.php" class="nav-link text-danger mt-4"><i class="fas fa-right-from-bracket me-2"></i> Logout</a>
            </nav>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-12 p-4" id="mainContent">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-secondary me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h4 class="mb-0 fs-5 fw-bold">Tambah Data Reservasi</h4>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <div class="row">
                            <!-- Pelanggan -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Pelanggan</label>
                                <select name="id_pelanggan" id="selectPelanggan" class="form-select" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php while ($p = mysqli_fetch_assoc($queryPelanggan)): ?>
                                        <option value="<?= $p['id_pelanggan']; ?>"><?= htmlspecialchars($p['nama_pelanggan']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <!-- Hewan -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Hewan</label>
                                <select name="id_hewan" id="selectHewan" class="form-select" required>
                                    <option value="">-- Pilih Hewan --</option>
                                    <?php
                                    $queryHewanAll = mysqli_query($conn, "SELECT id_hewan, id_pelanggan, nama_hewan, jenis_hewan FROM hewan ORDER BY nama_hewan ASC");
                                     while ($h = mysqli_fetch_assoc($queryHewan)): 
                                    ?>
                                    <option value="<?= $h['id_hewan']; ?>" data-pelanggan="<?= $h['id_pelanggan']; ?>">
                                      <?= htmlspecialchars($h['nama_hewan']); ?> (<?= htmlspecialchars($h['jenis_hewan']); ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Kamar -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Kamar</label>
                                <select name="id_kamar" class="form-select" required>
                                    <option value="">-- Pilih Kamar --</option>
                                    <?php while ($k = mysqli_fetch_assoc($queryKamar)): ?>
                                        <option value="<?= $k['id_kamar']; ?>">
                                            <?= htmlspecialchars(isset($k['jenis_kamar']) ? $k['jenis_kamar'] : (isset($k['tipe_kamar']) ? $k['tipe_kamar'] : 'Kamar #' . $k['id_kamar'])); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <!-- Status -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status" class="form-select" required>
                                    <option value="Proses">Proses</option>
                                    <option value="Selesai">Selesai</option>
                                    <option value="Batal">Batal</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tanggal Check-In -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tanggal Check-In</label>
                                <input type="date" name="check_in" class="form-control" required>
                            </div>
                            <!-- Tanggal Check-Out -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tanggal Check-Out</label>
                                <input type="date" name="check_out" class="form-control" required>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" name="simpan" class="btn btn-primary"><i class="fas fa-save me-1"></i> Simpan</button>
                            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const selectPelanggan = document.getElementById("selectPelanggan");
    const selectHewan = document.getElementById("selectHewan");
    const allHewanOptions = Array.from(selectHewan.querySelectorAll("option"));

    selectPelanggan.addEventListener("change", function () {
        const selectedPelangganId = this.value;
        
        // Reset pilihan hewan
        selectHewan.innerHTML = '<option value="">-- Pilih Hewan --</option>';

        if (selectedPelangganId) {
            // Saring hanya hewan yang id_pelanggan-nya cocok
            const filteredOptions = allHewanOptions.filter(opt => {
                return opt.getAttribute("data-pelanggan") === selectedPelangganId;
            });

            if (filteredOptions.length > 0) {
                filteredOptions.forEach(opt => selectHewan.appendChild(opt));
            } else {
                selectHewan.innerHTML = '<option value="">-- Tidak ada hewan terdaftar --</option>';
            }
        }
    });
});
</script>
</body>
</html>