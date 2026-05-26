var roleSelect = document.querySelector('[data-role-select]');
var companyBox = document.querySelector('[data-company-box]');
var companyName = document.querySelector('[data-company-name]');

function changeRoleFields() {
    if (!roleSelect || !companyBox) {
        return;
    }

    if (roleSelect.value === 'company') {
        companyBox.hidden = false;
        companyName.required = true;
    } else {
        companyBox.hidden = true;
        companyName.required = false;
    }
}

if (roleSelect) {
    roleSelect.onchange = changeRoleFields;
    changeRoleFields();
}

var registerForm = document.querySelector('[data-register-form]');
if (registerForm) {
    registerForm.onsubmit = function () {
        var pass = document.querySelector('[data-password]');
        var confirm = document.querySelector('[data-confirm]');
        var error = document.querySelector('[data-form-error]');

        if (pass.value !== confirm.value) {
            error.textContent = 'Passwords do not match.';
            return false;
        }

        error.textContent = '';
        return true;
    };
}

var loginForm = document.querySelector('[data-login-form]');
if (loginForm) {
    loginForm.onsubmit = function (event) {
        if (loginForm.getAttribute('data-jwt-ready') === '1') {
            return true;
        }

        event.preventDefault();

        var loginInput = loginForm.querySelector('input[name="login"]');
        var passwordInput = loginForm.querySelector('input[name="password"]');
        var submit = loginForm.querySelector('button[type="submit"]');

        if (submit) {
            submit.disabled = true;
        }

        fetch('api/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                login: loginInput ? loginInput.value : '',
                password: passwordInput ? passwordInput.value : ''
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (json) {
                if (json.success && json.data) {
                    localStorage.setItem('jwt', json.data.jwt || '');
                    localStorage.setItem('refresh_token', json.data.refresh_token || '');
                    localStorage.setItem('role', json.data.role || '');
                } else {
                    localStorage.removeItem('jwt');
                    localStorage.removeItem('refresh_token');
                    localStorage.removeItem('role');
                }
            })
            .catch(function () {
                localStorage.removeItem('jwt');
                localStorage.removeItem('refresh_token');
                localStorage.removeItem('role');
            })
            .finally(function () {
                loginForm.setAttribute('data-jwt-ready', '1');
                loginForm.submit();
            });

        return false;
    };
}

var logoutLink = document.querySelector('[data-logout-link]');
if (logoutLink) {
    logoutLink.onclick = function () {
        localStorage.removeItem('jwt');
        localStorage.removeItem('refresh_token');
        localStorage.removeItem('role');
        sessionStorage.removeItem('jwt');
        sessionStorage.removeItem('refresh_token');
        sessionStorage.removeItem('role');
        return true;
    };
}

var resendButton = document.querySelector('[data-resend-button]');
if (resendButton) {
    var resendTimerText = document.querySelector('[data-resend-timer]');
    var resendSeconds = Number(resendButton.getAttribute('data-resend-seconds') || 60);

    function updateResendTimer() {
        if (resendSeconds <= 0) {
            resendButton.disabled = false;
            if (resendTimerText) {
                resendTimerText.textContent = '';
            }
            return;
        }

        resendButton.disabled = true;
        if (resendTimerText) {
            resendTimerText.textContent = '(' + resendSeconds + 's)';
        }
        resendSeconds--;
        setTimeout(updateResendTimer, 1000);
    }

    updateResendTimer();
}

