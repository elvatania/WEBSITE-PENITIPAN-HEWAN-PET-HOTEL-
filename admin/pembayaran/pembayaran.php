<?php 
include __DIR__ . "/../../config/koneksi.php";  

    if (isset($status_baru)) {
        mysqli_query($koneksi, "UPDATE reservasi SET status = '$status_baru' WHERE id_reservasi = '$id_reservasi'");

        if ($status_baru == 'Lunas') {
            mysqli_query($koneksi, "
                INSERT INTO pembayaran (id_reservasi, tgl_bayar, total_bayar, metode_bayar, status_bayar, bukti_bayar)
                SELECT r.id_reservasi,
                       CURDATE(),
                       (r.lama_hari * k.harga_per_hari
                         * GREATEST((SELECT COUNT(*) FROM detail_reservasi dr WHERE dr.id_reservasi = r.id_reservasi), 1)
                       ) + r.total_layanan,
                       'Transfer',
                       'Lunas',
                       COALESCE(r.bukti_pembayaran, '')
                FROM reservasi r
                JOIN kamar k ON k.id_kamar = r.id_kamar
                WHERE r.id_reservasi = '$id_reservasi'
                  AND NOT EXISTS (SELECT 1 FROM pembayaran WHERE id_reservasi = r.id_reservasi)
            ");
        }

        header("Location: pembayaran.php?pesan=berhasil");
        exit();
    }
$kolomHargaKamar = null;
$qh = mysqli_query($koneksi, "SHOW COLUMNS FROM kamar");
while ($qh && $c = mysqli_fetch_assoc($qh)) {
    if (preg_match('/harga/i', $c['Field'])) {
        $kolomHargaKamar = $c['Field'];
        break;
    }
}
$kolom_harga = $kolomHargaKamar ? "k.`$kolomHargaKamar`" : "50000";

$ekspresi_bukti = [];

$qr = mysqli_query($koneksi, "SHOW COLUMNS FROM reservasi");
while ($qr && $c = mysqli_fetch_assoc($qr)) {
    if (preg_match('/bukti|foto|struk/i', $c['Field'])) {
        $ekspresi_bukti[] = "NULLIF(r.`" . $c['Field'] . "`, '')";
    }
}

$qp = mysqli_query($koneksi, "SHOW COLUMNS FROM pembayaran");
while ($qp && $c = mysqli_fetch_assoc($qp)) {
    if (preg_match('/bukti|foto|struk/i', $c['Field'])) {
        $ekspresi_bukti[] = "NULLIF((SELECT bb.`" . $c['Field'] . "` FROM pembayaran bb
                              WHERE bb.id_reservasi = r.id_reservasi
                                AND bb.`" . $c['Field'] . "` IS NOT NULL
                                AND bb.`" . $c['Field'] . "` <> ''
                              ORDER BY bb.id_bayar DESC LIMIT 1), '')";
    }
}

$kolom_bukti_bayar = count($ekspresi_bukti) > 0
    ? "COALESCE(" . implode(", ", $ekspresi_bukti) . ")"
    : "NULL";

function cariBukti($nama) {
    $nama = trim((string)$nama);
    if ($nama === '') return null;

    $nama = str_replace('\\', '/', $nama);
    $base = basename($nama);
    $root = str_replace('\\', '/', __DIR__);

    $kandidat = [
        $nama,
        'uploads/' . $base,
        'uploads/bukti/' . $base,
        'uploads/bukti_pembayaran/' . $base,
        'bukti/' . $base,
        'assets/uploads/' . $base,
        'config/uploads/' . $base,
        'config/assets/uploads/' . $base,
        '../uploads/' . $base,
        '../uploads/bukti/' . $base,
        '../' . $nama,
    ];

    $ketemu = null;
    foreach ($kandidat as $k) {
        if (file_exists($root . '/' . $k)) { $ketemu = $k; break; }
    }

    if ($ketemu === null) {
        static $indeks = null;
        if ($indeks === null) {
            $indeks = [];
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($it as $f) {
                    $path = str_replace('\\', '/', $f->getPathname());
                    if (!$f->isFile() || preg_match('#/(\.git|node_modules|vendor)/#', $path)) continue;
                    $indeks[$f->getFilename()] = substr($path, strlen($root) + 1);
                }
            } catch (Exception $e) {}
        }
        if (isset($indeks[$base])) $ketemu = $indeks[$base];
    }

    if ($ketemu === null) return null;
    return implode('/', array_map('rawurlencode', explode('/', $ketemu)));
}

$sql = "SELECT 
            r.id_reservasi,
            r.tgl_reservasi,
            r.lama_hari,
            r.total_layanan,
            r.status,
            r.bukti_pembayaran,
            $kolom_bukti_bayar AS bukti_bayar,
            p.nama_pelanggan,
            p.no_telp,
            k.nama_kamar,
            $kolom_harga AS harga_kamar
        FROM reservasi r
        JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
        LEFT JOIN kamar k ON r.id_kamar = k.id_kamar
        ORDER BY r.id_reservasi DESC";

$query_pembayaran = mysqli_query($koneksi, $sql);

$judulHalaman    = 'Verifikasi Pembayaran';
$menuAktif       = 'pembayaran.php';
$pakaiDataTables = true;
include __DIR__ . '/../../includes/sidebar_header.php';
?>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] == 'berhasil'): ?>
  <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
    <i class="fas fa-circle-check me-2"></i>Status pembayaran berhasil diperbarui! Data laporan dan statistik ikut ter-update otomatis.
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>

<div class="card card-custom overflow-hidden">
  <div class="card-header card-header-blue py-3">
    <i class="fas fa-wallet me-2"></i> Daftar Verifikasi Pembayaran
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 tabel-datatable">
        <thead>
          <tr class="table-sage-header">
            <th class="ps-3 py-3">ID / Tgl</th>
            <th class="py-3">Pelanggan</th>
            <th class="py-3">Kamar</th>
            <th class="py-3">Total Biaya</th>
            <th class="py-3 no-sort">Bukti Transfer</th>
            <th class="py-3">Status</th>
            <th class="py-3 text-center no-sort">Aksi Verifikasi</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($p = mysqli_fetch_assoc($query_pembayaran)):
            $tarif_kamar    = $p['harga_kamar'];
            $subtotal_kamar = $p['lama_hari'] * $tarif_kamar;
            $total_bayar    = $subtotal_kamar + $p['total_layanan'];
            $bukti          = !empty($p['bukti_pembayaran']) ? $p['bukti_pembayaran'] : ($p['bukti_bayar'] ?? '');
            $urlBukti       = cariBukti($bukti);
          ?>
            <tr>
              <td class="ps-3">
                <span class="fw-bold" style="color: var(--sidebar-blue);">#<?= $p['id_reservasi']; ?></span>
                <div class="text-muted small"><?= $p['tgl_reservasi']; ?></div>
              </td>
              <td>
                <div class="fw-semibold"><?= htmlspecialchars($p['nama_pelanggan']); ?></div>
                <div class="text-muted small"><?= htmlspecialchars($p['no_telp']); ?></div>
              </td>
              <td>
                <?= htmlspecialchars($p['nama_kamar'] ?? 'Standard'); ?>
              </td>
              <td data-order="<?= (int)$total_bayar; ?>">
                <span class="fw-bold text-success">Rp<?= number_format($total_bayar, 0, ',', '.'); ?></span>
              </td>
              <td>
                <?php if ($urlBukti): ?>
                  <a href="<?= $urlBukti; ?>" target="_blank" data-foto="<?= $urlBukti; ?>"
                     class="btn btn-sm btn-outline-primary rounded-2 py-1 px-2 btn-lihat-foto">
                    <i class="fas fa-image me-1"></i>Lihat Foto
                  </a>
                <?php elseif (!empty($bukti)): ?>
                  <span class="small text-danger">File tidak ditemukan</span>
                  <div class="text-muted small"><?= htmlspecialchars($bukti); ?></div>
                <?php else: ?>
                  <span class="small fst-italic text-danger">Belum Ada (Wajib Upload)</span>
                <?php endif; ?>
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
              <td class="text-center">
                <?php if ($p['status'] != 'Lunas' && $p['status'] != 'Dibatalkan'): ?>
                  <a href="pembayaran.php?aksi=konfirmasi&id=<?= $p['id_reservasi']; ?>"
                     onclick="return confirm('Apakah Anda yakin bukti transfer ini SUDAH SESUAI?')"
                     class="btn btn-sm btn-success text-white rounded-2 me-1" title="Konfirmasi">
                    <i class="fas fa-check"></i>
                  </a>
                  <a href="pembayaran.php?aksi=tolak&id=<?= $p['id_reservasi']; ?>"
                     onclick="return confirm('Apakah Anda yakin ingin MENOLAK pembayaran ini?')"
                     class="btn btn-sm btn-danger text-white rounded-2" title="Tolak">
                    <i class="fas fa-xmark"></i>
                  </a>
                <?php else: ?>
                  <span class="text-muted small"><i class="fas fa-check-double text-success me-1"></i>Selesai</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="modalBukti" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header card-header-blue" style="border-radius: 10px 10px 0 0;">
        <h5 class="modal-title"><i class="fas fa-image me-2"></i>Bukti Transfer</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body text-center p-3" style="background:#f7f5ed;">
        <img id="buktiGambar" src="" alt="Bukti transfer" class="img-fluid rounded shadow-sm" style="max-height:70vh; cursor:zoom-in;">
        <iframe id="buktiPdf" src="" class="w-100 rounded d-none" style="height:70vh;border:0;"></iframe>
      </div>
      <div class="modal-footer border-0">
        <span class="text-muted small me-auto"><i class="fas fa-magnifying-glass-plus me-1"></i>Klik foto untuk memperbesar</span>
        <button type="button" class="btn btn-tambah btn-sm px-3" data-bs-dismiss="modal">
          <i class="fas fa-arrow-left me-1"></i>Kembali
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('click', function (e) {
    var tombol = e.target.closest('.btn-lihat-foto');
    if (!tombol) return;
    e.preventDefault();

    var url = tombol.dataset.foto;
    var img = document.getElementById('buktiGambar');
    var pdf = document.getElementById('buktiPdf');

    if (/\.pdf($|\?)/i.test(url)) {
      img.classList.add('d-none'); img.src = '';
      pdf.classList.remove('d-none'); pdf.src = url;
    } else {
      pdf.classList.add('d-none'); pdf.src = '';
      img.classList.remove('d-none'); img.src = url;
    }

    img.style.maxHeight = '70vh';
    img.style.cursor = 'zoom-in';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBukti')).show();
  });

  document.getElementById('buktiGambar').addEventListener('click', function () {
    var besar = this.style.maxHeight === 'none';
    this.style.maxHeight = besar ? '70vh' : 'none';
    this.style.cursor    = besar ? 'zoom-in' : 'zoom-out';
  });
</script>

<?php include __DIR__ . '/../../includes/penutup_halaman.php';  ?>