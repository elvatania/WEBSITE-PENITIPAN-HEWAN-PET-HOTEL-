<?php
session_start();
include "../config/koneksi.php";

// Supaya error database dilempar sebagai exception (bisa ditangkap try/catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = (int)($_GET['id'] ?? 0);

// Ambil data reservasi + harga kamar + data pembayaran (kalau sudah ada)
$stmt = mysqli_prepare($conn, "
    SELECT reservasi.*,
           reservasi.lama_hari * kamar.harga_per_hari AS total_hitung,
           pembayaran.id_bayar, pembayaran.total_bayar, pembayaran.bukti_bayar
    FROM reservasi
    JOIN kamar ON reservasi.id_kamar = kamar.id_kamar
    LEFT JOIN pembayaran ON pembayaran.id_reservasi = reservasi.id_reservasi
    WHERE reservasi.id_reservasi = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    header("Location: index.php");
    exit();
}

$id_kamar_lama = (int)$data['id_kamar'];
$total_tampil  = $data['id_bayar'] ? (int)$data['total_bayar'] : (int)$data['total_hitung'];
$error = '';

if (isset($_POST['update'])) {
    $status_baru  = $_POST['status'] ?? '';
    $bukti_baru   = null;
    $status_valid = ['Proses', 'Selesai', 'Dibatalkan'];

    if (!in_array($status_baru, $status_valid, true)) {
        $error = 'Status reservasi tidak valid.';
    }

    // 1. Proses upload bukti (opsional)
    if ($error === '' && isset($_FILES['bukti']) && $_FILES['bukti']['error'] !== UPLOAD_ERR_NO_FILE) {
        $f = $_FILES['bukti'];

        if ($f['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload bukti gagal. Coba pilih file lagi.';
        } elseif ($f['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran bukti maksimal 2 MB.';
        } else {
            $info    = @getimagesize($f['tmp_name']);
            $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];

            if (!$info || !isset($allowed[$info[2]])) {
                $error = 'Bukti harus berupa gambar JPG, PNG, atau WEBP.';
            } else {
                $dir = __DIR__ . '/../uploads/bukti/';
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $nama = 'bukti_' . $id . '_' . time() . '.' . $allowed[$info[2]];

                if (move_uploaded_file($f['tmp_name'], $dir . $nama)) {
                    $bukti_baru = $nama;
                } else {
                    $error = 'Gagal menyimpan file bukti ke folder uploads/bukti.';
                }
            }
        }
    }

    // 2. Simpan ke database
    if ($error === '') {
        $file_lama_hapus = null;

        try {
            mysqli_begin_transaction($conn);

            // Update status reservasi
            $up = mysqli_prepare($conn, "UPDATE reservasi SET status = ? WHERE id_reservasi = ?");
            mysqli_stmt_bind_param($up, "si", $status_baru, $id);
            mysqli_stmt_execute($up);

            // Sesuaikan kapasitas kamar mengikuti perubahan status
            // (tabel kamar memakai kolom kapasitas, bukan kolom status)
            $lama_aktif = ($data['status'] === 'Proses');
            $baru_aktif = ($status_baru === 'Proses');

            if ($lama_aktif && !$baru_aktif) {
                // Selesai / Dibatalkan: kamar kembali tersedia, kapasitas +1
                $uk = mysqli_prepare($conn, "UPDATE kamar SET kapasitas = kapasitas + 1 WHERE id_kamar = ?");
                mysqli_stmt_bind_param($uk, "i", $id_kamar_lama);
                mysqli_stmt_execute($uk);
            } elseif (!$lama_aktif && $baru_aktif) {
                // Diaktifkan lagi (Proses): kapasitas -1, hanya jika masih tersisa
                $uk = mysqli_prepare($conn, "UPDATE kamar SET kapasitas = kapasitas - 1 WHERE id_kamar = ? AND kapasitas > 0");
                mysqli_stmt_bind_param($uk, "i", $id_kamar_lama);
                mysqli_stmt_execute($uk);
                if (mysqli_stmt_affected_rows($uk) !== 1) {
                    throw new Exception('Kamar sudah penuh, reservasi tidak bisa diaktifkan kembali.');
                }
            }

            // Simpan bukti ke tabel pembayaran
            if ($bukti_baru !== null) {
                if ($data['id_bayar']) {
                    $ub = mysqli_prepare($conn, "UPDATE pembayaran SET bukti_bayar = ? WHERE id_bayar = ?");
                    $id_bayar = (int)$data['id_bayar'];
                    mysqli_stmt_bind_param($ub, "si", $bukti_baru, $id_bayar);
                    mysqli_stmt_execute($ub);

                    // File bukti lama dihapus setelah database pasti berhasil
                    if (!empty($data['bukti_bayar'])) {
                        $file_lama_hapus = $data['bukti_bayar'];
                    }
                } else {
                    $ib = mysqli_prepare($conn, "INSERT INTO pembayaran (id_reservasi, tgl_bayar, total_bayar, bukti_bayar) VALUES (?, CURDATE(), ?, ?)");
                    mysqli_stmt_bind_param($ib, "iis", $id, $total_tampil, $bukti_baru);
                    mysqli_stmt_execute($ib);
                }
            }

            mysqli_commit($conn);

            if ($file_lama_hapus !== null) {
                @unlink(__DIR__ . '/../uploads/bukti/' . basename($file_lama_hapus));
            }

            $_SESSION['toast'] = [
                'type'    => 'success',
                'title'   => 'Berhasil!',
                'message' => 'Data Reservasi Berhasil Diperbarui.'
            ];
            header("Location: index.php");
            exit();

        } catch (Throwable $e) {
            // Kalau database gagal, semua perubahan dibatalkan dan file yang sudah terupload dibuang lagi
            mysqli_rollback($conn);
            if ($bukti_baru !== null) {
                @unlink(__DIR__ . '/../uploads/bukti/' . $bukti_baru);
            }
            $error = 'Gagal memperbarui data: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reservasi - Pet Heaven</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <style>
        body { background-color: #F6F3EB; color: #333333; font-family: system-ui, -apple-system, sans-serif; }
        .sidebar-wrapper { background-color: #27557A; transition: all 0.3s ease; }
        .sidebar-wrapper .nav-link { color: #C5D8D5; padding: 12px 18px; border-radius: 8px; margin-bottom: 6px; }
        .sidebar-wrapper .nav-link:hover { color: #ffffff; background-color: rgba(255, 255, 255, 0.12); }
        .sidebar-wrapper .nav-link.active { color: #ffffff; background-color: #5B8FA8; }
        .topbar-header { background-color: #C9DCDA; border: 1px solid rgba(91, 143, 168, 0.3); }
        .topbar-header h4, .topbar-header .topbar-user { color: #27557A; }
        #menuBtn { background-color: #ffffff; border: 1px solid #5B8FA8; color: #27557A; }
        .card-custom-form { background-color: #ffffff; border: 1px solid rgba(91, 143, 168, 0.2); border-radius: 12px; }
        .card-header-form { background-color: #27557A; color: #ffffff; border-top-left-radius: 11px; border-top-right-radius: 11px; }
        .form-control:focus, .form-select:focus { border-color: #5B8FA8; box-shadow: 0 0 0 0.25rem rgba(91, 143, 168, 0.25); }
        .form-label { color: #27557A; }
        .btn-custom-update { background-color: #27557A; border: 1px solid #27557A; color: #ffffff; padding: 8px 20px; border-radius: 8px; }
        .btn-custom-update:hover { background-color: #1e4260; color: #ffffff; }
        .btn-custom-back { background-color: #C9DCDA; border: 1px solid #b2c8c5; color: #27557A; padding: 8px 20px; border-radius: 8px; }
        .btn-custom-back:hover { background-color: #b5cecb; color: #27557A; }
        .bukti-preview { max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ced4da; object-fit: cover; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper min-vh-100 p-3 text-white d-none" id="sidebar">
            <div class="text-center pb-3 mb-3 border-bottom border-secondary">
                <img src="../config/assets/images/logo.png" alt="Logo" width="180" class="mb-2 d-block mx-auto img-fluid">
                <h5 class="fw-bold mb-0">Pet Heaven</h5>
            </div>
            <nav class="nav flex-column">
                <a href="../dashboard.php" class="nav-link"><i class="fas fa-house me-2"></i> Dashboard</a>
                <a href="../pelanggan/index.php" class="nav-link"><i class="fas fa-user me-2"></i> Pelanggan</a>
                <a href="../hewan/index.php" class="nav-link"><i class="fas fa-paw me-2"></i> Hewan</a>
                <a href="index.php" class="nav-link active"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="../kamar/index.php" class="nav-link"><i class="fas fa-bed me-2"></i> Kamar</a>
                <a href="../layanan.php" class="nav-link"><i class="fas fa-scissors me-2"></i> Layanan</a>
                <a href="../pembayaran.php" class="nav-link"><i class="fas fa-wallet me-2"></i> Pembayaran</a>
                <a href="../laporan.php" class="nav-link"><i class="fas fa-file me-2"></i> Laporan</a>
                <a href="../logout.php" class="nav-link text-danger mt-4"><i class="fas fa-right-from-bracket me-2"></i> Logout</a>
            </nav>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-12 p-4" id="mainContent">
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 rounded shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm px-3 py-2 me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h4 class="mb-0 fs-5 fw-bold">Edit Reservasi</h4>
                </div>
                <div class="d-flex align-items-center topbar-user">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span class="fw-semibold">Admin</span>
                </div>
            </div>

            <div class="card card-custom-form shadow-sm border-0 overflow-hidden">
                <div class="card-header card-header-form py-3">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fas fa-pen-to-square me-2"></i>Ubah Reservasi (RSV<?= str_pad($data['id_reservasi'], 3, "0", STR_PAD_LEFT); ?>)</h5>
                </div>
                <div class="card-body p-4">

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <!-- enctype wajib ada supaya file bisa terkirim -->
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status Reservasi</label>
                            <select name="status" class="form-select" required>
                                <option value="Proses" <?= ($data['status'] == 'Proses') ? 'selected' : ''; ?>>Proses (Kamar Terisi)</option>
                                <option value="Selesai" <?= ($data['status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai (Kamar Kembali Tersedia)</option>
                                <option value="Dibatalkan" <?= ($data['status'] == 'Dibatalkan') ? 'selected' : ''; ?>>Dibatalkan (Kamar Kembali Tersedia)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Total Pembayaran</label>
                            <input type="text" class="form-control" value="Rp <?= number_format($total_tampil, 0, ',', '.'); ?>" readonly>
                            <div class="form-text">
                                <?= $data['id_bayar']
                                    ? 'Total sudah tercatat di data pembayaran.'
                                    : 'Dihitung otomatis: lama menginap × harga kamar per malam.'; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Bukti Pembayaran</label>

                            <?php if (!empty($data['bukti_bayar'])): ?>
                                <div class="mb-2">
                                    <a href="../uploads/bukti/<?= htmlspecialchars($data['bukti_bayar']); ?>" target="_blank">
                                        <img src="../uploads/bukti/<?= htmlspecialchars($data['bukti_bayar']); ?>" alt="Bukti saat ini" class="bukti-preview">
                                    </a>
                                    <div class="form-text">Bukti saat ini. Pilih file baru di bawah kalau ingin menggantinya.</div>
                                </div>
                            <?php endif; ?>

                            <input type="file" name="bukti" id="inputBukti" class="form-control" accept="image/png, image/jpeg, image/webp">
                            <div class="form-text">Format JPG, PNG, atau WEBP. Maksimal 2 MB. Kosongkan kalau tidak ingin mengubah.</div>
                            <img id="previewBaru" class="bukti-preview mt-2 d-none" alt="Pratinjau bukti baru">
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" name="update" class="btn btn-custom-update shadow-sm">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
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

    // Pratinjau gambar yang baru dipilih
    const inputBukti = document.getElementById("inputBukti");
    const previewBaru = document.getElementById("previewBaru");
    inputBukti.addEventListener("change", function () {
        const file = this.files[0];
        if (file) {
            previewBaru.src = URL.createObjectURL(file);
            previewBaru.classList.remove("d-none");
        } else {
            previewBaru.classList.add("d-none");
        }
    });
});
</script>
</body>
</html>