<?php
session_start();
session_unset();
session_destroy();

// Mengarahkan kembali ke file landingpage.php (sesuaikan jalur foldernya jika landingpage.php berada di luar folder admin)
header("Location: ../landingpage.php");
exit();
?>