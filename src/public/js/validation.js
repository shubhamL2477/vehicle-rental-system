window.RentWheelValidation = (function () {
    function isEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim());
    }

    function isPhone(value) {
        return /^\+?[0-9][0-9\s-]{6,19}$/.test(value.trim());
    }

    function setFieldError(form, name, message) {
        var field = form.querySelector('[name="' + name + '"]');
        var messageBox = form.querySelector('[data-error-for="' + name + '"]');

        if (!field || !messageBox) {
            return;
        }

        var wrapper = field.closest('.field');

        if (wrapper) {
            wrapper.classList.toggle('has-error', Boolean(message));
        }

        messageBox.textContent = message || '';
    }

    function clearErrors(form) {
        var errorBoxes = form.querySelectorAll('[data-error-for]');
        var fields = form.querySelectorAll('.field');

        for (var i = 0; i < errorBoxes.length; i++) {
            errorBoxes[i].textContent = '';
        }

        for (var j = 0; j < fields.length; j++) {
            fields[j].classList.remove('has-error');
        }
    }

    return {
        isEmail: isEmail,
        isPhone: isPhone,
        setFieldError: setFieldError,
        clearErrors: clearErrors
    };
}());
