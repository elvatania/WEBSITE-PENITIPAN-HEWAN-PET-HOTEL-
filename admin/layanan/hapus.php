<?php
session_start();
include __DIR__ . "/../../config/koneksi.php";

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM layanan WHERE id_layanan = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['toast'] = [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Data layanan berhasil dihapus.'
            ];
        } else {
            $_SESSION['toast'] = [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Gagal menghapus data layanan.'
            ];
        }
    } catch (mysqli_sql_exception $e) {
        // Menangkap error jika masih terikat relasi foreign key dengan tabel lain
        $_SESSION['toast'] = [
            'type' => 'error',
            'title' => 'Gagal!',
            'message' => 'Data tidak bisa dihapus karena masih digunakan dalam transaksi reservasi.'
        ];
    }
}

header("Location: layanan.php");
exit();
?>