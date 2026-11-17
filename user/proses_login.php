<?php
session_start();
include '../config/koneksi.php'; // Sesuaikan lokasi file koneksi kamu

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = mysqli_real_escape_string($koneksi, $_POST['password']);

    $query_admin = mysqli_query($koneksi, "SELECT * FROM admin WHERE email = '$email' AND password = '$password' LIMIT 1");
    
    if ($row_admin = mysqli_fetch_assoc($query_admin)) {
        if ($row_admin['role'] == 'Admin') {
            $_SESSION['id_admin']   = $row_admin['id_admin'];
            $_SESSION['nama_admin'] = $row_admin['nm_admin'];
            $_SESSION['email']      = $row_admin['email'];
            $_SESSION['role']       = $row_admin['role'];

            header("Location: ../admin/dashboard.php");
            exit();
        }
    }

    $query_pelanggan = mysqli_query($koneksi, "SELECT * FROM pelanggan WHERE email = '$email' AND password = '$password' LIMIT 1");
    
    if ($row_pelanggan = mysqli_fetch_assoc($query_pelanggan)) {
        $_SESSION['id_pelanggan']   = $row_pelanggan['id_pelanggan'];
        $_SESSION['nama_pelanggan'] = $row_pelanggan['nama_pelanggan'];
        $_SESSION['email']          = $row_pelanggan['email'];

        header("Location: ../user/riwayat_pesanan.php");
        exit();
    }

    echo "<script>
            alert('Email atau Password salah!'); 
            window.location='login.php';
          </script>";
}
?>