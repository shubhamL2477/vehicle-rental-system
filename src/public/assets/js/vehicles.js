var vehicleSearchForm = document.querySelector('[data-vehicle-search-form]');

if (vehicleSearchForm) {
    var resultsBox = document.querySelector('[data-vehicle-results]');
    var summaryBox = document.querySelector('[data-vehicle-summary]');
    var loadingBox = document.querySelector('[data-vehicle-loading]');
    var apiUrl = vehicleSearchForm.getAttribute('data-api-url') || '';
    var vehicleUrl = vehicleSearchForm.getAttribute('data-vehicle-url') || '';
    var companyUrl = vehicleSearchForm.getAttribute('data-company-url') || '';
    var searchInput = vehicleSearchForm.querySelector('input[name="search"]');
    var locationInput = vehicleSearchForm.querySelector('input[name="location"]');
    var selectInputs = vehicleSearchForm.querySelectorAll('select');
    var searchTimer = 0;
    var lastRequest = 0;

    function badgeClass(status) {
        if (status === 'available' || status === 'confirmed' || status === 'approved') {
            return 'badge badge-success';
        }

        if (status === 'pending') {
            return 'badge badge-warning';
        }

        if (status === 'maintenance' || status === 'cancelled' || status === 'inactive' || status === 'rejected') {
            return 'badge badge-danger';
        }

        return 'badge badge-neutral';
    }

    function escapeHtml(value) {
        value = String(value || '');
        value = value.replace(/&/g, '&amp;');
        value = value.replace(/</g, '&lt;');
        value = value.replace(/>/g, '&gt;');
        value = value.replace(/"/g, '&quot;');
        value = value.replace(/'/g, '&#039;');
        return value;
    }

    function formatMoney(value) {
        return 'Rs. ' + Number(value || 0).toFixed(2);
    }

    function buildVehicleCard(vehicle) {
        var imageHtml = '<div class="listing-image placeholder">No image uploaded</div>';

        if (vehicle.image_url) {
            imageHtml = '<img src="' + escapeHtml(vehicle.image_url) + '" alt="' + escapeHtml(vehicle.name) + '" class="listing-image">';
        }

        return '' +
            '<article class="listing-card">' +
                imageHtml +
                '<div class="listing-content">' +
                    '<div class="card-topline">' +
                        '<span class="' + badgeClass(vehicle.status) + '">' + escapeHtml(vehicle.status.charAt(0).toUpperCase() + vehicle.status.slice(1)) + '</span>' +
                        '<span class="muted">' + escapeHtml(vehicle.type.charAt(0).toUpperCase() + vehicle.type.slice(1)) + '</span>' +
                    '</div>' +
                    '<h2>' + escapeHtml(vehicle.name) + '</h2>' +
                    '<p>' + escapeHtml(vehicle.company_name) + ' - ' + escapeHtml(vehicle.location) + '</p>' +
                    '<div class="price-row">' +
                        '<strong>' + formatMoney(vehicle.price_per_day) + '/day</strong>' +
                        '<span>Driver: ' + formatMoney(vehicle.driver_price_per_day) + '/day</span>' +
                    '</div>' +
                    '<div class="card-actions">' +
                        '<a class="button button-primary" href="' + escapeHtml(vehicleUrl) + '?id=' + vehicle.id + '">Open Listing</a>' +
                        '<a class="button button-secondary" href="' + escapeHtml(companyUrl) + '?id=' + vehicle.company_id + '">Company</a>' +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function buildEmptyState() {
        return '' +
            '<div class="empty-state">' +
                '<h3>No vehicles match your filters</h3>' +
                '<p>Try another search word or remove one filter.</p>' +
            '</div>';
    }

    function collectParams() {
        var formData = new FormData(vehicleSearchForm);
        var params = new URLSearchParams();

        formData.forEach(function (value, key) {
            value = String(value).trim();

            if (value !== '') {
                params.set(key, value);
            }
        });

        return params;
    }

    function updateUrl(params) {
        var queryString = params.toString();
        var nextUrl = window.location.pathname;

        if (queryString !== '') {
            nextUrl = nextUrl + '?' + queryString;
        }

        window.history.replaceState({}, '', nextUrl);
    }

    function renderVehicles(vehicles) {
        var html = '';

        if (!resultsBox) {
            return;
        }

        if (!vehicles.length) {
            resultsBox.innerHTML = buildEmptyState();
            return;
        }

        for (var i = 0; i < vehicles.length; i++) {
            html += buildVehicleCard(vehicles[i]);
        }

        resultsBox.innerHTML = html;
    }

    function fetchVehicles() {
        if (!apiUrl) {
            return;
        }

        var params = collectParams();
        var requestId = lastRequest + 1;
        lastRequest = requestId;

        updateUrl(params);

        if (loadingBox) {
            loadingBox.hidden = false;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('GET', apiUrl + '?' + params.toString(), true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function () {
            if (xhr.readyState !== 4) {
                return;
            }

            if (requestId !== lastRequest) {
                return;
            }

            if (loadingBox) {
                loadingBox.hidden = true;
            }

            if (xhr.status >= 200 && xhr.status < 300) {
                var result = {};
                var vehicles = [];

                try {
                    result = JSON.parse(xhr.responseText);
                } catch (e) {
                    result = {};
                }

                if (result.data && Array.isArray(result.data.vehicles)) {
                    vehicles = result.data.vehicles;
                }

                renderVehicles(vehicles);

                if (summaryBox) {
                    summaryBox.textContent = vehicles.length + ' vehicle(s) found.';
                }

                return;
            }

            if (summaryBox) {
                summaryBox.textContent = 'Could not load vehicles right now.';
            }
        };

        xhr.send();
    }

    function scheduleFetch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(fetchVehicles, 250);
    }

    if (searchInput) {
        searchInput.oninput = scheduleFetch;
    }

    if (locationInput) {
        locationInput.oninput = scheduleFetch;
    }

    for (var i = 0; i < selectInputs.length; i++) {
        selectInputs[i].onchange = fetchVehicles;
    }

    vehicleSearchForm.onsubmit = function (event) {
        event.preventDefault();
        fetchVehicles();
    };
}
