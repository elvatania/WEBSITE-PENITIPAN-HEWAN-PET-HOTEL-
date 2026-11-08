<?php
session_start();

// Hapus semua data session jika ada
session_unset();
session_destroy();

// Arahkan kembali ke Landing Page 
header("Location: landingpage.php");
exit;
?>