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
if (bookingForm) {
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
                validateBookingDates();
            })
            .catch(function () {
                setBookingMessage('Could not load availability. Dates will be checked again on submit.', 'wait');
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

    bookingForm.onsubmit = function () {
        validateBookingDates();
        if (submitButton && submitButton.disabled) {
            return false;
        }
        return true;
    };

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

    function chatbotMoney(amount) {
        return 'Rs. ' + Number(amount || 0).toFixed(2);
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
                '<small>' + chatbotEscape((vehicle.seating_capacity || 'N/A') + ' seats | ' + chatbotMoney(vehicle.daily_price) + ' per day') + '</small>' +
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
            addChatMessage('Checking vehicle filters and asking the AI consultant...', 'bot');

            fetch('api/consultant/index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(preferences)
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
                    if (json.data.missing_fields && json.data.missing_fields.length) {
                        addChatMessage('Missing: ' + json.data.missing_fields.join(', '), 'bot');
                    }
                    addRecommendationCards(json.data.recommendations || []);
                })
                .catch(function () {
                    addChatMessage('I could not reach the consultant API. Please try again.', 'bot');
                });
        };
    }
}
