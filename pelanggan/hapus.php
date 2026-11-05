<?php
session_start();
include "../config/koneksi.php";

$id = $_GET['id'] ?? '';

if ($id != '') {
    // 1. Ambil semua hewan milik pelanggan ini
    $queryHewan = mysqli_query($conn, "SELECT id_hewan FROM hewan WHERE id_pelanggan = '$id'");

    while ($rowHewan = mysqli_fetch_assoc($queryHewan)) {
        $id_hewan = $rowHewan['id_hewan'];

        // Ambil reservasi berdasarkan hewan
        $queryReservasi = mysqli_query($conn, "SELECT id_reservasi FROM reservasi WHERE id_hewan = '$id_hewan'");
        
        while ($rowReservasi = mysqli_fetch_assoc($queryReservasi)) {
            $id_reservasi = $rowReservasi['id_reservasi'];
            
            // Hapus pembayaran berdasarkan reservasi
            mysqli_query($conn, "DELETE FROM pembayaran WHERE id_reservasi = '$id_reservasi'");
        }

        // Hapus reservasi berdasarkan hewan
        mysqli_query($conn, "DELETE FROM reservasi WHERE id_hewan = '$id_hewan'");
    }

    // 2. TAMBAHAN PENTING: Hapus juga reservasi yang langsung terikat ke id_pelanggan (jika ada)
    $queryReservasiPelanggan = mysqli_query($conn, "SELECT id_reservasi FROM reservasi WHERE id_pelanggan = '$id'");
    while ($rowResPel = mysqli_fetch_assoc($queryReservasiPelanggan)) {
        $id_res_pel = $rowResPel['id_reservasi'];
        mysqli_query($conn, "DELETE FROM pembayaran WHERE id_reservasi = '$id_res_pel'");
    }
    mysqli_query($conn, "DELETE FROM reservasi WHERE id_pelanggan = '$id'");

    // 3. Hapus data hewan milik pelanggan
    mysqli_query($conn, "DELETE FROM hewan WHERE id_pelanggan = '$id'");

    // 4. Terakhir, hapus data pelanggan utama
    $delete = mysqli_query($conn, "DELETE FROM pelanggan WHERE id_pelanggan = '$id'");

    if ($delete) {
        $_SESSION['toast'] = [
            'type'    => 'success',
            'title'   => 'Terhapus!',
            'message' => 'Data Pelanggan beserta Hewan, Reservasi & Pembayarannya Berhasil Terhapus.'
        ];
    } else {
        $_SESSION['toast'] = [
            'type'    => 'error',
            'title'   => 'Gagal!',
            'message' => 'Gagal Menghapus Data Pelanggan.'
        ];
    }
} else {
    $_SESSION['toast'] = [
        'type'    => 'error',
        'title'   => 'Gagal!',
        'message' => 'ID Pelanggan tidak ditemukan.'
    ];
}

header("Location: index.php");
exit();
?>