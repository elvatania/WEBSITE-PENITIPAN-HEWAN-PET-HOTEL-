<?php
session_start();
include "../config/koneksi.php";

// Supaya error database dilempar sebagai exception (bisa ditangkap try/catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // 1. Ambil id_kamar terlebih dahulu sebelum data dihapus
    $s = mysqli_prepare($conn, "SELECT id_kamar, status FROM reservasi WHERE id_reservasi = ?");
    mysqli_stmt_bind_param($s, "i", $id);
    mysqli_stmt_execute($s);
    $res = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

    if ($res) {
        $id_kamar = (int)$res['id_kamar'];

        // 2. Catat nama file bukti yang terkait (dihapus dari folder setelah data berhasil dihapus)
        $files = [];
        $sb = mysqli_prepare($conn, "SELECT bukti_bayar FROM pembayaran WHERE id_reservasi = ?");
        mysqli_stmt_bind_param($sb, "i", $id);
        mysqli_stmt_execute($sb);
        $rb = mysqli_stmt_get_result($sb);
        while ($row = mysqli_fetch_assoc($rb)) {
            if (!empty($row['bukti_bayar'])) {
                $files[] = $row['bukti_bayar'];
            }
        }

        try {
            mysqli_begin_transaction($conn);

            // 3. Hapus data pembayaran yang berelasi dengan reservasi ini
            $dp = mysqli_prepare($conn, "DELETE FROM pembayaran WHERE id_reservasi = ?");
            mysqli_stmt_bind_param($dp, "i", $id);
            mysqli_stmt_execute($dp);

            // 4. Hapus data reservasi-nya
            $dr = mysqli_prepare($conn, "DELETE FROM reservasi WHERE id_reservasi = ?");
            mysqli_stmt_bind_param($dr, "i", $id);
            mysqli_stmt_execute($dr);

            // 5. Kembalikan kapasitas kamar (+1) hanya kalau reservasi masih berstatus Proses.
            //    Kalau sudah Selesai/Dibatalkan, kapasitasnya sudah dikembalikan lewat halaman Edit.
            if ($res['status'] === 'Proses') {
                $uk = mysqli_prepare($conn, "UPDATE kamar SET kapasitas = kapasitas + 1 WHERE id_kamar = ?");
                mysqli_stmt_bind_param($uk, "i", $id_kamar);
                mysqli_stmt_execute($uk);
            }

            mysqli_commit($conn);

            // 6. Hapus file bukti dari folder (setelah database pasti berhasil)
            foreach ($files as $nama) {
                @unlink(__DIR__ . '/../uploads/bukti/' . basename($nama));
            }

            $_SESSION['toast'] = [
                'type'    => 'success',
                'title'   => 'Berhasil!',
                'message' => 'Reservasi Berhasil Dihapus dan Kapasitas Kamar Diperbarui.'
            ];
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            $_SESSION['toast'] = [
                'type'    => 'error',
                'title'   => 'Gagal!',
                'message' => 'Gagal Menghapus Data Reservasi.'
            ];
        }
    } else {
        $_SESSION['toast'] = [
            'type'    => 'error',
            'title'   => 'Gagal!',
            'message' => 'Data Reservasi Tidak Ditemukan.'
        ];
    }
}

header("Location: index.php");
exit();
?>