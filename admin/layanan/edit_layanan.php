<?php
session_start();
include '../../config/koneksi.php'; 

$adaDeskripsi = false;
$cekKolom = mysqli_query($koneksi, "SHOW COLUMNS FROM layanan LIKE 'deskripsi'");
if ($cekKolom && mysqli_num_rows($cekKolom) > 0) {
    $adaDeskripsi = true;
}

$kolomNama = 'nama_layanan';
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
$id = (int)($_GET['id'] ?? $_POST['id_layanan'] ?? 0);

if ($id <= 0) {
    header("Location: layanan.php");
    exit();
}

function bersihkanHarga($nilai) {
    return (int) preg_replace('/\D/', '', $nilai ?? '0');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nama      = trim($_POST['nama_layanan'] ?? '');
    $harga     = bersihkanHarga($_POST['harga'] ?? '0');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') {
        $error = 'Nama wajib diisi.';
    } elseif ($harga <= 0) {
        $error = 'Harga harus lebih dari 0.';
    } else {
        if ($adaDeskripsi) {
            $stmt = mysqli_prepare($koneksi, "UPDATE layanan SET $kolomNama = ?, harga = ?, deskripsi = ? WHERE id_layanan = ?");
            mysqli_stmt_bind_param($stmt, "sisi", $nama, $harga, $deskripsi, $id);
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE layanan SET $kolomNama = ?, harga = ? WHERE id_layanan = ?");
            mysqli_stmt_bind_param($stmt, "sii", $nama, $harga, $id);
        }

        if ($stmt && mysqli_stmt_execute($stmt)) {
            header("Location: layanan.php?pesan=sukses");
            exit();
        } else {
            $error = 'Gagal memperbarui: ' . mysqli_error($koneksi);
        }
    }
}

$stmtCek = mysqli_prepare($koneksi, "SELECT * FROM layanan WHERE id_layanan = ?");
mysqli_stmt_bind_param($stmtCek, "i", $id);
mysqli_stmt_execute($stmtCek);
$resultCek = mysqli_stmt_get_result($stmtCek);
$dataLayanan = mysqli_fetch_assoc($resultCek);

if (!$dataLayanan) {
    header("Location: layanan.php");
    exit();
}

$judulHalaman = 'Edit';
$menuAktif    = 'layanan.php';
include '../../includes/sidebar_header.php';
?>

<?php if ($error != ''): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card card-custom border-0 shadow-sm mb-4">
    <div class="card-header card-header-blue py-3">
        <h5 class="mb-0 text-white"><i class="fas fa-pen-to-square me-2"></i> Edit</h5>
    </div>
    <form method="POST" class="card-body p-4">
        <input type="hidden" name="id_layanan" value="<?= $id; ?>">

        <div class="mb-3">
            <label class="form-label fw-semibold">Nama Layanan</label>
            <input type="text" name="nama_layanan" class="form-control" required value="<?= htmlspecialchars($_POST['nama_layanan'] ?? $dataLayanan[$kolomNama] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Harga (Rp)</label>
            <input type="text" name="harga" class="form-control" inputmode="numeric" required value="<?= htmlspecialchars($_POST['harga'] ?? $dataLayanan['harga'] ?? ''); ?>">
        </div>

        <?php if ($adaDeskripsi): ?>
            <div class="mb-3">
                <label class="form-label fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($_POST['deskripsi'] ?? $dataLayanan['deskripsi'] ?? ''); ?></textarea>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 pt-2">
            <button type="submit" name="update" class="btn btn-biru fw-semibold text-white px-4" style="background-color: var(--sidebar-blue);">
                <i class="fas fa-floppy-disk me-1"></i> Simpan
            </button>
            <a href="layanan.php" class="btn btn-secondary px-4">
                <i class="fas fa-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </form>
</div>

<?php include '../../includes/penutup_halaman.php'; ?>