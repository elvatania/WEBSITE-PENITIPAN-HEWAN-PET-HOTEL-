<?php
include "config/koneksi.php";

$id = $_GET['id'] ?? 0;

// Perbaiki query dengan menggunakan SELECT * atau menyesuaikan kolom harga
$query = mysqli_query($koneksi, "SELECT reservasi.*, pelanggan.*, hewan.*, kamar.*, 
                                 hewan.nama_hewan AS nama_hewan_peliharaan, 
                                 kamar.nama_kamar AS nama_kamar_inap
                                 FROM reservasi 
                                 JOIN pelanggan ON reservasi.id_pelanggan = pelanggan.id_pelanggan 
                                 LEFT JOIN hewan ON reservasi.id_hewan = hewan.id_hewan 
                                 LEFT JOIN kamar ON reservasi.id_kamar = kamar.id_kamar 
                                 WHERE reservasi.id_reservasi = '$id'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    echo "<script>alert('Data struk tidak ditemukan!'); window.location='index.php';</script>";
    exit();
}

// Ambil tarif kamar dengan aman dari database
$tarif_kamar = $data['harga_kamar'] ?? $data['harga'] ?? $data['tarif'] ?? 50000;
$lama_hari   = $data['lama_hari'] ?? 1;
$total_layanan = $data['total_layanan'] ?? 0;

// Hitung total keseluruhan (Kamar * Hari + Layanan)
$subtotal_kamar = $lama_hari * $tarif_kamar;
$total_biaya    = $subtotal_kamar + $total_layanan;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Struk Reservasi - #<?= $data['id_reservasi']; ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background: white !important; }
      .kartu-utama { border: none !important; box-shadow: none !important; }
    }
  </style>
</head>
<body class="halaman-warna min-h-screen p-6 flex items-center justify-center">

  <div class="kartu-utama w-full max-w-md rounded-2xl shadow-lg p-6 border border-gray-200"> 
    
    <!-- Header -->
    <div class="text-center border-b pb-4 mb-4">
      <h2 class="text-lg font-bold text-[#3368A0]">PET HOTEL</h2>
      <p class="text-xs text-gray-500">Bukti Reservasi & Transaksi Sah</p>
    </div>

    <!-- Info Transaksi -->
    <div class="text-xs space-y-2 mb-4">
      <div class="flex justify-between"><span class="text-gray-500">No. Invoice:</span> <span class="font-bold text-[#3368A0]">#INV-<?= $data['id_reservasi']; ?></span></div>
      <div class="flex justify-between"><span class="text-gray-500">Tanggal Pesan:</span> <span class="font-semibold text-[#3368A0]"><?= $data['tgl_reservasi'] ?? date('Y-m-d'); ?></span></div>
      <div class="flex justify-between"><span class="text-gray-500">Nama Pelanggan:</span> <span class="font-semibold text-[#3368A0]"><?= htmlspecialchars($data['nama_pelanggan'] ?? '-'); ?></span></div>
      <div class="flex justify-between"><span class="text-gray-500">No. Telepon:</span> <span class="font-semibold text-[#3368A0]"><?= htmlspecialchars($data['no_telp'] ?? '-'); ?></span></div>
    </div>

    <!-- Rincian Biaya -->
    <div class="border-t border-b py-3 my-2 text-xs space-y-2 border-gray-200">
      <div class="flex justify-between"><span class="text-gray-500">Hewan:</span> <span class="font-semibold text-[#3368A0]"><?= htmlspecialchars($data['nama_hewan_peliharaan'] ?? '-'); ?></span></div>
      <div class="flex justify-between"><span class="text-gray-500">Kamar:</span> <span class="font-semibold text-[#3368A0]"><?= htmlspecialchars($data['nama_kamar_inap'] ?? 'Standard'); ?> (<?= $data['lama_hari'] ?? 1; ?> Hari)</span></div>
      <div class="flex justify-between font-bold text-sm pt-2"><span class="text-gray-700">Total Biaya:</span> <span class="text-[#3368A0]">Rp<?= number_format($total_biaya, 0, ',', '.'); ?></span></div>
    </div>

    <!-- Tombol Aksi Berwarna Background -->
    <div class="mt-6 flex gap-3 no-print">
      <button onclick="window.print()" class="w-1/2 bg-[#3368A0] hover:bg-[#66A3BF] text-white font-semibold py-2.5 rounded-xl text-xs flex justify-center items-center gap-1 shadow transition">
        <i data-lucide="printer" class="w-4 h-4"></i> Cetak Struk
      </button>
      <a href="index.php" class="w-1/2 bg-[#C8DFDB] hover:bg-[#b0d2cb] text-[#3368A0] font-semibold py-2.5 rounded-xl text-xs text-center transition flex justify-center items-center">
        Selesai
      </a>
    </div>

  </div>

  <script>lucide.createIcons();</script>
</body>
</html>