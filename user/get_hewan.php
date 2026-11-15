<?php
session_start();
include "../config/koneksi.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id_pelanggan'])) {
    echo '[]';
    exit();
}

$id = (int) $_SESSION['id_pelanggan'];

$q = mysqli_query($koneksi, "
    SELECT nama_hewan, jenis_hewan, ras, jenis_kelamin, usia_hewan, berat_hewan, kondisi
    FROM hewan
    WHERE id_pelanggan = '$id'
    AND id_hewan IN (
        SELECT MAX(id_hewan) FROM hewan
        WHERE id_pelanggan = '$id'
        GROUP BY nama_hewan
    )
    ORDER BY nama_hewan
");

$data = [];
if ($q) {
    while ($row = mysqli_fetch_assoc($q)) {
        $data[] = $row;
    }
}

echo json_encode($data);