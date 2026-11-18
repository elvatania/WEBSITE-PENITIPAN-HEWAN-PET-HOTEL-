<?php
session_start();
include __DIR__ . "/../../config/koneksi.php";

$id = $_GET['id'] ?? '';

if (!empty($id)) {
    // Ambil id_pelanggan terlebih dahulu dari reservasi sebelum datanya dihapus
    $q_res = mysqli_query($koneksi, "SELECT id_pelanggan FROM reservasi WHERE id_reservasi = '$id' LIMIT 1");
    $data_res = mysqli_fetch_assoc($q_res);
    $id_pelanggan = $data_res['id_pelanggan'] ?? null;

    // Ambil daftar id_hewan yang terkait dengan reservasi ini melalui tabel detail_reservasi
    $q_detail = mysqli_query($koneksi, "SELECT id_hewan FROM detail_reservasi WHERE id_reservasi = '$id'");
    $array_id_hewan = [];
    while ($row_det = mysqli_fetch_assoc($q_detail)) {
        if (!empty($row_det['id_hewan'])) {
            $array_id_hewan[] = $row_det['id_hewan'];
        }
    }

    // 1. Hapus data di tabel pembayaran
    mysqli_query($koneksi, "DELETE FROM pembayaran WHERE id_reservasi = '$id'");

    // 2. Hapus data di tabel detail_layanan
    mysqli_query($koneksi, "DELETE FROM detail_layanan WHERE id_reservasi = '$id'");

    // 3. Hapus data di tabel detail_reservasi
    mysqli_query($koneksi, "DELETE FROM detail_reservasi WHERE id_reservasi = '$id'");

    // 4. Hapus data utama di tabel reservasi
    $delete_reservasi = mysqli_query($koneksi, "DELETE FROM reservasi WHERE id_reservasi = '$id'");

    if ($delete_reservasi) {
        // (Opsional) Jika Anda ingin data hewannya ikut terhapus dari tabel hewan:
        if (!empty($array_id_hewan)) {
            foreach ($array_id_hewan as $id_hew) {
                // Pastikan hewan ini tidak terpakai di reservasi lain sebelum dihapus
                $cek_pakai = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM detail_reservasi WHERE id_hewan = '$id_hew'");
                $jml_pakai = mysqli_fetch_assoc($cek_pakai)['total'] ?? 0;
                
                if ($jml_pakai == 0) {
                    mysqli_query($koneksi, "DELETE FROM hewan WHERE id_hewan = '$id_hew'");
                }
            }
        }

        // (Opsional) Jika Anda ingin data pelanggannya ikut terhapus (jika tidak ada reservasi lain):
        if (!empty($id_pelanggan)) {
            $cek_pelanggan = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM reservasi WHERE id_pelanggan = '$id_pelanggan'");
            $jml_reservasi_lain = mysqli_fetch_assoc($cek_pelanggan)['total'] ?? 0;

            if ($jml_reservasi_lain == 0) {
                mysqli_query($koneksi, "DELETE FROM pelanggan WHERE id_pelanggan = '$id_pelanggan'");
            }
        }

        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data Reservasi, Hewan, dan data terkait berhasil dihapus.'
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