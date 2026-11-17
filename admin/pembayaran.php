<?php
session_start();
include __DIR__ . '/../config/koneksi.php';

// Tangkap aksi konfirmasi atau tolak dari URL
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $status_baru = ($_GET['aksi'] == 'konfirmasi') ? 'Lunas' : 'Dibatalkan';
    $id_reservasi = (int)$_GET['id'];

    if ($id_reservasi > 0) {
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

        $_SESSION['toast'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Status pembayaran berhasil diperbarui.'
        ];
        header("Location: pembayaran.php");
        exit();
    }
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
    
    $root = str_replace('\\', '/', realpath(__DIR__ . '/../'));

    $kandidat = [
        $nama,
        'uploads/' . $base,
        'uploads/bukti/' . $base,
        'uploads/bukti_pembayaran/' . $base,
        'bukti/' . $base,
        'assets/uploads/' . $base,
        'config/uploads/' . $base,
        'config/assets/uploads/' . $base,
        'admin/uploads/' . $base,
        '../uploads/' . $base,
        '../../uploads/' . $base,
        '../../uploads/bukti/' . $base,
        '../' . $nama,
        '../../' . $nama,
    ];

    $ketemu = null;
    foreach ($kandidat as $k) {
        if (file_exists($root . '/' . ltrim($k, '/'))) { 
            $ketemu = '../' . ltrim($k, '/');
            break; 
        }
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
        if (isset($indeks[$base])) {
            $ketemu = '../' . $indeks[$base];
        }
    }

    if ($ketemu === null) return null;
    return $ketemu;
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
            $kolom_harga AS harga_kamar,
            (SELECT COUNT(*) FROM detail_reservasi dr WHERE dr.id_reservasi = r.id_reservasi) AS jumlah_hewan
        FROM reservasi r
        JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
        LEFT JOIN kamar k ON r.id_kamar = k.id_kamar
        ORDER BY r.id_reservasi DESC";

$query_pembayaran = mysqli_query($koneksi, $sql);
$totalPembayaran = $query_pembayaran ? mysqli_num_rows($query_pembayaran) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - Pet Heaven</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <style>
        :root {
            --sidebar-blue: #2c5f85;      
            --sidebar-active: #5382a6;    
            --header-sage: #c9dede;       
            --bg-cream: #f7f5ed;          
            --text-dark: #203a43;
        }

        body {
            background-color: var(--bg-cream);
            color: #333333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .sidebar-wrapper {
            background-color: var(--sidebar-blue);
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            color: #ffffff;
            font-weight: bold;
            font-size: 1.25rem;
        }

        .sidebar-wrapper .nav-link {
            color: #dce7f0;
            font-weight: 500;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: all 0.2s ease;
        }

        .sidebar-wrapper .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .sidebar-wrapper .nav-link.active {
            background-color: var(--sidebar-active);
            color: #ffffff;
        }

        .topbar-header {
            background-color: var(--header-sage);
            color: var(--text-dark);
            border-radius: 10px;
        }

        .btn-toggle-sidebar {
            background: transparent;
            border: 1px solid var(--text-dark);
            color: var(--text-dark);
        }

        .card-custom {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .card-header-blue {
            background-color: var(--sidebar-blue);
            color: #ffffff;
            font-weight: 600;
            border-top-left-radius: 10px !important;
            border-top-right-radius: 10px !important;
        }

        .table-sage-header {
            background-color: var(--header-sage) !important;
            color: var(--text-dark) !important;
        }

        .dataTables_wrapper {
            padding: 1.5rem !important;
        }

        .dataTables_wrapper .dataTables_length {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            color: #333;
            font-size: 0.95rem;
        }

        .dataTables_wrapper .dataTables_length select {
            width: 75px !important;
            display: inline-block;
            padding: 4px 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #212529;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 6px;
            text-align: center;
            height: auto !important;
        }

        .dataTables_wrapper .dataTables_length select:focus {
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 0.2rem rgba(83, 130, 166, 0.25);
            outline: none;
        }

        .dataTables_wrapper .dataTables_filter {
            display: inline-block;
            float: right;
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            display: inline-block;
            width: auto;
            padding: 4px 12px;
            font-size: 0.9rem;
            border-radius: 6px;
            border: 1px solid #ced4da;
            background-color: #fff;
            margin-left: 8px; 
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--sidebar-active);
            box-shadow: 0 0 0 0.2rem rgba(83, 130, 166, 0.25);
            outline: none;
        }
    </style>
</head>
<body>

<?php
if (isset($_SESSION['toast'])):
?>
<script>
    $(document).ready(function() {
        toastr.options = {
            "closeButton": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "timeOut": "5000"
        };
        toastr["<?= $_SESSION['toast']['type']?>"](
            "<?= $_SESSION['toast']['message'] ?>",
            "<?= $_SESSION['toast']['title'] ?>"
        );
    });
</script>
<?php
unset($_SESSION['toast']);
endif;
?>

<div class="container-fluid">
    <div class="row">
        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 sidebar-wrapper p-3" id="sidebar">
            <div class="text-center py-3 mb-3 border-bottom border-light-subtle">
                <img src="../config/assets/images/logo.png" alt="Logo" width="180" class="mb-2 d-block mx-auto img-fluid rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" onerror="this.onerror=null;">
                <div class="sidebar-brand">Pet Heaven</div>
            </div>
            <nav class="nav flex-column">
                <a href="dashboard.php" class="nav-link"><i class="fas fa-house me-2"></i> Dashboard</a>
                <a href="pelanggan/index.php" class="nav-link"><i class="fas fa-user me-2"></i> Pelanggan</a>
                <a href="hewan/index.php" class="nav-link"><i class="fas fa-paw me-2"></i> Hewan</a>
                <a href="reservasi/index.php" class="nav-link"><i class="fas fa-calendar-check me-2"></i> Reservasi</a>
                <a href="kamar/index.php" class="nav-link"><i class="fas fa-bed me-2"></i> Kamar</a>
                <a href="layanan/layanan.php" class="nav-link"><i class="fas fa-scissors me-2"></i> Layanan</a>
                <a href="pembayaran.php" class="nav-link active"><i class="fas fa-wallet me-2"></i> Pembayaran</a>
                <a href="laporan.php" class="nav-link"><i class="fas fa-file me-2"></i> Laporan</a>
                <a href="logout.php" class="nav-link text-danger mt-4">
                    <i class="fas fa-right-from-bracket me-2"></i> Logout
                </a>
            </nav>
        </div>

        <!-- MAIN CONTENT -->
        <div class="col-md-9 col-lg-10 p-4" id="mainContent">
            <!-- TOPBAR -->
            <div class="d-flex justify-content-between align-items-center topbar-header p-3 shadow-sm mb-4">
                <div class="d-flex align-items-center">
                    <button class="btn btn-sm btn-toggle-sidebar me-3" id="menuBtn"><i class="fas fa-bars"></i></button>
                    <h5 class="mb-0 fw-bold">Verifikasi Pembayaran</h5>
                </div>
                <div class="d-flex align-items-center fw-semibold">
                    <i class="fas fa-circle-user fs-4 me-2"></i>
                    <span><?= htmlspecialchars($_SESSION['nm_admin'] ?? 'Admin'); ?></span>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="card card-custom overflow-hidden">
                <div class="card-header card-header-blue py-3">
                    <h5 class="mb-0 fw-bold fs-6"><i class="fas fa-wallet me-2"></i>Daftar Verifikasi Pembayaran (<?= $totalPembayaran; ?>)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="myTable">
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
                                    $jml_hewan      = max((int)$p['jumlah_hewan'], 1);
                                    $subtotal_kamar = $p['lama_hari'] * $tarif_kamar * $jml_hewan;
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
        </div>
    </div>
</div>

<!-- MODAL BUKTI TRANSFER -->
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
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    document.getElementById("menuBtn").addEventListener("click", function () {
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");
        sidebar.classList.toggle("d-none");
        mainContent.className = sidebar.classList.contains("d-none") ? "col-12 p-4" : "col-md-9 col-lg-10 p-4";
    });

    $(document).ready(function() {
        $('#myTable').DataTable({
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    });

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
</body>
</html>