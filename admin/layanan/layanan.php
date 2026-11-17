<?php
session_start();
include '../../config/koneksi.php';

$adaDeskripsi = false;
$cekKolom = mysqli_query($koneksi, "SHOW COLUMNS FROM layanan LIKE 'deskripsi'");
if ($cekKolom && mysqli_num_rows($cekKolom) > 0) {
    $adaDeskripsi = true;
}

$kolomNama = 'nama_layanan';
$daftarKolom = [];
$qKolom = mysqli_query($koneksi, "SHOW COLUMNS FROM layanan");
while ($qKolom && $k = mysqli_fetch_assoc($qKolom)) {
    $daftarKolom[] = $k['Field'];
}
foreach (['nama_layanan', 'jenis_layanan', 'layanan', 'nama'] as $kandidat) {
    if (in_array($kandidat, $daftarKolom)) {
        $kolomNama = $kandidat;
        break;
    }
}

$error = '';

// Proses Hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id = (int) ($_POST['id_layanan'] ?? 0);

    if ($id > 0) {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM layanan WHERE id_layanan = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);

        try {
            if (mysqli_stmt_execute($stmt)) {
                header("Location: layanan.php?pesan=sukses");
                exit();
            }
            $error = 'Gagal menghapus data: ' . mysqli_error($koneksi);
        } catch (mysqli_sql_exception $e) {
            $error = 'Data tidak bisa dihapus karena masih dipakai di tabel reservasi.';
        }
    }
}

$queryLayanan = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY id_layanan DESC");
$totalLayanan = $queryLayanan ? mysqli_num_rows($queryLayanan) : 0;

$judulHalaman = 'Layanan';
$pakaiDataTables = true;
$menuAktif    = 'layanan.php';
include '../../includes/sidebar_header.php';
?>

<?php if (isset($_GET['pesan'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-circle-check me-2"></i>Aksi berhasil dilakukan.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error != ''): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card card-custom overflow-hidden">
    <div class="card-header card-header-blue py-3 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-scissors me-2"></i> Layanan (<?= $totalLayanan; ?>)</span>
        <a href="tambah_layanan.php" class="btn btn-tambah btn-sm fw-semibold text-white text-decoration-none">
            <i class="fas fa-plus me-1"></i> Tambah
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 tabel-datatable">
                <thead>
                    <tr class="table-sage-header">
                        <th class="ps-3 py-3">ID</th>
                        <th class="py-3">Nama Layanan</th>
                        <?php if ($adaDeskripsi): ?>
                            <th class="py-3">Deskripsi</th>
                        <?php endif; ?>
                        <th class="py-3">Harga</th>
                        <th class="py-3 text-center no-sort" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($queryLayanan && $totalLayanan > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($queryLayanan)): ?>
                            <tr>
                                <td class="ps-3 fw-bold" style="color: var(--sidebar-blue);">LYN<?= str_pad((int)$row['id_layanan'], 2, '0', STR_PAD_LEFT); ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row[$kolomNama] ?? '-'); ?></td>
                                <?php if ($adaDeskripsi): ?>
                                    <td class="text-muted"><?= htmlspecialchars($row['deskripsi'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td class="fw-bold text-success">Rp<?= number_format((float)($row['harga'] ?? 0), 0, ',', '.'); ?></td>
                                <td class="text-center">
                                    <a href="edit_layanan.php?id=<?= (int)$row['id_layanan']; ?>" class="btn btn-sm btn-warning text-white rounded-2 me-1" title="Edit">
                                        <i class="fas fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                        <input type="hidden" name="id_layanan" value="<?= (int)$row['id_layanan']; ?>">
                                        <button type="submit" name="hapus" class="btn btn-sm btn-danger text-white rounded-2" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/penutup_halaman.php'; ?>