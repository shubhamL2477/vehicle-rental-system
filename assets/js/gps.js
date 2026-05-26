var gpsTracker = document.querySelector('[data-gps-tracker]');
if (gpsTracker) {
    var baseLat = Number(gpsTracker.getAttribute('data-lat'));
    var baseLng = Number(gpsTracker.getAttribute('data-lng'));

    if (!Number.isNaN(baseLat) && !Number.isNaN(baseLng)) {
        setInterval(function () {
            var lat = baseLat + ((Math.random() - 0.5) / 1000);
            var lng = baseLng + ((Math.random() - 0.5) / 1000);
            gpsTracker.innerHTML = '<b>GPS</b>' + lat.toFixed(7) + ', ' + lng.toFixed(7) + ' <small>mock live</small>';
        }, 3500);
    }
}

