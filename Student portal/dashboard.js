document.addEventListener("DOMContentLoaded", () => {
    const b = document.body;
    const nav = document.querySelector("nav");
    const rightCell = document.querySelector("nav td:last-child") || b;

    const t = document.createElement("button");
    t.className = "js-theme";
    t.innerHTML = '<span class="theme-icon">&#9789;</span> Dark';
    rightCell.append(t);

    t.onclick = () => {
        b.classList.toggle("dark-mode");
        t.innerHTML = b.classList.contains("dark-mode") ? '<span class="theme-icon">&#9728;</span> Light' : '<span class="theme-icon">&#9789;</span> Dark';
    };

    if (nav) {
        const h = document.createElement("button");
        h.className = "js-menu";
        h.setAttribute("aria-label", "Toggle navigation");
        h.innerHTML = '<span class="bar"></span><span class="bar"></span><span class="bar"></span>';
        nav.prepend(h);

        h.onclick = () => nav.classList.toggle("open");
    }
});