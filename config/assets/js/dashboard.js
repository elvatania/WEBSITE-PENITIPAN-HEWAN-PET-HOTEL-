const menuButton = document.getElementById("menuButton");
const sidebar = document.getElementById("sidebar");
const mainContent = document.getElementById("mainContent");

if (menuButton && sidebar && mainContent) {

    menuButton.addEventListener("click", function () {

        sidebar.classList.toggle("show");

        mainContent.classList.toggle("sidebar-open");

    });

}