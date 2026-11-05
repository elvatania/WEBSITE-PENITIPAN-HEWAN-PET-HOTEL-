<?php
session_start();
include "../config/koneksi.php"; 

// Supaya error database dilempar sebagai exception (bisa ditangkap try/catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$queryPelanggan = mysqli_query($conn, "SELECT * FROM pelanggan ORDER BY nama_pelanggan ASC");
$queryHewan     = mysqli_query($conn, "SELECT * FROM hewan ORDER BY nama_hewan ASC");
// Hanya tampilkan kamar yang kapasitasnya masih > 0
$queryKamar     = mysqli_query($conn, "SELECT * FROM kamar WHERE kapasitas > 0");

$error = '';

if (isset($_POST['simpan'])) {
    $id_pelanggan  = (int)($_POST['id_pelanggan'] ?? 0);
    $id_hewan      = (int)($_POST['id_hewan'] ?? 0);
    $id_kamar      = (int)($_POST['id_kamar'] ?? 0);
    $tgl_reservasi = date('Y-m-d');
    $check_in      = $_POST['check_in'] ?? '';
    $check_out     = $_POST['check_out'] ?? '';
    $status        = 'Proses';
    $bukti_baru    = null;
    $total         = 0;
    $lama_hari     = 1;

    // 1. Validasi tanggal
    $tgl1 = DateTime::createFromFormat('Y-m-d', $check_in);
    $tgl2 = DateTime::createFromFormat('Y-m-d', $check_out);

    if (!$tgl1 || !$tgl2) {
        $error = 'Tanggal check in dan check out wajib diisi.';
    } elseif ($tgl2 < $tgl1) {
        $error = 'Tanggal check out tidak boleh sebelum check in.';
    }

    // 2. Cek kapasitas kamar & ambil harga langsung dari database
    if ($error === '') {
        $sk = mysqli_prepare($conn, "SELECT kapasitas, harga_per_hari FROM kamar WHERE id_kamar = ?");
        mysqli_stmt_bind_param($sk, "i", $id_kamar);
        mysqli_stmt_execute($sk);
        $dataKamar = mysqli_fetch_assoc(mysqli_stmt_get_result($sk));

        if (!$dataKamar || $dataKamar['kapasitas'] <= 0) {
            $error = 'Maaf, kamar yang dipilih sudah penuh (kapasitas habis).';
        } else {
            // Hitung lama hari dan total (dihitung ulang di server, bukan dari browser)
            $lama_hari = $tgl1->diff($tgl2)->days;
            if ($lama_hari <= 0) $lama_hari = 1;
            $total = $lama_hari * (int)$dataKamar['harga_per_hari'];
        }
    }

    // 3. Upload bukti (opsional)
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
                $nama = 'bukti_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$info[2]];

                if (move_uploaded_file($f['tmp_name'], $dir . $nama)) {
                    $bukti_baru = $nama;
                } else {
                    $error = 'Gagal menyimpan file bukti ke folder uploads/bukti.';
                }
            }
        }
    }

    // 4. Simpan semuanya dalam satu transaksi
    if ($error === '') {
        try {
            mysqli_begin_transaction($conn);

            // Simpan reservasi
            $ir = mysqli_prepare($conn, "INSERT INTO reservasi (id_pelanggan, id_hewan, id_kamar, tgl_reservasi, check_in, check_out, lama_hari, status) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($ir, "iiisssis", $id_pelanggan, $id_hewan, $id_kamar, $tgl_reservasi, $check_in, $check_out, $lama_hari, $status);
            mysqli_stmt_execute($ir);
            $id_reservasi = mysqli_insert_id($conn);

            // Kurangi kapasitas kamar 1 (hanya jika masih tersisa)
            $uk = mysqli_prepare($conn, "UPDATE kamar SET kapasitas = kapasitas - 1 WHERE id_kamar = ? AND kapasitas > 0");
            mysqli_stmt_bind_param($uk, "i", $id_kamar);
            mysqli_stmt_execute($uk);
            if (mysqli_stmt_affected_rows($uk) !== 1) {
                throw new Exception('Kamar yang dipilih sudah penuh.');
            }

            // Simpan total & bukti ke tabel pembayaran
            $ib = mysqli_prepare($conn, "INSERT INTO pembayaran (id_reservasi, tgl_bayar, total_bayar, bukti_bayar) VALUES (?, CURDATE(), ?, ?)");
            mysqli_stmt_bind_param($ib, "iis", $id_reservasi, $total, $bukti_baru);
            mysqli_stmt_execute($ib);

            mysqli_commit($conn);

            $_SESSION['toast'] = [
                'type'    => 'success',
                'title'   => 'Berhasil!',
                'message' => 'Reservasi Berhasil Ditambahkan.'
            ];
            header("Location: index.php");
            exit();

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            if ($bukti_baru !== null) {
                @unlink(__DIR__ . '/../uploads/bukti/' . $bukti_baru);
            }
            $error = 'Gagal menambahkan reservasi: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Reservasi - Pet Heaven</title>
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
        .btn-custom-save { background-color: #27557A; border: 1px solid #27557A; color: #ffffff; padding: 8px 20px; border-radius: 8px; }
        .btn-custom-save:hover { background-color: #1e4260; color: #ffffff; }
        .btn-custom-back { background-color: #C9DCDA; border: 1px solid #b2c8c5; color: #27557A; padding: 8px 20px; border-radius: 8px; }
        .btn-custom-back:hover { background-color: #b5cecb; color: #27557A; }
        .bukti-preview { max-width: 200px; max-height: 200px; border-radius: 8px; border: 1px solid #ced4da; object-fit: cover; }
        #totalBayar { font-weight: 700; color: #27557A; background-color: #f1f6f6; }
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
                    <h4 class="mb-0 fs-5 fw-bold">Tambah Reservasi</h4>
                </div>
                <div class="d-flex align-items-center topbar-user">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span class="fw-semibold">Admin</span>
                </div>
            </div>

            <div class="card card-custom-form shadow-sm border-0 overflow-hidden">
                <div class="card-header card-header-form py-3">
                    <h5 class="mb-0 fs-6 fw-bold"><i class="fas fa-plus-circle me-2"></i>Formulir Reservasi Baru</h5>
                </div>
                <div class="card-body p-4">

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <!-- enctype wajib ada supaya file bisa terkirim -->
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Pelanggan</label>
                                <select name="id_pelanggan" class="form-select" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php while ($p = mysqli_fetch_assoc($queryPelanggan)): ?>
                                        <option value="<?= $p['id_pelanggan']; ?>" <?= (($_POST['id_pelanggan'] ?? '') == $p['id_pelanggan']) ? 'selected' : ''; ?>><?= htmlspecialchars($p['nama_pelanggan']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Hewan</label>
                                <select name="id_hewan" class="form-select" required>
                                    <option value="">-- Pilih Hewan --</option>
                                    <?php while ($h = mysqli_fetch_assoc($queryHewan)): ?>
                                        <option value="<?= $h['id_hewan']; ?>" <?= (($_POST['id_hewan'] ?? '') == $h['id_hewan']) ? 'selected' : ''; ?>><?= htmlspecialchars($h['nama_hewan']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-semibold">Pilih Kamar (Tersedia)</label>
                                <select name="id_kamar" id="inputKamar" class="form-select" required>
                                    <option value="">-- Pilih Kamar --</option>
                                    <?php while ($k = mysqli_fetch_assoc($queryKamar)): ?>
                                        <option value="<?= $k['id_kamar']; ?>" data-harga="<?= (int)$k['harga_per_hari']; ?>" <?= (($_POST['id_kamar'] ?? '') == $k['id_kamar']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($k['nama_kamar']); ?> (Sisa Kapasitas: <?= $k['kapasitas']; ?>) - Rp<?= number_format($k['harga_per_hari'], 0, ',', '.'); ?>/hari
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tanggal Check In</label>
                                <input type="date" name="check_in" id="inputCheckIn" class="form-control" value="<?= htmlspecialchars($_POST['check_in'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Tanggal Check Out</label>
                                <input type="date" name="check_out" id="inputCheckOut" class="form-control" value="<?= htmlspecialchars($_POST['check_out'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Total Pembayaran</label>
                                <input type="text" id="totalBayar" class="form-control" placeholder="Terhitung otomatis" readonly>
                                <div class="form-text" id="totalInfo">Pilih kamar dan tanggal untuk melihat total.</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Bukti Pembayaran</label>
                                <input type="file" name="bukti" id="inputBukti" class="form-control" accept="image/png, image/jpeg, image/webp">
                                <div class="form-text">Format JPG, PNG, atau WEBP. Maksimal 2 MB. Boleh dikosongkan dan diunggah nanti lewat Edit.</div>
                                <img id="previewBukti" class="bukti-preview mt-2 d-none" alt="Pratinjau bukti">
                            </div>
                        </div>

                        <div class="d-flex gap-2 pt-2">
                            <button type="submit" name="simpan" class="btn btn-custom-save shadow-sm">
                                <i class="fas fa-save me-1"></i> Simpan Reservasi
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

    // ===== Hitung total otomatis =====
    const kamar     = document.getElementById("inputKamar");
    const checkIn   = document.getElementById("inputCheckIn");
    const checkOut  = document.getElementById("inputCheckOut");
    const totalBox  = document.getElementById("totalBayar");
    const totalInfo = document.getElementById("totalInfo");

    function hitungTotal() {
        const opt   = kamar.options[kamar.selectedIndex];
        const harga = parseInt(opt && opt.dataset.harga ? opt.dataset.harga : 0, 10);

        if (!harga || !checkIn.value || !checkOut.value) {
            totalBox.value = "";
            totalInfo.textContent = "Pilih kamar dan tanggal untuk melihat total.";
            return;
        }

        const a = new Date(checkIn.value);
        const b = new Date(checkOut.value);

        if (b < a) {
            totalBox.value = "";
            totalInfo.textContent = "Check out tidak boleh sebelum check in.";
            return;
        }

        const hari = Math.max(1, Math.round((b - a) / 86400000));
        totalBox.value = "Rp " + (hari * harga).toLocaleString("id-ID");
        totalInfo.textContent = hari + " hari × Rp " + harga.toLocaleString("id-ID");
    }

    checkIn.addEventListener("change", function () {
        checkOut.min = checkIn.value; // check out tidak bisa sebelum check in
        hitungTotal();
    });
    checkOut.addEventListener("change", hitungTotal);
    kamar.addEventListener("change", hitungTotal);
    hitungTotal();

    // ===== Pratinjau bukti =====
    const inputBukti   = document.getElementById("inputBukti");
    const previewBukti = document.getElementById("previewBukti");
    inputBukti.addEventListener("change", function () {
        const file = this.files[0];
        if (file) {
            previewBukti.src = URL.createObjectURL(file);
            previewBukti.classList.remove("d-none");
        } else {
            previewBukti.classList.add("d-none");
        }
    });
});
</script>
</body>
</html>