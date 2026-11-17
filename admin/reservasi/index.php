<?php
session_start();
include __DIR__ . "/../../config/koneksi.php";

$error = '';

/* ================= TAMBAH RESERVASI ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_reservasi'])) {

    $id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
    $id_hewan     = (int) ($_POST['id_hewan'] ?? 0);
    $id_kamar     = (int) ($_POST['id_kamar'] ?? 0);
    $check_in     = $_POST['check_in'] ?? '';
    $check_out    = $_POST['check_out'] ?? '';

    if (
        $id_pelanggan <= 0 ||
        $id_hewan <= 0 ||
        $id_kamar <= 0 ||
        $check_in === '' ||
        $check_out === ''
    ) {
        $error = "Semua data wajib diisi.";
    } elseif ($check_out <= $check_in) {
        $error = "Tanggal check out harus setelah check in.";
    } else {
        $status = "Proses";

        $stmt = mysqli_prepare($koneksi, "
            INSERT INTO reservasi
            (id_pelanggan, id_hewan, id_kamar, check_in, check_out, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {
            mysqli_stmt_bind_param(
                $stmt,
                "iiisss",
                $id_pelanggan,
                $id_hewan,
                $id_kamar,
                $check_in,
                $check_out,
                $status
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: reservasi.php?pesan=berhasil");
                exit();
            } else {
                $error = "Gagal menambahkan reservasi: " . mysqli_stmt_error($stmt);
            }
        } else {
            $error = "Query gagal: " . mysqli_error($koneksi);
        }
    }
}

/* ================= DATA PILIHAN FORM ================= */
$dataPelanggan = mysqli_query($koneksi, "
    SELECT id_pelanggan, nama_pelanggan
    FROM pelanggan ORDER BY nama_pelanggan ASC
");

$dataHewan = mysqli_query($koneksi, "
    SELECT id_hewan, nama_hewan
    FROM hewan ORDER BY nama_hewan ASC
");

$dataKamar = mysqli_query($koneksi, "
    SELECT id_kamar, jenis_kamar
    FROM kamar ORDER BY id_kamar ASC
");

/* ================= DATA RESERVASI ================= */
$queryReservasi = mysqli_query($koneksi, "
    SELECT
        reservasi.id_reservasi,
        COALESCE(pelanggan.nama_pelanggan, '-') AS nama_pelanggan,
        COALESCE(hewan.nama_hewan, '-') AS nama_hewan,
        COALESCE(hewan.jenis_hewan, '-') AS jenis_hewan,
        COALESCE(kamar.jenis_kamar, 'Standard') AS jenis_kamar,
        reservasi.check_in,
        reservasi.check_out,
        reservasi.status
    FROM reservasi
    LEFT JOIN pelanggan
        ON reservasi.id_pelanggan = pelanggan.id_pelanggan
    LEFT JOIN hewan
        ON reservasi.id_hewan = hewan.id_hewan
    LEFT JOIN kamar
        ON reservasi.id_kamar = kamar.id_kamar
    ORDER BY reservasi.id_reservasi DESC
");

$totalReservasi = $queryReservasi
    ? mysqli_num_rows($queryReservasi)
    : 0;

$judulHalaman = 'Reservasi';
$menuAktif = 'reservasi.php';
$pakaiDataTables = true;

include '../../includes/sidebar_header.php';
?>

<?php if (isset($_GET['pesan']) && $_GET['pesan'] === 'berhasil'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Reservasi berhasil ditambahkan.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- TABEL RESERVASI -->
<div class="card card-custom overflow-hidden">

    <div class="card-header card-header-blue py-3 d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-calendar-check me-2"></i>
            Daftar Reservasi (<?= $totalReservasi; ?>)
        </span>

        <button class="btn btn-light btn-sm fw-semibold"
                data-bs-toggle="modal"
                data-bs-target="#modalTambahReservasi">
            <i class="fas fa-plus me-1"></i>
            Tambah Reservasi
        </button>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle tabel-datatable">
                <thead>
                    <tr class="table-sage-header">
                        <th>Booking Code</th>
                        <th>Pelanggan</th>
                        <th>Hewan</th>
                        <th>Kamar</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($queryReservasi && $totalReservasi > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($queryReservasi)): ?>
                        <tr>
                            <td class="fw-bold" style="color:var(--sidebar-blue);">
                                RSV<?= str_pad($row['id_reservasi'], 3, '0', STR_PAD_LEFT); ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['nama_pelanggan']); ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['nama_hewan']); ?>
                                <br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($row['jenis_hewan']); ?>
                                </small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['jenis_kamar']); ?>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($row['check_in'])); ?>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($row['check_out'])); ?>
                            </td>
                            <td class="text-center">
                                <?php if ($row['status'] === 'Selesai'): ?>
                                    <span class="badge bg-success">Selesai</span>
                                <?php elseif ($row['status'] === 'Proses'): ?>
                                    <span class="badge bg-warning text-dark">Proses</span>
                                <?php elseif ($row['status'] === 'Dibatalkan'): ?>
                                    <span class="badge bg-danger">Dibatalkan</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($row['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
    <tr>
        <td colspan="7" class="text-center py-4 text-muted">
            Belum ada data reservasi.
        </td>
    </tr>
<?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH RESERVASI -->
<div class="modal fade" id="modalTambahReservasi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">

            <div class="modal-header card-header-blue">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Tambah Reservasi
                </h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Pelanggan</label>
                    <select name="id_pelanggan" class="form-select" required>
                        <option value="">-- Pilih Pelanggan --</option>
                        <?php while ($p = mysqli_fetch_assoc($dataPelanggan)): ?>
                            <option value="<?= $p['id_pelanggan']; ?>">
                                <?= htmlspecialchars($p['nama_pelanggan']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Hewan</label>
                    <select name="id_hewan" class="form-select" required>
                        <option value="">-- Pilih Hewan --</option>
                        <?php while ($h = mysqli_fetch_assoc($dataHewan)): ?>
                            <option value="<?= $h['id_hewan']; ?>">
                                <?= htmlspecialchars($h['nama_hewan']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Kamar</label>
                    <select name="id_kamar" class="form-select" required>
                        <option value="">-- Pilih Kamar --</option>
                        <?php while ($k = mysqli_fetch_assoc($dataKamar)): ?>
                            <option value="<?= $k['id_kamar']; ?>">
                                <?= htmlspecialchars($k['jenis_kamar']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Check In</label>
                    <input type="date" name="check_in" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Check Out</label>
                    <input type="date" name="check_out" class="form-control" required>
                </div>

            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Batal
                </button>

                <button type="submit" name="tambah_reservasi"
                        class="btn btn-tambah fw-semibold">
                    <i class="fas fa-save me-1"></i>
                    Simpan Reservasi
                </button>
            </div>

        </form>
    </div>
</div>

<?php include '../../includes/penutup_halaman.php'; ?>