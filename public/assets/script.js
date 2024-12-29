
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

