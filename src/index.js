import "../css/style.scss";




document.addEventListener("DOMContentLoaded", () => {
    const hamburger = document.getElementById("hamburger");
    const navList = document.querySelector(".nav-list"); // ← FIXED
    const dropdownToggles = document.querySelectorAll(".dropdown-toggle");
    const isMobileNav = () => window.matchMedia("(max-width: 1100px)").matches;

    if (!hamburger || !navList) return;

    hamburger.addEventListener("click", () => {
        const open = navList.classList.toggle("show");
        hamburger.classList.toggle("active");
        hamburger.setAttribute("aria-expanded", String(open));
        // Lock background scroll while the full-screen panel is open.
        document.body.classList.toggle("nav-open", open);
        // Collapse every dropdown sub-menu whenever the panel closes.
        if (!open) {
            dropdownToggles.forEach((toggle) => toggle.setAttribute("aria-expanded", "false"));
        }
    });

    // On mobile, each dropdown expands in-flow on tap (hover handles desktop).
    dropdownToggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            if (!isMobileNav()) return;
            const expanded = toggle.getAttribute("aria-expanded") === "true";
            toggle.setAttribute("aria-expanded", String(!expanded));
        });
    });
});
