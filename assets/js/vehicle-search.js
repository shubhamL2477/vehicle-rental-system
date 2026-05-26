var searchForm = document.querySelector('[data-search-form]');
if (searchForm) {
    var searchInput = searchForm.querySelector('input[name="search"]');
    var startInput = searchForm.querySelector('input[name="start_date"]');
    var endInput = searchForm.querySelector('input[name="end_date"]');
    var categoryFilter = searchForm.querySelector('select[name="category_id"]');
    var typeFilter = searchForm.querySelector('select[name="type_id"]');
    var minPriceInput = searchForm.querySelector('input[name="min_price"]');
    var maxPriceInput = searchForm.querySelector('input[name="max_price"]');
    var sortFilter = searchForm.querySelector('select[name="sort"]');
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

    function vehicleImageSrc(image) {
        var imagePath = String(image || '').replace(/\\/g, '/');

        if (
            imagePath.indexOf('uploads/') === 0 ||
            imagePath.indexOf('assets/images/') === 0 ||
            imagePath.indexOf('http://') === 0 ||
            imagePath.indexOf('https://') === 0
        ) {
            return imagePath;
        }

        return 'uploads/vehicles/' + imagePath;
    }

    function card(vehicle) {
        var img = '<div class="image-place">Vehicle image coming soon</div>';
        if (vehicle.image) {
            img = '<img src="' + clean(vehicleImageSrc(vehicle.image)) + '" alt="">';
        }

        var seats = vehicle.seating_capacity || 'N/A';
        var availability = vehicle.availability || vehicle.status || 'available';

        return '' +
            '<article class="vehicle-card">' +
            '<div class="vehicle-media">' +
            img +
            '<span class="vehicle-chip">' + clean(vehicle.category_name) + '</span>' +
            '<span class="vehicle-status-chip badge ' + (availability === 'available' ? 'good' : 'wait') + '">' + clean(availability) + '</span>' +
            '</div>' +
            '<div class="vehicle-card-body">' +
            '<div class="card-line vehicle-title-row">' +
            '<div><h3>' + clean(vehicle.name) + '</h3><p class="muted">' + clean(vehicle.company_name) + '</p></div>' +
            ratingHtml(vehicle.average_rating, vehicle.review_count) +
            '</div>' +
            '<div class="vehicle-specs">' +
            '<span>' + clean(seats) + ' seats</span>' +
            '<span>' + clean(vehicle.type_name) + '</span>' +
            '<span>' + clean(vehicle.location) + '</span>' +
            '<span>' + clean(availability) + '</span>' +
            '<span>' + clean(vehicle.rental_count || 0) + ' rental(s)</span>' +
            '</div>' +
            '<div class="vehicle-card-footer">' +
            '<p><b>Rs. ' + Number(vehicle.self_drive_price).toFixed(2) + '</b><span>/day self-drive</span></p>' +
            '<p><b>Rs. ' + Number(vehicle.with_driver_price).toFixed(2) + '</b><span>/day with driver</span></p>' +
            '<a class="btn small light" href="vehicle.php?id=' + clean(vehicle.id) + '">View details</a>' +
            '</div>' +
            '</div>' +
            '</article>';
    }

    function ratingHtml(averageRating, reviewCount) {
        reviewCount = Number(reviewCount || 0);

        if (reviewCount < 1) {
            return '<p class="rating-line"><span class="rating-stars empty" aria-hidden="true">&#9734;&#9734;&#9734;&#9734;&#9734;</span> No reviews yet</p>';
        }

        var rounded = Math.max(1, Math.min(5, Math.round(Number(averageRating || 0))));
        var stars = '';
        for (var i = 1; i <= 5; i++) {
            stars += i <= rounded ? '&#9733;' : '&#9734;';
        }

        return '<p class="rating-line"><span class="rating-stars" aria-hidden="true">' + stars + '</span> ' + Number(averageRating || 0).toFixed(1) + '/5 from ' + reviewCount + ' review(s)</p>';
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
        var sort = sortFilter ? sortFilter.value : 'latest';
        var url = 'api.php?action=vehicles&search=' + encodeURIComponent(value) +
            '&start_date=' + encodeURIComponent(start) +
            '&end_date=' + encodeURIComponent(end) +
            '&category_id=' + encodeURIComponent(category) +
            '&type_id=' + encodeURIComponent(type) +
            '&min_price=' + encodeURIComponent(minPrice) +
            '&max_price=' + encodeURIComponent(maxPrice) +
            '&sort=' + encodeURIComponent(sort);

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

    if (sortFilter) {
        sortFilter.onchange = liveSearch;
    }

    searchForm.onsubmit = function (e) {
        e.preventDefault();
        liveSearch();
    };
}

