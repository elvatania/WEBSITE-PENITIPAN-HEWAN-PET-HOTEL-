<?php
include "../config/koneksi.php";

$id = $_GET['id'] ?? '';

if ($id != '') {
    mysqli_query($conn, "DELETE FROM pelanggan WHERE id_pelanggan = '$id'");
}

header("Location: index.php");
exit();
?>