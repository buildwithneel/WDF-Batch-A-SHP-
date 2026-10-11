document.addEventListener("DOMContentLoaded", () => {
    const b = document.body;
    const n = document.querySelector("nav");
    const h = document.querySelector("header");

    fetch("session_status.php")
        .then(r => r.json())
        .then(st => {
            if (!st || !st.logged_in) return;
            const nav = document.querySelector("nav table");
            if (!nav) return;
            const cells = nav.querySelectorAll("td");
            if (cells.length >= 3) {
                const centerTd = cells[1];
                const rightTd = cells[2];
                if (st.role === "Admin") {
                    centerTd.innerHTML = `
                        <a href="admin_dashboard.php">Admin Dashboard</a> |
                        <a href="manage_students.php">Manage Students</a> |
                        <a href="manage_events.php">Manage Events</a> |
                        <a href="index.php">Public Events</a> |
                        <a href="view_records.php">All Records</a>
                    `;
                    rightTd.innerHTML = `
                        <span style="font-size:13px;margin-right:10px;color:#fff;">${st.fullname} (Admin)</span>
                        <a href="logout.php" style="color:#ffcdd2;font-weight:600;">Logout</a>
                    `;
                } else if (st.role === "Student") {
                    centerTd.innerHTML = `
                        <a href="dashboard.php">Dashboard</a> |
                        <a href="profile.php">Profile</a> |
                        <a href="index.php">Events</a> |
                        <a href="register_event.php">Register Event</a> |
                        <a href="feedback.html">Feedback</a> |
                        <a href="contact.html">Contact</a>
                    `;
                    rightTd.innerHTML = `
                        <span style="font-size:13px;margin-right:10px;color:#fff;">${st.fullname}</span>
                        <a href="logout.php" style="color:#ffcdd2;font-weight:600;">Logout</a>
                    `;
                }
            }
        })
        .catch(() => {});

    if (h && n && !document.querySelector(".js-menu")) {
        const menuBtn = document.createElement("button");
        menuBtn.className = "js-menu";
        menuBtn.setAttribute("aria-label", "Toggle navigation");
        menuBtn.innerHTML = '<span class="bar"></span><span class="bar"></span><span class="bar"></span>';
        n.prepend(menuBtn);
        menuBtn.addEventListener("click", () => n.classList.toggle("open"));
    }

    if (h && n && !document.querySelector(".js-theme")) {
        const themeBtn = document.createElement("button");
        themeBtn.className = "js-theme";
        themeBtn.innerHTML = localStorage.getItem("studenthub-theme") === "dark" ? '<span class="theme-icon">&#9728;</span> Light' : '<span class="theme-icon">&#9789;</span> Dark';

        themeBtn.addEventListener("click", () => {
            b.classList.toggle("dark-mode");
            const isDark = b.classList.contains("dark-mode");
            localStorage.setItem("studenthub-theme", isDark ? "dark" : "light");
            themeBtn.innerHTML = isDark ? '<span class="theme-icon">&#9728;</span> Light' : '<span class="theme-icon">&#9789;</span> Dark';
        });

        const rightCell = n.querySelector("td[align='right']") || n;
        rightCell.appendChild(themeBtn);

        if (localStorage.getItem("studenthub-theme") === "dark") {
            b.classList.add("dark-mode");
        }
    }

    document.querySelectorAll('a[href="#"]').forEach(a => {
        a.addEventListener("click", e => {
            e.preventDefault();
            alert(a.textContent.trim() + " is available in this StudentHub demo.");
        });
    });

    document.querySelectorAll("details").forEach(d => {
        d.addEventListener("toggle", () => {
            if (d.open) {
                document.querySelectorAll("details").forEach(x => {
                    if (x !== d) x.removeAttribute("open");
                });
            }
        });
    });
});