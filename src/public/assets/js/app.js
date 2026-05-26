var roleToggle = document.querySelector('[data-role-toggle]');
var companyFields = document.querySelector('[data-company-fields]');

function showCompanyFields() {
    if (!roleToggle || !companyFields) {
        return;
    }

    if (roleToggle.value === 'company') {
        companyFields.classList.remove('is-hidden');
    } else {
        companyFields.classList.add('is-hidden');
    }
}

if (roleToggle && companyFields) {
    roleToggle.onchange = showCompanyFields;
    showCompanyFields();
}

var openButtons = document.querySelectorAll('[data-open-modal]');
for (var i = 0; i < openButtons.length; i++) {
    openButtons[i].onclick = function () {
        var modalId = this.getAttribute('data-open-modal');
        var modal = document.getElementById(modalId);

        if (modal) {
            modal.hidden = false;
        }
    };
}

var closeButtons = document.querySelectorAll('[data-close-modal]');
for (var j = 0; j < closeButtons.length; j++) {
    closeButtons[j].onclick = function () {
        var modal = this.closest('.modal');

        if (modal) {
            modal.hidden = true;
        }
    };
}

var modalList = document.querySelectorAll('.modal');
for (var k = 0; k < modalList.length; k++) {
    modalList[k].onclick = function (event) {
        if (event.target === this) {
            this.hidden = true;
        }
    };
}

document.onkeydown = function (event) {
    if (event.key === 'Escape') {
        var allModals = document.querySelectorAll('.modal');

        for (var i = 0; i < allModals.length; i++) {
            allModals[i].hidden = true;
        }
    }
};

var bookingForm = document.querySelector('[data-booking-form]');

function bookingDays(startValue, endValue) {
    var start = new Date(startValue);
    var end = new Date(endValue);

    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end <= start) {
        return 0;
    }

    var diff = end.getTime() - start.getTime();
    return Math.max(1, Math.ceil(diff / (1000 * 60 * 60 * 24)));
}

function formatNepaliMoney(amount) {
    return 'Rs. ' + amount.toFixed(2);
}

if (bookingForm) {
    var startInput = bookingForm.querySelector('input[name="start_datetime"]');
    var endInput = bookingForm.querySelector('input[name="end_datetime"]');
    var driverToggle = bookingForm.querySelector('[data-driver-toggle]');
    var preview = bookingForm.querySelector('[data-price-preview]');
    var licenseInput = bookingForm.querySelector('[data-license-input]');
    var licenseHelp = bookingForm.querySelector('[data-license-help]');
    var basePrice = Number(bookingForm.getAttribute('data-base-price') || 0);
    var driverPrice = Number(bookingForm.getAttribute('data-driver-price') || 0);

    function updateLicenseRule() {
        if (!driverToggle || !licenseInput) {
            return;
        }

        if (driverToggle.checked) {
            licenseInput.required = false;

            if (licenseHelp) {
                licenseHelp.textContent = 'License is not required because driver is selected.';
            }
        } else {
            licenseInput.required = true;

            if (licenseHelp) {
                licenseHelp.textContent = 'License is required for self-drive booking.';
            }
        }
    }

    function updateBookingPreview() {
        var days = bookingDays(startInput.value, endInput.value);

        if (!days) {
            preview.textContent = 'Select valid dates to preview the total.';
            return;
        }

        var total = days * basePrice;

        if (driverToggle.checked) {
            total = total + days * driverPrice;
        }

        preview.textContent = days + ' day(s) estimated total: ' + formatNepaliMoney(total);
    }

    startInput.oninput = updateBookingPreview;
    endInput.oninput = updateBookingPreview;

    if (driverToggle) {
        driverToggle.onchange = function () {
            updateBookingPreview();
            updateLicenseRule();
        };
    }

    updateLicenseRule();
}

var resendButton = document.querySelector('[data-resend-button]');

if (resendButton) {
    var secondsLeft = Number(resendButton.getAttribute('data-resend-seconds') || 0);
    var normalText = resendButton.getAttribute('data-resend-default-text') || 'Resend OTP';

    function updateResendButton() {
        if (secondsLeft > 0) {
            resendButton.disabled = true;
            resendButton.textContent = normalText + ' in ' + secondsLeft + 's';
            secondsLeft = secondsLeft - 1;
            return;
        }

        resendButton.disabled = false;
        resendButton.textContent = normalText;
        clearInterval(timerId);
    }

    var timerId = setInterval(updateResendButton, 1000);
    updateResendButton();
}
