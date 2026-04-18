(function () {
    var validation = window.RentWheelValidation;

    function setFeedback(element, type, message) {
        if (!element) {
            return;
        }

        element.hidden = !message;
        element.className = 'feedback' + (message ? ' is-' + type : '');
        element.textContent = message || '';
    }

    function setSubmitState(form, busy) {
        var submitButton = form.querySelector('button[type="submit"]');

        if (!submitButton) {
            return;
        }

        submitButton.disabled = busy;
        submitButton.textContent = busy ? 'Please wait...' : submitButton.getAttribute('data-default-label') || submitButton.textContent;
    }

    function parseJson(response) {
        return response.json().catch(function () {
            return {};
        });
    }

    function syncMethodStyles(form) {
        var options = form.querySelectorAll('[data-method-option]');

        for (var i = 0; i < options.length; i++) {
            var input = options[i].querySelector('input');
            options[i].classList.toggle('is-active', Boolean(input && input.checked));
        }
    }

    function updateRegistrationMode(form) {
        var method = form.querySelector('input[name="registration_method"]:checked').value;
        var emailField = form.querySelector('[data-contact-field="email"]');
        var phoneField = form.querySelector('[data-contact-field="phone"]');
        var emailInput = form.querySelector('[name="email"]');
        var phoneInput = form.querySelector('[name="phone"]');

        emailField.classList.toggle('is-hidden', method !== 'email');
        phoneField.classList.toggle('is-hidden', method !== 'phone');
        emailInput.required = method === 'email';
        phoneInput.required = method === 'phone';

        syncMethodStyles(form);
    }

    function initRegisterForm() {
        var form = document.getElementById('register-form');
        var feedback = document.getElementById('register-feedback');

        if (!form || !validation) {
            return;
        }

        var methodInputs = form.querySelectorAll('input[name="registration_method"]');
        var submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            submitButton.setAttribute('data-default-label', submitButton.textContent);
        }

        for (var i = 0; i < methodInputs.length; i++) {
            methodInputs[i].addEventListener('change', function () {
                validation.clearErrors(form);
                setFeedback(feedback, 'error', '');
                updateRegistrationMode(form);
            });
        }

        updateRegistrationMode(form);

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            validation.clearErrors(form);
            setFeedback(feedback, 'error', '');

            var formData = new FormData(form);
            var method = formData.get('registration_method');
            var name = String(formData.get('name') || '').trim();
            var email = String(formData.get('email') || '').trim();
            var phone = String(formData.get('phone') || '').trim();
            var password = String(formData.get('password') || '');
            var confirmPassword = String(formData.get('confirm_password') || '');
            var hasError = false;

            if (!name) {
                validation.setFieldError(form, 'name', 'Full name is required.');
                hasError = true;
            }

            if (method === 'email' && !validation.isEmail(email)) {
                validation.setFieldError(form, 'email', 'Enter a valid email address.');
                hasError = true;
            }

            if (method === 'phone' && !validation.isPhone(phone)) {
                validation.setFieldError(form, 'phone', 'Enter a valid phone number.');
                hasError = true;
            }

            if (password.length < 6) {
                validation.setFieldError(form, 'password', 'Password must be at least 6 characters.');
                hasError = true;
            }

            if (confirmPassword !== password) {
                validation.setFieldError(form, 'confirm_password', 'Passwords do not match.');
                hasError = true;
            }

            if (hasError) {
                setFeedback(feedback, 'error', 'Please fix the highlighted fields.');
                return;
            }

            setSubmitState(form, true);

            fetch('./src/api/auth/register.php', {
                method: 'POST',
                body: formData
            }).then(function (response) {
                return parseJson(response).then(function (payload) {
                    if (!response.ok || !payload.success) {
                        throw payload;
                    }

                    setFeedback(feedback, 'success', payload.message || 'Account created successfully.');

                    window.setTimeout(function () {
                        window.location.href = payload.redirect_url || './signinpage.html?registered=1';
                    }, 900);
                });
            }).catch(function (payload) {
                setFeedback(feedback, 'error', payload.message || 'Could not create account.');
            }).finally(function () {
                setSubmitState(form, false);
            });
        });
    }

    function renderSession(session) {
        var card = document.getElementById('session-card');
        var name = document.getElementById('session-name');
        var meta = document.getElementById('session-meta');

        if (!card || !name || !meta || !session || !session.user) {
            return;
        }

        card.hidden = false;
        name.textContent = session.user.name;

        var parts = [];

        if (session.user.email) {
            parts.push(session.user.email);
        }

        if (session.user.phone) {
            parts.push(session.user.phone);
        }

        meta.textContent = parts.join(' | ') || 'Authenticated session active.';
    }

    function initLoginForm() {
        var form = document.getElementById('login-form');
        var feedback = document.getElementById('login-feedback');

        if (!form || !validation) {
            return;
        }

        var submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            submitButton.setAttribute('data-default-label', submitButton.textContent);
        }

        var params = new URLSearchParams(window.location.search);

        if (params.get('logout') === '1') {
            setFeedback(feedback, 'success', 'You have been logged out successfully.');
        } else if (params.get('registered') === '1') {
            setFeedback(feedback, 'success', 'Registration complete. You can log in now.');
        }

        fetch('./src/api/auth/me.php')
            .then(function (response) {
                return parseJson(response);
            })
            .then(function (payload) {
                if (payload && payload.success) {
                    renderSession(payload.data);
                }
            })
            .catch(function () {
                return null;
            });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            validation.clearErrors(form);
            setFeedback(feedback, 'error', '');

            var formData = new FormData(form);
            var credential = String(formData.get('credential') || '').trim();
            var password = String(formData.get('password') || '');
            var hasError = false;

            if (!credential) {
                validation.setFieldError(form, 'credential', 'Email or phone is required.');
                hasError = true;
            }

            if (!password) {
                validation.setFieldError(form, 'password', 'Password is required.');
                hasError = true;
            }

            if (hasError) {
                setFeedback(feedback, 'error', 'Please complete the required fields.');
                return;
            }

            setSubmitState(form, true);

            fetch('./src/api/auth/login.php', {
                method: 'POST',
                body: formData
            }).then(function (response) {
                return parseJson(response).then(function (payload) {
                    if (!response.ok || !payload.success) {
                        throw payload;
                    }

                    setFeedback(feedback, 'success', payload.message || 'Login successful.');
                    renderSession(payload.data);
                });
            }).catch(function (payload) {
                setFeedback(feedback, 'error', payload.message || 'Login failed.');
            }).finally(function () {
                setSubmitState(form, false);
            });
        });
    }

    initRegisterForm();
    initLoginForm();
}());
