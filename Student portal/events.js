document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    if (!document.querySelector(".js-theme")) {
        const themeBtn = document.createElement("button");
        themeBtn.textContent = "☾ Dark";
        themeBtn.className = "js-theme";
        const nav = document.querySelector("nav") || document.querySelector("header");
        if (nav) {
            nav.appendChild(themeBtn);
        } else {
            body.appendChild(themeBtn);
        }

        themeBtn.onclick = () => {
            body.classList.toggle("dark-mode");
            const isDark = body.classList.contains("dark-mode");
            localStorage.setItem("studenthub-theme", isDark ? "dark" : "light");
            themeBtn.textContent = isDark ? "☀ Light" : "☾ Dark";
        };

        if (localStorage.getItem("studenthub-theme") === "dark") {
            body.classList.add("dark-mode");
            themeBtn.textContent = "☀ Light";
        }
    }

    const eventContainer = document.getElementById("event-container");
    if (!eventContainer) return;

    const searchInput = document.getElementById("search");
    const categorySelect = document.getElementById("category");
    const sortSelect = document.getElementById("sort");
    const resetBtn = document.getElementById("reset");
    const noEventsMsg = document.getElementById("no-events");
    const paginationContainer = document.getElementById("pagination");

    const fallbackEvents = [
        {
            id: 1,
            name: "Hackathon 2026",
            category: "Technical",
            date: "2026-08-15",
            venue: "Seminar Hall",
            description: "A 24-hour coding competition for innovative software solutions.",
            image: "https://blog.bham.ac.uk/lpip/wp-content/uploads/sites/140/2025/05/Hackathon.png",
            link: "register_event.php?event_id=1"
        },
        {
            id: 2,
            name: "AI Workshop",
            category: "Workshop",
            date: "2026-08-22",
            venue: "Computer Lab 1",
            description: "Learn Artificial Intelligence and Machine Learning from industry experts.",
            image: "https://digitallearning.eletsonline.com/wp-content/uploads/2013/12/techfest.jpg",
            link: "register_event.php?event_id=2"
        },
        {
            id: 3,
            name: "Annual Sports Meet",
            category: "Sports",
            date: "2026-09-05",
            venue: "College Ground",
            description: "Participate in various indoor and outdoor sports competitions.",
            image: "https://iper.ac.in/wp-content/uploads/2021/12/IPER-UG-Inter-College-Sports-2021-22.jpg",
            link: "register_event.php?event_id=3"
        },
        {
            id: 4,
            name: "Cultural Fest 2026",
            category: "Cultural",
            date: "2026-09-15",
            venue: "University Auditorium",
            description: "A celebration of music, dance, drama and student creativity.",
            image: "https://images.unsplash.com/photo-1492684223066-81342ee5ff30",
            link: "register_event.php?event_id=4"
        },
        {
            id: 5,
            name: "Cyber Security Seminar",
            category: "Seminar",
            date: "2026-09-20",
            venue: "Conference Hall",
            description: "Learn about modern cybersecurity threats and protection techniques.",
            image: "https://images.unsplash.com/photo-1563013544-824ae1b704d3",
            link: "register_event.php?event_id=5"
        },
        {
            id: 6,
            name: "Web Development Workshop",
            category: "Workshop",
            date: "2026-10-05",
            venue: "Computer Lab 2",
            description: "Hands-on workshop covering HTML, CSS, JavaScript, PHP and MySQL.",
            image: "https://images.unsplash.com/photo-1498050108023-c5249f4df085",
            link: "register_event.php?event_id=6"
        }
    ];

    let allEvents = [];
    let currentPage = 1;
    const itemsPerPage = 3;

    function renderEvents(events) {
        eventContainer.innerHTML = "";

        if (events.length === 0) {
            noEventsMsg.style.display = "block";
            paginationContainer.innerHTML = "";
            return;
        }

        noEventsMsg.style.display = "none";

        const totalPages = Math.ceil(events.length / itemsPerPage);
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const startIndex = (currentPage - 1) * itemsPerPage;
        const pageItems = events.slice(startIndex, startIndex + itemsPerPage);

        pageItems.forEach(ev => {
            const card = document.createElement("div");
            card.className = "event-card";
            card.innerHTML = `
                <div class="event-card-img-wrap">
                    <img src="${ev.image || 'pic1.png'}" alt="${ev.name}" onerror="this.src='pic1.png'">
                    <span class="event-card-category">${ev.category || 'Event'}</span>
                </div>
                <div class="event-card-body">
                    <h3 class="event-card-title">${ev.name}</h3>
                    <div class="event-card-meta">
                        <span>📅 <b>Date:</b> ${ev.date}</span>
                        <span>📍 <b>Venue:</b> ${ev.venue}</span>
                    </div>
                    <p class="event-card-desc">${ev.description}</p>
                    <div class="event-card-action">
                        <a href="register_event.php?event_id=${ev.id}" class="event-register-btn">Register for Event</a>
                    </div>
                </div>
            `;
            eventContainer.appendChild(card);
        });

        renderPagination(totalPages);
    }

    function renderPagination(totalPages) {
        paginationContainer.innerHTML = "";
        if (totalPages <= 1) return;

        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement("button");
            btn.className = "page-btn" + (i === currentPage ? " active" : "");
            btn.textContent = i;
            btn.addEventListener("click", () => {
                currentPage = i;
                applyFilters();
            });
            paginationContainer.appendChild(btn);
        }
    }

    function applyFilters() {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : "";
        const selectedCat = categorySelect ? categorySelect.value : "All";
        const selectedSort = sortSelect ? sortSelect.value : "default";

        let filtered = allEvents.filter(ev => {
            const matchesQuery = !query ||
                ev.name.toLowerCase().includes(query) ||
                ev.description.toLowerCase().includes(query) ||
                ev.venue.toLowerCase().includes(query);

            const matchesCat = (selectedCat === "All") || (ev.category.toLowerCase() === selectedCat.toLowerCase());

            return matchesQuery && matchesCat;
        });

        if (selectedSort === "name-asc") {
            filtered.sort((a, b) => a.name.localeCompare(b.name));
        } else if (selectedSort === "name-desc") {
            filtered.sort((a, b) => b.name.localeCompare(a.name));
        } else if (selectedSort === "date-asc") {
            filtered.sort((a, b) => new Date(a.date) - new Date(b.date));
        } else if (selectedSort === "date-desc") {
            filtered.sort((a, b) => new Date(b.date) - new Date(a.date));
        }

        renderEvents(filtered);
    }

    fetch("api_events.php")
        .then(res => res.json())
        .then(json => {
            if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                allEvents = json.data.map(e => ({
                    id: e.id,
                    name: e.name,
                    category: e.category,
                    date: e.event_date || e.date,
                    venue: e.venue,
                    description: e.description,
                    image: e.image_url || e.image || "pic1.png",
                    link: "register_event.php?event_id=" + e.id
                }));
            } else {
                allEvents = fallbackEvents;
            }
            applyFilters();
        })
        .catch(() => {
            allEvents = fallbackEvents;
            applyFilters();
        });

    if (searchInput) {
        searchInput.addEventListener("input", () => {
            currentPage = 1;
            applyFilters();
        });
    }

    if (categorySelect) {
        categorySelect.addEventListener("change", () => {
            currentPage = 1;
            applyFilters();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener("change", () => {
            currentPage = 1;
            applyFilters();
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener("click", () => {
            if (searchInput) searchInput.value = "";
            if (categorySelect) categorySelect.value = "All";
            if (sortSelect) sortSelect.value = "default";
            currentPage = 1;
            applyFilters();
        });
    }
});