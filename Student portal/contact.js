document.addEventListener("DOMContentLoaded", () => {
    const f = document.querySelector("form");
    if (f) {
        f.addEventListener("submit", e => {
            const fullname = f.querySelector("input[name='fullname']");
            const email = f.querySelector("input[name='email']");
            const subject = f.querySelector("input[name='subject']");
            const message = f.querySelector("textarea[name='message']");

            if (fullname && fullname.value.trim().length < 2) {
                e.preventDefault();
                alert("Please enter your full name (minimum 2 characters).");
                fullname.focus();
                return;
            }

            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                e.preventDefault();
                alert("Please enter a valid email address.");
                email.focus();
                return;
            }

            if (subject && subject.value.trim().length < 3) {
                e.preventDefault();
                alert("Please enter a subject (minimum 3 characters).");
                subject.focus();
                return;
            }

            if (message && message.value.trim().length < 10) {
                e.preventDefault();
                alert("Please enter your message (minimum 10 characters).");
                message.focus();
                return;
            }
        });
    }
});