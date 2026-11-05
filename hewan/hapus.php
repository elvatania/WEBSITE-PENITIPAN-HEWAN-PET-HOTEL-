<?php
session_start();
include "../config/koneksi.php";

$id = $_GET['id'] ?? '';

if ($id != '') {
    $queryReservasi = mysqli_query($conn, "SELECT id_reservasi FROM reservasi WHERE id_hewan = '$id'");

    while ($rowReservasi = mysqli_fetch_assoc($queryReservasi)){
        $id_reservasi = $rowReservasi['id_reservasi'];
        mysqli_query($conn, "DELETE FROM pembayaran WHERE id_reservasi = '$id_reservasi'");
    }

    mysqli_query($conn, "DELETE FROM reservasi WHERE id_hewan = '$id'");

    $delete = mysqli_query($conn, "DELETE FROM hewan WHERE id_hewan = '$id'");

    if ($delete) {
        $_SESSION['toast'] = [
            'type'    => 'success',
            'title'   => 'Terhapus!',
            'message' => 'Data Hewan beserta Reservasi & Pembayarannya Berhasil Terhapus.'
        ];
    } else {
        $_SESSION['toast'] = [
            'type'    => 'error',
            'title'   => 'Gagal!',
            'message' => 'Gagal Menghapus Data Hewan.'
        ];
    }
} else {
    $_SESSION['toast'] = [
        'type'    => 'error',
        'title'   => 'Gagal!',
        'message' => 'ID Hewan tidak ditemukan.'
    ];
}

header("Location: index.php");
exit();
?>