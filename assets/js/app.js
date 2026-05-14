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

var bookingForm = document.querySelector('[data-booking-form]');
var availabilityCalendar = document.querySelector('[data-availability-calendar]');
if (bookingForm || availabilityCalendar) {
    var driverCheck = bookingForm ? bookingForm.querySelector('[data-driver-check]') : null;
    var documentBox = bookingForm ? bookingForm.querySelector('[data-document-box]') : null;
    var docFile = bookingForm ? bookingForm.querySelector('[data-doc-file]') : null;
    var licenseFile = bookingForm ? bookingForm.querySelector('[data-license-file]') : null;
    var bookingStart = bookingForm ? bookingForm.querySelector('input[name="start_date"]') : null;
    var bookingEnd = bookingForm ? bookingForm.querySelector('input[name="end_date"]') : null;
    var bookingError = bookingForm ? bookingForm.querySelector('[data-booking-error]') : null;
    var availabilityStatus = bookingForm ? bookingForm.querySelector('[data-availability-status]') : null;
    var bookingTotal = bookingForm ? bookingForm.querySelector('[data-booking-total]') : null;
    var submitButton = bookingForm ? bookingForm.querySelector('button[type="submit"]') : null;
    var calendarSummary = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-summary]') : null;
    var calendarMonths = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-months]') : null;
    var calendarPrev = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-prev]') : null;
    var calendarNext = availabilityCalendar ? availabilityCalendar.querySelector('[data-calendar-next]') : null;
    var vehicleId = bookingForm ? bookingForm.getAttribute('data-vehicle-id') : availabilityCalendar.getAttribute('data-vehicle-id');
    var blockedRanges = [];
    var blockedDatesByDay = {};
    var calendarMonthOffset = 0;
    var calendarVisibleMonths = 2;

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

    function setCalendarSummary(message) {
        if (!calendarSummary) {
            return;
        }

        calendarSummary.textContent = message;
    }

    function createLocalDate(dateString) {
        var parts = String(dateString || '').split('-');
        return new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    }

    function formatDateKey(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function addDays(dateString, days) {
        var date = createLocalDate(dateString);
        date.setDate(date.getDate() + days);
        return formatDateKey(date);
    }

    function sourcePriority(source) {
        if (source === 'maintenance') {
            return 3;
        }

        if (source === 'availability_block') {
            return 2;
        }

        return 1;
    }

    function sourceLabel(source) {
        if (source === 'maintenance') {
            return 'Maintenance';
        }

        if (source === 'availability_block') {
            return 'Blocked';
        }

        return 'Booked';
    }

    function rangeLabel(range) {
        var label = sourceLabel(range.source);
        var reason = String(range.reason || '').trim();
        return reason ? label + ': ' + reason : label;
    }

    function cacheBlockedDates() {
        blockedDatesByDay = {};

        for (var i = 0; i < blockedRanges.length; i++) {
            var range = blockedRanges[i];
            var currentDate = range.start_date;
            var endDate = range.end_date;
            var safety = 0;

            while (currentDate && endDate && currentDate <= endDate && safety < 730) {
                var existing = blockedDatesByDay[currentDate];
                var nextEntry = {
                    source: range.source,
                    reason: range.reason || '',
                    status: range.status || '',
                    start_date: range.start_date,
                    end_date: range.end_date,
                    label: rangeLabel(range),
                    priority: sourcePriority(range.source)
                };

                if (!existing || nextEntry.priority >= existing.priority) {
                    blockedDatesByDay[currentDate] = nextEntry;
                }

                currentDate = addDays(currentDate, 1);
                safety++;
            }
        }
    }

    function dateRangeConflicts(start, end) {
        for (var i = 0; i < blockedRanges.length; i++) {
            if (start <= blockedRanges[i].end_date && end >= blockedRanges[i].start_date) {
                return blockedRanges[i];
            }
        }

        return null;
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

    function renderCalendar() {
        if (!availabilityCalendar || !calendarMonths) {
            return;
        }

        calendarMonths.innerHTML = '';

        var selectedStart = bookingStart ? bookingStart.value : '';
        var selectedEnd = bookingEnd ? bookingEnd.value : '';
        var now = new Date();

        for (var monthIndex = 0; monthIndex < calendarVisibleMonths; monthIndex++) {
            var monthDate = new Date(now.getFullYear(), now.getMonth() + calendarMonthOffset + monthIndex, 1);
            var monthCard = document.createElement('section');
            monthCard.className = 'calendar-month';

            var monthTitle = document.createElement('h4');
            monthTitle.textContent = monthDate.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
            monthCard.appendChild(monthTitle);

            var weekRow = document.createElement('div');
            weekRow.className = 'calendar-weekdays';

            for (var weekday = 0; weekday < 7; weekday++) {
                var weekdayCell = document.createElement('span');
                weekdayCell.textContent = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][weekday];
                weekRow.appendChild(weekdayCell);
            }

            monthCard.appendChild(weekRow);

            var monthGrid = document.createElement('div');
            monthGrid.className = 'calendar-grid';

            for (var blank = 0; blank < monthDate.getDay(); blank++) {
                var emptyCell = document.createElement('span');
                emptyCell.className = 'calendar-day is-empty';
                monthGrid.appendChild(emptyCell);
            }

            var totalDays = new Date(monthDate.getFullYear(), monthDate.getMonth() + 1, 0).getDate();

            for (var day = 1; day <= totalDays; day++) {
                var dayDate = new Date(monthDate.getFullYear(), monthDate.getMonth(), day);
                var dateKey = formatDateKey(dayDate);
                var dayInfo = blockedDatesByDay[dateKey];
                var dayButton = document.createElement('button');
                var note = document.createElement('small');

                dayButton.type = 'button';
                dayButton.className = 'calendar-day';
                dayButton.setAttribute('data-date', dateKey);

                if (dateKey === formatDateKey(new Date())) {
                    dayButton.className += ' is-today';
                }

                if (dayInfo) {
                    dayButton.className += dayInfo.source === 'booking' ? ' is-booked' : ' is-maintenance';
                    dayButton.title = dayInfo.label + ' (' + dayInfo.start_date + ' to ' + dayInfo.end_date + ')';
                } else {
                    dayButton.className += ' is-available';
                    dayButton.title = 'Available';
                }

                if (selectedStart && dateKey === selectedStart) {
                    dayButton.className += ' is-selected-start';
                }

                if (selectedEnd && dateKey === selectedEnd) {
                    dayButton.className += ' is-selected-end';
                }

                if (selectedStart && selectedEnd && dateKey >= selectedStart && dateKey <= selectedEnd) {
                    dayButton.className += ' is-selected-range';
                }

                dayButton.textContent = String(day);
                note.textContent = dayInfo ? sourceLabel(dayInfo.source) : 'Open';
                dayButton.appendChild(note);

                dayButton.onclick = (function (selectedDate, selectedInfo) {
                    return function () {
                        if (!bookingStart || !bookingEnd || selectedInfo) {
                            return;
                        }

                        if (!bookingStart.value || bookingEnd.value) {
                            bookingStart.value = selectedDate;
                            bookingEnd.value = '';
                            bookingEnd.min = selectedDate;
                            setBookingMessage('Start date selected. Choose an end date.', 'wait');
                        } else if (selectedDate < bookingStart.value) {
                            bookingStart.value = selectedDate;
                            bookingEnd.value = '';
                            bookingEnd.min = selectedDate;
                            setBookingMessage('Start date updated. Choose an end date.', 'wait');
                        } else {
                            bookingEnd.value = selectedDate;
                            validateBookingDates();
                        }

                        renderCalendar();
                    };
                })(dateKey, dayInfo);

                monthGrid.appendChild(dayButton);
            }

            monthCard.appendChild(monthGrid);
            calendarMonths.appendChild(monthCard);
        }
    }

    function validateBookingDates() {
        var start = bookingStart ? bookingStart.value : '';
        var end = bookingEnd ? bookingEnd.value : '';

        if (!bookingForm) {
            renderCalendar();
            return;
        }

        if (start === '' && end === '') {
            if (bookingError) {
                bookingError.textContent = '';
            }
            setBookingMessage('Choose start and end dates. Unavailable days are marked in the calendar.', 'wait');
            if (submitButton) {
                submitButton.disabled = false;
            }
            updateBookingTotal();
            renderCalendar();
            return;
        }

        if (start !== '' && end === '') {
            if (bookingError) {
                bookingError.textContent = '';
            }
            setBookingMessage('Start date selected. Choose an end date.', 'wait');
            if (submitButton) {
                submitButton.disabled = false;
            }
            updateBookingTotal();
            renderCalendar();
            return;
        }

        if (start === '' || end < start) {
            if (bookingError) {
                bookingError.textContent = 'End date must be the same day or later than start date.';
            }
            setBookingMessage('Choose a valid date range.', 'bad');
            if (submitButton) {
                submitButton.disabled = true;
            }
            updateBookingTotal();
            renderCalendar();
            return;
        }

        var conflict = dateRangeConflicts(start, end);
        if (conflict) {
            if (bookingError) {
                bookingError.textContent = 'Vehicle is unavailable for the selected dates.';
            }
            setBookingMessage(rangeLabel(conflict) + ' is already blocking ' + conflict.start_date + ' to ' + conflict.end_date + '.', 'bad');
            if (submitButton) {
                submitButton.disabled = true;
            }
            updateBookingTotal();
            renderCalendar();
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
        renderCalendar();
    }

    function updateCalendarSummary() {
        var blockedDays = 0;
        var bookedDays = 0;

        for (var dateKey in blockedDatesByDay) {
            if (!Object.prototype.hasOwnProperty.call(blockedDatesByDay, dateKey)) {
                continue;
            }

            blockedDays++;
            if (blockedDatesByDay[dateKey].source === 'booking') {
                bookedDays++;
            }
        }

        if (blockedDays === 0) {
            setCalendarSummary('No unavailable dates found right now. Use the next and previous buttons to review nearby months.');
            return;
        }

        setCalendarSummary(bookedDays + ' booked day(s) and ' + (blockedDays - bookedDays) + ' maintenance / blocked day(s) are marked unavailable.');
    }

    function loadBlockedDates() {
        if (!vehicleId) {
            setCalendarSummary('Vehicle id is missing, so the calendar could not be loaded.');
            validateBookingDates();
            return;
        }

        fetch('api/vehicles/booked_dates.php?vehicle_id=' + encodeURIComponent(vehicleId))
            .then(function (res) {
                return res.json();
            })
            .then(function (json) {
                blockedRanges = json && json.success && json.data && json.data.ranges ? json.data.ranges : [];
                cacheBlockedDates();
                updateCalendarSummary();

                if (blockedRanges.length > 0 && bookingForm) {
                    setBookingMessage(blockedRanges.length + ' unavailable range(s) loaded.', 'wait');
                }

                validateBookingDates();
            })
            .catch(function () {
                blockedRanges = [];
                blockedDatesByDay = {};
                setCalendarSummary('Could not load the availability calendar right now. Date checks will still run when booking is submitted.');
                if (bookingForm) {
                    setBookingMessage('Could not load availability. Dates will be checked again on submit.', 'wait');
                }
                validateBookingDates();
            });
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

    if (calendarPrev) {
        calendarPrev.onclick = function () {
            calendarMonthOffset--;
            renderCalendar();
        };
    }

    if (calendarNext) {
        calendarNext.onclick = function () {
            calendarMonthOffset++;
            renderCalendar();
        };
    }

    if (bookingForm) {
        bookingForm.onsubmit = function () {
            validateBookingDates();
            if (submitButton && submitButton.disabled) {
                return false;
            }
            return true;
        };
    }

    renderCalendar();
    loadBlockedDates();
}

var categorySelect = document.querySelector('[data-category-select]');
var typeSelect = document.querySelector('[data-type-select]');

function updateVehicleTypes() {
    if (!categorySelect || !typeSelect) {
        return;
    }

    var selectedCategory = categorySelect.value;
    var firstVisibleValue = '';

    for (var i = 0; i < typeSelect.options.length; i++) {
        var option = typeSelect.options[i];
        var optionCategory = option.getAttribute('data-category');
        var show = option.value === '' || optionCategory === selectedCategory;

        option.hidden = !show;

        if (show && option.value !== '' && firstVisibleValue === '') {
            firstVisibleValue = option.value;
        }
    }

    var currentOption = typeSelect.options[typeSelect.selectedIndex];
    if (currentOption && currentOption.hidden) {
        typeSelect.value = firstVisibleValue;
    }
}

if (categorySelect) {
    categorySelect.onchange = updateVehicleTypes;
    updateVehicleTypes();
}

var searchForm = document.querySelector('[data-search-form]');
if (searchForm) {
    var searchInput = searchForm.querySelector('input[name="search"]');
    var startInput = searchForm.querySelector('input[name="start_date"]');
    var endInput = searchForm.querySelector('input[name="end_date"]');
    var categoryFilter = searchForm.querySelector('select[name="category_id"]');
    var typeFilter = searchForm.querySelector('select[name="type_id"]');
    var minPriceInput = searchForm.querySelector('input[name="min_price"]');
    var maxPriceInput = searchForm.querySelector('input[name="max_price"]');
    var results = document.querySelector('[data-vehicle-results]');
    var countBox = document.querySelector('[data-result-count]');
    var timer = 0;

    function clean(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function card(vehicle) {
        var img = '<div class="image-place">No image</div>';
        if (vehicle.image) {
            var imagePath = String(vehicle.image);
            if (imagePath.indexOf('uploads/') !== 0) {
                imagePath = 'uploads/vehicles/' + imagePath;
            }
            img = '<img src="' + clean(imagePath) + '" alt="">';
        }

        return '' +
            '<article class="vehicle-card">' +
            img +
            '<h3>' + clean(vehicle.name) + '</h3>' +
            '<p class="rating-line">' + clean(ratingText(vehicle.average_rating, vehicle.review_count)) + '</p>' +
            '<p>' + clean(vehicle.company_name) + ' | ' + clean(vehicle.location) + ' | ' + clean(vehicle.category_name) + ' - ' + clean(vehicle.type_name) + '</p>' +
            '<p><b>Rs. ' + Number(vehicle.self_drive_price).toFixed(2) + '</b> self-drive</p>' +
            '<p><b>Rs. ' + Number(vehicle.with_driver_price).toFixed(2) + '</b> with driver</p>' +
            '<a class="btn small" href="vehicle.php?id=' + clean(vehicle.id) + '">View</a>' +
            '</article>';
    }

    function ratingText(averageRating, reviewCount) {
        reviewCount = Number(reviewCount || 0);

        if (reviewCount < 1) {
            return 'No reviews yet';
        }

        return Number(averageRating || 0).toFixed(1) + '/5 from ' + reviewCount + ' review(s)';
    }

    function liveSearch() {
        var value = searchInput.value;
        var start = startInput ? startInput.value : '';
        var end = endInput ? endInput.value : '';
        var category = categoryFilter ? categoryFilter.value : '';
        var type = typeFilter ? typeFilter.value : '';
        var minPrice = minPriceInput ? minPriceInput.value : '';
        var maxPrice = maxPriceInput ? maxPriceInput.value : '';
        var url = 'api.php?action=vehicles&search=' + encodeURIComponent(value) +
            '&start_date=' + encodeURIComponent(start) +
            '&end_date=' + encodeURIComponent(end) +
            '&category_id=' + encodeURIComponent(category) +
            '&type_id=' + encodeURIComponent(type) +
            '&min_price=' + encodeURIComponent(minPrice) +
            '&max_price=' + encodeURIComponent(maxPrice);

        fetch(url)
            .then(function (res) {
                return res.json();
            })
            .then(function (json) {
                var vehicles = json.data.vehicles || [];
                var html = '';
                for (var i = 0; i < vehicles.length; i++) {
                    html += card(vehicles[i]);
                }

                results.innerHTML = html || '<div class="box">No vehicles found.</div>';
                countBox.textContent = vehicles.length + ' vehicle(s) found';
            });
    }

    searchInput.oninput = function () {
        clearTimeout(timer);
        timer = setTimeout(liveSearch, 250);
    };

    if (startInput) {
        startInput.onchange = liveSearch;
    }

    if (endInput) {
        endInput.onchange = liveSearch;
    }

    if (categoryFilter) {
        categoryFilter.onchange = function () {
            updateVehicleTypes();
            liveSearch();
        };
    }

    if (typeFilter) {
        typeFilter.onchange = liveSearch;
    }

    if (minPriceInput) {
        minPriceInput.oninput = function () {
            clearTimeout(timer);
            timer = setTimeout(liveSearch, 250);
        };
    }

    if (maxPriceInput) {
        maxPriceInput.oninput = function () {
            clearTimeout(timer);
            timer = setTimeout(liveSearch, 250);
        };
    }

    searchForm.onsubmit = function (e) {
        e.preventDefault();
        liveSearch();
    };
}

var chatbotWidget = document.querySelector('[data-chatbot-widget]');
if (chatbotWidget) {
    var chatbotPanel = chatbotWidget.querySelector('[data-chatbot-panel]');
    var chatbotToggle = chatbotWidget.querySelector('[data-chatbot-toggle]');
    var chatbotClose = chatbotWidget.querySelector('[data-chatbot-close]');
    var chatbotForm = chatbotWidget.querySelector('[data-chatbot-form]');
    var chatbotMessages = chatbotWidget.querySelector('[data-chatbot-messages]');

    function chatbotEscape(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function addChatMessage(text, sender) {
        var bubble = document.createElement('div');
        bubble.className = 'chat-message ' + sender;
        bubble.innerHTML = chatbotEscape(text);
        chatbotMessages.appendChild(bubble);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function addRecommendationCards(recommendations) {
        if (!recommendations.length) {
            addChatMessage('I could not find a matching available vehicle for those details. Try a wider budget, another date, or a broader location.', 'bot');
            return;
        }

        var wrap = document.createElement('div');
        wrap.className = 'chat-recommendations';

        for (var i = 0; i < recommendations.length; i++) {
            var vehicle = recommendations[i];
            var card = document.createElement('article');
            card.className = 'chat-recommendation';
            card.innerHTML = '' +
                '<strong>' + chatbotEscape(vehicle.name) + '</strong>' +
                '<span>' + chatbotEscape(vehicle.company_name) + ' | ' + chatbotEscape(vehicle.location) + '</span>' +
                '<small>' + chatbotEscape(vehicle.category_name + ' - ' + vehicle.type_name) + '</small>' +
                '<p>' + chatbotEscape(vehicle.explanation) + '</p>' +
                '<a class="btn small" href="' + chatbotEscape(vehicle.url) + '">View vehicle</a>';
            wrap.appendChild(card);
        }

        chatbotMessages.appendChild(wrap);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function setChatbotOpen(open) {
        if (chatbotPanel) {
            chatbotPanel.hidden = !open;
        }
    }

    if (chatbotToggle) {
        chatbotToggle.onclick = function () {
            setChatbotOpen(chatbotPanel.hidden);
        };
    }

    if (chatbotClose) {
        chatbotClose.onclick = function () {
            setChatbotOpen(false);
        };
    }

    if (chatbotForm) {
        chatbotForm.onsubmit = function (event) {
            event.preventDefault();

            var formData = new FormData(chatbotForm);
            var preferences = {};
            formData.forEach(function (value, key) {
                preferences[key] = value;
            });

            addChatMessage(preferences.message || 'Please recommend vehicles for my trip.', 'user');
            addChatMessage('Checking live vehicle availability...', 'bot');

            fetch('api.php?action=vehicle_consultant', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: preferences.message,
                    preferences: preferences
                })
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    if (!json.success) {
                        addChatMessage(json.message || 'Consultant is unavailable right now.', 'bot');
                        return;
                    }

                    addChatMessage(json.data.answer, 'bot');
                    if (json.data.consultant && json.data.consultant.note) {
                        addChatMessage(json.data.consultant.note, 'bot');
                    }
                    addRecommendationCards(json.data.consultant ? json.data.consultant.recommendations : []);
                })
                .catch(function () {
                    addChatMessage('I could not reach the consultant API. Please try again.', 'bot');
                });
        };
    }
}
