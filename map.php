<?php
session_start();
require_once 'config.db.php';

$disasters = $pdo->query("SELECT * FROM disasters ORDER BY created_at DESC")->fetchAll();
$centers = $pdo->query("SELECT * FROM evacuation_centers")->fetchAll();
$routes = $pdo->query("SELECT er.*, d.title as disaster_title FROM evacuation_routes er LEFT JOIN disasters d ON er.disaster_id = d.id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Map — RescuerNet AI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="map.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-brand">
        <div class="brand-icon">🛡️</div>
        <div>
            <span class="brand-name">RescuerNet AI</span>
            <span class="brand-sub">ASEAN Disaster Response</span>
        </div>
    </div>
    <div class="nav-links">
        <a href="index.php"><i class="fas fa-gauge"></i> Dashboard</a>
        <a href="map.php" class="active"><i class="fas fa-map"></i> Live Map</a>
        <a href="prediction.php"><i class="fas fa-brain"></i> AI Predict</a>
        <a href="routes.php"><i class="fas fa-route"></i> Routes</a>
        <a href="centers.php"><i class="fas fa-hospital"></i> Centers</a>
    </div>
    <div class="nav-status">
        <span class="status-dot pulse"></span>
        <span>Live Monitoring</span>
    </div>
</nav>

<div class="map-page">

    <!-- Left Panel -->
    <div class="map-sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-layer-group"></i> Map Layers</h2>
        </div>

        <!-- Layer Toggles -->
        <div class="layer-section">
            <div class="layer-toggle active" onclick="toggleLayer('disasters')" id="toggle-disasters">
                <span class="layer-dot red"></span>
                <span>Active Disasters</span>
                <span class="layer-count"><?= count($disasters) ?></span>
            </div>
            <div class="layer-toggle active" onclick="toggleLayer('centers')" id="toggle-centers">
                <span class="layer-dot green"></span>
                <span>Evacuation Centers</span>
                <span class="layer-count"><?= count($centers) ?></span>
            </div>
            <div class="layer-toggle active" onclick="toggleLayer('routes')" id="toggle-routes">
                <span class="layer-dot blue"></span>
                <span>Evacuation Routes</span>
                <span class="layer-count"><?= count($routes) ?></span>
            </div>
        </div>

        <!-- Disaster List -->
        <div class="sidebar-section">
            <h3>Active Disasters</h3>
            <?php foreach($disasters as $d): ?>
            <div class="sidebar-item <?= $d['severity'] ?>" onclick="flyToMarker(<?= $d['latitude'] ?>, <?= $d['longitude'] ?>)">
                <div class="item-icon">
                    <?= $d['type'] === 'flood' ? '🌊' : ($d['type'] === 'typhoon' ? '🌀' : '🌋') ?>
                </div>
                <div class="item-info">
                    <span class="item-title"><?= htmlspecialchars($d['title']) ?></span>
                    <span class="item-meta"><?= $d['country'] ?> · <?= $d['region'] ?></span>
                </div>
                <span class="badge <?= $d['severity'] ?>"><?= strtoupper($d['severity']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Centers List -->
        <div class="sidebar-section">
            <h3>Evacuation Centers</h3>
            <?php foreach($centers as $c): ?>
            <div class="sidebar-item" onclick="flyToMarker(<?= $c['latitude'] ?>, <?= $c['longitude'] ?>)">
                <div class="item-icon">🏠</div>
                <div class="item-info">
                    <span class="item-title"><?= htmlspecialchars($c['name']) ?></span>
                    <span class="item-meta"><?= $c['current_occupancy'] ?>/<?= $c['capacity'] ?> capacity</span>
                </div>
                <span class="badge <?= $c['status'] === 'open' ? 'safe' : 'critical' ?>"><?= strtoupper($c['status']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Map Container -->
    <div class="map-main">
        <!-- Map Controls -->
        <div class="map-controls">
            <button class="map-btn active" onclick="setMapStyle('street')" id="btn-street">
                <i class="fas fa-map"></i> Street
            </button>
            <button class="map-btn" onclick="setMapStyle('satellite')" id="btn-satellite">
                <i class="fas fa-satellite"></i> Satellite
            </button>
            <button class="map-btn" onclick="setMapStyle('dark')" id="btn-dark">
                <i class="fas fa-moon"></i> Dark
            </button>
        </div>

        <!-- Legend -->
        <div class="map-legend">
            <div class="legend-title">Legend</div>
            <div class="legend-item"><span class="legend-dot" style="background:#ff3b3b"></span> Critical</div>
            <div class="legend-item"><span class="legend-dot" style="background:#ff9500"></span> High</div>
            <div class="legend-item"><span class="legend-dot" style="background:#ffd60a"></span> Medium</div>
            <div class="legend-item"><span class="legend-dot" style="background:#34c759"></span> Evacuation Center</div>
        </div>

        <div id="full-map"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ---- Map Init ----
const map = L.map('full-map', { zoomControl: false }).setView([10.0, 115.0], 5);
L.control.zoom({ position: 'bottomright' }).addTo(map);

// Tile Layers
const tileLayers = {
    street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: '© Esri' }),
    dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '© CartoDB' })
};
tileLayers.street.addTo(map);

function setMapStyle(style) {
    Object.values(tileLayers).forEach(l => map.removeLayer(l));
    tileLayers[style].addTo(map);
    document.querySelectorAll('.map-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn-' + style).classList.add('active');
}

// ---- Marker Groups ----
const disasterGroup = L.layerGroup().addTo(map);
const centerGroup = L.layerGroup().addTo(map);
const routeGroup = L.layerGroup().addTo(map);

// ---- Disaster Markers ----
const disasters = <?= json_encode($disasters) ?>;
disasters.forEach(d => {
    const colors = { critical: '#ff3b3b', high: '#ff9500', medium: '#ffd60a', low: '#34c759' };
    const color = colors[d.severity] || '#ff9500';
    const size = d.severity === 'critical' ? 22 : d.severity === 'high' ? 18 : 14;

    const icon = L.divIcon({
        html: `<div style="
            background:${color};
            width:${size}px;height:${size}px;
            border-radius:50%;
            border:3px solid white;
            box-shadow:0 0 10px ${color};
            animation: pulse-marker 1.5s infinite;
        "></div>`,
        iconSize: [size, size],
        className: ''
    });

    const marker = L.marker([d.latitude, d.longitude], { icon })
        .bindPopup(`
            <div style="min-width:220px;font-family:sans-serif;">
                <div style="background:${color};color:white;padding:8px 12px;margin:-12px -12px 10px;border-radius:4px 4px 0 0;font-weight:700;">
                    ${d.type === 'flood' ? '🌊' : d.type === 'typhoon' ? '🌀' : '🌋'} ${d.title}
                </div>
                <div style="padding:0 4px;">
                    <p><b>Country:</b> ${d.country}</p>
                    <p><b>Region:</b> ${d.region}</p>
                    <p><b>Severity:</b> <span style="color:${color};font-weight:700;">${d.severity.toUpperCase()}</span></p>
                    <p><b>Status:</b> ${d.status.toUpperCase()}</p>
                    <p><b>Coordinates:</b> ${d.latitude}, ${d.longitude}</p>
                </div>
            </div>
        `, { maxWidth: 280 })
        .addTo(disasterGroup);
});

// ---- Evacuation Center Markers ----
const centers = <?= json_encode($centers) ?>;
centers.forEach(c => {
    const pct = Math.round((c.current_occupancy / c.capacity) * 100);
    const color = pct > 80 ? '#ff9500' : '#34c759';

    const icon = L.divIcon({
        html: `<div style="
            background:${color};
            color:white;
            width:28px;height:28px;
            border-radius:6px;
            border:2px solid white;
            display:flex;align-items:center;justify-content:center;
            font-size:14px;
            box-shadow:0 2px 8px rgba(0,0,0,0.4);
        ">🏠</div>`,
        iconSize: [28, 28],
        className: ''
    });

    L.marker([c.latitude, c.longitude], { icon })
        .bindPopup(`
            <div style="min-width:200px;font-family:sans-serif;">
                <div style="background:#1a3a5c;color:white;padding:8px 12px;margin:-12px -12px 10px;border-radius:4px 4px 0 0;font-weight:700;">
                    🏠 ${c.name}
                </div>
                <div style="padding:0 4px;">
                    <p><b>Country:</b> ${c.country}</p>
                    <p><b>Capacity:</b> ${c.current_occupancy.toLocaleString()} / ${c.capacity.toLocaleString()}</p>
                    <div style="background:#eee;border-radius:4px;height:8px;margin:6px 0;">
                        <div style="background:${color};width:${pct}%;height:100%;border-radius:4px;"></div>
                    </div>
                    <p style="color:${color};font-weight:700;">${pct}% Full</p>
                    <p><b>Status:</b> ${c.status.toUpperCase()}</p>
                </div>
            </div>
        `, { maxWidth: 260 })
        .addTo(centerGroup);
});

// ---- Layer Toggles ----
const layers = { disasters: disasterGroup, centers: centerGroup, routes: routeGroup };
function toggleLayer(name) {
    const btn = document.getElementById('toggle-' + name);
    if (map.hasLayer(layers[name])) {
        map.removeLayer(layers[name]);
        btn.classList.remove('active');
    } else {
        map.addLayer(layers[name]);
        btn.classList.add('active');
    }
}

// ---- Fly To ----
function flyToMarker(lat, lng) {
    map.flyTo([lat, lng], 10, { duration: 1.5 });
}
</script>
</body>
</html>