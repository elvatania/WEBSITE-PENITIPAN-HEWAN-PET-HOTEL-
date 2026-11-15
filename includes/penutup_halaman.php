<!-- ===== ISI HALAMAN SELESAI ===== -->

        </div><!-- /#mainContent -->
    </div><!-- /.row -->
</div><!-- /.container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (!empty($pakaiDataTables)): ?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    // Semua <table class="tabel-datatable"> otomatis jadi DataTables.
    // Kolom yang tidak boleh diurutkan: beri class "no-sort" pada <th>-nya.
    $(document).ready(function () {
        $('.tabel-datatable').DataTable({
            order: [],
            columnDefs: [{ orderable: false, targets: 'no-sort' }],
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                infoEmpty: "Menampilkan 0 sampai 0 dari 0 data",
                infoFiltered: "(disaring dari _MAX_ total data)",
                zeroRecords: "Data tidak ditemukan",
                emptyTable: "Belum ada data",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            }
        });
    });
</script>
<?php endif; ?>

<script>
    document.getElementById("menuBtn").addEventListener("click", function () {
        const sidebar = document.getElementById("sidebar");
        const mainContent = document.getElementById("mainContent");

        if (sidebar.classList.contains("d-none")) {
            sidebar.classList.remove("d-none");
            mainContent.className = "col-md-9 col-lg-10 p-4";
        } else {
            sidebar.classList.add("d-none");
            mainContent.className = "col-12 p-4";
        }
    });
</script>
</body>
</html>