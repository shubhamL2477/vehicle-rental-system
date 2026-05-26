var bookingForm = document.querySelector('[data-booking-form]');
if (bookingForm) {
    var bookingVehicleSelect = bookingForm.querySelector('[data-booking-vehicle-select]');
    var driverCheck = bookingForm.querySelector('[data-driver-check]');
    var documentBox = bookingForm.querySelector('[data-document-box]');
    var docFile = bookingForm.querySelector('[data-doc-file]');
    var licenseFile = bookingForm.querySelector('[data-license-file]');
    var bookingStart = bookingForm.querySelector('input[name="start_date"]');
    var bookingEnd = bookingForm.querySelector('input[name="end_date"]');
    var bookingError = bookingForm.querySelector('[data-booking-error]');
    var availabilityStatus = bookingForm.querySelector('[data-availability-status]');
    var bookingTotal = bookingForm.querySelector('[data-booking-total]');
    var submitButton = bookingForm.querySelector('button[type="submit"]');
    var availabilityCalendar = document.querySelector('[data-availability-calendar]');
    var calendarGrid = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-grid]') : null;
    var calendarTitle = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-title]') : null;
    var calendarPrev = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-prev]') : null;
    var calendarNext = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-next]') : null;
    var calendarMonth = new Date();
    calendarMonth.setDate(1);
    var blockedRanges = [];

    function updateDocumentFields() {
        if (!driverCheck || !documentBox) {
            return;
        }

        if (driverCheck.checked) {
            documentBox.hidden = true;
            docFile.required = false;
            licenseFile.required = false;
        } else {
            documentBox.hidden = false;
            docFile.required = true;
            licenseFile.required = true;
        }
    }

    if (driverCheck) {
        driverCheck.onchange = updateDocumentFields;
        updateDocumentFields();
    }

    function setBookingMessage(message, type) {
        if (!availabilityStatus) {
            return;
        }

        availabilityStatus.textContent = message;
        availabilityStatus.className = 'availability-status ' + (type || '');
    }

    function dateRangeConflicts(start, end) {
        for (var i = 0; i < blockedRanges.length; i++) {
            if (start <= blockedRanges[i].end_date && end >= blockedRanges[i].start_date) {
                return blockedRanges[i];
            }
        }

        return null;
    }

    function calendarDateString(date) {
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return date.getFullYear() + '-' + month + '-' + day;
    }

    function renderAvailabilityCalendar() {
        if (!calendarGrid || !calendarTitle) {
            return;
        }

        var year = calendarMonth.getFullYear();
        var month = calendarMonth.getMonth();
        var firstDay = new Date(year, month, 1);
        var lastDay = new Date(year, month + 1, 0);
        var today = calendarDateString(new Date());
        var html = '';

        calendarTitle.textContent = firstDay.toLocaleString(undefined, { month: 'long', year: 'numeric' });

        for (var pad = 0; pad < firstDay.getDay(); pad++) {
            html += '<span class="calendar-day empty"></span>';
        }

        for (var day = 1; day <= lastDay.getDate(); day++) {
            var date = new Date(year, month, day);
            var dateText = calendarDateString(date);
            var conflict = dateRangeConflicts(dateText, dateText);
            var classes = 'calendar-day';
            var label = conflict ? 'Unavailable' : 'Available';

            if (dateText < today) {
                classes += ' past';
                label = 'Past date';
            } else if (conflict) {
                classes += ' blocked';
            } else {
                classes += ' free';
            }

            html += '<span class="' + classes + '" title="' + label + '">' + day + '</span>';
        }

        calendarGrid.innerHTML = html;
    }

    function dateDiffDays(start, end) {
        var startDate = new Date(start + 'T00:00:00');
        var endDate = new Date(end + 'T00:00:00');
        return Math.max(1, Math.floor((endDate - startDate) / 86400000) + 1);
    }

    function updateBookingTotal() {
        if (!bookingTotal || !bookingStart || !bookingEnd || !bookingStart.value || !bookingEnd.value || bookingEnd.value < bookingStart.value) {
            if (bookingTotal) {
                bookingTotal.textContent = '';
            }
            return;
        }

        var rate = Number(driverCheck && driverCheck.checked ? bookingForm.dataset.driverRate : bookingForm.dataset.selfRate);
        var total = dateDiffDays(bookingStart.value, bookingEnd.value) * rate;
        bookingTotal.textContent = 'Estimated total: Rs. ' + total.toFixed(2);
    }

    function validateBookingDates() {
        var start = bookingStart ? bookingStart.value : '';
        var end = bookingEnd ? bookingEnd.value : '';

        if (start === '' || end === '' || end < start) {
            if (bookingError) {
                bookingError.textContent = 'End date must be the same day or later than start date.';
            }
            setBookingMessage('Choose a valid date range.', 'bad');
            if (submitButton) {
                submitButton.disabled = true;
            }
            updateBookingTotal();
            return;
        }

        var conflict = dateRangeConflicts(start, end);
        if (conflict) {
            if (bookingError) {
                bookingError.textContent = 'Vehicle is unavailable for the selected dates.';
            }
            setBookingMessage('Unavailable: ' + conflict.start_date + ' to ' + conflict.end_date + ' is blocked.', 'bad');
            if (submitButton) {
                submitButton.disabled = true;
            }
            updateBookingTotal();
            return;
        }

        if (bookingError) {
            bookingError.textContent = '';
        }
        setBookingMessage('Available for selected dates.', 'good');
        if (submitButton) {
            submitButton.disabled = false;
        }
        updateBookingTotal();
    }

    function loadBlockedDates() {
        var vehicleId = bookingForm.getAttribute('data-vehicle-id');

        if (!vehicleId) {
            validateBookingDates();
            renderAvailabilityCalendar();
            return;
        }

        fetch('api/vehicles/booked_dates.php?vehicle_id=' + encodeURIComponent(vehicleId))
            .then(function (res) {
                return res.json();
            })
            .then(function (json) {
                blockedRanges = json && json.success && json.data && json.data.ranges ? json.data.ranges : [];
                if (blockedRanges.length > 0) {
                    setBookingMessage(blockedRanges.length + ' unavailable range(s) loaded.', 'wait');
                }
                renderAvailabilityCalendar();
                validateBookingDates();
            })
            .catch(function () {
                setBookingMessage('Could not load availability. Dates will be checked again on submit.', 'wait');
                renderAvailabilityCalendar();
                validateBookingDates();
            });
    }

    function updateSelectedBookingVehicle() {
        if (!bookingVehicleSelect) {
            return;
        }

        var selected = bookingVehicleSelect.options[bookingVehicleSelect.selectedIndex];
        if (!selected) {
            return;
        }

        bookingForm.setAttribute('data-vehicle-id', selected.value);
        bookingForm.dataset.selfRate = selected.getAttribute('data-self-rate') || '0';
        bookingForm.dataset.driverRate = selected.getAttribute('data-driver-rate') || '0';
        blockedRanges = [];
        setBookingMessage('Loading availability for selected vehicle...', 'wait');
        updateBookingTotal();
        loadBlockedDates();
    }

    if (bookingStart) {
        bookingStart.onchange = function () {
            if (bookingEnd && bookingStart.value) {
                bookingEnd.min = bookingStart.value;
            }
            validateBookingDates();
        };
    }

    if (bookingEnd) {
        bookingEnd.onchange = validateBookingDates;
    }

    if (driverCheck) {
        driverCheck.addEventListener('change', updateBookingTotal);
    }

    if (bookingVehicleSelect) {
        bookingVehicleSelect.onchange = updateSelectedBookingVehicle;
    }

    if (calendarPrev) {
        calendarPrev.onclick = function () {
            calendarMonth.setMonth(calendarMonth.getMonth() - 1);
            renderAvailabilityCalendar();
        };
    }

    if (calendarNext) {
        calendarNext.onclick = function () {
            calendarMonth.setMonth(calendarMonth.getMonth() + 1);
            renderAvailabilityCalendar();
        };
    }

    bookingForm.onsubmit = function () {
        validateBookingDates();
        if (submitButton && submitButton.disabled) {
            return false;
        }
        return true;
    };

    loadBlockedDates();
}

