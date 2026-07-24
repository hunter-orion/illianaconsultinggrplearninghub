import "../css/style.scss";




document.addEventListener("DOMContentLoaded", () => {
    const dropdownToggles = document.querySelectorAll(".dropdown-toggle");
    const isMobileNav = () => window.matchMedia("(max-width: 1100px)").matches;

    // On mobile/touch, the dropdown expands on tap (hover handles desktop/mouse).
    dropdownToggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            if (!isMobileNav()) return;
            const expanded = toggle.getAttribute("aria-expanded") === "true";
            toggle.setAttribute("aria-expanded", String(!expanded));
        });
    });
});
