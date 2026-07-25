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

    // Registration page (page-registration.php): every form here submits via
    // fetch() to admin-post.php and shows the JSON response as a .reg-message
    // banner in #reg-messages, scrolled into view — no page reload, no
    // redirect, no query string. novalidate is set on these forms, so this
    // is the only validation UI shown — no native browser tooltips either.
    const messagesBox = document.getElementById("reg-messages");

    const showRegMessage = (type, text) => {
        if (!messagesBox) return;
        messagesBox.querySelectorAll(".reg-message").forEach((el) => el.remove());

        const message = document.createElement("div");
        message.className = "reg-message " + type;
        message.textContent = text;
        messagesBox.appendChild(message);
        message.scrollIntoView({ behavior: "smooth", block: "center" });
    };

    // Signup is the one action that changes the page from the logged-out
    // form to the logged-in panels, which only the server can render — so
    // on success it queues its message(s) and does a plain same-URL reload
    // instead of updating the DOM in place. Read back and shown once here.
    (() => {
        const raw = sessionStorage.getItem("illiana_pending_messages");
        if (!raw || !messagesBox) return;
        sessionStorage.removeItem("illiana_pending_messages");

        let pending;
        try {
            pending = JSON.parse(raw);
        } catch (e) {
            return;
        }

        pending.forEach(({ type, text }) => {
            const el = document.createElement("div");
            el.className = "reg-message " + type;
            el.textContent = text;
            messagesBox.appendChild(el);
        });
        const first = messagesBox.querySelector(".reg-message");
        if (first) first.scrollIntoView({ behavior: "smooth", block: "center" });
    })();

    // Reads minlength/maxlength straight off the field (rendered from the
    // same PHP constants inc/registration.php validates against server-side)
    // so the limit only lives in one place, not duplicated here as numbers.
    const lengthError = (field, label) => {
        const value = field.value.trim();
        const min = field.hasAttribute("minlength") ? field.minLength : null;
        const max = field.hasAttribute("maxlength") ? field.maxLength : null;

        if (min !== null && value.length > 0 && value.length < min) {
            return max !== null
                ? `${label} must be between ${min} and ${max} characters.`
                : `${label} must be at least ${min} characters.`;
        }
        if (max !== null && value.length > max) {
            return `${label} must be ${max} characters or fewer.`;
        }
        return "";
    };

    // POSTs a form's own data to its own action URL and resolves with the
    // parsed JSON response ({success, message, ...}) — never rejects, so
    // every call site can handle network failure the same way as a normal
    // server error instead of needing its own .catch().
    const submitFormViaAjax = (form) =>
        // form.action (the DOM property) is shadowed by these forms' own
        // <input name="action"> field (admin-post.php requires that name to
        // route the request) — it resolves to the input element itself, not
        // the URL, so this reads the raw action="" attribute instead.
        fetch(form.getAttribute("action"), { method: "POST", body: new FormData(form) })
            .then((res) => res.json())
            .catch(() => ({ success: false, message: "Something went wrong — please try again." }));

    const registrationForm = document.getElementById("registration-form");
    if (registrationForm) {
        registrationForm.addEventListener("submit", (event) => {
            event.preventDefault();

            const fullName = document.getElementById("full_name");
            const email = document.getElementById("email");
            const emailConfirm = document.getElementById("email_confirm");
            const employer = document.getElementById("employer");
            const phone = document.getElementById("phone");
            const accessCode = document.getElementById("access_code");
            const password = document.getElementById("password");
            const passwordConfirm = document.getElementById("password_confirm");
            const agreeTerms = document.getElementById("agree_terms");

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            let error =
                (!fullName.value.trim() || !emailPattern.test(email.value.trim())) && "Enter your name and a valid email address.";
            error = error || lengthError(fullName, "Full name");
            error = error || lengthError(email, "Email address");
            error = error || (email.value.trim() !== emailConfirm.value.trim() && "Email addresses do not match.");
            error = error || lengthError(employer, "Employer/Organization");
            error = error || lengthError(phone, "Phone number");
            error = error || lengthError(accessCode, "Access code");
            error = error || lengthError(password, "Password");
            error = error || (password.value !== passwordConfirm.value && "Passwords do not match.");
            error = error || (!agreeTerms.checked && "Please agree to the Terms and Privacy Policy.");

            if (error) {
                showRegMessage("error", error);
                return;
            }

            submitFormViaAjax(registrationForm).then((data) => {
                if (!data.success) {
                    showRegMessage("error", data.message);
                    return;
                }

                const pending = [{ type: "success", text: data.message }];
                if (data.code) {
                    pending.push({ type: data.code.success ? "success" : "error", text: data.code.message });
                }
                sessionStorage.setItem("illiana_pending_messages", JSON.stringify(pending));
                window.location.reload();
            });
        });
    }

    // Profile form (page-registration.php, logged in): same length limits on
    // full name / employer / phone, then AJAX — stays in place, no reload.
    const profileForm = document.getElementById("profile-form");
    if (profileForm) {
        profileForm.addEventListener("submit", (event) => {
            event.preventDefault();

            const fullName = document.getElementById("full_name");
            const employer = document.getElementById("employer");
            const phone = document.getElementById("phone");

            const error =
                lengthError(fullName, "Full name") ||
                lengthError(employer, "Employer/Organization") ||
                lengthError(phone, "Phone number");

            if (error) {
                showRegMessage("error", error);
                return;
            }

            submitFormViaAjax(profileForm).then((data) => {
                showRegMessage(data.success ? "success" : "error", data.message);
            });
        });
    }

    const passwordResetForm = document.getElementById("password-reset-form");
    if (passwordResetForm) {
        passwordResetForm.addEventListener("submit", (event) => {
            event.preventDefault();
            submitFormViaAjax(passwordResetForm).then((data) => {
                showRegMessage(data.success ? "success" : "error", data.message);
            });
        });
    }

    const redeemCodeForm = document.getElementById("redeem-code-form");
    if (redeemCodeForm) {
        redeemCodeForm.addEventListener("submit", (event) => {
            event.preventDefault();
            submitFormViaAjax(redeemCodeForm).then((data) => {
                showRegMessage(data.success ? "success" : "error", data.message);
                if (data.success) {
                    redeemCodeForm.reset();
                    const enrollments = document.getElementById("reg-enrollments");
                    if (enrollments && data.enrollment_html) {
                        enrollments.innerHTML = data.enrollment_html;
                    }
                }
            });
        });
    }
});
