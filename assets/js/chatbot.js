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

    function chatbotAvailabilityUrl(preferences) {
        var params = new URLSearchParams();
        params.set('action', 'vehicles');
        params.set('location', preferences.location || '');
        params.set('vehicle_type', preferences.vehicle_type || '');
        params.set('start_date', preferences.start_date || '');
        params.set('end_date', preferences.end_date || '');
        params.set('seats', preferences.seats || '');
        params.set('max_price', preferences.budget || '');
        params.set('driver_preference', preferences.driver_preference || '');

        return 'api.php?' + params.toString();
    }

    function chatbotDateText(preferences) {
        if (preferences.start_date && preferences.end_date) {
            return preferences.start_date + ' to ' + preferences.end_date;
        }

        return 'the selected dates';
    }

    function chatbotDailyPrice(vehicle, driverPreference) {
        if (driverPreference === 'with_driver') {
            return vehicle.with_driver_price;
        }

        return vehicle.self_drive_price;
    }

    function addAvailabilityCards(vehicles, preferences) {
        var wrap = document.createElement('div');
        wrap.className = 'chat-availability';

        for (var i = 0; i < vehicles.length && i < 3; i++) {
            var vehicle = vehicles[i];
            var card = document.createElement('article');
            card.className = 'chat-availability-card';
            card.innerHTML = '' +
                '<span class="availability-pill">Available now</span>' +
                '<strong>' + chatbotEscape(vehicle.name) + '</strong>' +
                '<span>' + chatbotEscape(vehicle.company_name) + ' | ' + chatbotEscape(vehicle.location) + '</span>' +
                '<small>' + chatbotEscape(vehicle.category_name + ' - ' + vehicle.type_name) + '</small>' +
                '<small>' + chatbotEscape((vehicle.seating_capacity || 'N/A') + ' seats | ' + chatbotMoney(chatbotDailyPrice(vehicle, preferences.driver_preference)) + ' per day') + '</small>' +
                '<a class="btn small" href="vehicle.php?id=' + chatbotEscape(vehicle.id) + '">View vehicle</a>';
            wrap.appendChild(card);
        }

        chatbotMessages.appendChild(wrap);
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
                '<small>' + chatbotEscape((vehicle.seating_capacity || 'N/A') + ' seats | ' + chatbotMoney(vehicle.daily_price) + ' per day') + '</small>' +
                '<p>' + chatbotEscape(vehicle.explanation) + '</p>' +
                '<a class="btn small" href="' + chatbotEscape(vehicle.url) + '">View vehicle</a>';
            wrap.appendChild(card);
        }

        chatbotMessages.appendChild(wrap);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    function chatbotQuickAnswer(message) {
        var text = String(message || '').toLowerCase();

        if (text.indexOf('payment') !== -1 || text.indexOf('pay') !== -1 || text.indexOf('stripe') !== -1) {
            return 'Payment help: choose a vehicle, submit the booking dates, then use the payment status page to finish or retry payment.';
        }

        if (text.indexOf('book') !== -1 || text.indexOf('rent') !== -1 || text.indexOf('reservation') !== -1) {
            return 'Booking help: select your dates on a vehicle detail page. The system checks booked and maintenance dates before confirming.';
        }

        if (text.indexOf('account') !== -1 || text.indexOf('login') !== -1 || text.indexOf('register') !== -1 || text.indexOf('otp') !== -1) {
            return 'Account help: register, verify the OTP from email, then log in. Logout clears your browser token and session.';
        }

        if (text.indexOf('vehicle') !== -1 || text.indexOf('available') !== -1 || text.indexOf('car') !== -1 || text.indexOf('bike') !== -1) {
            return 'Vehicle help: I will check the live availability list using your type, budget, location and dates.';
        }

        return '';
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
            var quickAnswer = chatbotQuickAnswer(preferences.message);
            if (quickAnswer) {
                addChatMessage(quickAnswer, 'bot');
            }
            addChatMessage('Checking live vehicle availability...', 'bot');

            fetch(chatbotAvailabilityUrl(preferences))
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    if (!json.success) {
                        addChatMessage(json.message || 'Live availability could not be checked right now.', 'bot');
                        return [];
                    }

                    var vehicles = json.data && json.data.vehicles ? json.data.vehicles : [];
                    addChatMessage('Live availability: ' + vehicles.length + ' vehicle(s) found for ' + chatbotDateText(preferences) + '.', 'bot');

                    if (vehicles.length > 0) {
                        addAvailabilityCards(vehicles, preferences);
                    } else {
                        addChatMessage('No vehicles matched the live filters. Try another date, location, type, or budget.', 'bot');
                    }

                    return vehicles;
                })
                .catch(function () {
                    addChatMessage('I could not reach the live availability API. I will still ask the AI consultant.', 'bot');
                    return [];
                })
                .then(function () {
                    addChatMessage('Asking AI consultant to explain the best matches...', 'bot');

                    return fetch('api/consultant/index.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(preferences)
                    });
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
