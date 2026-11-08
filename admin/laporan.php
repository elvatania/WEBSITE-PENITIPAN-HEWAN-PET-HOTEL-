<?php
include "../config/koneksi.php"; // Pastikan di file koneksi.php menggunakan $conn

// Sesuai struktur database, kolom harga bernama 'harga_per_hari'
$kolom_harga = "k.harga_per_hari";

// ==========================================
// 2. STATISTIK PENITIPAN (KPI CARDS)
// ==========================================
$q_total_pendapatan = mysqli_query($conn, "SELECT SUM(r.lama_hari * $kolom_harga) AS total_pendapatan FROM reservasi r LEFT JOIN kamar k ON r.id_kamar = k.id_kamar WHERE r.status = 'Lunas'");
$d_pendapatan = mysqli_fetch_assoc($q_total_pendapatan);
$total_pendapatan = $d_pendapatan['total_pendapatan'] ?? 0;

$q_total_reservasi = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservasi");
$d_reservasi = mysqli_fetch_assoc($q_total_reservasi);
$total_reservasi = $d_reservasi['total'] ?? 0;

$q_lunas = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservasi WHERE status = 'Lunas'");
$d_lunas = mysqli_fetch_assoc($q_lunas);
$total_lunas = $d_lunas['total'] ?? 0;

$q_pending = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservasi WHERE status IN ('Menunggu Pembayaran', 'Pending Konfirmasi', 'Menunggu Konfirmasi')");
$d_pending = mysqli_fetch_assoc($q_pending);
$total_pending = $d_pending['total'] ?? 0;

// 3. Query Data Utama Laporan (Tanpa kolom bukti_pembayaran)
$sql = "SELECT 
            r.id_reservasi,
            r.tgl_reservasi,
            r.check_in,
            r.check_out,
            r.lama_hari,
            r.status,
            p.nama_pelanggan,
            p.no_telp,
            h.nama_hewan,
            h.jenis_hewan,
            k.nama_kamar,
            $kolom_harga AS harga_kamar
        FROM reservasi r
        JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
        LEFT JOIN hewan h ON r.id_hewan = h.id_hewan
        LEFT JOIN kamar k ON r.id_kamar = k.id_kamar
        ORDER BY r.id_reservasi DESC";

$query_reservasi  = mysqli_query($conn, $sql);
$query_pembayaran = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Laporan & Statistik Penitipan - Admin Pet Hotel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    @media print {
      .no-print { display: none !important; }
      body { background-color: #fff !important; }
      .card { border: none !important; box-shadow: none !important; }
      #paneReservasi, #panePembayaran { display: block !important; opacity: 1 !important; }
    }
  </style>
</head>
<body class="bg-light py-4">

  <div class="container-fluid px-md-5">

    <!-- Header Laporan -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
      <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-dark mb-1">
            <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Laporan & Statistik Penitipan
          </h3>
          <p class="text-muted small mb-0">Rekapitulasi otomatis data pemesanan kamar, keuangan, dan statistik operasional Pet Hotel.</p>
        </div>
        <div class="no-print">
          <a href="pembayaran.php" class="btn btn-outline-success rounded-3 px-3 py-2 fw-semibold me-2">
            <i class="bi bi-wallet2 me-1"></i> Verifikasi Pembayaran
          </a>
          <button onclick="window.print()" class="btn btn-success rounded-3 px-3 py-2 fw-semibold">
            <i class="bi bi-printer-fill me-1"></i> Cetak Laporan
          </button>
        </div>
      </div>
    </div>

    <!-- ================= STATISTIK PENITIPAN (KPI CARDS) ================= -->
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
          <span class="text-uppercase text-muted fw-bold small">Total Pendapatan (Lunas)</span>
          <h4 class="fw-bold text-success mb-0 mt-2">Rp<?= number_format($total_pendapatan, 0, ',', '.'); ?></h4>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
          <span class="text-uppercase text-muted fw-bold small">Total Reservasi</span>
          <h4 class="fw-bold text-dark mb-0 mt-2"><?= $total_reservasi; ?> Transaksi</h4>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
          <span class="text-uppercase text-muted fw-bold small">Reservasi Lunas</span>
          <h4 class="fw-bold text-primary mb-0 mt-2"><?= $total_lunas; ?> Lunas</h4>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
          <span class="text-uppercase text-muted fw-bold small">Menunggu Konfirmasi</span>
          <h4 class="fw-bold text-warning mb-0 mt-2"><?= $total_pending; ?> Pending</h4>
        </div>
      </div>
    </div>

    <!-- ================= TOMBOL NAVIGASI TAB KECIL ================= -->
    <div class="d-flex gap-2 mb-4 no-print">
      <button id="btnTabReservasi" onclick="bukaTab('reservasi')" type="button" class="btn btn-success fw-semibold rounded-pill px-4 py-2 shadow-sm">
        <i class="bi bi-calendar-check me-1"></i> Data Reservasi
      </button>
      <button id="btnTabPembayaran" onclick="bukaTab('pembayaran')" type="button" class="btn btn-outline-success fw-semibold rounded-pill px-4 py-2">
        <i class="bi bi-credit-card-2-front me-1"></i> Data Pembayaran
      </button>
    </div>

    <!-- ================= TAB 1: LAPORAN RESERVASI ================= -->
    <div id="paneReservasi" class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
      <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold text-dark mb-0">
          <i class="bi bi-calendar-check text-primary me-2"></i>Laporan Data Reservasi Pelanggan
        </h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-uppercase small text-muted">
            <tr>
              <th class="ps-4">ID / Tgl</th>
              <th>Pelanggan</th>
              <th>Hewan Peliharaan</th>
              <th>Kamar</th>
              <th>Durasi Menginap</th>
              <th class="text-center pe-4">Status Pemesanan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($query_reservasi) > 0): ?>
              <?php while ($row = mysqli_fetch_assoc($query_reservasi)): ?>
                <tr>
                  <td class="ps-4">
                    <span class="fw-bold text-success">#<?= $row['id_reservasi']; ?></span>
                    <div class="text-muted small"><?= $row['tgl_reservasi']; ?></div>
                  </td>
                  <td>
                    <div class="fw-semibold text-dark"><?= htmlspecialchars($row['nama_pelanggan']); ?></div>
                    <div class="text-muted small"><?= htmlspecialchars($row['no_telp']); ?></div>
                  </td>
                  <td>
                    <span class="fw-medium text-dark"><?= htmlspecialchars($row['nama_hewan'] ?? '-'); ?></span>
                    <small class="text-muted d-block">(<?= htmlspecialchars($row['jenis_hewan'] ?? '-'); ?>)</small>
                  </td>
                  <td>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      <?= htmlspecialchars($row['nama_kamar'] ?? 'Standard'); ?>
                    </span>
                  </td>
                  <td>
                    <div class="fw-semibold small text-dark"><?= $row['lama_hari']; ?> Hari</div>
                    <small class="text-muted"><?= $row['check_in']; ?> s/d <?= $row['check_out']; ?></small>
                  </td>
                  <td class="text-center pe-4">
                    <?php if ($row['status'] == 'Lunas'): ?>
                      <span class="badge bg-success rounded-pill px-3 py-2">Lunas</span>
                    <?php elseif ($row['status'] == 'Dibatalkan'): ?>
                      <span class="badge bg-danger rounded-pill px-3 py-2">Dibatalkan</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><?= htmlspecialchars($row['status'] ?? 'Menunggu Konfirmasi'); ?></span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-center py-4 text-muted">Belum ada data reservasi.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ================= TAB 2: LAPORAN PEMBAYARAN ================= -->
    <div id="panePembayaran" class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden d-none">
      <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold text-dark mb-0">
          <i class="bi bi-credit-card-2-front text-success me-2"></i>Laporan Transaksi Pembayaran
        </h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-uppercase small text-muted">
            <tr>
              <th class="ps-4">ID Transaksi</th>
              <th>Pelanggan</th>
              <th>Rincian Biaya</th>
              <th>Total Bayar</th>
              <th class="text-center pe-4">Status Verifikasi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($query_pembayaran) > 0): ?>
              <?php while ($p = mysqli_fetch_assoc($query_pembayaran)): 
                $tarif_kamar = $p['harga_kamar'];
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
                    <small class="text-muted d-block">Durasi: <?= $p['lama_hari']; ?> Hari x Rp<?= number_format($tarif_kamar, 0, ',', '.'); ?></small>
                  </td>
                  <td>
                    <span class="fw-bold text-success fs-6">Rp<?= number_format($total_bayar, 0, ',', '.'); ?></span>
                  </td>
                  <td class="text-center pe-4">
                    <?php if ($p['status'] == 'Lunas'): ?>
                      <span class="badge bg-success rounded-pill px-3 py-2">Terverifikasi (Lunas)</span>
                    <?php elseif ($p['status'] == 'Dibatalkan'): ?>
                      <span class="badge bg-danger rounded-pill px-3 py-2">Ditolak</span>
                    <?php else: ?>
                      <span class="badge bg-warning text-dark rounded-pill px-3 py-2">Menunggu Konfirmasi</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">Belum ada data pembayaran.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Bootstrap JS & Script Tab Interaktif -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/bootstrap.bundle.min.js"></script>
  <script>
    function bukaTab(jenis) {
      const paneReservasi = document.getElementById('paneReservasi');
      const panePembayaran = document.getElementById('panePembayaran');
      const btnReservasi = document.getElementById('btnTabReservasi');
      const btnPembayaran = document.getElementById('btnTabPembayaran');

      if (jenis === 'reservasi') {
        paneReservasi.classList.remove('d-none');
        panePembayaran.classList.add('d-none');
        
        btnReservasi.className = "btn btn-success fw-semibold rounded-pill px-4 py-2 shadow-sm";
        btnPembayaran.className = "btn btn-outline-success fw-semibold rounded-pill px-4 py-2";
      } else {
        panePembayaran.classList.remove('d-none');
        paneReservasi.classList.add('d-none');
        
        btnPembayaran.className = "btn btn-success fw-semibold rounded-pill px-4 py-2 shadow-sm";
        btnReservasi.className = "btn btn-outline-success fw-semibold rounded-pill px-4 py-2";
      }
    }
  </script>
</body>
</html>