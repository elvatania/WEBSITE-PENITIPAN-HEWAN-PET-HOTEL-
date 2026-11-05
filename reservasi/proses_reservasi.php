<?php
session_start();
include "../config/koneksi.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Cek apakah user sudah login atau belum
    if (isset($_SESSION['id_pelanggan']) && !empty($_SESSION['id_pelanggan'])) {
        // Jika sudah login, gunakan ID dari session yang aktif
        $id_pelanggan = $_SESSION['id_pelanggan'];
    } else {
        // Jika belum login (sebagai tamu), tangkap data pelanggan dari form lalu simpan ke tabel 'pelanggan'
        $nama_pelanggan = mysqli_real_escape_string($koneksi, $_POST['nama_pelanggan'] ?? '');
        $no_telepon     = mysqli_real_escape_string($koneksi, $_POST['no_telepon'] ?? '');
        $email          = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');

        $sql_pelanggan = "INSERT INTO pelanggan (nama_pelanggan, no_telp, email, alamat) 
                          VALUES ('$nama_pelanggan', '$no_telepon', '$email', '$alamat')";
        
        if (mysqli_query($koneksi, $sql_pelanggan)) {
            $id_pelanggan = mysqli_insert_id($koneksi);
        } else {
            echo "<script>
                    alert('Gagal menyimpan data pelanggan: " . mysqli_error($koneksi) . "');
                    window.location.href = 'index.php';
                  </script>";
            exit();
        }
    }

    // 2. Tangkap Data Penitipan
    $checkin       = mysqli_real_escape_string($koneksi, $_POST['checkin'] ?? date('Y-m-d'));
    $checkout      = mysqli_real_escape_string($koneksi, $_POST['checkout'] ?? date('Y-m-d'));
    $catatan       = mysqli_real_escape_string($koneksi, $_POST['catatan'] ?? '');
    $input_kamar   = mysqli_real_escape_string($koneksi, $_POST['id_kamar'] ?? '1');
    $tgl_reservasi = date('Y-m-d');

    // Hitung lama hari
    $tgl_in    = new DateTime($checkin);
    $tgl_out   = new DateTime($checkout);
    $diff      = $tgl_in->diff($tgl_out);
    $lama_hari = $diff->days > 0 ? $diff->days : 1;

    // Ambil harga kamar & validasi kamar dari database
    $harga_kamar_per_hari = 0;
    $cek_id = mysqli_query($koneksi, "SELECT * FROM kamar WHERE id_kamar = '$input_kamar' LIMIT 1");
    if ($data_kamar = mysqli_fetch_assoc($cek_id)) {
        $id_kamar = $data_kamar['id_kamar'];
        $harga_kamar_per_hari = intval($data_kamar['harga'] ?? 35000); 
    } else {
        $q_std = mysqli_query($koneksi, "SELECT * FROM kamar LIMIT 1");
        $d_std = mysqli_fetch_assoc($q_std);
        $id_kamar = $d_std['id_kamar'] ?? 1;
        $harga_kamar_per_hari = intval($d_std['harga'] ?? 35000);
    }

    // 3. Tangkap Array Hewan
    $array_nama_hewan = $_POST['nama_hewan'] ?? [];
    $array_jenis      = $_POST['jenis_hewan'] ?? [];
    $array_ras        = $_POST['Ras'] ?? [];
    $array_kelamin    = $_POST['jenis_kelamin'] ?? [];
    $array_usia       = $_POST['usia'] ?? [];
    $array_berat      = $_POST['berat'] ?? [];
    $array_kondisi    = $_POST['kondisi'] ?? [];
    
    $last_id_reservasi = 0;

    for ($i = 0; $i < count($array_nama_hewan); $i++) {
        $h_nama    = mysqli_real_escape_string($koneksi, $array_nama_hewan[$i]);
        $h_jenis   = mysqli_real_escape_string($koneksi, $array_jenis[$i] ?? 'Kucing');
        $h_ras     = mysqli_real_escape_string($koneksi, $array_ras[$i] ?? '');
        $h_kelamin = mysqli_real_escape_string($koneksi, $array_kelamin[$i] ?? 'Jantan');
        $h_usia    = mysqli_real_escape_string($koneksi, $array_usia[$i] ?? '');
        $h_berat   = mysqli_real_escape_string($koneksi, $array_berat[$i] ?? '');
        $h_kondisi = mysqli_real_escape_string($koneksi, $array_kondisi[$i] ?? '');

        // Simpan lengkap ke tabel hewan
        $sql_hewan = "INSERT INTO hewan (id_pelanggan, nama_hewan, jenis_hewan, ras, jenis_kelamin, usia_hewan, berat_hewan, kondisi) 
                      VALUES ('$id_pelanggan', '$h_nama', '$h_jenis', '$h_ras', '$h_kelamin', '$h_usia', '$h_berat', '$h_kondisi')";
        
        if (mysqli_query($koneksi, $sql_hewan)) {
            $id_hewan = mysqli_insert_id($koneksi);

            // --- PENGAMAN MUTLAK TOTAL LAYANAN ---
            $total_layanan_harga = 0;

            if (isset($_POST['layanan']) && is_array($_POST['layanan'])) {
                foreach ($_POST['layanan'] as $id_layanan) {
                    if (!empty($id_layanan) && is_numeric($id_layanan)) {
                        $safe_id_layanan = mysqli_real_escape_string($koneksi, $id_layanan);
                        
                        $q_harga = mysqli_query($koneksi, "SELECT harga FROM layanan WHERE id_layanan = '$safe_id_layanan' LIMIT 1");
                        if ($q_harga && $d_harga = mysqli_fetch_assoc($q_harga)) {
                            $total_layanan_harga += intval($d_harga['harga']);
                        }
                    }
                }
            }

            if ($total_layanan_harga < 0) {
                $total_layanan_harga = 0;
            }

            // Simpan ke tabel 'reservasi'
            $sql_reservasi = "INSERT INTO reservasi (id_pelanggan, id_hewan, id_kamar, tgl_reservasi, check_in, check_out, lama_hari, total_layanan, catatan, status) 
                              VALUES ('$id_pelanggan', '$id_hewan', '$id_kamar', '$tgl_reservasi', '$checkin', '$checkout', '$lama_hari', '$total_layanan_harga', '$catatan', 'Menunggu Konfirmasi')";

            if (mysqli_query($koneksi, $sql_reservasi)) {
                $last_id_reservasi = mysqli_insert_id($koneksi);
            }
        }
    }

    // Arahkan ke halaman detail/konfirmasi reservasi terlebih dahulu
    if ($last_id_reservasi > 0) {
        echo "<script>
                alert('Reservasi Berhasil! Silakan cek detail pesanan Anda.');
                window.location.href = 'detail_reservasi.php?id=" . $last_id_reservasi . "';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Gagal memproses reservasi hewan.');
                window.location.href = 'index.php';
              </script>";
    }

} else {
    header("Location: index.php");
    exit();
}
?>