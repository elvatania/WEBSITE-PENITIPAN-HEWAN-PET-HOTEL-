<?php
session_start();
include '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan']);
    $email          = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password       = mysqli_real_escape_string($koneksi, $_POST['password']);

    // Simpan data akun pelanggan
    $query = "INSERT INTO pelanggan (nama_pelanggan, email, password) VALUES ('$nama_pelanggan', '$email', '$password')";
    
    if (mysqli_query($koneksi, $query)) {
        $id_pelanggan = mysqli_insert_id($koneksi);
        
        // Simpan data ke session agar profil langsung muncul
        $_SESSION['id_pelanggan']   = $id_pelanggan;
        $_SESSION['nama_pelanggan'] = $nama_pelanggan;
        $_SESSION['email']          = $email;

        echo "<script>
                alert('Registrasi Berhasil!'); 
                window.location='../user/riwayat_pesanan.php';
              </script>";
    } else {
        echo "<script>
                alert('Gagal mendaftar: " . mysqli_error($koneksi) . "'); 
                window.location='register.php';
              </script>";
    }
}
?>