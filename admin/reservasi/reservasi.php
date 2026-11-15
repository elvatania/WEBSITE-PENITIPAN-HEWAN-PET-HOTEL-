<?php
// Halaman ini ada di dalam subfolder, jadi path memakai ../
include __DIR__ . "/../config/koneksi.php";
$basePath = '../';

// Daftar status yang bisa dipilih admin
$daftarStatus = [
    'Menunggu Pembayaran',
    'Menunggu Konfirmasi',
    'Dikonfirmasi',
    'Proses',
    'Selesai',
    'Lunas',
    'Dibatalkan',
];

$error = '';
$modalBuka = '';

// Kalau kamu sudah punya halaman tambah reservasi sendiri (misal 'tambah.php'),
// isi nama filenya di sini. Tombol "Tambah Reservasi" akan membuka halaman itu.
// Kalau dikosongkan, tombol membuka form popup bawaan.
$halamanTambah = '';

// Deteksi kolom yang mungkin ada / tidak ada
$cekB = mysqli_query($koneksi, "SHOW COLUMNS FROM reservasi LIKE 'bukti_pembayaran'");
$adaBuktiRes = $cekB && mysqli_num_rows($cekB) > 0;

$cekHP = mysqli_query($koneksi, "SHOW COLUMNS FROM hewan LIKE 'id_pelanggan'");
$adaHewanPel = $cekHP && mysqli_num_rows($cekHP) > 0;

// Kolom nama & harga di tabel layanan (namanya dideteksi otomatis)
$kolomNamaLay  = 'nama_layanan';
$kolomHargaLay = null;
$qL = mysqli_query($koneksi, "SHOW COLUMNS FROM layanan");
$kolomLayanan = [];
while ($qL && $c = mysqli_fetch_assoc($qL)) { $kolomLayanan[] = $c['Field']; }
foreach (['nama_layanan', 'jenis_layanan', 'layanan', 'nama'] as $kand) {
    if (in_array($kand, $kolomLayanan)) { $kolomNamaLay = $kand; break; }
}
foreach ($kolomLayanan as $f) {
    if (preg_match('/harga/i', $f)) { $kolomHargaLay = $f; break; }
}

// Tabel penghubung reservasi <-> layanan (punya kolom id_reservasi dan id_layanan)
$tabelRsvLayanan = null;
$kolomRsvLayanan = [];
$qT = mysqli_query($koneksi, "SHOW TABLES");
while ($qT && $t = mysqli_fetch_row($qT)) {
    $nt = $t[0];
    if (in_array(strtolower($nt), ['reservasi', 'hewan', 'pelanggan', 'kamar', 'pembayaran', 'layanan'])) continue;
    $kt = [];
    $qk = mysqli_query($koneksi, "SHOW COLUMNS FROM `$nt`");
    while ($qk && $k = mysqli_fetch_assoc($qk)) { $kt[] = $k['Field']; }
    if (in_array('id_reservasi', $kt) && in_array('id_layanan', $kt)) {
        $tabelRsvLayanan = $nt;
        $kolomRsvLayanan = $kt;
        break;
    }
}

// Kolom nama hewan yang tersimpan langsung di tabel reservasi (kalau ada)
$kolomHewanLangsung = null;
$qc = mysqli_query($koneksi, "SHOW COLUMNS FROM reservasi");
while ($qc && $c = mysqli_fetch_assoc($qc)) {
    if (preg_match('/hewan/i', $c['Field']) && !preg_match('/^id_/i', $c['Field'])) {
        $kolomHewanLangsung = $c['Field'];
        break;
    }
}

// =====================================
// PROSES UBAH STATUS / HAPUS
// (harus di atas layout, karena memakai header() redirect)
// =====================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- TAMBAH RESERVASI ----------
    if (isset($_POST['tambah_reservasi'])) {
        $idPel  = (int) ($_POST['id_pelanggan'] ?? 0);
        $idHew  = (int) ($_POST['id_hewan'] ?? 0);
        $idKam  = (int) ($_POST['id_kamar'] ?? 0);
        $ci     = $_POST['check_in'] ?? '';
        $co     = $_POST['check_out'] ?? '';
        $status = $_POST['status'] ?? 'Menunggu Pembayaran';
        $idLayananPilih = array_values(array_unique(array_filter(array_map('intval', $_POST['id_layanan'] ?? []))));

        $formatTgl = '/^\d{4}-\d{2}-\d{2}$/';

        if ($idPel <= 0 || $idHew <= 0 || $idKam <= 0) {
            $error = 'Pelanggan, hewan, dan kamar wajib dipilih.';
        } elseif (!preg_match($formatTgl, $ci) || !preg_match($formatTgl, $co)) {
            $error = 'Tanggal check in dan check out wajib diisi.';
        } elseif ($co < $ci) {
            $error = 'Tanggal check out tidak boleh sebelum check in.';
        } elseif (!in_array($status, $daftarStatus, true)) {
            $error = 'Status tidak valid.';
        } else {
            $lama = max(1, (int) ((strtotime($co) - strtotime($ci)) / 86400));
            $tgl  = date('Y-m-d');

            $kolom = "id_pelanggan, id_hewan, id_kamar, tgl_reservasi, check_in, check_out, lama_hari, total_layanan, status";
            $isi   = "?, ?, ?, ?, ?, ?, ?, ?, ?";

            // Hitung total layanan dari layanan yang dicentang
            $totalLayanan = 0;
            $hargaLayanan = [];
            if ($idLayananPilih && $kolomHargaLay) {
                $daftarId = implode(',', $idLayananPilih); // sudah dipastikan angka
                $qh = mysqli_query($koneksi, "SELECT id_layanan, `$kolomHargaLay` AS harga FROM layanan WHERE id_layanan IN ($daftarId)");
                while ($qh && $h = mysqli_fetch_assoc($qh)) {
                    $hargaLayanan[(int)$h['id_layanan']] = (int)$h['harga'];
                    $totalLayanan += (int)$h['harga'];
                }
            }
            if ($adaBuktiRes) {
                $kolom .= ", bukti_pembayaran";
                $isi   .= ", ''";
            }

            try {
                $stmt = mysqli_prepare($koneksi, "INSERT INTO reservasi ($kolom) VALUES ($isi)");
                mysqli_stmt_bind_param($stmt, "iiisssiis", $idPel, $idHew, $idKam, $tgl, $ci, $co, $lama, $totalLayanan, $status);
                mysqli_stmt_execute($stmt);
                $idBaru = mysqli_insert_id($koneksi);

                // Simpan rincian layanan yang dipilih ke tabel penghubung (kalau ada)
                if ($tabelRsvLayanan && $idBaru && $idLayananPilih) {
                    foreach ($idLayananPilih as $idLay) {
                        $kol = ['id_reservasi', 'id_layanan'];
                        $val = [(int)$idBaru, (int)$idLay];
                        $harga = $hargaLayanan[$idLay] ?? 0;

                        foreach ($kolomRsvLayanan as $f) {
                            if (in_array($f, ['id_reservasi', 'id_layanan'])) continue;
                            if (preg_match('/^jumlah|^qty/i', $f)) { $kol[] = $f; $val[] = 1; }
                            elseif (preg_match('/harga|subtotal|total/i', $f)) { $kol[] = $f; $val[] = (int)$harga; }
                        }

                        $sqlPivot = "INSERT INTO `$tabelRsvLayanan` (`" . implode('`, `', $kol) . "`) VALUES (" . implode(', ', $val) . ")";
                        mysqli_query($koneksi, $sqlPivot);
                    }
                }

                header("Location: index.php?pesan=tambah_berhasil");
                exit();
            } catch (mysqli_sql_exception $e) {
                $error = 'Gagal menyimpan reservasi: ' . $e->getMessage();
            }
        }
        if ($error != '') $modalBuka = 'tambah';
    }

    // ---------- UBAH STATUS ----------
    if (isset($_POST['ubah_status'])) {
        $id     = (int) ($_POST['id_reservasi'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($id <= 0 || !in_array($status, $daftarStatus, true)) {
            $error = 'Data tidak valid.';
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE reservasi SET status = ? WHERE id_reservasi = ?");
            mysqli_stmt_bind_param($stmt, "si", $status, $id);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: index.php?pesan=status_berhasil");
                exit();
            }
            $error = 'Gagal mengubah status: ' . mysqli_error($koneksi);
        }
    }

    // ---------- HAPUS ----------
    if (isset($_POST['hapus_reservasi'])) {
        $id = (int) ($_POST['id_reservasi'] ?? 0);

        if ($id > 0) {
            $stmt = mysqli_prepare($koneksi, "DELETE FROM reservasi WHERE id_reservasi = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);

            try {
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: index.php?pesan=hapus_berhasil");
                    exit();
                }
                $error = 'Gagal menghapus reservasi: ' . mysqli_error($koneksi);
            } catch (mysqli_sql_exception $e) {
                // Biasanya karena reservasi masih terhubung dengan data pembayaran/layanan
                $error = 'Reservasi tidak bisa dihapus karena masih terhubung dengan data lain (misalnya pembayaran).';
            }
        }
    }
}

// =====================================
// DATA RESERVASI
// =====================================
// Cari kolom harga di tabel kamar (namanya bisa harga, harga_kamar, harga_per_hari, dst.)
$kolomHargaKamar = null;
$qh = mysqli_query($koneksi, "SHOW COLUMNS FROM kamar");
while ($qh && $c = mysqli_fetch_assoc($qh)) {
    if (preg_match('/harga/i', $c['Field'])) {
        $kolomHargaKamar = $c['Field'];
        break;
    }
}
$kolomHarga = $kolomHargaKamar ? "k.`$kolomHargaKamar`" : "0";

$exprNamaHewan = $kolomHewanLangsung
    ? "COALESCE(h.nama_hewan, NULLIF(r.`$kolomHewanLangsung`, ''))"
    : "h.nama_hewan";

// Cadangan: cari tabel penghubung reservasi <-> hewan (yang punya kolom
// id_reservasi dan id_hewan), misalnya detail_reservasi / reservasi_hewan.
$exprHewanPel = "NULL";
$tabelPivot = null;
$qt = mysqli_query($koneksi, "SHOW TABLES");
while ($qt && $t = mysqli_fetch_row($qt)) {
    $nt = $t[0];
    if (in_array(strtolower($nt), ['reservasi', 'hewan', 'pelanggan', 'kamar', 'pembayaran', 'layanan'])) continue;

    $kolomTabel = [];
    $qk = mysqli_query($koneksi, "SHOW COLUMNS FROM `$nt`");
    while ($qk && $k = mysqli_fetch_assoc($qk)) $kolomTabel[] = $k['Field'];

    if (in_array('id_reservasi', $kolomTabel) && in_array('id_hewan', $kolomTabel)) {
        $tabelPivot = $nt;
        break;
    }
}
if ($tabelPivot) {
    $exprHewanPel = "(SELECT GROUP_CONCAT(DISTINCT h3.nama_hewan SEPARATOR ', ')
                      FROM `$tabelPivot` pv
                      JOIN hewan h3 ON pv.id_hewan = h3.id_hewan
                      WHERE pv.id_reservasi = r.id_reservasi)";
}

$query = mysqli_query($koneksi, "
    SELECT
        r.id_reservasi,
        r.tgl_reservasi,
        r.check_in,
        r.check_out,
        r.lama_hari,
        r.total_layanan,
        r.status,

        p.nama_pelanggan,
        p.no_telp,

        $exprNamaHewan AS nama_hewan,
        $exprHewanPel AS hewan_pelanggan,
        h.jenis_hewan,

        k.nama_kamar,
        $kolomHarga AS harga_kamar

    FROM reservasi r
    LEFT JOIN pelanggan p ON r.id_pelanggan = p.id_pelanggan
    LEFT JOIN hewan h     ON r.id_hewan = h.id_hewan
    LEFT JOIN kamar k     ON r.id_kamar = k.id_kamar
    ORDER BY r.id_reservasi DESC
");

$dataReservasi = [];
$hitung = ['total' => 0, 'menunggu' => 0, 'aktif' => 0, 'batal' => 0];

while ($query && $row = mysqli_fetch_assoc($query)) {
    $dataReservasi[] = $row;

    $st = strtolower(trim($row['status'] ?? ''));
    $hitung['total']++;

    if (strpos($st, 'menunggu') !== false || strpos($st, 'pending') !== false) {
        $hitung['menunggu']++;
    } elseif ($st === 'dibatalkan') {
        $hitung['batal']++;
    } else {
        $hitung['aktif']++;
    }
}

// =====================================
// FUNGSI BANTU TAMPILAN
// =====================================
function tglIndo($tgl) {
    if (empty($tgl) || $tgl == '0000-00-00') return '-';
    return date('d M Y', strtotime($tgl));
}

function badgeStatus($status) {
    $st = strtolower(trim($status ?? ''));

    if ($st === 'lunas')                      $kelas = 'bg-success';
    elseif ($st === 'selesai')                $kelas = 'badge-selesai';
    elseif ($st === 'dikonfirmasi')           $kelas = 'bg-info text-dark';
    elseif ($st === 'proses')                 $kelas = 'bg-primary';
    elseif ($st === 'dibatalkan')             $kelas = 'bg-danger';
    elseif (strpos($st, 'menunggu') !== false || strpos($st, 'pending') !== false)
                                              $kelas = 'bg-warning text-dark';
    else                                      $kelas = 'bg-secondary';

    return '<span class="badge ' . $kelas . ' px-3 py-2 rounded-pill">'
         . htmlspecialchars($status ?: '-') . '</span>';
}

// =====================================
// PASANG LAYOUT (sidebar + header)
// =====================================
// Data pilihan untuk form Tambah Reservasi
$listPelanggan = mysqli_query($koneksi, "SELECT id_pelanggan, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan");
$listHewan = mysqli_query($koneksi, "SELECT id_hewan, nama_hewan, jenis_hewan, " . ($adaHewanPel ? "id_pelanggan" : "0 AS id_pelanggan") . " FROM hewan ORDER BY nama_hewan");
$listKamar = mysqli_query($koneksi, "SELECT id_kamar, nama_kamar FROM kamar ORDER BY nama_kamar");
$listLayanan = [];
$qLay = mysqli_query($koneksi, "SELECT id_layanan, `$kolomNamaLay` AS nama" . ($kolomHargaLay ? ", `$kolomHargaLay` AS harga" : ", 0 AS harga") . " FROM layanan ORDER BY `$kolomNamaLay`");
while ($qLay && $o = mysqli_fetch_assoc($qLay)) { $listLayanan[] = $o; }
$pilihLayananPost = ($modalBuka == 'tambah') ? array_map('intval', $_POST['id_layanan'] ?? []) : [];
$pv = function ($k) use ($modalBuka) { return $modalBuka == 'tambah' ? ($_POST[$k] ?? '') : ''; };

$judulHalaman    = 'Kelola Reservasi';
$menuAktif       = 'reservasi/index.php';
$pakaiDataTables = true;
include __DIR__ . '/../includes/sidebar_header.php';
?>

<style>
    .badge-selesai { background-color: #d1e7dd; color: #0f5132; }
    .kartu-mini .angka { font-size: 1.6rem; font-weight: 700; }
</style>

<!-- NOTIFIKASI -->
<?php
$pesanNotif = [
    'tambah_berhasil' => 'Reservasi baru berhasil ditambahkan.',
    'status_berhasil' => 'Status reservasi berhasil diperbarui.',
    'hapus_berhasil'  => 'Reservasi berhasil dihapus.',
];
if (isset($_GET['pesan']) && isset($pesanNotif[$_GET['pesan']])):
?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-circle-check me-2"></i><?= $pesanNotif[$_GET['pesan']]; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error != ''): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- RINGKASAN -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 bg-white kartu-mini">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small">Total Reservasi</span>
                    <div class="angka"><?= $hitung['total']; ?></div>
                </div>
                <i class="fas fa-calendar-check fa-2x text-primary"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 bg-white kartu-mini">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small">Menunggu</span>
                    <div class="angka"><?= $hitung['menunggu']; ?></div>
                </div>
                <i class="fas fa-hourglass-half fa-2x text-warning"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 bg-white kartu-mini">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small">Aktif / Selesai</span>
                    <div class="angka"><?= $hitung['aktif']; ?></div>
                </div>
                <i class="fas fa-circle-check fa-2x text-success"></i>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-custom p-3 bg-white kartu-mini">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small">Dibatalkan</span>
                    <div class="angka"><?= $hitung['batal']; ?></div>
                </div>
                <i class="fas fa-circle-xmark fa-2x text-danger"></i>
            </div>
        </div>
    </div>
</div>

<!-- TABEL RESERVASI -->
<div class="card card-custom overflow-hidden">
    <div class="card-header card-header-blue py-3 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-calendar-check me-2"></i> Daftar Reservasi</span>

        <?php if ($halamanTambah != ''): ?>
            <a href="<?= htmlspecialchars($halamanTambah); ?>" class="btn btn-tambah btn-sm fw-semibold">
                <i class="fas fa-plus me-1"></i> Tambah Reservasi
            </a>
        <?php else: ?>
            <button type="button" class="btn btn-tambah btn-sm fw-semibold"
                    data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="fas fa-plus me-1"></i> Tambah Reservasi
            </button>
        <?php endif; ?>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabel-datatable">
                <thead>
                    <tr class="table-sage-header">
                        <th class="ps-3 py-3">Booking Code</th>
                        <th class="py-3">Pelanggan</th>
                        <th class="py-3">Hewan</th>
                        <th class="py-3">Kamar</th>
                        <th class="py-3">Check In</th>
                        <th class="py-3">Check Out</th>
                        <th class="py-3 text-center">Status</th>
                        <th class="py-3 text-center no-sort" style="width:150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataReservasi as $r):
                        $kode        = 'RSV' . str_pad($r['id_reservasi'], 3, '0', STR_PAD_LEFT);
                        $subKamar    = (int)$r['lama_hari'] * (float)$r['harga_kamar'];
                        $totalBiaya  = $subKamar + (float)$r['total_layanan'];

                        // Nama hewan untuk tampilan
                        if (!empty($r['nama_hewan'])) {
                            $hewanTampil = $r['nama_hewan'] . (!empty($r['jenis_hewan']) ? ' (' . $r['jenis_hewan'] . ')' : '');
                        } elseif (!empty($r['hewan_pelanggan'])) {
                            $hewanTampil = $r['hewan_pelanggan'];
                        } else {
                            $hewanTampil = '-';
                        }

                        $detail = [
                            'kode'      => $kode,
                            'tgl'       => tglIndo($r['tgl_reservasi']),
                            'pelanggan' => $r['nama_pelanggan'] ?? '-',
                            'telp'      => $r['no_telp'] ?? '-',
                            'hewan'     => $hewanTampil,
                            'kamar'     => $r['nama_kamar'] ?? '-',
                            'checkin'   => tglIndo($r['check_in']),
                            'checkout'  => tglIndo($r['check_out']),
                            'lama'      => (int)$r['lama_hari'] . ' Hari',
                            'subkamar'  => 'Rp' . number_format($subKamar, 0, ',', '.'),
                            'layanan'   => 'Rp' . number_format((float)$r['total_layanan'], 0, ',', '.'),
                            'total'     => 'Rp' . number_format($totalBiaya, 0, ',', '.'),
                            'status'    => $r['status'] ?? '-',
                        ];
                    ?>
                        <tr>
                            <td class="ps-3 fw-bold" style="color: var(--sidebar-blue);"
                                data-order="<?= (int)$r['id_reservasi']; ?>">
                                <?= $kode; ?>
                            </td>
                            <td><?= htmlspecialchars($r['nama_pelanggan'] ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($r['nama_hewan'])): ?>
                                    <?= htmlspecialchars($r['nama_hewan']); ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($r['jenis_hewan'] ?? ''); ?></small>
                                <?php elseif (!empty($r['hewan_pelanggan'])): ?>
                                    <?= htmlspecialchars($r['hewan_pelanggan']); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($r['nama_kamar'] ?? '-'); ?></td>
                            <td data-order="<?= htmlspecialchars($r['check_in'] ?? ''); ?>"><?= tglIndo($r['check_in']); ?></td>
                            <td data-order="<?= htmlspecialchars($r['check_out'] ?? ''); ?>"><?= tglIndo($r['check_out']); ?></td>
                            <td class="text-center"><?= badgeStatus($r['status']); ?></td>
                            <td class="text-center">

                                <!-- Detail -->
                                <button type="button"
                                        class="btn btn-sm btn-info text-white rounded-2 me-1 btn-detail"
                                        title="Detail"
                                        data-detail="<?= htmlspecialchars(json_encode($detail), ENT_QUOTES); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalDetail">
                                    <i class="fas fa-eye"></i>
                                </button>

                                <!-- Ubah status -->
                                <button type="button"
                                        class="btn btn-sm btn-warning text-white rounded-2 me-1 btn-status"
                                        title="Ubah Status"
                                        data-id="<?= (int)$r['id_reservasi']; ?>"
                                        data-kode="<?= $kode; ?>"
                                        data-status="<?= htmlspecialchars($r['status'] ?? '', ENT_QUOTES); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalStatus">
                                    <i class="fas fa-pen-to-square"></i>
                                </button>

                                <!-- Hapus -->
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Yakin ingin menghapus reservasi <?= $kode; ?>?');">
                                    <input type="hidden" name="id_reservasi" value="<?= (int)$r['id_reservasi']; ?>">
                                    <button type="submit" name="hapus_reservasi"
                                            class="btn btn-sm btn-danger text-white rounded-2" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>

                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<!-- ================= MODAL TAMBAH RESERVASI ================= -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header card-header-blue" style="border-radius: 10px 10px 0 0;">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Reservasi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Pelanggan</label>
                    <select name="id_pelanggan" id="tPelanggan" class="form-select" required>
                        <option value="">-- Pilih pelanggan --</option>
                        <?php while ($listPelanggan && $o = mysqli_fetch_assoc($listPelanggan)): ?>
                            <option value="<?= (int)$o['id_pelanggan']; ?>"
                                <?= $pv('id_pelanggan') == $o['id_pelanggan'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($o['nama_pelanggan']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Hewan</label>
                    <select name="id_hewan" id="tHewan" class="form-select" required>
                        <option value="">-- Pilih hewan --</option>
                        <?php while ($listHewan && $o = mysqli_fetch_assoc($listHewan)): ?>
                            <option value="<?= (int)$o['id_hewan']; ?>"
                                    data-pelanggan="<?= (int)$o['id_pelanggan']; ?>"
                                <?= $pv('id_hewan') == $o['id_hewan'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($o['nama_hewan'] . ' (' . $o['jenis_hewan'] . ')'); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($adaHewanPel): ?>
                        <div class="form-text">Daftar hewan menyesuaikan pelanggan yang dipilih.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kamar</label>
                    <select name="id_kamar" class="form-select" required>
                        <option value="">-- Pilih kamar --</option>
                        <?php while ($listKamar && $o = mysqli_fetch_assoc($listKamar)): ?>
                            <option value="<?= (int)$o['id_kamar']; ?>"
                                <?= $pv('id_kamar') == $o['id_kamar'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($o['nama_kamar']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Check In</label>
                        <input type="date" name="check_in" class="form-control" required
                               value="<?= htmlspecialchars($pv('check_in')); ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Check Out</label>
                        <input type="date" name="check_out" class="form-control" required
                               value="<?= htmlspecialchars($pv('check_out')); ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Layanan Tambahan <span class="text-muted fw-normal">(opsional)</span></label>
                    <?php if ($listLayanan): ?>
                        <div class="border rounded-3 p-2" style="max-height:150px; overflow-y:auto;">
                            <?php foreach ($listLayanan as $lay): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="id_layanan[]"
                                           id="lay<?= (int)$lay['id_layanan']; ?>" value="<?= (int)$lay['id_layanan']; ?>"
                                           <?= in_array((int)$lay['id_layanan'], $pilihLayananPost) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="lay<?= (int)$lay['id_layanan']; ?>">
                                        <?= htmlspecialchars($lay['nama']); ?>
                                        <span class="text-muted small">- Rp<?= number_format((float)$lay['harga'], 0, ',', '.'); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-text">Centang satu atau lebih. Total biaya layanan dihitung otomatis.</div>
                    <?php else: ?>
                        <div class="text-muted small">Belum ada layanan. Tambahkan dulu di menu Layanan.</div>
                    <?php endif; ?>
                </div>

                <div class="mb-1">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select" required>
                        <?php foreach ($daftarStatus as $st): ?>
                            <option value="<?= $st; ?>" <?= $pv('status') == $st ? 'selected' : ''; ?>><?= $st; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_reservasi" class="btn btn-tambah fw-semibold">
                    <i class="fas fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Filter pilihan hewan sesuai pelanggan yang dipilih
    function filterHewan() {
        var pel = document.getElementById('tPelanggan').value;
        var sel = document.getElementById('tHewan');

        Array.from(sel.options).forEach(function (o) {
            if (!o.value) return;
            var cocok = (o.dataset.pelanggan === '0') || (o.dataset.pelanggan === pel);
            o.hidden   = !cocok;
            o.disabled = !cocok;
        });

        if (sel.selectedOptions[0] && sel.selectedOptions[0].disabled) {
            sel.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('tPelanggan').addEventListener('change', filterHewan);
        filterHewan();

        <?php if ($modalBuka == 'tambah'): ?>
        new bootstrap.Modal(document.getElementById('modalTambah')).show();
        <?php endif; ?>
    });
</script>


<!-- ================= MODAL DETAIL ================= -->
<div class="modal fade" id="modalDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header card-header-blue" style="border-radius: 10px 10px 0 0;">
                <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Detail Reservasi <span id="dKode"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="width:40%">Tanggal Reservasi</td><td id="dTgl" class="fw-semibold"></td></tr>
                    <tr><td class="text-muted">Pelanggan</td><td id="dPelanggan" class="fw-semibold"></td></tr>
                    <tr><td class="text-muted">No. Telepon</td><td id="dTelp"></td></tr>
                    <tr><td class="text-muted">Hewan</td><td id="dHewan" class="fw-semibold"></td></tr>
                    <tr><td class="text-muted">Kamar</td><td id="dKamar"></td></tr>
                    <tr><td class="text-muted">Check In</td><td id="dCheckin"></td></tr>
                    <tr><td class="text-muted">Check Out</td><td id="dCheckout"></td></tr>
                    <tr><td class="text-muted">Durasi</td><td id="dLama"></td></tr>
                    <tr><td colspan="2"><hr class="my-2"></td></tr>
                    <tr><td class="text-muted">Biaya Kamar</td><td id="dSubKamar"></td></tr>
                    <tr><td class="text-muted">Biaya Layanan</td><td id="dLayanan"></td></tr>
                    <tr><td class="fw-bold">Total Biaya</td><td id="dTotal" class="fw-bold text-success"></td></tr>
                    <tr><td class="text-muted">Status</td><td id="dStatus" class="fw-semibold"></td></tr>
                </table>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-tambah px-3" data-bs-dismiss="modal">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ================= MODAL UBAH STATUS ================= -->
<div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header card-header-blue" style="border-radius: 10px 10px 0 0;">
                <h5 class="modal-title"><i class="fas fa-pen-to-square me-2"></i>Ubah Status <span id="sKode"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="id_reservasi" id="sId">
                <label class="form-label fw-semibold">Status Reservasi</label>
                <select name="status" id="sStatus" class="form-select" required>
                    <?php foreach ($daftarStatus as $st): ?>
                        <option value="<?= $st; ?>"><?= $st; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="ubah_status" class="btn btn-tambah fw-semibold">
                    <i class="fas fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>


<script>
    // Event delegation supaya tombol tetap jalan di semua halaman DataTables
    document.addEventListener('click', function (e) {

        // ----- DETAIL -----
        var btnDetail = e.target.closest('.btn-detail');
        if (btnDetail) {
            var d = JSON.parse(btnDetail.dataset.detail);
            document.getElementById('dKode').textContent      = d.kode;
            document.getElementById('dTgl').textContent       = d.tgl;
            document.getElementById('dPelanggan').textContent = d.pelanggan;
            document.getElementById('dTelp').textContent      = d.telp;
            document.getElementById('dHewan').textContent     = d.hewan;
            document.getElementById('dKamar').textContent     = d.kamar;
            document.getElementById('dCheckin').textContent   = d.checkin;
            document.getElementById('dCheckout').textContent  = d.checkout;
            document.getElementById('dLama').textContent      = d.lama;
            document.getElementById('dSubKamar').textContent  = d.subkamar;
            document.getElementById('dLayanan').textContent   = d.layanan;
            document.getElementById('dTotal').textContent     = d.total;
            document.getElementById('dStatus').textContent    = d.status;
        }

        // ----- UBAH STATUS -----
        var btnStatus = e.target.closest('.btn-status');
        if (btnStatus) {
            document.getElementById('sId').value          = btnStatus.dataset.id;
            document.getElementById('sKode').textContent  = btnStatus.dataset.kode;

            var pilih = document.getElementById('sStatus');
            pilih.value = btnStatus.dataset.status;
            if (pilih.value !== btnStatus.dataset.status) {
                pilih.selectedIndex = 0; // status lama tidak ada di daftar
            }
        }
    });
</script>

<?php include __DIR__ . '/../includes/penutup_halaman.php'; ?>