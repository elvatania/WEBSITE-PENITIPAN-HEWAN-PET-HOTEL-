<?php
include "../config/koneksi.php";

$checkin  = $_GET['checkin'] ?? date('Y-m-d');
$checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+1 day'));

$checkin_sql = mysqli_real_escape_string($koneksi, $checkin);
$checkout_sql = mysqli_real_escape_string($koneksi, $checkout);

$q_kamar = mysqli_query($koneksi, "SELECT * FROM kamar");
$data_kamar = [];

while ($k = mysqli_fetch_assoc($q_kamar)) {

    $id_kamar = (int) $k['id_kamar'];
    $kapasitas_total = !empty($k['kapasitas'])
        ? (int) $k['kapasitas']
        : 10;
    $q_terpakai = mysqli_query($koneksi, "
        SELECT COUNT(*) AS total
        FROM (
            SELECT dr.id_detail_reservasi
            FROM detail_reservasi dr
            JOIN reservasi r
                ON dr.id_reservasi = r.id_reservasi
            WHERE r.id_kamar = '$id_kamar'
            AND r.status != 'Dibatalkan'
            AND r.check_in < '$checkout_sql'
            AND r.check_out > '$checkin_sql'

            UNION ALL

            SELECT r.id_reservasi
            FROM reservasi r
            WHERE r.id_kamar = '$id_kamar'
            AND r.id_hewan IS NOT NULL
            AND r.status != 'Dibatalkan'
            AND r.check_in < '$checkout_sql'
            AND r.check_out > '$checkin_sql'
            AND NOT EXISTS (
                SELECT 1
                FROM detail_reservasi dr
                WHERE dr.id_reservasi = r.id_reservasi
            )
        ) AS data_terpakai
    ");

    if (!$q_terpakai) {
        die("Gagal menghitung stok: " . mysqli_error($koneksi));
    }

    $d_terpakai = mysqli_fetch_assoc($q_terpakai);
    $terpakai = (int) ($d_terpakai['total'] ?? 0);

    $sisa = $kapasitas_total - $terpakai;

    if ($sisa < 0) {
        $sisa = 0;
    }

    $data_kamar[] = [
        'id_kamar'   => $k['id_kamar'],
        'nama_kamar' => $k['nama_kamar'],
        'harga'      => $k['harga_per_hari'],
        'sisa'       => $sisa
    ];
}

header('Content-Type: application/json');
echo json_encode($data_kamar);
?>
