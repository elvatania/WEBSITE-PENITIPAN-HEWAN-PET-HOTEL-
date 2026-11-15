<?php
session_start();
include "../config/koneksi.php";

$id_reservasi = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$query = mysqli_query($koneksi, "
    SELECT reservasi.*, pelanggan.*, kamar.*
    FROM reservasi
    JOIN pelanggan
        ON reservasi.id_pelanggan = pelanggan.id_pelanggan
    LEFT JOIN kamar
        ON reservasi.id_kamar = kamar.id_kamar
    WHERE reservasi.id_reservasi = '$id_reservasi'
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>
        alert('Data reservasi tidak ditemukan!');
        window.location='riwayat_pesanan.php';
    </script>";
    exit();
}
$query_hewan = mysqli_query($koneksi, "
    SELECT hewan.nama_hewan, hewan.jenis_hewan
    FROM reservasi
    JOIN hewan ON reservasi.id_hewan = hewan.id_hewan
    WHERE reservasi.id_reservasi = '$id_reservasi'
");

$daftar_hewan = [];

while ($h = mysqli_fetch_assoc($query_hewan)) {
    $daftar_hewan[] = $h['nama_hewan'] . ' (' . $h['jenis_hewan'] . ')';
}

$str_hewan = !empty($daftar_hewan)
    ? implode(', ', $daftar_hewan)
    : 'Hewan Peliharaan';

$booking_code = "RSV" . str_pad(
    $data['id_reservasi'],
    3,
    "0",
    STR_PAD_LEFT
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Konfirmasi Pembayaran - Pet Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center py-5">
  <div class="container">
    <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5 bg-white mx-auto" style="max-width: 650px;">
      <div class="d-flex align-items-center gap-2 mb-4 pb-3 border-bottom">
        <i class="bi bi-credit-card-fill text-success fs-4"></i>
        <h2 class="h4 fw-bold text-dark mb-0">Konfirmasi Pembayaran</h2>
      </div>
      <div class="p-3 bg-light border rounded-3 mb-4 small">
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted fw-semibold">Booking Code:</span> 
          <span class="fw-bold text-primary"><?= $booking_code; ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2">
          <span class="text-muted fw-semibold">Nama Pelanggan:</span> 
          <span class="fw-semibold text-dark"><?= htmlspecialchars($data['nama_pelanggan'] ?? '-'); ?></span>
        </div>
        <div class="d-flex justify-content-between mb-2 align-items-start">
          <span class="text-muted fw-semibold">Hewan Peliharaan:</span> 
          <span class="fw-semibold text-success text-end" style="max-width: 60%;"><?= htmlspecialchars($str_hewan); ?></span>
        </div>
        <div class="d-flex justify-content-between">
          <span class="text-muted fw-semibold">Durasi Menginap:</span> 
          <span class="fw-semibold text-dark"><?= $data['lama_hari']; ?> Hari (<?= $data['check_in']; ?> s/d <?= $data['check_out']; ?>)</span>
        </div>
      </div>
      <div class="alert alert-primary py-3 px-3 rounded-3 mb-4 small" role="alert">
        <div class="fw-bold mb-1"><i class="bi bi-info-circle-fill"></i> Silakan Transfer Biaya Penitipan ke Rekening Berikut:</div>
        <div>Bank BCA: <strong class="text-dark">123-456-7890</strong> a/n Pet Hotel Admin</div>
      </div>
      <form action="proses_konfirmasi.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        <input type="hidden" name="id_reservasi" value="<?= $data['id_reservasi']; ?>">
        <div class="mb-4">
          <label class="form-label small fw-bold text-muted">Upload Bukti Transfer</label>
          <input type="file" name="bukti_transfer" required class="form-control form-control-sm bg-white">
        </div>
        <button type="submit" class="btn btn-success w-100 py-2 fw-semibold d-flex justify-content-center align-items-center gap-2 mb-3">
          <i class="bi bi-upload"></i> Kirim Bukti Pembayaran
        </button>
      </form>
      <div class="text-center">
        <a href="index.php" class="text-decoration-none text-secondary small">Kembali ke Halaman Utama</a>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>