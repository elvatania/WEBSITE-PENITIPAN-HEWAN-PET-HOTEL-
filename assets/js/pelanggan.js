document.addEventListener("DOMContentLoaded", function () {

    // =========================
    // SIDEBAR
    // =========================

    const menuButton = document.getElementById("menuButton");
    const sidebar = document.getElementById("sidebar");

    if (menuButton && sidebar) {

        menuButton.addEventListener("click", function () {
            sidebar.classList.toggle("show");
        });

    }


    // =========================
    // PENCARIAN PELANGGAN
    // =========================

    const searchInput =
        document.getElementById("searchPelanggan");

    if (searchInput) {

        searchInput.addEventListener("keyup", function () {

            const keyword =
                searchInput.value.toLowerCase();

            const rows =
                document.querySelectorAll(
                    ".table-container tbody tr"
                );

            rows.forEach(function (row) {

                const text =
                    row.textContent.toLowerCase();

                if (text.includes(keyword)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }

            });

        });

    }

});