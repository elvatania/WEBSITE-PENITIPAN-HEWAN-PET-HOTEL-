<?php
session_start();
include __DIR__ . "/../../config/koneksi.php";

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    // 1. Hapus dulu data terkait di tabel anak (detail_layanan)
    mysqli_query($koneksi, "DELETE FROM detail_layanan WHERE id_reservasi = '$id'");

    // 2. Setelah itu, baru hapus data utama di tabel reservasi
    $delete = mysqli_query($koneksi, "DELETE FROM reservasi WHERE id_reservasi = '$id'");

    if ($delete) {
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data Reservasi Berhasil Dihapus.'
        ];
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Gagal!',
            'message' => 'Gagal Menghapus Data Reservasi.'
        ];
    }
}

header("Location: index.php");
exit();
?>