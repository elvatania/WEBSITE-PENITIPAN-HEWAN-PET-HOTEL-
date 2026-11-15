<?php
// 1. Pengaturan Zona Waktu (Opsional, agar waktu sesuai dengan WIB)
date_default_timezone_set('Asia/Jakarta');

// 2. Konfigurasi Database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pet_hotel";

// 3. Proses Koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// 4. Pengecekan Koneksi
if (!$koneksi) {
    // Jika gagal, hentikan program dan tampilkan pesan error
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

// 5. Set charset ke utf8mb4 (agar emoji/karakter khusus aman)
mysqli_set_charset($koneksi, "utf8mb4");
?>