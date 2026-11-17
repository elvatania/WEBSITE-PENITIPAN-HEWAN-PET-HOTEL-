<?php
session_start();
session_unset();
session_destroy();

// Arahkan kembali ke halaman login utama (sesuaikan path naik satu tingkat keluar dari folder admin)
header("Location: ../index.php");
exit();
?>