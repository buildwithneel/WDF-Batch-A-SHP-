document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("registrationForm");

    form.addEventListener("submit", function (event) {

        event.preventDefault();

        let valid = true;

        const name = document.getElementById("fullname");
        const username = document.getElementById("username");
        const email = document.getElementById("email");
        const phone = document.getElementById("phone");
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirm_password");
        const course = document.getElementById("course");
        const year = document.getElementById("year");
        const terms = document.getElementById("terms");

        const gender = document.querySelector(
            'input[name="gender"]:checked'
        );

        document.querySelectorAll(".error").forEach(function (error) {
            error.remove();
        });

        const nameRegex = /^[A-Za-z ]{2,50}$/;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        const phoneRegex = /^[6-9][0-9]{9}$/;

        const passwordRegex =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[@#$%^&*!]).{8,20}$/;

        function showError(input, message) {

            const error = document.createElement("span");

            error.className = "error";

            error.textContent = message;

            input.parentNode.appendChild(error);

            input.style.border = "1px solid red";
        }

        function clearInput(input) {
            if (input) {
                input.style.border = "1px solid #ccc";
            }
        }

        clearInput(name);
        clearInput(username);
        clearInput(email);
        clearInput(phone);
        clearInput(password);
        clearInput(confirmPassword);
        clearInput(course);
        clearInput(year);

        if (name.value.trim() === "") {

            showError(name, "Please enter your full name.");

            valid = false;

        }
        else if (!nameRegex.test(name.value.trim())) {

            showError(
                name,
                "Name should contain only letters and spaces."
            );

            valid = false;

        }

        if (username && username.value.trim() !== "") {
            const userRegex = /^[A-Za-z0-9_]{3,30}$/;
            if (!userRegex.test(username.value.trim())) {
                showError(
                    username,
                    "Username must be 3-30 letters, numbers, or underscores."
                );
                valid = false;
            }
        }

        if (email.value.trim() === "") {

            showError(
                email,
                "Please enter your email address."
            );

            valid = false;

        }
        else if (!emailRegex.test(email.value.trim())) {

            showError(
                email,
                "Please enter a valid email address."
            );

            valid = false;

        }
        if (phone.value.trim() === "") {

            showError(
                phone,
                "Please enter your mobile number."
            );

            valid = false;

        }
        else if (!phoneRegex.test(phone.value.trim())) {

            showError(
                phone,
                "Enter a valid 10-digit mobile number."
            );

            valid = false;

        }
       if (password.value === "") {

            showError(
                password,
                "Please enter a password."
            );

            valid = false;

        }
        else if (!passwordRegex.test(password.value)) {

            showError(
                password,
                "Password must contain 8-20 characters, uppercase, lowercase, number and special character."
            );

            valid = false;

        }


        if (confirmPassword.value === "") {

            showError(
                confirmPassword,
                "Please confirm your password."
            );

            valid = false;

        }
        else if (password.value !== confirmPassword.value) {

            showError(
                confirmPassword,
                "Passwords do not match."
            );

            valid = false;

        }
        if (course.value === "") {

            showError(
                course,
                "Please select your course."
            );

            valid = false;

        }


        if (year.value === "") {

            showError(
                year,
                "Please select your year."
            );

            valid = false;

        }
        if (!gender) {

            const genderError = document.createElement("span");

            genderError.className = "error";

            genderError.textContent =
                "Please select your gender.";

            document
                .querySelector('input[name="gender"]')
                .parentNode
                .appendChild(genderError);

            valid = false;

        }


        if (!terms.checked) {

            const termsError = document.createElement("span");

            termsError.className = "error";

            termsError.textContent =
                "Please accept the Terms and Conditions.";

            terms.parentNode.appendChild(termsError);

            valid = false;

        }
    if (valid) {

            const modal = document.createElement("div");

            modal.className = "js-modal";

            modal.innerHTML = `
                <div class="js-modal-box">

                    <h2>Registration Successful!</h2>
                    <p>
                        Your registration details have been
                        validated successfully.
                    </p>
                    <button id="continueBtn">
                        Continue
                    </button>

                </div>
            `;
            document.body.appendChild(modal);
            document
                .getElementById("continueBtn")
                .addEventListener("click", function () {

                    modal.remove();

                    form.submit();

                });

        }

    });

    form.addEventListener("reset", function () {

        setTimeout(function () {

            document
                .querySelectorAll(".error")
                .forEach(function (error) {

                    error.remove();

                });


            document
                .querySelectorAll("input, select")
                .forEach(function (input) {

                    input.style.border =
                        "1px solid #ccc";

                });

        }, 10);

    });

});

