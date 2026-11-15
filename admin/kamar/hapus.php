<?php
session_start();
include "../config/koneksi.php";

$id = $_GET['id'] ?? '';

if ($id != '') {
    // 1. Cek apakah kamar masih terikat dengan reservasi (opsional jika ada foreign key, atau langsung hapus)
    $delete = mysqli_query($conn, "DELETE FROM kamar WHERE id_kamar = '$id'");

    if ($delete) {
        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data Kamar Berhasil Dihapus.'
        ];
    } else {
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Gagal!',
            'message' => 'Gagal Menghapus Data Kamar (Kemungkinan data sedang digunakan).'
        ];
    }
} else {
    $_SESSION['toast'] = [
        'type' => 'error',
        'title' => 'Gagal!',
        'message' => 'ID Kamar Tidak Ditemukan.'
    ];
}

header("Location: index.php");
exit();
?>