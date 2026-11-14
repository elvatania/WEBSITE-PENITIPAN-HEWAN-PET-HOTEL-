<?php
include "../config/koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_reservasi = $_POST['id_reservasi'] ?? 0;

    // Tangkap file bukti transfer yang di-upload
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['bukti_transfer']['tmp_name'];
        $file_name = $_FILES['bukti_transfer']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Ekstensi yang diizinkan
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

        if (in_array($file_ext, $allowed_ext)) {
            // Buat nama file unik agar tidak bentrok
            $nama_file_baru = "bukti_" . $id_reservasi . "_" . time() . "." . $file_ext;
            $folder_tujuan = "../assets/uploads/"; // Pastikan folder ini ada

            // Buat folder jika belum ada
            if (!is_dir($folder_tujuan)) {
                mkdir($folder_tujuan, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $folder_tujuan . $nama_file_baru)) {
                // Update database: simpan nama file bukti transfer & ubah status jadi 'Menunggu Konfirmasi'
                $update = mysqli_query($koneksi, "UPDATE reservasi SET bukti_transfer = '$nama_file_baru', status = 'Menunggu Konfirmasi' WHERE id_reservasi = '$id_reservasi'");

                if ($update) {
                    // Berhasil! Alihkan otomatis ke halaman riwayat / dashboard user
                    echo "<script>
                            alert('Bukti pembayaran berhasil dikirim! Menunggu konfirmasi dari admin.');
                            window.location='riwayat.php';
                          </script>";
                    exit();
                } else {
                    echo "Gagal memperbarui database: " . mysqli_error($koneksi);
                }
            } else {
                echo "Gagal mengunggah file ke server.";
            }
        } else {
            echo "Format file tidak didukung! Harap upload file berformat JPG, JPEG, PNG, atau PDF.";
        }
    } else {
        echo "Harap pilih file bukti transfer terlebih dahulu.";
    }
} else {
    header("Location: index.php");
    exit();
}
?>