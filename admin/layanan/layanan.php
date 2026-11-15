<?php
include __DIR__ . "/../config/koneksi.php"; 

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
$modalBuka = ''; 

function bersihkanHarga($nilai) {
    return (int) preg_replace('/\D/', '', $nilai ?? '0');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['tambah_layanan'])) {

        $nama      = trim($_POST['nama_layanan'] ?? '');
        $harga     = bersihkanHarga($_POST['harga'] ?? '0');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($nama === '') {
            $error = 'Nama layanan wajib diisi.';
            $modalBuka = 'tambah';
        } elseif ($harga <= 0) {
            $error = 'Harga harus lebih dari 0.';
            $modalBuka = 'tambah';
        } else {
            if ($adaDeskripsi) {
                $stmt = mysqli_prepare($koneksi,
                    "INSERT INTO layanan ($kolomNama, harga, deskripsi) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "sis", $nama, $harga, $deskripsi);
            } else {
                $stmt = mysqli_prepare($koneksi,
                    "INSERT INTO layanan ($kolomNama, harga) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt, "si", $nama, $harga);
            }

            if ($stmt && mysqli_stmt_execute($stmt)) {
                header("Location: layanan.php?pesan=tambah_berhasil");
                exit();
            } else {
                $error = 'Gagal menyimpan layanan: ' . mysqli_error($koneksi);
                $modalBuka = 'tambah';
            }
        }
    }

    if (isset($_POST['edit_layanan'])) {

        $id        = (int) ($_POST['id_layanan'] ?? 0);
        $nama      = trim($_POST['nama_layanan'] ?? '');
        $harga     = bersihkanHarga($_POST['harga'] ?? '0');
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if ($id <= 0) {
            $error = 'Data layanan tidak valid.';
        } elseif ($nama === '') {
            $error = 'Nama layanan wajib diisi.';
            $modalBuka = 'edit';
        } elseif ($harga <= 0) {
            $error = 'Harga harus lebih dari 0.';
            $modalBuka = 'edit';
        } else {
            if ($adaDeskripsi) {
                $stmt = mysqli_prepare($koneksi,
                    "UPDATE layanan SET $kolomNama = ?, harga = ?, deskripsi = ? WHERE id_layanan = ?");
                mysqli_stmt_bind_param($stmt, "sisi", $nama, $harga, $deskripsi, $id);
            } else {
                $stmt = mysqli_prepare($koneksi,
                    "UPDATE layanan SET $kolomNama = ?, harga = ? WHERE id_layanan = ?");
                mysqli_stmt_bind_param($stmt, "sii", $nama, $harga, $id);
            }

            if ($stmt && mysqli_stmt_execute($stmt)) {
                header("Location: layanan.php?pesan=edit_berhasil");
                exit();
            } else {
                $error = 'Gagal memperbarui layanan: ' . mysqli_error($koneksi);
                $modalBuka = 'edit';
            }
        }
    }

    // ---------- HAPUS ----------
    if (isset($_POST['hapus_layanan'])) {

        $id = (int) ($_POST['id_layanan'] ?? 0);

        if ($id > 0) {
            $stmt = mysqli_prepare($koneksi, "DELETE FROM layanan WHERE id_layanan = ?");
            mysqli_stmt_bind_param($stmt, "i", $id);

            try {
                if (mysqli_stmt_execute($stmt)) {
                    header("Location: layanan.php?pesan=hapus_berhasil");
                    exit();
                }
                $error = 'Gagal menghapus layanan: ' . mysqli_error($koneksi);
            } catch (mysqli_sql_exception $e) {
                // Biasanya karena layanan masih dipakai di data reservasi (foreign key)
                $error = 'Layanan tidak bisa dihapus karena masih dipakai di data reservasi.';
            }
        }
    }
}

$queryLayanan = mysqli_query($koneksi, "SELECT * FROM layanan ORDER BY id_layanan DESC");
$totalLayanan = $queryLayanan ? mysqli_num_rows($queryLayanan) : 0;

$judulHalaman = 'Layanan';
$pakaiDataTables = true;
$menuAktif    = 'layanan.php';
include __DIR__ . '/includes/sidebar_header.php';
?>

<style>
    .card-header-blue {
        background-color: var(--sidebar-blue);
        color: #ffffff;
        font-weight: 600;
        border-top-left-radius: 10px !important;
        border-top-right-radius: 10px !important;
    }
    .card-custom {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    .table-sage-header {
        background-color: var(--header-sage) !important;
        color: var(--text-dark) !important;
    }
    .btn-biru {
        background-color: var(--sidebar-blue);
        border-color: var(--sidebar-blue);
        color: #fff;
    }
    .btn-biru:hover {
        background-color: var(--sidebar-active);
        border-color: var(--sidebar-active);
        color: #fff;
    }
</style>

<?php
$pesanNotif = [
    'tambah_berhasil' => 'Layanan baru berhasil ditambahkan.',
    'edit_berhasil'   => 'Layanan berhasil diperbarui.',
    'hapus_berhasil'  => 'Layanan berhasil dihapus.',
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

<div class="card card-custom overflow-hidden">
    <div class="card-header card-header-blue py-3 d-flex justify-content-between align-items-center">
        <span><i class="fas fa-scissors me-2"></i> Daftar Layanan (<?= $totalLayanan; ?>)</span>
        <button type="button" class="btn btn-tambah btn-sm fw-semibold"
                data-bs-toggle="modal" data-bs-target="#modalTambahLayanan">
            <i class="fas fa-plus me-1"></i> Tambah Layanan
        </button>
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
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($row[$kolomNama] ?? '-'); ?>
                                </td>
                                <?php if ($adaDeskripsi): ?>
                                    <td class="text-muted">
                                        <?= htmlspecialchars($row['deskripsi'] ?? '-'); ?>
                                    </td>
                                <?php endif; ?>
                                <td class="fw-bold text-success">
                                    Rp<?= number_format((float)($row['harga'] ?? 0), 0, ',', '.'); ?>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-warning text-white rounded-2 me-1 btn-edit" title="Edit"
                                            data-id="<?= (int)$row['id_layanan']; ?>"
                                            data-nama="<?= htmlspecialchars($row[$kolomNama] ?? '', ENT_QUOTES); ?>"
                                            data-harga="<?= (int)($row['harga'] ?? 0); ?>"
                                            data-deskripsi="<?= htmlspecialchars($row['deskripsi'] ?? '', ENT_QUOTES); ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalEditLayanan">
                                        <i class="fas fa-pen-to-square"></i>
                                    </button>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin ingin menghapus layanan &quot;<?= htmlspecialchars(addslashes($row[$kolomNama] ?? ''), ENT_QUOTES); ?>&quot;?');">
                                        <input type="hidden" name="id_layanan" value="<?= (int)$row['id_layanan']; ?>">
                                        <button type="submit" name="hapus_layanan"
                                                class="btn btn-sm btn-danger text-white rounded-2" title="Hapus">
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


<div class="modal fade" id="modalTambahLayanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">

            <div class="modal-header card-header-blue" style="border-radius: 10px 10px 0 0;">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Tambah Layanan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Layanan</label>
                    <input type="text" name="nama_layanan" class="form-control"
                           placeholder="Contoh: Grooming" required
                           value="<?= $modalBuka == 'tambah' ? htmlspecialchars($_POST['nama_layanan'] ?? '') : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Harga (Rp)</label>
                    <input type="text" name="harga" class="form-control"
                           placeholder="Contoh: 50000" inputmode="numeric" required
                           value="<?= $modalBuka == 'tambah' ? htmlspecialchars($_POST['harga'] ?? '') : ''; ?>">
                </div>

                <?php if ($adaDeskripsi): ?>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="deskripsi" class="form-control" rows="3"
                                  placeholder="Penjelasan singkat layanan"><?= $modalBuka == 'tambah' ? htmlspecialchars($_POST['deskripsi'] ?? '') : ''; ?></textarea>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="tambah_layanan" class="btn btn-biru fw-semibold">
                    <i class="fas fa-floppy-disk me-1"></i> Simpan
                </button>
            </div>

        </form>
    </div>
</div>


<div class="modal fade" id="modalEditLayanan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">

            <div class="modal-header card-header-blue" style="border-radius: 10px 10px 0 0;">
                <h5 class="modal-title"><i class="fas fa-pen me-2"></i>Edit Layanan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <input type="hidden" name="id_layanan" id="editId"
                       value="<?= $modalBuka == 'edit' ? (int)($_POST['id_layanan'] ?? 0) : ''; ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Layanan</label>
                    <input type="text" name="nama_layanan" id="editNama" class="form-control" required
                           value="<?= $modalBuka == 'edit' ? htmlspecialchars($_POST['nama_layanan'] ?? '') : ''; ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Harga (Rp)</label>
                    <input type="text" name="harga" id="editHarga" class="form-control"
                           inputmode="numeric" required
                           value="<?= $modalBuka == 'edit' ? htmlspecialchars($_POST['harga'] ?? '') : ''; ?>">
                </div>

                <?php if ($adaDeskripsi): ?>
                    <div class="mb-1">
                        <label class="form-label fw-semibold">Deskripsi <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="deskripsi" id="editDeskripsi" class="form-control" rows="3"><?= $modalBuka == 'edit' ? htmlspecialchars($_POST['deskripsi'] ?? '') : ''; ?></textarea>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modal-footer border-0 pt-0 px-4 pb-4">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="edit_layanan" class="btn btn-biru fw-semibold">
                    <i class="fas fa-floppy-disk me-1"></i> Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-edit');
            if (!btn) return;

            document.getElementById('editId').value    = btn.dataset.id;
            document.getElementById('editNama').value  = btn.dataset.nama;
            document.getElementById('editHarga').value = btn.dataset.harga;

            var desk = document.getElementById('editDeskripsi');
            if (desk) desk.value = btn.dataset.deskripsi;
        });

        <?php if ($modalBuka == 'tambah'): ?>
        new bootstrap.Modal(document.getElementById('modalTambahLayanan')).show();
        <?php elseif ($modalBuka == 'edit'): ?>
        new bootstrap.Modal(document.getElementById('modalEditLayanan')).show();
        <?php endif; ?>

    });
</script>

<?php include __DIR__ . '/includes/penutup_halaman.php'; ?>