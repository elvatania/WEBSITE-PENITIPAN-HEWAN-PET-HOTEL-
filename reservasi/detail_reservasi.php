<?php
include "../config/koneksi.php";

$id = $_GET['id'] ?? 0;
$query = mysqli_query($koneksi, "SELECT reservasi.*, pelanggan.*, kamar.* 
                                 FROM reservasi 
                                 JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan 
                                 LEFT JOIN kamar ON reservasi.id_kamar = kamar.id_kamar 
                                 WHERE reservasi.id_reservasi = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data tidak ditemukan!'); window.location='index.php';</script>";
    exit();
}

$id_pelanggan = $data['id_pelanggan'];
$check_in = $data['check_in'];
$check_out = $data['check_out'];

// Ambil daftar hewan peliharaan
$query_hewan = mysqli_query($koneksi, "SELECT hewan.nama_hewan, hewan.jenis_hewan 
                                     FROM reservasi 
                                     JOIN hewan ON reservasi.id_hewan = hewan.id_hewan 
                                     WHERE reservasi.id_pelanggan = '$id_pelanggan' 
                                     AND reservasi.check_in = '$check_in' 
                                     AND reservasi.check_out = '$check_out'");
$daftar_hewan = [];
while ($h = mysqli_fetch_assoc($query_hewan)) {
    $daftar_hewan[] = $h['nama_hewan'] . ' (' . $h['jenis_hewan'] . ')';
}
$str_hewan = !empty($daftar_hewan) ? implode(', ', $daftar_hewan) : 'Hewan Peliharaan';

// Ambil data biaya dari database
$id_reservasi_current = $data['id_reservasi'];
$query_layanan_total = mysqli_query($koneksi, "SELECT total_layanan FROM reservasi WHERE id_reservasi = '$id_reservasi_current' LIMIT 1");
$data_layanan = mysqli_fetch_assoc($query_layanan_total);
$total_layanan = intval($data_layanan['total_layanan'] ?? 0);

$lama_hari = intval($data['lama_hari'] ?? 1);
$harga_per_hari = intval($data['harga'] ?? $data['harga_kamar'] ?? $data['harga_per_hari'] ?? 35000);

// Kalkulasi Total Kamar yang diperbaiki (menggunakan tanda kali *)
$total_kamar = $lama_hari * $harga_per_hari;

// Total Keseluruhan Biaya
$total_biaya = $total_kamar + $total_layanan;
$booking_code = "RSV" . str_pad($data['id_reservasi'], 3, "0", STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detail Reservasi - Pet Hotel</title>
  <!-- Bootstrap 5 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center justify-content-center p-4">

  <div class="card shadow-lg border-0 rounded-4 p-4 w-100" style="max-width: 550px;">
    <h2 class="h4 fw-bold mb-4 pb-2 border-bottom text-dark d-flex align-items-center gap-2">
      <i class="bi bi-file-text text-success"></i> Detail Reservasi Anda
    </h2>

    <div class="card bg-light border-0 p-3 rounded-3 mb-4">
      <div class="d-flex justify-content-between mb-2">
        <span class="text-muted small">Booking Code:</span> 
        <span class="fw-bold text-primary"><?= $booking_code; ?></span>
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span class="text-muted small">Nama Pelanggan:</span> 
        <span class="fw-semibold text-dark"><?= htmlspecialchars($data['nama_pelanggan'] ?? '-'); ?></span>
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span class="text-muted small">No. Telepon:</span> 
        <span class="fw-semibold text-dark"><?= htmlspecialchars($data['no_telepon'] ?? $data['no_telp'] ?? '-'); ?></span>
      </div>
      
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="text-muted small">Hewan Peliharaan:</span> 
        <span class="fw-semibold text-success text-end" style="max-width: 65%;"><?= htmlspecialchars($str_hewan); ?></span>
      </div>

      <div class="d-flex justify-content-between mb-2">
        <span class="text-muted small">Tipe Kamar:</span> 
        <span class="fw-semibold text-dark"><?= htmlspecialchars($data['nama_kamar'] ?? 'Standard'); ?></span>
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span class="text-muted small">Durasi Inap:</span> 
        <span class="fw-semibold text-dark"><?= $lama_hari; ?> Hari (<?= $data['check_in'] ?? '-'; ?> s/d <?= $data['check_out'] ?? '-'; ?>)</span>
      </div>
      
      <div class="d-flex justify-content-between mb-3">
        <span class="text-muted small">Catatan Khusus:</span> 
        <span class="fw-semibold text-secondary fst-italic text-end"><?= htmlspecialchars($data['catatan'] ?? 'Tidak ada catatan'); ?></span>
      </div>
      
      <hr class="text-muted">
      
      <div class="d-flex justify-content-between text-muted small mb-1">
        <span>Total Kamar (<?= $lama_hari; ?> hari x Rp<?= number_format($harga_per_hari, 0, ',', '.'); ?>):</span> 
        <span>Rp<?= number_format($total_kamar, 0, ',', '.'); ?></span>
      </div>
      <div class="d-flex justify-content-between text-muted small mb-2">
        <span>Total Layanan Tambahan:</span> 
        <span>Rp<?= number_format($total_layanan, 0, ',', '.'); ?></span>
      </div>
      
      <hr class="text-muted">
      
      <div class="d-flex justify-content-between align-items-center mt-2">
        <span class="fw-bold text-secondary">Total Tagihan:</span> 
        <span class="fs-5 fw-bolder text-success">Rp<?= number_format($total_biaya, 0, ',', '.'); ?></span>
      </div>
    </div>

    <a href="konfirmasi_pembayaran.php?id=<?= $data['id_reservasi']; ?>" class="btn btn-primary w-100 py-3 rounded-3 fw-semibold d-flex justify-content-center align-items-center gap-2 shadow-sm">
      Lanjut ke Konfirmasi Pembayaran <i class="bi bi-arrow-right"></i>
    </a>
  </div>

  <!-- Bootstrap 5 JS Bundle CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>