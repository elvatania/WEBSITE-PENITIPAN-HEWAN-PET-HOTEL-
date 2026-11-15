<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
$nama_user = $_SESSION['nama_pelanggan'] ?? 'Pelanggan';
$email_user = $_SESSION['email'] ?? '-';

$query_riwayat = mysqli_query($koneksi, "
    SELECT
        r.*,
        k.nama_kamar,
        k.harga_per_hari,

        (
            SELECT COUNT(*)
            FROM detail_reservasi dr
            WHERE dr.id_reservasi = r.id_reservasi
        ) AS jumlah_hewan,

        (
            SELECT GROUP_CONCAT(
                CONCAT(h.nama_hewan, ' (', h.jenis_hewan, ')')
                SEPARATOR ', '
            )
            FROM detail_reservasi dr
            JOIN hewan h ON dr.id_hewan = h.id_hewan
            WHERE dr.id_reservasi = r.id_reservasi
        ) AS daftar_hewan,

        (
            SELECT SUM(dl.subtotal)
            FROM detail_layanan dl
            WHERE dl.id_reservasi = r.id_reservasi
        ) AS total_layanan_detail

    FROM reservasi r

    LEFT JOIN kamar k
        ON r.id_kamar = k.id_kamar

    WHERE r.id_pelanggan = '$id_pelanggan'

    ORDER BY r.id_reservasi DESC
");

if (!$query_riwayat) {
    die("Gagal mengambil riwayat: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Pet Hotel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white shadow-sm px-4">
    <div class="container-fluid">
        <div class="d-flex flex-row align-items-center gap-3">
            <a href="../landingpage.php" class="tombol-back" title="Kembali">
                <i class="bi bi-arrow-90deg-left"></i>
            </a>
            <div class="d-flex flex-row align-items-center gap-2">
                <span class="ikon-paw">🐾</span>
                <h3 class="judul-pet-hotel mb-0">PET HOTEL</h3>
            </div>
        </div>
        <div class="dropdown">
            <button class="btn btn-light border rounded-pill dropdown-toggle"
                    type="button" data-bs-toggle="dropdown">

                <i class="bi bi-person-circle"></i>
                <?= htmlspecialchars($nama_user); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li>
                    <span class="dropdown-item-text fw-semibold">
                        <?= htmlspecialchars($email_user); ?>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold mb-1">Riwayat Pesanan Saya</h2>
            <p class="text-muted mb-0">
                Halo, <?= htmlspecialchars($nama_user); ?>.
                Berikut daftar reservasi hewan Anda.
            </p>
        </div>
        <a href="index.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>
            Buat Reservasi
        </a>
    </div>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Booking Code</th>
                            <th>Nama Hewan</th>
                            <th>Kamar & Tanggal Inap</th>
                            <th>Total Biaya</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (mysqli_num_rows($query_riwayat) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($query_riwayat)): ?>
                            <?php
                            $jumlah_hewan = (int) $row['jumlah_hewan'];
                            $nama_hewan = $row['daftar_hewan'];
                            if ($jumlah_hewan == 0 && !empty($row['id_hewan'])) {
                                $id_hewan_lama = (int) $row['id_hewan'];
                                $q_hewan_lama = mysqli_query($koneksi, "
                                    SELECT nama_hewan, jenis_hewan
                                    FROM hewan
                                    WHERE id_hewan = '$id_hewan_lama'
                                ");

                                $hewan_lama = mysqli_fetch_assoc($q_hewan_lama);
                                if ($hewan_lama) {
                                    $jumlah_hewan = 1;
                                    $nama_hewan = $hewan_lama['nama_hewan'] .
                                        ' (' . $hewan_lama['jenis_hewan'] . ')';
                                }
                            }

                            if (empty($nama_hewan)) {
                                $nama_hewan = 'Belum ada hewan';
                            }

                            $harga_per_hari = (int) ($row['harga_per_hari'] ?? 0);
                            $lama_hari = (int) $row['lama_hari'];
                            $total_kamar = $harga_per_hari * $lama_hari * $jumlah_hewan;

                            if ($row['total_layanan_detail'] !== null) {
                                $total_layanan = (int) $row['total_layanan_detail'];
                            } else {
                                $total_layanan = (int) ($row['total_layanan'] ?? 0);
                            }

                            $total_biaya = $total_kamar + $total_layanan;
                            $status = $row['status'] ?? '';

                            if ($status == 'Lunas') {
                                $warna_status = 'bg-success';
                            } elseif ($status == 'Dibatalkan') {
                                $warna_status = 'bg-danger';
                            } else {
                                $warna_status = 'bg-warning text-dark';
                            }
                            ?>

                            <tr>
                                <td class="fw-bold text-primary">
                                    RSV<?= str_pad($row['id_reservasi'], 3, "0", STR_PAD_LEFT); ?>
                                </td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($nama_hewan); ?>
                                </td>
                                <td>
                                    <span class="fw-semibold d-block">
                                        <?= htmlspecialchars($row['nama_kamar'] ?? '-'); ?>
                                    </span>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($row['check_in'] ?? '-'); ?>
                                        s/d
                                        <?= htmlspecialchars($row['check_out'] ?? '-'); ?>
                                        (<?= $lama_hari; ?> Hari)
                                    </small>
                                </td>
                                <td class="fw-bold text-success">
                                    Rp<?= number_format($total_biaya, 0, ',', '.'); ?>
                                </td>
                                <td>
                                    <span class="badge <?= $warna_status; ?> rounded-pill px-3 py-2">
                                        <?= htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="struk.php?id=<?= $row['id_reservasi']; ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-receipt me-1"></i>
                                        Lihat Struk
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada riwayat pesanan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
