<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Air Tracken</title>
    <style>
        /* Basic styling */
        #map {
            height: 600px;
            width: 100%;
        }

        /* Custom style for the plane icons */
        .plane-icon {
            font-size: 20px;
            color: #0073e6;
        }

        .highlighted-plane-icon {
            font-size: 24px;
            color: orange;
        }
    </style>
    <!-- Link to Font Awesome for plane icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>

<body>
    <h1>Live Radar Data using OpenSky</h1>
    <div>
        <label for="callsign">Enter Callsign:</label>
        <input type="text" id="callsign" placeholder="Enter callsign">
        <button onclick="highlightPlane()">Find Plane</button>
    </div>
    <div id="map"></div>

    <!-- Include Leaflet.js for mapping -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <script>
        const username = 'KawsarAhmad43';
        const password = 'password()';

        // Initialize map centered at some coordinates
        const map = L.map('map').setView([20, 0], 2);

        // Load and display tile layers on the map
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Marker group for aircraft
        const aircraftLayer = L.layerGroup().addTo(map);
        let aircraftData = []; // Store aircraft data for lookup

        // Define custom plane icons
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

        // Function to fetch aircraft data
        async function fetchAircraftData() {
            try {
                const response = await fetch('https://opensky-network.org/api/states/all', {
                    headers: {
                        'Authorization': 'Basic ' + btoa(username + ':' + password)
                    }
                });

                if (response.status === 429) {
                    console.warn('Rate limit exceeded. Slowing down request interval.');
                    setTimeout(fetchAircraftData, 120000); // Increase interval on rate limit hit
                    return;
                }

                const data = await response.json();
                aircraftData = data.states; // Store data for highlight lookup
                updateAircraftMarkers(aircraftData);

            } catch (error) {
                console.error('Error fetching data:', error);
            }
        }

        // Function to update aircraft markers
        function updateAircraftMarkers(data) {
            aircraftLayer.clearLayers(); // Clear existing markers

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

        // Highlight the plane with the specified callsign
        function highlightPlane() {
            const callsignInput = document.getElementById('callsign').value.trim().toUpperCase();
            const plane = aircraftData.find(state => state[1]?.trim() === callsignInput);

            if (plane && plane[6] !== null && plane[5] !== null) {
                const [icao24, callsign, originCountry, timePosition, lastContact, lon, lat, baroAltitude, onGround] = plane
                ;

                // Clear previous markers
                aircraftLayer.clearLayers();
                updateAircraftMarkers(aircraftData);

                // Highlight specific plane marker
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

                highlightedMarker.openPopup(); // Open popup for the highlighted marker
                map.setView([lat, lon], 6); // Zoom in to the plane's location
            } else {
                alert('Plane with that callsign not found.');
            }
        }

        // Fetch data at an interval
        setInterval(fetchAircraftData, 60000); // Fetch data every 60 seconds initially
        fetchAircraftData(); // Initial fetch on page load
    </script>
</body>

</html>
