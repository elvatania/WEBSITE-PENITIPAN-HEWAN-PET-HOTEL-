<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_pelanggan'])) {
    header("Location: login.php");
    exit();
}

$id_pelanggan = (int) $_SESSION['id_pelanggan'];

$query_pemilik = mysqli_query($koneksi, "
    SELECT * FROM pelanggan
    WHERE id_pelanggan = '$id_pelanggan'");

$data_pemilik = mysqli_fetch_assoc($query_pemilik);
$q_layanan = mysqli_query($koneksi, "SELECT * FROM layanan");
$list_layanan = [];
while ($l = mysqli_fetch_assoc($q_layanan)) {
    $list_layanan[] = $l;
}

// Daftar hewan yang pernah dititipkan oleh pelanggan ini (untuk autocomplete)
$q_hewan = mysqli_query($koneksi, "
    SELECT nama_hewan, jenis_hewan, ras, jenis_kelamin, usia_hewan, berat_hewan, kondisi
    FROM hewan
    WHERE id_pelanggan = '$id_pelanggan'
    AND id_hewan IN (
        SELECT MAX(id_hewan) FROM hewan
        WHERE id_pelanggan = '$id_pelanggan'
        GROUP BY nama_hewan
    )
    ORDER BY nama_hewan
");
$list_hewan = [];
if ($q_hewan) {
    while ($h = mysqli_fetch_assoc($q_hewan)) {
        $h['usia_hewan']  = ((int) $h['usia_hewan'] > 0) ? ((int) $h['usia_hewan']) . ' tahun' : '';
        $h['berat_hewan'] = ((float) $h['berat_hewan'] > 0) ? ((float) $h['berat_hewan']) . ' kg' : '';
        $list_hewan[] = $h;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Form Reservasi Penitipan Hewan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="bg-light min-vh-100 d-flex align-items-center py-5">

  <div class="container">
    <div class="card shadow-lg border-0 rounded-4 p-4 p-md-5 bg-white mx-auto" style="max-width: 950px;">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-4 pb-3 border-bottom">
        <h1 class="h3 fw-bold text-dark mb-2 mb-sm-0">Form Reservasi Penitipan Hewan</h1>
        <div class="d-flex gap-3 fw-semibold text-secondary small">
          <a href="../landingpage.php" class="text-decoration-none text-dark">Beranda</a>
          <a href="index.php" class="text-decoration-none text-primary active">Reservasi</a>
        </div>
      </div>

      <form action="proses_reservasi.php" method="POST" id="reservationForm" class="needs-validation" novalidate>

        <!-- ================= DATA PEMILIK ================= -->
        <div class="mb-4">
          <div class="bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-3 fw-semibold small d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-person-fill"></i> Data Pemilik
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Nama Lengkap</label>
              <input type="text" name="nama_pelanggan"
                value="<?= htmlspecialchars($data_pemilik['nama_pelanggan'] ?? '') ?>"
                required class="form-control form-control-sm bg-white">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Email</label>
              <input type="email" name="email"
                value="<?= htmlspecialchars($data_pemilik['email'] ?? '') ?>"
                readonly required class="form-control form-control-sm bg-light">
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">No. Telepon</label>
              <input type="tel" name="no_telepon" placeholder="08xxxxxxxxxx" maxlength="13"
                value="<?= htmlspecialchars($data_pemilik['no_telp'] ?? '') ?>"
                inputmode="numeric" required class="form-control form-control-sm bg-white">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-bold text-muted">Alamat Lengkap</label>
              <input type="text" name="alamat"
                value="<?= htmlspecialchars($data_pemilik['alamat'] ?? '') ?>"
                placeholder="Contoh: Jl. Mawar No. 09, Malang" required class="form-control form-control-sm bg-white">
            </div>
          </div>
        </div>

        <!-- ================= DATA HEWAN ================= -->
        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-3 fw-semibold small d-flex align-items-center gap-2 m-0">
              <i class="bi bi-heart-fill"></i> Data Hewan &amp; Layanan Masing-masing
            </div>
            <div class="d-flex gap-2">
              <!-- Dropdown hewan yang pernah dititipkan -->
              <div class="dropdown">
                <button class="btn btn-primary btn-sm d-flex align-items-center gap-1 dropdown-toggle" type="button"
                  data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="bi bi-clock-history"></i> Pilih Hewan Saya
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                  <?php if (count($list_hewan) > 0) { ?>
                    <?php foreach ($list_hewan as $idx => $hw) { ?>
                      <li>
                        <button type="button" class="dropdown-item small btn-pilih-hewan" data-index="<?= $idx; ?>">
                          <?= htmlspecialchars($hw['nama_hewan']); ?>
                          <span class="text-muted">(<?= htmlspecialchars($hw['jenis_hewan']); ?>)</span>
                        </button>
                      </li>
                    <?php } ?>
                  <?php } else { ?>
                    <li><span class="dropdown-item-text small text-muted">Belum ada riwayat hewan</span></li>
                  <?php } ?>
                </ul>
              </div>

              <button type="button" id="btnTambahHewan" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-plus-lg"></i> Tambah Hewan Lain
              </button>
            </div>
          </div>

          <!-- Daftar saran nama hewan (diisi lewat JavaScript) -->
          <datalist id="daftarHewan"></datalist>



          <div id="container-hewan" class="vstack gap-3">
            <div class="hewan-item p-3 border rounded-3 position-relative bg-light bg-opacity-50">
              <div class="row g-3 mb-3">
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Nama Hewan</label>
                  <input type="text" name="nama_hewan[]" placeholder="Ciko" required
                    list="daftarHewan" autocomplete="off"
                    class="form-control form-control-sm input-nama-hewan">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Jenis Hewan</label>
                  <select name="jenis_hewan[]" class="form-select form-select-sm">
                    <option>Kucing</option>
                    <option>Anjing</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Ras</label>
                  <input type="text" name="Ras[]" placeholder="Persia" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-bold">Jenis Kelamin</label>
                  <select name="jenis_kelamin[]" class="form-select form-select-sm">
                    <option>Jantan</option>
                    <option>Betina</option>
                  </select>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Usia</label>
                  <input type="text" name="usia[]" placeholder="2 tahun" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Berat</label>
                  <input type="text" name="berat[]" placeholder="4 kg" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-bold">Kondisi</label>
                  <input type="text" name="kondisi[]" placeholder="sehat/sakit" class="form-control form-control-sm">
                </div>
              </div>

              <div class="pt-3 border-top">
                <label class="form-label small fw-bold text-primary mb-2">
                  <i class="bi bi-gear-fill"></i> Layanan Tambahan untuk Hewan Ini
                </label>
                <div class="row g-2">
                  <?php foreach ($list_layanan as $l) {
                      $id_lay = $l['id_layanan'];
                      $nama_lay = htmlspecialchars($l['jenis_layanan']);
                      $harga_lay = $l['harga'];
                      $format_harga = number_format($harga_lay, 0, ',', '.');
                  ?>
                      <div class="col-md-4">
                        <div class="form-check">
                          <input class="form-check-input addon-check" type="checkbox" name="layanan[0][]" value="<?= $id_lay; ?>" data-harga="<?= $harga_lay; ?>">
                          <label class="form-check-label small">
                            <?= $nama_lay; ?> (Rp<?= $format_harga; ?>)
                          </label>
                        </div>
                      </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="mb-4">
          <div class="bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-3 fw-semibold small d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-calendar-event-fill"></i> Data Penitipan &amp; Tipe Kamar
          </div>
          <div class="row g-4">
            <div class="col-md-12">
              <div class="row g-3 mb-3">
                <div class="col-sm-6">
                  <label class="form-label small fw-bold">Tanggal Check-in</label>
                  <input type="date" id="checkin" name="checkin" value="<?= date('Y-m-d'); ?>" min="<?= date('Y-m-d'); ?>" class="form-control form-control-sm" required>
                </div>
                <div class="col-sm-6">
                  <label class="form-label small fw-bold">Tanggal Check-out</label>
                  <input type="date" id="checkout" name="checkout" value="<?= date('Y-m-d', strtotime('+1 day')); ?>" class="form-control form-control-sm" required>
                </div>
              </div>
              <div>
                <label class="form-label small fw-bold">Tipe Kamar</label>
                <select id="kamar" name="id_kamar" class="form-select form-select-sm" required>
                  <option value="" disabled selected>-- Pilih Tipe Kamar --</option>
                  <option value="1" data-harga="35000">Standard (Rp35.000/hari)</option>
                  <option value="2" data-harga="50000">VIP (Rp50.000/hari)</option>
                  <option value="3" data-harga="75000">VVIP (Rp75.000/hari)</option>
                </select>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <label class="form-label small fw-bold">Catatan Khusus</label>
            <textarea name="catatan" rows="2" placeholder="Contoh: makanan khusus, obat, dll..." class="form-control form-control-sm"></textarea>
          </div>
        </div>
        <div class="mb-4">
          <div class="bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-3 fw-semibold small d-flex align-items-center gap-2 mb-3">
            <label class="form-label small fw-bold mb-0">Rincian Perhitungan</label>
          </div>
          <div class="border rounded-3 p-3 bg-light">
            <div class="row text-center g-3">
              <div class="col-6 col-md-3">
                <span class="d-block text-muted small fw-bold">Lama Hari</span>
                <span id="txtLamaHari" class="h6 fw-bold text-dark">0 hari</span>
              </div>
              <div class="col-6 col-md-3">
                <span class="d-block text-muted small fw-bold">Total Kamar</span>
                <span id="txtTotalKamar" class="h6 fw-bold text-dark">Rp0</span>
              </div>
              <div class="col-6 col-md-3">
                <span class="d-block text-muted small fw-bold">Total Layanan</span>
                <span id="txtTotalLayanan" class="h6 fw-bold text-dark">Rp0</span>
              </div>
              <div class="col-6 col-md-3">
                <span class="d-block text-muted small fw-bold">Total Harga</span>
                <span id="txtTotalHarga" class="h6 fw-bold text-success">Rp0</span>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" id="btnSubmit" class="btn btn-success px-4 py-2 fw-semibold d-flex align-items-center gap-2">
            <i class="bi bi-save"></i> Simpan Reservasi
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/script.js?v=8.0"></script>
  <script>
document.addEventListener('DOMContentLoaded', function() {
    const checkinInput   = document.querySelector('input[name="checkin"]');
    const checkoutInput  = document.querySelector('input[name="checkout"]');
    const selectKamar    = document.querySelector('select[name="id_kamar"]');
    const btnTambahHewan = document.getElementById('btnTambahHewan');
    const containerHewan = document.getElementById('container-hewan');
    function aturNamaLayanan() {
        const semuaHewan = containerHewan.querySelectorAll('.hewan-item');
        semuaHewan.forEach(function(item, index) {
            item.querySelectorAll('.addon-check').forEach(function(checkbox) {
                checkbox.name = 'layanan[' + index + '][]';
            });
        });
    }
    function ambilDataStok() {
        if (!checkinInput || !checkoutInput || !selectKamar) return;

        const checkin  = checkinInput.value;
        const checkout = checkoutInput.value;

        if (checkin && checkout) {
            fetch(`stok.php?checkin=${checkin}&checkout=${checkout}`)
                .then(response => response.json())
                .then(data => {
                    selectKamar.innerHTML = '<option value="" disabled selected>-- Pilih Tipe Kamar --</option>';
                    data.forEach(kamar => {
                        const option = document.createElement('option');
                        option.value = kamar.id_kamar;
                        option.setAttribute('data-harga', kamar.harga);
                        const formatHarga = new Intl.NumberFormat('id-ID').format(kamar.harga);

                        if (kamar.sisa > 0) {
                            option.text = `${kamar.nama_kamar} | ${kamar.sisa} Kamar Tersedia | Rp${formatHarga}/hari`;
                        } else {
                            option.text = `${kamar.nama_kamar} | 0 Kamar Tersedia (Penuh) | Rp${formatHarga}/hari`;
                            option.disabled = true;
                            option.style.color = 'red';
                        }
                        selectKamar.appendChild(option);
                    });
                })
                .catch(error => console.error('Gagal memuat stok kamar:', error));
        }
    }

    if (checkinInput && checkoutInput) {
        checkinInput.addEventListener('change', ambilDataStok);
        checkoutInput.addEventListener('change', ambilDataStok);
        ambilDataStok();
    }
    function hitungTotalSemua() {
        let lamaHari = 1;
        if (checkinInput && checkoutInput && checkinInput.value && checkoutInput.value) {
            const d1 = new Date(checkinInput.value);
            const d2 = new Date(checkoutInput.value);
            const diffDays = Math.ceil((d2 - d1) / (1000 * 60 * 60 * 24));
            lamaHari = diffDays > 0 ? diffDays : 1;
        }
        document.getElementById('txtLamaHari').innerText = lamaHari + ' hari';

        let hargaKamarPerHari = 0;
        if (selectKamar && selectKamar.selectedIndex > 0) {
            const selectedOpt = selectKamar.options[selectKamar.selectedIndex];
            hargaKamarPerHari = parseInt(selectedOpt.getAttribute('data-harga')) || 0;
        }

        const jumlahHewan = containerHewan.querySelectorAll('.hewan-item').length;
        const totalKamar = hargaKamarPerHari * lamaHari * jumlahHewan;
        document.getElementById('txtTotalKamar').innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(totalKamar);

        let totalLayananSemua = 0;
        document.querySelectorAll('input.addon-check:checked').forEach(cb => {
            totalLayananSemua += parseInt(cb.getAttribute('data-harga')) || 0;
        });
        document.getElementById('txtTotalLayanan').innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(totalLayananSemua);

        const grandTotal = totalKamar + totalLayananSemua;
        document.getElementById('txtTotalHarga').innerText = 'Rp' + new Intl.NumberFormat('id-ID').format(grandTotal);
    }

    if (selectKamar)   selectKamar.addEventListener('change', hitungTotalSemua);
    if (checkinInput)  checkinInput.addEventListener('change', hitungTotalSemua);
    if (checkoutInput) checkoutInput.addEventListener('change', hitungTotalSemua);
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('addon-check')) {
            hitungTotalSemua();
        }
    });
    if (btnTambahHewan && containerHewan) {
        btnTambahHewan.addEventListener('click', function() {
            const jumlahHewan = containerHewan.querySelectorAll('.hewan-item').length;

            if (jumlahHewan >= 3) {
                alert('Maksimal hanya 3 hewan dalam satu reservasi!');
                return;
            }

            const itemPertama = containerHewan.querySelector('.hewan-item');
            if (itemPertama) {
                const clone = itemPertama.cloneNode(true);
                clone.querySelectorAll('input[type="text"], input[type="number"]').forEach(input => {
                    input.value = '';
                });
                clone.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                const divHapus = document.createElement('div');
                divHapus.className = 'text-end mt-2';
                divHapus.innerHTML = `
                    <button type="button" class="btn btn-danger btn-sm btn-hapus-hewan">
                        <i class="bi bi-trash"></i> Hapus Hewan Ini
                    </button>
                `;
                clone.appendChild(divHapus);

                divHapus.querySelector('.btn-hapus-hewan').addEventListener('click', function() {
                    clone.remove();
                    aturNamaLayanan();
                    hitungTotalSemua();
                });

                containerHewan.appendChild(clone);
                aturNamaLayanan();
                hitungTotalSemua();
            }
        });
    }
    const hewanSebelumnya = <?= json_encode($list_hewan) ?>;
    document.getElementById('daftarHewan').innerHTML =
        hewanSebelumnya.map(h => `<option value="${String(h.nama_hewan).replace(/"/g, '&quot;')}">`).join('');

    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-pilih-hewan');
        if (!btn) return;

        const h = hewanSebelumnya[btn.dataset.index];
        if (!h) return;

        let cards = containerHewan.querySelectorAll('.hewan-item');
        let card = Array.from(cards).find(c => !c.querySelector('[name="nama_hewan[]"]').value.trim());

        if (!card) {
            if (cards.length >= 3) {
                alert('Maksimal hanya 3 hewan dalam satu reservasi!');
                return;
            }
            btnTambahHewan.click();
            cards = containerHewan.querySelectorAll('.hewan-item');
            card = cards[cards.length - 1];
        }

        card.querySelector('[name="nama_hewan[]"]').value    = h.nama_hewan;
        card.querySelector('[name="jenis_hewan[]"]').value   = h.jenis_hewan;
        card.querySelector('[name="Ras[]"]').value           = h.ras ?? '';
        card.querySelector('[name="jenis_kelamin[]"]').value = h.jenis_kelamin;
        card.querySelector('[name="usia[]"]').value          = h.usia_hewan ?? '';
        card.querySelector('[name="berat[]"]').value         = h.berat_hewan ?? '';
        card.querySelector('[name="kondisi[]"]').value       = h.kondisi ?? '';
    });

    document.addEventListener('change', function(e) {
        if (!e.target.classList.contains('input-nama-hewan')) return;

        const h = hewanSebelumnya.find(
            x => x.nama_hewan.toLowerCase() === e.target.value.trim().toLowerCase()
        );
        if (!h) return;

        const card = e.target.closest('.hewan-item');
        card.querySelector('[name="jenis_hewan[]"]').value   = h.jenis_hewan;
        card.querySelector('[name="Ras[]"]').value           = h.ras ?? '';
        card.querySelector('[name="jenis_kelamin[]"]').value = h.jenis_kelamin;
        card.querySelector('[name="usia[]"]').value          = h.usia_hewan ?? '';
        card.querySelector('[name="berat[]"]').value         = h.berat_hewan ?? '';
        card.querySelector('[name="kondisi[]"]').value       = h.kondisi ?? '';
    });
});
</script>

</body>
</html>