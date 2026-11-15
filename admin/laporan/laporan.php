<?php
include "config/koneksi.php";


$tab = isset($_GET['tab']) ? $_GET['tab'] : 'reservasi';

if (!in_array($tab, ['reservasi', 'pembayaran'])) {
    $tab = 'reservasi';
}


$cari = isset($_GET['cari'])
    ? mysqli_real_escape_string($koneksi, $_GET['cari'])
    : '';

$dari = isset($_GET['dari']) &&
        preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dari'])
    ? $_GET['dari'] : '';

$sampai = isset($_GET['sampai']) &&
          preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['sampai'])
    ? $_GET['sampai'] : '';



function laporanRupiah($angka) {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}

function laporanTanggal($tanggal) {
    if (empty($tanggal) || $tanggal == '0000-00-00') {
        return '-';
    }

    return date('d-m-Y', strtotime($tanggal));
}

function laporanStatus($status) {
    $status = strtolower(trim($status ?? ''));

    if (in_array($status, ['lunas', 'berhasil', 'dikonfirmasi'])) {
        return 'status-lunas';
    }

    if (in_array($status, ['ditolak', 'dibatalkan'])) {
        return 'status-ditolak';
    }

    return 'status-pending';
}


$queryPendapatan = mysqli_query($koneksi, "
    SELECT COALESCE(SUM(b.total_bayar), 0) AS total
    FROM pembayaran b
    JOIN reservasi r ON b.id_reservasi = r.id_reservasi
    WHERE LOWER(TRIM(r.status)) = 'lunas'
");

$pendapatan = 0;

if ($queryPendapatan) {
    $dataPendapatan = mysqli_fetch_assoc($queryPendapatan);
    $pendapatan = $dataPendapatan['total'] ?? 0;
}


$queryReservasi = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM reservasi
");

$totalReservasi = 0;

if ($queryReservasi) {
    $dataReservasi = mysqli_fetch_assoc($queryReservasi);
    $totalReservasi = $dataReservasi['total'] ?? 0;
}


$queryLunas = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM reservasi
    WHERE LOWER(TRIM(status)) = 'lunas'
");

$totalLunas = 0;

if ($queryLunas) {
    $dataLunas = mysqli_fetch_assoc($queryLunas);
    $totalLunas = $dataLunas['total'] ?? 0;
}


// Jumlah reservasi menunggu konfirmasi
$queryPending = mysqli_query($koneksi, "
    SELECT COUNT(*) AS total
    FROM reservasi
    WHERE LOWER(TRIM(status)) IN (
        'pending konfirmasi',
        'menunggu konfirmasi'
    )
");

$totalPending = 0;

if ($queryPending) {
    $dataPending = mysqli_fetch_assoc($queryPending);
    $totalPending = $dataPending['total'] ?? 0;
}



$whereReservasi = " WHERE 1=1 ";

if ($cari != '') {
    $whereReservasi .= " AND (
        p.nama_pelanggan LIKE '%$cari%'
        OR r.id_reservasi LIKE '%$cari%'
        OR h.nama_hewan LIKE '%$cari%'
    )";
}

if ($dari != '') {
    $whereReservasi .= " AND r.tgl_reservasi >= '$dari'";
}

if ($sampai != '') {
    $whereReservasi .= " AND r.tgl_reservasi <= '$sampai'";
}

$queryDataReservasi = mysqli_query($koneksi, "
    SELECT
        r.id_reservasi,
        r.id_pelanggan,
        r.id_hewan,
        r.id_kamar,
        r.tgl_reservasi,
        r.check_in,
        r.check_out,
        r.lama_hari,
        r.status,

        p.nama_pelanggan,

        h.nama_hewan,
        h.jenis_hewan,

        k.nama_kamar

    FROM reservasi r

    LEFT JOIN pelanggan p
        ON r.id_pelanggan = p.id_pelanggan

    LEFT JOIN hewan h
        ON r.id_hewan = h.id_hewan

    LEFT JOIN kamar k
        ON r.id_kamar = k.id_kamar

    $whereReservasi

    ORDER BY r.id_reservasi DESC
");



$wherePembayaran = " WHERE 1=1 ";

if ($cari != '') {
    $wherePembayaran .= " AND (
        p.nama_pelanggan LIKE '%$cari%'
        OR b.id_bayar LIKE '%$cari%'
        OR b.id_reservasi LIKE '%$cari%'
    )";
}

if ($dari != '') {
    $wherePembayaran .= " AND b.tgl_bayar >= '$dari'";
}

if ($sampai != '') {
    $wherePembayaran .= " AND b.tgl_bayar <= '$sampai'";
}

$queryDataPembayaran = mysqli_query($koneksi, "
    SELECT
        b.id_bayar,
        b.id_reservasi,
        b.tgl_bayar,
        b.total_bayar,
        b.metode_bayar,
        b.status_bayar,
        b.bukti_bayar,

        r.lama_hari,
        r.check_in,
        r.check_out,
        r.status AS status_reservasi,

        p.nama_pelanggan,
        p.no_telp

    FROM pembayaran b

    LEFT JOIN reservasi r
        ON b.id_reservasi = r.id_reservasi

    LEFT JOIN pelanggan p
        ON r.id_pelanggan = p.id_pelanggan

    $wherePembayaran

    ORDER BY b.id_bayar DESC
");

$judulHalaman = 'Laporan';
$menuAktif    = 'laporan.php';
include __DIR__ . '/includes/sidebar_header.php';
?>

<style>
.halaman-laporan {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    color: #263746;
}

/* STATISTIK */

.statistik-laporan {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 25px;
}

.kartu-statistik {
    background: #FFFFFF;
    padding: 22px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.kartu-statistik h6 {
    color: #555;
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
}

.kartu-statistik h3 {
    color: #3368A0;
    font-size: 25px;
    font-weight: bold;
    margin: 0;
}

.statistik-pendapatan h3 {
    color: #198754;
}

.statistik-lunas h3 {
    color: #3368A0;
}

.statistik-pending h3 {
    color: #E6A23C;
}


.tombol-laporan {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 25px;
}

.tombol-laporan a {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    border: 1px solid #3368A0;
    border-radius: 30px;
    color: #3368A0;
    text-decoration: none;
    font-weight: 600;
    background: #FFFFFF;
    transition: 0.3s;
}

.tombol-laporan a:hover,
.tombol-laporan a.aktif {
    background: #3368A0;
    color: #FFFFFF;
}


.filter-laporan {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.filter-laporan input {
    padding: 10px 12px;
    border: 1px solid #C8DFDB;
    border-radius: 8px;
    background: #FFFFFF;
    color: #333;
    outline: none;
}

.filter-laporan input:focus {
    border-color: #3368A0;
}

.filter-laporan input[type="text"] {
    min-width: 190px;
}

.filter-laporan label {
    font-size: 13px;
    color: #444;
    margin: 0;
}

.btn-filter,
.btn-reset,
.btn-cetak {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 17px;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}

.btn-filter {
    background: #3368A0;
    color: #FFFFFF;
}

.btn-filter:hover {
    background: #28547F;
    color: #FFFFFF;
}

.btn-reset {
    background: #C8DFDB;
    color: #3368A0;
}

.btn-reset:hover {
    background: #66A3BF;
    color: #FFFFFF;
}

.btn-cetak {
    background: #66A3BF;
    color: #FFFFFF;
}

.btn-cetak:hover {
    background: #3368A0;
}


.kartu-laporan {
    background: #FFFFFF;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.judul-laporan {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 18px 22px;
    font-size: 21px;
    font-weight: bold;
    color: #3368A0;
    background: #FFFFFF;
}


.tabel-laporan {
    width: 100%;
    border-collapse: collapse;
    background: #FFFFFF;
}

.tabel-laporan thead {
    background: #C8DFDB;
}

.tabel-laporan th {
    padding: 14px;
    color: #263746;
    font-size: 14px;
    font-weight: 700;
    text-align: left;
    white-space: nowrap;
}

.tabel-laporan td {
    padding: 15px 14px;
    border-bottom: 1px solid #E5E5E5;
    font-size: 14px;
    color: #333333;
}

.tabel-laporan tbody tr:hover {
    background: #F5F9FA;
}

.tabel-laporan small {
    color: #777777;
    font-size: 12px;
}

.teks-biru {
    color: #3368A0;
}

.teks-pendapatan {
    color: #198754;
}

.data-kosong {
    text-align: center;
    padding: 30px !important;
    color: #777777 !important;
}


.status-lunas,
.status-pending,
.status-ditolak {
    display: inline-block;
    padding: 7px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}

.status-lunas {
    background: #D1E7DD;
    color: #198754;
}

.status-pending {
    background: #FFF0C2;
    color: #946200;
}

.status-ditolak {
    background: #F8D7DA;
    color: #B02A37;
}

/* CETAK */

@media print {
    .tombol-laporan,
    .filter-laporan {
        display: none !important;
    }

    .halaman-laporan {
        width: 100%;
        padding: 0;
        background: #FFFFFF;
    }

    .kartu-laporan,
    .kartu-statistik {
        box-shadow: none;
        border: 1px solid #DDDDDD;
    }
}

/* RESPONSIVE */

@media (max-width: 1200px) {
    .statistik-laporan {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .kartu-laporan {
        overflow-x: auto;
    }

    .tabel-laporan {
        min-width: 750px;
    }
}

@media (max-width: 576px) {
    .statistik-laporan {
        grid-template-columns: 1fr;
    }

    .tombol-laporan a {
        flex: 1;
        justify-content: center;
        padding: 10px;
        font-size: 13px;
    }
}
</style>


<div class="halaman-laporan">

    <div class="statistik-laporan">

        <div class="kartu-statistik statistik-pendapatan">
            <h6>TOTAL PENDAPATAN (LUNAS)</h6>
            <h3><?= laporanRupiah($pendapatan); ?></h3>
        </div>

        <div class="kartu-statistik">
            <h6>TOTAL RESERVASI</h6>
            <h3><?= $totalReservasi; ?> Transaksi</h3>
        </div>

        <div class="kartu-statistik statistik-lunas">
            <h6>RESERVASI LUNAS</h6>
            <h3><?= $totalLunas; ?> Lunas</h3>
        </div>

        <div class="kartu-statistik statistik-pending">
            <h6>MENUNGGU KONFIRMASI</h6>
            <h3><?= $totalPending; ?> Pending</h3>
        </div>

    </div>


    <div class="tombol-laporan">

        <a href="?tab=reservasi"
           class="<?= $tab == 'reservasi' ? 'aktif' : ''; ?>">

            <i class="bi bi-calendar-check"></i>
            Data Reservasi

        </a>

        <a href="?tab=pembayaran"
           class="<?= $tab == 'pembayaran' ? 'aktif' : ''; ?>">

            <i class="bi bi-credit-card"></i>
            Data Pembayaran

        </a>

    </div>


    <form method="GET" class="filter-laporan">

        <input type="hidden"
               name="tab"
               value="<?= htmlspecialchars($tab); ?>">

        <input type="text"
               name="cari"
               placeholder="Cari data..."
               value="<?= htmlspecialchars($cari); ?>">

        <label>Dari:</label>

        <input type="date"
               name="dari"
               value="<?= htmlspecialchars($dari); ?>">

        <label>Sampai:</label>

        <input type="date"
               name="sampai"
               value="<?= htmlspecialchars($sampai); ?>">

        <button type="submit" class="btn-filter">
            <i class="bi bi-search"></i>
            Cari
        </button>

        <a href="?tab=<?= htmlspecialchars($tab); ?>"
           class="btn-reset">
            Reset
        </a>

        <button type="button"
                onclick="window.print()"
                class="btn-cetak">

            <i class="bi bi-printer"></i>
            Cetak

        </button>

    </form>



    <?php if ($tab == 'reservasi') { ?>

    <div class="kartu-laporan">

        <div class="judul-laporan">
            <i class="bi bi-calendar-check"></i>
            Laporan Data Reservasi Pelanggan
        </div>

        <table class="tabel-laporan">

            <thead>
                <tr>
                    <th>ID / TGL</th>
                    <th>PELANGGAN</th>
                    <th>HEWAN PELIHARAAN</th>
                    <th>KAMAR</th>
                    <th>DURASI MENGINAP</th>
                    <th>STATUS PEMESANAN</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($queryDataReservasi &&
                      mysqli_num_rows($queryDataReservasi) > 0) { ?>

                <?php while ($row = mysqli_fetch_assoc($queryDataReservasi)) { ?>

                <tr>

                    <td>
                        <strong class="teks-biru">
                            #<?= $row['id_reservasi']; ?>
                        </strong>

                        <br>

                        <?= laporanTanggal($row['tgl_reservasi']); ?>
                    </td>


                    <td>
                        <?= htmlspecialchars(
                            $row['nama_pelanggan'] ?? '-'
                        ); ?>
                    </td>


                    <td>

                        <?php if (!empty($row['nama_hewan'])) { ?>

                            <strong>
                                <?= htmlspecialchars($row['nama_hewan']); ?>
                            </strong>

                            <br>

                            <small>
                                (<?= htmlspecialchars(
                                    $row['jenis_hewan'] ?? '-'
                                ); ?>)
                            </small>

                        <?php } else { ?>

                            <span>-</span>

                        <?php } ?>

                    </td>


                    <td>
                        <?= htmlspecialchars($row['nama_kamar'] ?? '-'); ?>
                    </td>


                    <!-- DURASI -->
                    <td>
                        <?= (int)$row['lama_hari']; ?> Hari

                        <br>

                        <small>
                            <?= laporanTanggal($row['check_in']); ?>
                            s/d
                            <?= laporanTanggal($row['check_out']); ?>
                        </small>
                    </td>


                    <!-- STATUS -->
                    <td>
                        <span class="<?= laporanStatus($row['status']); ?>">
                            <?= htmlspecialchars($row['status'] ?? '-'); ?>
                        </span>
                    </td>

                </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>
                    <td colspan="6" class="data-kosong">
                        Data reservasi belum tersedia.
                    </td>
                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

    <?php } else { ?>

    <div class="kartu-laporan">

        <div class="judul-laporan">
            <i class="bi bi-credit-card"></i>
            Laporan Transaksi Pembayaran
        </div>

        <table class="tabel-laporan">

            <thead>
                <tr>
                    <th>ID TRANSAKSI</th>
                    <th>PELANGGAN</th>
                    <th>RINCIAN BIAYA</th>
                    <th>TOTAL BAYAR</th>
                    <th>STATUS VERIFIKASI</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($queryDataPembayaran &&
                      mysqli_num_rows($queryDataPembayaran) > 0) { ?>

                <?php while ($row = mysqli_fetch_assoc($queryDataPembayaran)) { ?>

                <tr>

                    <td>
                        <strong class="teks-biru">
                            #<?= $row['id_bayar']; ?>
                        </strong>

                        <br>

                        <?= laporanTanggal($row['tgl_bayar']); ?>
                    </td>

                    <td>
                        <strong>
                            <?= htmlspecialchars(
                                $row['nama_pelanggan'] ?? '-'
                            ); ?>
                        </strong>

                        <br>

                        <small>
                            <?= htmlspecialchars($row['no_telp'] ?? '-'); ?>
                        </small>
                    </td>


                    <td>
                        Durasi:
                        <?= (int)$row['lama_hari']; ?> Hari

                        <br>

                        <small>
                            Metode:
                            <?= htmlspecialchars(
                                $row['metode_bayar'] ?? '-'
                            ); ?>
                        </small>
                    </td>


                    <td>
                        <strong class="teks-pendapatan">
                            <?= laporanRupiah($row['total_bayar']); ?>
                        </strong>
                    </td>

                    <td>
                        <?php
                        $statusTampil = $row['status_reservasi']
                            ?? $row['status_bayar']
                            ?? '-';
                        ?>
                        <span class="<?= laporanStatus($statusTampil); ?>">
                            <?= htmlspecialchars($statusTampil); ?>
                        </span>
                    </td>

                </tr>

                <?php } ?>

            <?php } else { ?>

                <tr>
                    <td colspan="5" class="data-kosong">
                        Data pembayaran belum tersedia.
                    </td>
                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

    <?php } ?>

</div>

<?php include __DIR__ . '/includes/penutup_halaman.php'; ?>