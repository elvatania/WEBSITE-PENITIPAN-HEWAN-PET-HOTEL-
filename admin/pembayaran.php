<?php
include "../config/koneksi.php"; // Menggunakan koneksi $conn

// Proses Aksi Konfirmasi atau Tolak Pembayaran oleh Admin
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_reservasi = intval($_GET['id']);
    $aksi = $_GET['aksi'];

    if ($aksi == 'konfirmasi') {
        $status_baru = 'Lunas';
    } elseif ($aksi == 'tolak') {
        $status_baru = 'Dibatalkan';
    }

    if (isset($status_baru)) {
        mysqli_query($conn, "UPDATE reservasi SET status = '$status_baru' WHERE id_reservasi = '$id_reservasi'");
        header("Location: pembayaran.php?pesan=berhasil");
        exit();
    }
}

// Cek ketersediaan kolom harga di tabel kamar
$cek_kolom = mysqli_query($conn, "SHOW COLUMNS FROM kamar LIKE 'harga'");
$ada_harga = mysqli_num_rows($cek_kolom) > 0;
$kolom_harga = $ada_harga ? "k.harga" : "k.harga_per_hari";

// Query Data Pembayaran
$sql = "SELECT 
            r.id_reservasi,
            r.tgl_reservasi,
            r.lama_hari,
            r.status,
            p.nama_pelanggan,
            p.no_telp,
            k.nama_kamar,
            $kolom_harga AS harga_kamar
        FROM reservasi r
        JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
        LEFT JOIN kamar k ON r.id_kamar = k.id_kamar
        ORDER BY r.id_reservasi DESC";

$query_pembayaran = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Pembayaran - Admin Pet Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light py-4">

  <div class="container-fluid px-md-5">

    <!-- Header & Navigasi -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-wallet2 text-success me-2"></i>Verifikasi Pembayaran
          </h3>
          <p class="text-muted small mb-0">Kelola konfirmasi transaksi dan pembayaran pelanggan Pet Hotel.</p>
        </div>
        <div>
          <a href="pembayaran.php" class="btn btn-success rounded-3 px-3 py-2 fw-semibold me-2">
            <i class="bi bi-wallet2 me-1"></i> Pembayaran
          </a>
          <a href="laporan.php" class="btn btn-outline-success rounded-3 px-3 py-2 fw-semibold">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Laporan & Statistik
          </a>
        </div>
      </div>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'berhasil'): ?>
      <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>Status pembayaran berhasil diperbarui!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <!-- Tabel Daftar Pembayaran -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-uppercase small text-muted">
            <tr>
              <th class="ps-4">ID / Tgl</th>
              <th>Pelanggan</th>
              <th>Kamar</th>
              <th>Total Biaya</th>
              <th>Status</th>
              <th class="text-center pe-4">Aksi Verifikasi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($query_pembayaran) > 0): ?>
              <?php while ($p = mysqli_fetch_assoc($query_pembayaran)): 
                $tarif_kamar = $p['harga_kamar'] ?? 50000;
                $total_bayar = $p['lama_hari'] * $tarif_kamar;
              ?>
                <tr>
                  <td class="ps-4">
                    <span class="fw-bold text-success">#<?= $p['id_reservasi']; ?></span>
                    <div class="text-muted small"><?= $p['tgl_reservasi']; ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($p['nama_pelanggan']); ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($p['no_telp']); ?></div>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border px-2 py-1">
                      <?= htmlspecialchars($p['nama_kamar'] ?? 'Standard'); ?>
                    </span>
                  </td>
                  <td>
                    <span class="fw-bold text-success">Rp<?= number_format($total_bayar, 0, ',', '.'); ?></span>
                  </td>
                  <td>
                    <?php if ($p['status'] == 'Lunas'): ?>
                      <span class="badge bg-success rounded-pill px-3 py-2">Lunas</span>
                    <?php elseif ($p['status'] == 'Dibatalkan'): ?>
                      <span class="badge bg-danger rounded-pill px-3 py-2">Ditolak</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Menunggu Konfirmasi</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center pe-4">
                    <?php if ($p['status'] != 'Lunas' && $p['status'] != 'Dibatalkan'): ?>
                      <a href="pembayaran.php?aksi=konfirmasi&id=<?= $p['id_reservasi']; ?>" 
                         onclick="return confirm('Apakah Anda yakin ingin mengonfirmasi pembayaran ini?')" 
                         class="btn btn-sm btn-success rounded-2 me-1">
                        <i class="bi bi-check-lg me-1"></i>Konfirmasi
                      </a>
                      <a href="pembayaran.php?aksi=tolak&id=<?= $p['id_reservasi']; ?>" 
                         onclick="return confirm('Apakah Anda yakin ingin MENOLAK pembayaran ini?')" 
                         class="btn btn-sm btn-outline-danger rounded-2">
                        <i class="bi bi-x-lg me-1"></i>Tolak
                      </a>
                    <?php else: ?>
                      <span class="text-muted small"><i class="bi bi-check2-all text-success me-1"></i>Selesai</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi pembayaran.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/npm/bootstrap.bundle.min.js"></script>
</body>
</html>