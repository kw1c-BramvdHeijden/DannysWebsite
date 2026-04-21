const body = document.body;
const navToggleButton = document.querySelector(".nav-toggle-button");
const navPanel = document.querySelector(".nav-panel");
const dropdowns = Array.from(document.querySelectorAll(".dropdown"));

function closeDropdown(dropdown) {
    const trigger = dropdown.querySelector(".dropdown-trigger");
    const menu = dropdown.querySelector(".dropdown-menu");

    dropdown.classList.remove("is-open");
    trigger?.setAttribute("aria-expanded", "false");
    if (menu) {
        menu.hidden = true;
    }
}

function openDropdown(dropdown) {
    dropdowns.forEach((item) => {
        if (item !== dropdown) {
            closeDropdown(item);
        }
    });

    const trigger = dropdown.querySelector(".dropdown-trigger");
    const menu = dropdown.querySelector(".dropdown-menu");

    dropdown.classList.add("is-open");
    trigger?.setAttribute("aria-expanded", "true");
    if (menu) {
        menu.hidden = false;
    }
}

function closeMobileMenu() {
    body.classList.remove("nav-open");
    navToggleButton?.setAttribute("aria-expanded", "false");
}

navToggleButton?.addEventListener("click", () => {
    const isOpen = body.classList.toggle("nav-open");
    navToggleButton.setAttribute("aria-expanded", String(isOpen));

    if (!isOpen) {
        dropdowns.forEach(closeDropdown);
    }
});

dropdowns.forEach((dropdown) => {
    const trigger = dropdown.querySelector(".dropdown-trigger");

    trigger?.addEventListener("click", (event) => {
        event.stopPropagation();
        const isOpen = dropdown.classList.contains("is-open");

        if (isOpen) {
            closeDropdown(dropdown);
        } else {
            openDropdown(dropdown);
        }
    });
});

document.addEventListener("click", (event) => {
    dropdowns.forEach((dropdown) => {
        if (!dropdown.contains(event.target)) {
            closeDropdown(dropdown);
        }
    });
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        dropdowns.forEach(closeDropdown);
        closeMobileMenu();
    }
});

window.addEventListener("resize", () => {
    if (window.innerWidth > 760) {
        closeMobileMenu();
    }
});

navPanel?.querySelectorAll("a").forEach((link) => {
    link.addEventListener("click", () => {
        if (window.innerWidth <= 760) {
            closeMobileMenu();
            dropdowns.forEach(closeDropdown);
        }
    });
});
