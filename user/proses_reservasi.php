<?php
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: ../user/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

$id_pelanggan = (int) $_SESSION['id_pelanggan'];
$no_telepon = mysqli_real_escape_string($koneksi, $_POST['no_telepon'] ?? '');
$alamat     = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');

mysqli_query($koneksi, "
    UPDATE pelanggan
    SET no_telp = '$no_telepon', alamat = '$alamat'
    WHERE id_pelanggan = '$id_pelanggan'
");

$checkin         = $_POST['checkin'] ?? '';
$checkout        = $_POST['checkout'] ?? '';
$catatan         = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
$id_kamar        = (int) ($_POST['id_kamar'] ?? 0);
$tgl_reservasi   = date('Y-m-d');
$waktu_reservasi = date('Y-m-d H:i:s');
$tgl_in  = DateTime::createFromFormat('Y-m-d', $checkin);
$tgl_out = DateTime::createFromFormat('Y-m-d', $checkout);

if (!$tgl_in || !$tgl_out || $tgl_out <= $tgl_in) {
    echo "<script>alert('Tanggal check-out harus setelah tanggal check-in!'); window.location='index.php';</script>";
    exit();
}

$lama_hari = $tgl_in->diff($tgl_out)->days;
$q_kamar = mysqli_query($koneksi, "
    SELECT * FROM kamar
    WHERE id_kamar = '$id_kamar'
    LIMIT 1
");

$data_kamar = mysqli_fetch_assoc($q_kamar);

if (!$data_kamar) {
    die("Kamar tidak ditemukan. Silakan pilih kamar kembali.");
}

$harga_kamar_per_hari = (int) $data_kamar['harga_per_hari'];
$kapasitas = (int) $data_kamar['kapasitas'];

$array_nama_hewan = $_POST['nama_hewan'] ?? [];
$array_jenis      = $_POST['jenis_hewan'] ?? [];
$array_ras        = $_POST['Ras'] ?? [];
$array_kelamin    = $_POST['jenis_kelamin'] ?? [];
$array_usia       = $_POST['usia'] ?? [];
$array_berat      = $_POST['berat'] ?? [];
$array_kondisi    = $_POST['kondisi'] ?? [];

$jumlah_hewan = count($array_nama_hewan);
if ($jumlah_hewan < 1 || $jumlah_hewan > 3) {
    die("Jumlah hewan harus antara 1 sampai 3.");
}

$checkin_sql  = mysqli_real_escape_string($koneksi, $checkin);
$checkout_sql = mysqli_real_escape_string($koneksi, $checkout);
$q_terpakai = mysqli_query($koneksi, "
    SELECT COUNT(dr.id_detail_reservasi) AS total
    FROM detail_reservasi dr
    JOIN reservasi r
        ON dr.id_reservasi = r.id_reservasi
    WHERE r.id_kamar = '$id_kamar'
    AND r.status != 'Dibatalkan'
    AND r.check_in < '$checkout_sql'
    AND r.check_out > '$checkin_sql'
");

if (!$q_terpakai) {
    die('Gagal memeriksa stok kamar: ' . mysqli_error($koneksi));
}

$data_terpakai   = mysqli_fetch_assoc($q_terpakai);
$jumlah_terpakai = (int) $data_terpakai['total'];
$sisa_kamar      = $kapasitas - $jumlah_terpakai;
if ($sisa_kamar < $jumlah_hewan) {
    die(
        "Maaf, kapasitas kamar tidak mencukupi untuk "
        . $jumlah_hewan
        . " hewan. Sisa kapasitas saat ini: "
        . $sisa_kamar
        . "."
    );
}

$total_kamar = $harga_kamar_per_hari * $lama_hari * $jumlah_hewan;
mysqli_begin_transaction($koneksi);
try {
    $sql_reservasi = "
    INSERT INTO reservasi
    (
        id_pelanggan,
        id_hewan,
        id_kamar,
        tgl_reservasi,
        waktu_reservasi,
        check_in,
        check_out,
        lama_hari,
        total_layanan,
        catatan,
        status
    )
    VALUES
    (
        '$id_pelanggan',
        NULL,
        '$id_kamar',
        '$tgl_reservasi',
        '$waktu_reservasi',
        '$checkin_sql',
        '$checkout_sql',
        '$lama_hari',
        0,
        '$catatan',
        'Menunggu Konfirmasi'
    )
";

    if (!mysqli_query($koneksi, $sql_reservasi)) {
        throw new Exception(mysqli_error($koneksi));
    }

    $id_reservasi  = mysqli_insert_id($koneksi);
    $total_layanan = 0;

    for ($i = 0; $i < $jumlah_hewan; $i++) {
        $h_nama    = mysqli_real_escape_string($koneksi, trim($array_nama_hewan[$i] ?? ''));
        $h_jenis   = mysqli_real_escape_string($koneksi, $array_jenis[$i] ?? '');
        $h_ras     = mysqli_real_escape_string($koneksi, $array_ras[$i] ?? '');
        $h_kelamin = mysqli_real_escape_string($koneksi, $array_kelamin[$i] ?? '');
        $h_usia    = mysqli_real_escape_string($koneksi, $array_usia[$i] ?? '');
        $h_berat   = mysqli_real_escape_string($koneksi, $array_berat[$i] ?? '');
        $h_kondisi = mysqli_real_escape_string($koneksi, $array_kondisi[$i] ?? '');

        if ($h_nama == '') {
            throw new Exception("Nama hewan ke-" . ($i + 1) . " belum diisi.");
        }

        // Cek apakah hewan dengan nama ini sudah pernah terdaftar untuk pelanggan ini
        $q_cek = mysqli_query($koneksi, "
            SELECT id_hewan FROM hewan
            WHERE id_pelanggan = '$id_pelanggan' AND nama_hewan = '$h_nama'
            ORDER BY id_hewan DESC LIMIT 1
        ");
        $hewan_lama = mysqli_fetch_assoc($q_cek);

        if ($hewan_lama) {
            // Pakai data lama, perbarui info terbarunya
            $id_hewan = (int) $hewan_lama['id_hewan'];
            $sql_hewan = "
                UPDATE hewan SET
                    jenis_hewan   = '$h_jenis',
                    ras           = '$h_ras',
                    jenis_kelamin = '$h_kelamin',
                    usia_hewan    = '$h_usia',
                    berat_hewan   = '$h_berat',
                    kondisi       = '$h_kondisi',
                    catatan       = '$catatan'
                WHERE id_hewan = '$id_hewan'
            ";
            if (!mysqli_query($koneksi, $sql_hewan)) {
                throw new Exception(mysqli_error($koneksi));
            }
        } else {
            // Hewan baru
            $sql_hewan = "
                INSERT INTO hewan
                (id_pelanggan, nama_hewan, jenis_hewan, ras, jenis_kelamin, usia_hewan, berat_hewan, kondisi, catatan)
                VALUES
                ('$id_pelanggan', '$h_nama', '$h_jenis', '$h_ras', '$h_kelamin', '$h_usia', '$h_berat', '$h_kondisi', '$catatan')
            ";
            if (!mysqli_query($koneksi, $sql_hewan)) {
                throw new Exception(mysqli_error($koneksi));
            }
            $id_hewan = mysqli_insert_id($koneksi);
        }

        $sql_detail_reservasi = "
            INSERT INTO detail_reservasi (id_reservasi, id_hewan)
            VALUES ('$id_reservasi', '$id_hewan')
        ";

        if (!mysqli_query($koneksi, $sql_detail_reservasi)) {
            throw new Exception(mysqli_error($koneksi));
        }

        $id_detail_reservasi = mysqli_insert_id($koneksi);
        $layanan_hewan = $_POST['layanan'][$i] ?? [];
        if (is_array($layanan_hewan)) {
            foreach ($layanan_hewan as $id_layanan) {
                $id_layanan = (int) $id_layanan;
                if ($id_layanan <= 0) {
                    continue;
                }
                $q_layanan = mysqli_query($koneksi, "
                    SELECT harga
                    FROM layanan
                    WHERE id_layanan = '$id_layanan'
                    LIMIT 1
                ");
                $data_layanan = mysqli_fetch_assoc($q_layanan);
                if (!$data_layanan) {
                    throw new Exception("Layanan dengan ID $id_layanan tidak ditemukan.");
                }
                $harga_layanan = (int) $data_layanan['harga'];
                $subtotal = $harga_layanan;
                $sql_detail_layanan = "
                    INSERT INTO detail_layanan
                    (
                        id_reservasi,
                        id_detail_reservasi,
                        id_layanan,
                        jumlah,
                        harga_satuan,
                        subtotal
                    )
                    VALUES
                    (
                        '$id_reservasi',
                        '$id_detail_reservasi',
                        '$id_layanan',
                        1,
                        '$harga_layanan',
                        '$subtotal'
                    )
                ";

                if (!mysqli_query($koneksi, $sql_detail_layanan)) {
                    throw new Exception(mysqli_error($koneksi));
                }

                $total_layanan += $subtotal;
            }
        }
    }

    $sql_update = "
        UPDATE reservasi
        SET total_layanan = '$total_layanan'
        WHERE id_reservasi = '$id_reservasi'
    ";

    if (!mysqli_query($koneksi, $sql_update)) {
        throw new Exception(mysqli_error($koneksi));
    }

    mysqli_commit($koneksi);
    header("Location: detail_reservasi.php?id=" . $id_reservasi);
    exit();
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    die("Gagal menyimpan reservasi: " . $e->getMessage());
}
?>