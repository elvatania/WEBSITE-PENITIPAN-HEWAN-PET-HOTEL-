<?php
include "../config/koneksi.php";

$checkin  = $_GET['checkin'] ?? date('Y-m-d');
$checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+1 day'));

$q_kamar = mysqli_query($koneksi, "SELECT * FROM kamar");
$data_kamar = [];

while ($k = mysqli_fetch_assoc($q_kamar)) {
    $id_kamar = $k['id_kamar'];
    // Jika kolom kapasitas kosong, otomatis gunakan angka 10 sebagai default
    $kapasitas_total = !empty($k['kapasitas']) ? intval($k['kapasitas']) : 10; 

    // Hitung reservasi yang aktif pada rentang tanggal tersebut
    $q_terpakai = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM reservasi 
                                         WHERE id_kamar = '$id_kamar' 
                                         AND status != 'Dibatalkan'
                                         AND ('$checkin' < check_out AND '$checkout' > check_in)");
    $d_terpakai = mysqli_fetch_assoc($q_terpakai);
    $terpakai = $d_terpakai['total'] ?? 0;

    // Hitung sisa kamar
    $sisa = $kapasitas_total - $terpakai;
    if ($sisa < 0) $sisa = 0;

    $data_kamar[] = [
        'id_kamar'   => $k['id_kamar'],
        'nama_kamar' => $k['nama_kamar'],
        'harga'      => $k['harga'] ?? $k['harga_per_hari'] ?? 35000,
        'sisa'       => $sisa
    ];
}

header('Content-Type: application/json');
echo json_encode($data_kamar);
?>