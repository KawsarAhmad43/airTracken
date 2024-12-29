<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Air Tracken</title>
    <style>
        #map {
            height: 600px;
            width: 100%;
        }

        .plane-icon {
            font-size: 20px;
            color: #0073e6;
        }

        .highlighted-plane-icon {
            font-size: 24px;
            color: orange;
        }
    </style>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>

<body>
    <div class="container-fluid mt-5 p-5">
        <h1 class="text-center mb-4">Hello! Welcome Alex Jonathan Mark</h1>
        <div class="row">
            <!-- Left Column: Form -->
            <div class="col-md-7">
                <form>
                    <label for="from">Track ID:</label>
                    <div class="form-group d-flex align-items-center">

                        <input type="text" id="callsign" class="form-control mr-2" placeholder="CA-x5T5VVB786uv">
                        <button type="button" class="btn btn-primary" onclick="highlightPlane()">Track</button>
                    </div>
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" class="form-control" value="Alex Jonathan Mark" readonly>
                    </div>
                    <div class="form-group">
                        <label for="from">From:</label>
                        <input type="text" id="from" class="form-control" value="Bahamas" readonly>
                    </div>
                    <div class="form-group">
                        <label for="to">To:</label>
                        <input type="text" id="to" class="form-control" value="Florida" readonly>
                    </div>
                    <div class="form-group">
                        <label for="details">Flight Details:</label>
                        <textarea id="details" class="form-control" rows="4"></textarea>
                    </div>

                </form>
            </div>

            <!-- Right Column: Map -->
            <div class="col-md-5">
                <div id="map"></div>
                <!-- Progress Bar Section -->
                <div class="progress-bar-section mt-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Started: <strong>Miami</strong></span>
                        <i class="fas fa-plane plane-icon"></i>
                        <span>Destination: <strong>Florida</strong></span>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div id="flight-progress" class="progress-bar bg-primary" role="progressbar" style="width: 50%;"
                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-center mt-2">
                        <strong>Estimated Time to Reach:</strong> <span id="time-to-reach">4:38:10s</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Leaflet JS and Bootstrap JS Dependencies -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const username = 'KawsarAhmad43';
        const password = 'password()';

        const map = L.map('map').setView([20, 0], 2);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        const aircraftLayer = L.layerGroup().addTo(map);
        let aircraftData = [];

        const planeIcon = L.divIcon({
            html: '<i class="fas fa-plane plane-icon"></i>',
            iconSize: [20, 20],
            className: 'custom-plane-icon'
        });

        const highlightedPlaneIcon = L.divIcon({
            html: '<i class="fas fa-plane highlighted-plane-icon"></i>',
            iconSize: [24, 24],
            className: 'custom-highlighted-plane-icon'
        });

        async function fetchAircraftData() {
            try {
                const response = await fetch('https://opensky-network.org/api/states/all', {
                    headers: {
                        'Authorization': 'Basic ' + btoa(username + ':' + password)
                    }
                });

                if (response.status === 429) {
                    console.warn('Rate limit exceeded. Slowing down request interval.');
                    setTimeout(fetchAircraftData, 120000);
                    return;
                }

                const data = await response.json();
                aircraftData = data.states;
                updateAircraftMarkers(aircraftData);

            } catch (error) {
                console.error('Error fetching data:', error);
            }
        }

        function updateAircraftMarkers(data) {
            aircraftLayer.clearLayers();
            if (!data) return;

            data.forEach(state => {
                const [icao24, callsign, originCountry, timePosition, lastContact, lon, lat, baroAltitude,
                    onGround
                ] = state;

                if (lat !== null && lon !== null) {
                    L.marker([lat, lon], {
                            icon: planeIcon
                        })
                        .bindPopup(`
                            <strong>ICAO24:</strong> ${icao24}<br>
                            <strong>Callsign:</strong> ${callsign}<br>
                            <strong>Country:</strong> ${originCountry}<br>
                            <strong>Altitude:</strong> ${baroAltitude ? baroAltitude.toFixed(2) + ' m' : 'N/A'}<br>
                            <strong>Status:</strong> ${onGround ? 'On Ground' : 'In Air'}
                        `)
                        .addTo(aircraftLayer);
                }
            });
        }

        function highlightPlane() {
            const callsignInput = document.getElementById('callsign').value.trim().toUpperCase();
            const plane = aircraftData.find(state => state[1]?.trim() === callsignInput);

            if (plane && plane[6] !== null && plane[5] !== null) {
                const [icao24, callsign, originCountry, timePosition, lastContact, lon, lat, baroAltitude, onGround] = plane
                ;

                aircraftLayer.clearLayers();
                updateAircraftMarkers(aircraftData);

                const highlightedMarker = L.marker([lat, lon], {
                        icon: highlightedPlaneIcon
                    })
                    .bindPopup(`
                        <strong>ICAO24:</strong> ${icao24}<br>
                        <strong>Callsign:</strong> ${callsign}<br>
                        <strong>Country:</strong> ${originCountry}<br>
                        <strong>Altitude:</strong> ${baroAltitude ? baroAltitude.toFixed(2) + ' m' : 'N/A'}<br>
                        <strong>Status:</strong> ${onGround ? 'On Ground' : 'In Air'}
                    `)
                    .addTo(aircraftLayer);

                highlightedMarker.openPopup();
                map.setView([lat, lon], 6);
            } else {
                alert('Plane with that callsign not found.');
            }
        }

        setInterval(fetchAircraftData, 60000);
        fetchAircraftData();

        function updateProgressBar(progressPercentage, timeRemaining) {
            const progressBar = document.getElementById('flight-progress');
            const timeToReach = document.getElementById('time-to-reach');

            // Update progress bar width
            progressBar.style.width = `${progressPercentage}%`;
            progressBar.setAttribute('aria-valuenow', progressPercentage);

            // Update time remaining
            timeToReach.textContent = timeRemaining;
        }

        // Example: Update progress to 70% with 2 hours remaining
        updateProgressBar(70, '2:15:30s');
    </script>
</body>

</html>
