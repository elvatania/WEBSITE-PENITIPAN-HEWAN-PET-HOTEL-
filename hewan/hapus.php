<?php
include "../config/koneksi.php";

$id = $_GET['id'] ?? '';

if ($id != '') {
    mysqli_query($conn, "DELETE FROM hewan WHERE id_hewan = '$id'");
}

header("Location: index.php");
exit();
?>