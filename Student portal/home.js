document.addEventListener("DOMContentLoaded", () => {
    const b = document.body;
    const n = document.querySelector("nav");

    if (n) {
        const m = document.createElement("button");
        m.className = "js-menu";
        m.setAttribute("aria-label", "Toggle navigation");
        m.innerHTML = '<span class="bar"></span><span class="bar"></span><span class="bar"></span>';
        n.prepend(m);
        m.onclick = () => n.classList.toggle("open");
    }

    const t = document.createElement("button");
    t.className = "js-theme";
    t.innerHTML = '<span class="theme-icon">&#9789;</span> Dark';
    const rightCell = document.querySelector("nav td:last-child") || b;
    rightCell.append(t);

    t.onclick = () => {
        b.classList.toggle("dark-mode");
        t.innerHTML = b.classList.contains("dark-mode") ? '<span class="theme-icon">&#9728;</span> Light' : '<span class="theme-icon">&#9789;</span> Dark';
    };

    const banner = document.createElement("div");
    banner.className = "js-banner";
    banner.innerHTML = '<span class="banner-badge">Portal</span> <span>Welcome to StudentHub &mdash; Your Central Campus Platform</span> <button class="banner-close" aria-label="Close">&times;</button>';
    b.prepend(banner);

    const closeBtn = banner.querySelector(".banner-close");
    if (closeBtn) {
        closeBtn.onclick = () => banner.remove();
    }
});