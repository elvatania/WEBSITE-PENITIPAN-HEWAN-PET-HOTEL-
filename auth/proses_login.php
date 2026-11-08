<?php
session_start();
include '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE email = '$email' AND password = '$password' LIMIT 1");
    
    if ($row = mysqli_fetch_assoc($query)) {
        // Set data session user
        $_SESSION['id_pelanggan']   = $row['id_pelanggan'];
        $_SESSION['nama_pelanggan'] = $row['nama_pelanggan'];
        $_SESSION['email']          = $row['email'];

        header("Location: ../user/riwayat_pesanan.php");
        exit();
    } else {
        echo "<script>
                alert('Email atau Password salah!'); 
                window.location='login.php';
              </script>";
    }
}
?>