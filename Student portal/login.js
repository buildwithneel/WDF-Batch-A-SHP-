document.addEventListener("DOMContentLoaded", () => {
    const f = document.querySelector("form");
    if (f) {
        f.addEventListener("submit", e => {
            const emailInput = f.querySelector("input[name='email']");
            const passInput = f.querySelector("input[name='password']");
            if (emailInput && emailInput.value.trim() === "") {
                e.preventDefault();
                alert("Please enter your email address or username.");
                emailInput.focus();
                return;
            }
            if (passInput && passInput.value.trim() === "") {
                e.preventDefault();
                alert("Please enter your password.");
                passInput.focus();
                return;
            }
        });
    }
});