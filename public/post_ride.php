<?php
require_once '../includes/auth_check.php';
require_once '../includes/db_connection.php';
include '../includes/header_private.php';

$user_id = $_SESSION['user_id'];
$user_role = '';
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $user_role = $user['role'] ?? 'user';
    if ($user_role !== 'driver') {
        header('Location: dashboard.php');
        exit;
    }
} catch (Exception $e) {
    $erro = "Não foi possível verificar o seu perfil de motorista.";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Publicar uma Viagem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />

    <!-- Leaflet CSS e JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
</head>
<body class="bg-gray-50 font-sans">

<section class="form-container max-w-3xl mx-auto px-4 py-8 bg-white shadow-lg rounded-lg mt-8 mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Publicar uma Nova Viagem</h2>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-error bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-sm">
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <form action="post_ride_process.php" method="POST" class="post-ride-form space-y-6">
        <fieldset class="border-t border-gray-200 pt-6">
            <legend class="text-lg font-semibold text-gray-700 mb-4">Detalhes da Viagem</legend>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="form-group">
                    <label for="pickup_location" class="block text-sm font-medium text-gray-600 mb-1">Local de Partida</label>
                    <input type="text" id="pickup_location" name="pickup_location" placeholder="Digite o local de partida" required class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent" />
                </div>

                <div class="form-group">
                    <label for="pickup_time" class="block text-sm font-medium text-gray-600 mb-1">Hora de Partida</label>
                    <input type="datetime-local" id="pickup_time" name="pickup_time" required class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent" />
                </div>

                <div class="form-group">
                    <label for="destination" class="block text-sm font-medium text-gray-600 mb-1">Destino</label>
                    <input type="text" id="destination" name="destination" placeholder="Digite o destino" required class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent" />
                </div>
            </div>

            <div class="form-group">
                <label for="ride_type" class="block text-sm font-medium text-gray-600 mb-1">Tipo de Viagem</label>
                <select id="ride_type" name="ride_type" required class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent">
                    <option value="drop">Ida</option>
                    <option value="pick">Volta</option>
                    <option value="roundtrip">Ida e Volta</option>
                </select>
            </div>

            <!-- Mapa -->
            <div id="rideMap" style="height: 400px; margin-top: 1rem;"></div>

            <!-- Campos ocultos para enviar latitude/longitude -->
            <input type="hidden" name="pickup_lat" id="pickup_lat">
            <input type="hidden" name="pickup_lon" id="pickup_lon">
            <input type="hidden" name="destination_lat" id="destination_lat">
            <input type="hidden" name="destination_lon" id="destination_lon">
        </fieldset>

        <button type="submit" class="mt-6 bg-blue-600 text-white font-semibold py-3 px-6 rounded hover:bg-blue-700 transition duration-200 text-sm w-full md:w-auto">
            Publicar Viagem
        </button>
    </form>
</section>

<script>
let map = L.map('rideMap').setView([0, 0], 2);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let markers = [];
let routeControl = null;

function addMarker(lat, lon, label) {
    markers = markers.filter(m => {
        if (m.options.customLabel === label) {
            map.removeLayer(m);
            return false;
        }
        return true;
    });

    let marker = L.marker([lat, lon], { customLabel: label }).addTo(map);
    marker.bindPopup(label);
    markers.push(marker);

    if (markers.length > 0) {
        let group = new L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.2));
    }

    if (markers.length === 2) drawRoute();
}

function drawRoute() {
    let partida = markers.find(m => m.options.customLabel === "Partida");
    let destino = markers.find(m => m.options.customLabel === "Destino");

    if (!partida || !destino) return;

    if (routeControl) map.removeControl(routeControl);

    routeControl = L.Routing.control({
        waypoints: [
            L.latLng(partida.getLatLng().lat, partida.getLatLng().lng),
            L.latLng(destino.getLatLng().lat, destino.getLatLng().lng)
        ],
        lineOptions: {
            addWaypoints: false,
            draggableWaypoints: false,
            extendToWaypoints: false
        },
        routeWhileDragging: false,
        showAlternatives: false,
        createMarker: () => null
    }).addTo(map);
}

async function geocode(address, label) {
    if (!address) return;
    try {
        let response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`);
        let data = await response.json();

        if (data && data.length > 0) {
            let { lat, lon } = data[0];
            addMarker(lat, lon, label);

            if (label === 'Partida') {
                document.getElementById('pickup_lat').value = lat;
                document.getElementById('pickup_lon').value = lon;
            } else if (label === 'Destino') {
                document.getElementById('destination_lat').value = lat;
                document.getElementById('destination_lon').value = lon;
            }
        }
    } catch (err) {
        console.error("Erro ao buscar endereço:", err);
    }
}

document.getElementById('pickup_location').addEventListener('blur', function () {
    geocode(this.value, 'Partida');
});

document.getElementById('destination').addEventListener('blur', function () {
    geocode(this.value, 'Destino');
});
</script>
</body>
</html>
