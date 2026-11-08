
<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include "../config/koneksi.php";

// Cek login
if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil data reservasi, pelanggan, dan kamar
$query = mysqli_query($koneksi, "
    SELECT
        r.*,
        p.nama_pelanggan,
        p.no_telp,
        k.nama_kamar,
        k.harga_per_hari,
        h.nama_hewan AS nama_hewan_lama
    FROM reservasi r
    JOIN pelanggan p
        ON r.id_pelanggan = p.id_pelanggan
    LEFT JOIN kamar k
        ON r.id_kamar = k.id_kamar
    LEFT JOIN hewan h
        ON r.id_hewan = h.id_hewan
    WHERE r.id_reservasi = '$id'
    AND r.id_pelanggan = '$id_pelanggan'
");

$data = mysqli_fetch_assoc($query);

// Jika data tidak ditemukan
if (!$data) {
    echo "<script>
        alert('Struk tidak ditemukan atau bukan milik akun Anda!');
        window.location='riwayat_pesanan.php';
    </script>";
    exit();
}

$booking_code = "RSV" . str_pad($data['id_reservasi'], 3, "0", STR_PAD_LEFT);

// Tanggal dan waktu pemesanan
$tanggal_pesan = !empty($data['waktu_reservasi'])
    ? date('d-m-Y', strtotime($data['waktu_reservasi']))
    : date('d-m-Y', strtotime($data['tgl_reservasi']));

$jam_pesan = '-';

if (
    !empty($data['waktu_reservasi']) &&
    date('H:i:s', strtotime($data['waktu_reservasi'])) != '00:00:00'
) {
    $jam_pesan = date('H:i', strtotime($data['waktu_reservasi'])) . ' WIB';
}

// Ambil semua hewan dalam reservasi
$query_hewan = mysqli_query($koneksi, "
    SELECT h.*
    FROM detail_reservasi dr
    JOIN hewan h ON dr.id_hewan = h.id_hewan
    WHERE dr.id_reservasi = '$id'
");

$daftar_hewan = [];

while ($hewan = mysqli_fetch_assoc($query_hewan)) {
    $daftar_hewan[] = $hewan;
}

// Untuk reservasi lama yang masih menggunakan reservasi.id_hewan
if (count($daftar_hewan) == 0 && !empty($data['nama_hewan_lama'])) {
    $daftar_hewan[] = [
        'nama_hewan' => $data['nama_hewan_lama']
    ];
}

$jumlah_hewan = count($daftar_hewan);

// Ambil layanan beserta nama hewan
$query_layanan = mysqli_query($koneksi, "
    SELECT
        h.nama_hewan,
        l.jenis_layanan,
        dl.subtotal
    FROM detail_layanan dl
    INNER JOIN detail_reservasi dr
        ON dl.id_detail_reservasi = dr.id_detail_reservasi
    INNER JOIN hewan h
        ON dr.id_hewan = h.id_hewan
    INNER JOIN layanan l
        ON dl.id_layanan = l.id_layanan
    WHERE dl.id_reservasi = '$id'
    ORDER BY dr.id_detail_reservasi, l.jenis_layanan
");

if (!$query_layanan) {
    die('Gagal mengambil layanan: ' . mysqli_error($koneksi));
}

$daftar_layanan = [];
$total_layanan_detail = 0;

while ($layanan = mysqli_fetch_assoc($query_layanan)) {
    $daftar_layanan[] = $layanan;
    $total_layanan_detail += (int) $layanan['subtotal'];
}

$total_layanan = $total_layanan_detail;

if (count($daftar_layanan) == 0) {
    $total_layanan = (int) ($data['total_layanan'] ?? 0);
}

// Perhitungan biaya kamar
$lama_hari = (int) ($data['lama_hari'] ?? 1);
$tarif_kamar = (int) ($data['harga_per_hari'] ?? 35000);

$subtotal_kamar = $tarif_kamar * $lama_hari * $jumlah_hewan;
$total_biaya = $subtotal_kamar + $total_layanan;

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Struk Reservasi - Pet Hotel</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #F2EFE7;
            min-height: 100vh;
        }

        .struk {
            max-width: 500px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .judul-struk {
            color: #3368A0;
            font-weight: bold;
        }

        .garis {
            border-top: 1px dashed #ccc;
            margin: 18px 0;
        }

        .total {
            color: #3368A0;
            font-size: 20px;
            font-weight: bold;
        }

        @media print {
            body {
                background: white;
            }

            .struk {
                box-shadow: none;
                margin: 0 auto;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="struk">

        <!-- Header -->
        <div class="text-center mb-4">
            <i class="bi bi-paw-fill fs-1 judul-struk"></i>

            <h3 class="judul-struk mt-2">PET HOTEL</h3>

            <p class="text-muted small mb-0">
                Bukti Reservasi Penitipan Hewan
            </p>
        </div>

        <div class="garis"></div>

        <!-- Informasi Reservasi -->
        <h6 class="fw-bold mb-3">Informasi Reservasi</h6>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Booking Code</span>
            <strong class="text-primary">
                <?= htmlspecialchars($booking_code); ?>
            </strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Tanggal Pemesanan</span>
            <strong><?= htmlspecialchars($tanggal_pesan); ?></strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Waktu Pemesanan</span>
            <strong><?= htmlspecialchars($jam_pesan); ?></strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Nama Pelanggan</span>
            <strong>
                <?= htmlspecialchars($data['nama_pelanggan'] ?? '-'); ?>
            </strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">No. Telepon</span>
            <strong>
                <?= htmlspecialchars($data['no_telp'] ?? '-'); ?>
            </strong>
        </div>

        <div class="garis"></div>

        <!-- Detail Penitipan -->
        <h6 class="fw-bold mb-3">Detail Penitipan</h6>

        <div class="mb-3">
            <span class="text-muted d-block mb-2">Nama Hewan</span>

            <?php if ($jumlah_hewan > 0) : ?>
                <?php foreach ($daftar_hewan as $index => $hewan) : ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Hewan <?= $index + 1; ?></span>
                        <strong>
                            <?= htmlspecialchars($hewan['nama_hewan']); ?>
                        </strong>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <strong>-</strong>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Jumlah Hewan</span>
            <strong><?= $jumlah_hewan; ?> Ekor</strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Kamar</span>
            <strong>
                <?= htmlspecialchars($data['nama_kamar'] ?? '-'); ?>
            </strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Check In</span>
            <strong>
                <?= htmlspecialchars($data['check_in'] ?? '-'); ?>
            </strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Check Out</span>
            <strong>
                <?= htmlspecialchars($data['check_out'] ?? '-'); ?>
            </strong>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Lama Penitipan</span>
            <strong><?= $lama_hari; ?> Hari</strong>
        </div>

        <div class="garis"></div>

        <!-- Rincian Biaya -->
        <h6 class="fw-bold mb-3">Rincian Biaya</h6>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Tarif Kamar / Hari</span>
            <span>
                Rp<?= number_format($tarif_kamar, 0, ',', '.'); ?>
            </span>
        </div>

        <div class="d-flex justify-content-between mb-2">
            <span class="text-muted">Biaya Kamar</span>
            <span>
                Rp<?= number_format($subtotal_kamar, 0, ',', '.'); ?>
            </span>
        </div>

        <div class="mb-2">
    <span class="text-muted d-block mb-2">
        Layanan Tambahan
    </span>

    <?php if (count($daftar_layanan) > 0) : ?>

        <?php foreach ($daftar_layanan as $layanan) : ?>
            <div class="d-flex justify-content-between mb-2">
                <span>
                    <?= htmlspecialchars($layanan['jenis_layanan']); ?>
                    (<?= htmlspecialchars($layanan['nama_hewan']); ?>)
                </span>

                <span>
                    Rp<?= number_format($layanan['subtotal'], 0, ',', '.'); ?>
                </span>
            </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-between mt-2">
            <span class="text-muted small">
                Total Layanan Tambahan
            </span>
            <strong class="small">
                Rp<?= number_format($total_layanan, 0, ',', '.'); ?>
            </strong>
        </div>

    <?php else : ?>

        <div class="d-flex justify-content-between">
            <span>Tidak ada layanan tambahan</span>
            <span>Rp<?= number_format($total_layanan, 0, ',', '.'); ?></span>
        </div>

    <?php endif; ?>
</div>

<div class="garis"></div>

<div class="d-flex justify-content-between align-items-center">
    <span class="fw-bold">Total Biaya</span>
    <span class="total">
        Rp<?= number_format($total_biaya, 0, ',', '.'); ?>
    </span>
</div>
        <div class="text-center mt-4">
            <p class="text-muted small mb-0">
                Terima kasih telah mempercayakan hewan kesayangan Anda kepada Pet Hotel 🐾
            </p>
        </div>

        <!-- Tombol -->
        <div class="d-flex gap-2 mt-4 no-print">

            <a href="riwayat_pesanan.php"
               class="btn btn-outline-secondary w-50">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali
            </a>

            <button onclick="window.print()"
                    class="btn btn-primary w-50"
                    style="background-color: #3368A0;">
                <i class="bi bi-printer me-1"></i>
                Cetak Struk
            </button>

        </div>

    </div>

</div>

</body>
</html>
