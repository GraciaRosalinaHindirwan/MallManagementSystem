const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("sidebarOverlay");
const hamburgerBtn = document.getElementById("hamburgerBtn");

hamburgerBtn?.addEventListener("click", () => {

    sidebar.classList.remove("-translate-x-full");
    overlay.classList.remove("hidden");

});

overlay?.addEventListener("click", () => {

    sidebar.classList.add("-translate-x-full");
    overlay.classList.add("hidden");

});