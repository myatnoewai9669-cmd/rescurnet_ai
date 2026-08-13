<?php
session_start();
require_once 'config.db.php';

$routes = $pdo->query("
    SELECT er.*, d.title as disaster_title, d.type as disaster_type, d.severity 
    FROM evacuation_routes er 
    LEFT JOIN disasters d ON er.disaster_id = d.id 
    ORDER BY er.created_at DESC
")->fetchAll();

$disasters = $pdo->query("SELECT id, title, country FROM disasters WHERE status = 'active'")->fetchAll();

// Add new route
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO evacuation_routes 
        (disaster_id, route_name, origin_lat, origin_lng, destination_name, destination_lat, destination_lng, distance_km, estimated_time_min, status) 
        VALUES (?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['disaster_id'],
        $_POST['route_name'],
        $_POST['origin_lat'],
        $_POST['origin_lng'],
        $_POST['destination_name'],
        $_POST['destination_lat'],
        $_POST['destination_lng'],
        $_POST['distance_km'],
        $_POST['estimated_time_min'],
        $_POST['status']
    ]);
    header('Location: routes.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evacuation Routes — RescuerNet AI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .routes-layout { display: grid; grid-template-columns: 1fr 380px; gap: 20px; padding: 24px; max-width: 1300px; margin: 0 auto; }
        #route-map { height: 420px; border-radius: 0 0 12px 12px; }
        .routes-list { display: flex; flex-direction: column; gap: 10px; padding: 16px; }
        .route-item { background: var(--bg-card2); border-radius: 10px; padding: 14px; border-left: 4px solid; cursor: pointer; transition: background 0.2s; }
        .route-item:hover { background: var(--bg); }
        .route-item.open { border-left-color: #34c759; }
        .route-item.congested { border-left-color: #ff9500; }
        .route-item.closed { border-left-color: #ff3b3b; }
        .route-name { font-weight: 600; font-size: 0.92rem; margin-bottom: 6px; }
        .route-meta { font-size: 0.78rem; color: var(--text-muted); display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 8px; }
        .route-meta span { display: flex; align-items: center; gap: 4px; }
        .status-open { color: #34c759; font-weight: 700; font-size: 0.75rem; }
        .status-congested { color: #ff9500; font-weight: 700; font-size: 0.75rem; }
        .status-closed { color: #ff3b3b; font-weight: 700; font-size: 0.75rem; }
        .add-form { padding: 16px; display: flex; flex-direction: column; gap: 12px; }
        .add-form input, .add-form select { background: var(--bg-card2); border: 1px solid var(--border); color: var(--text); padding: 9px 12px; border-radius: 8px; font-size: 0.85rem; width: 100%; outline: none; }
        .add-form input:focus, .add-form select:focus { border-color: var(--accent); }
        .form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
        .form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .form-label { font-size: 0.78rem; color: var(--text-muted); margin-bottom: 4px; }
        .btn-add { background: var(--accent); color: var(--bg); border: none; padding: 11px; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.9rem; }
        .empty-routes { text-align: center; padding: 40px; color: var(--text-muted); }
        @media(max-width:900px){ .routes-layout{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-brand">
        <div class="brand-icon">🛡️</div>
        <div><span class="brand-name">RescuerNet AI</span><span class="brand-sub">ASEAN Disaster Response</span></div>
    </div>
    <div class="nav-links">
        <a href="index.php"><i class="fas fa-gauge"></i> Dashboard</a>
        <a href="map.php"><i class="fas fa-map"></i> Live Map</a>
        <a href="prediction.php"><i class="fas fa-brain"></i> AI Predict</a>
        <a href="routes.php" class="active"><i class="fas fa-route"></i> Routes</a>
        <a href="centers.php"><i class="fas fa-hospital"></i> Centers</a>
    </div>
    <div class="nav-status"><span class="status-dot pulse"></span><span>Live Monitoring</span></div>
</nav>

<div style="max-width:1300px;margin:0 auto;padding:24px 24px 0;">
    <div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card safe">
            <div class="stat-icon"><i class="fas fa-route"></i></div>
            <div class="stat-info">
                <span class="stat-num"><?= count(array_filter($routes, fn($r) => $r['status']==='open')) ?></span>
                <span class="stat-label">Open Routes</span>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-traffic-light"></i></div>
            <div class="stat-info">
                <span class="stat-num"><?= count(array_filter($routes, fn($r) => $r['status']==='congested')) ?></span>
                <span class="stat-label">Congested</span>
            </div>
        </div>
        <div class="stat-card critical">
            <div class="stat-icon"><i class="fas fa-road-barrier"></i></div>
            <div class="stat-info">
                <span class="stat-num"><?= count(array_filter($routes, fn($r) => $r['status']==='closed')) ?></span>
                <span class="stat-label">Closed Routes</span>
            </div>
        </div>
    </div>
</div>

<div class="routes-layout">
    <!-- Map + Routes List -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-map"></i> Evacuation Routes Map</h2>
            </div>
            <div id="route-map"></div>
        </div>

        <div class="card" style="margin-top:16px;">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> All Routes</h2>
            </div>
            <?php if(empty($routes)): ?>
            <div class="empty-routes">
                <i class="fas fa-route" style="font-size:2rem;margin-bottom:10px;display:block;"></i>
                <p>No routes added yet.</p>
            </div>
            <?php else: ?>
            <div class="routes-list">
                <?php foreach($routes as $r): ?>
                <div class="route-item <?= $r['status'] ?>" onclick="showRoute(<?= $r['origin_lat'] ?>,<?= $r['origin_lng'] ?>,<?= $r['destination_lat'] ?>,<?= $r['destination_lng'] ?>,'<?= addslashes($r['route_name']) ?>')">
                    <div class="route-name"><i class="fas fa-route"></i> <?= htmlspecialchars($r['route_name']) ?></div>
                    <div class="route-meta">
                        <span><i class="fas fa-location-dot"></i> To: <?= htmlspecialchars($r['destination_name']) ?></span>
                        <?php if($r['distance_km']): ?><span><i class="fas fa-road"></i> <?= $r['distance_km'] ?> km</span><?php endif; ?>
                        <?php if($r['estimated_time_min']): ?><span><i class="fas fa-clock"></i> ~<?= $r['estimated_time_min'] ?> min</span><?php endif; ?>
                        <?php if($r['disaster_title']): ?><span><i class="fas fa-triangle-exclamation"></i> <?= htmlspecialchars($r['disaster_title']) ?></span><?php endif; ?>
                    </div>
                    <span class="status-<?= $r['status'] ?>"><?= strtoupper($r['status']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Route Form -->
    <div class="card" style="height:fit-content;">
        <div class="card-header">
            <h2><i class="fas fa-plus"></i> Add Evacuation Route</h2>
        </div>
        <form method="POST" class="add-form">
            <div>
                <div class="form-label">Linked Disaster</div>
                <select name="disaster_id">
                    <option value="">None</option>
                    <?php foreach($disasters as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <div class="form-label">Route Name *</div>
                <input type="text" name="route_name" placeholder="e.g. Sagaing to Mandalay Highway" required>
            </div>
            <div>
                <div class="form-label">Origin Coordinates</div>
                <div class="form-row-2">
                    <input type="number" step="any" name="origin_lat" placeholder="Latitude" required>
                    <input type="number" step="any" name="origin_lng" placeholder="Longitude" required>
                </div>
            </div>
            <div>
                <div class="form-label">Destination Name *</div>
                <input type="text" name="destination_name" placeholder="e.g. Mandalay Sports Complex" required>
            </div>
            <div>
                <div class="form-label">Destination Coordinates</div>
                <div class="form-row-2">
                    <input type="number" step="any" name="destination_lat" placeholder="Latitude" required>
                    <input type="number" step="any" name="destination_lng" placeholder="Longitude" required>
                </div>
            </div>
            <div class="form-row-2">
                <div>
                    <div class="form-label">Distance (km)</div>
                    <input type="number" step="0.1" name="distance_km" placeholder="0.0">
                </div>
                <div>
                    <div class="form-label">Est. Time (min)</div>
                    <input type="number" name="estimated_time_min" placeholder="0">
                </div>
            </div>
            <div>
                <div class="form-label">Status</div>
                <select name="status">
                    <option value="open">✅ Open</option>
                    <option value="congested">⚠️ Congested</option>
                    <option value="closed">🚫 Closed</option>
                </select>
            </div>
            <button type="submit" class="btn-add"><i class="fas fa-plus"></i> Add Route</button>
        </form>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('route-map').setView([15.0, 105.0], 5);
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);

const routes = <?= json_encode($routes) ?>;
const routeLayers = [];

routes.forEach(r => {
    if (!r.origin_lat || !r.destination_lat) return;
    const color = r.status === 'open' ? '#34c759' : r.status === 'congested' ? '#ff9500' : '#ff3b3b';
    const line = L.polyline([
        [r.origin_lat, r.origin_lng],
        [r.destination_lat, r.destination_lng]
    ], { color, weight: 3, opacity: 0.85, dashArray: r.status === 'congested' ? '8,4' : null })
    .bindPopup(`<b>${r.route_name}</b><br>→ ${r.destination_name}<br>Status: <b style="color:${color}">${r.status.toUpperCase()}</b>`)
    .addTo(map);

    L.circleMarker([r.origin_lat, r.origin_lng], { radius: 6, color: '#fff', fillColor: color, fillOpacity: 1, weight: 2 }).addTo(map);
    L.circleMarker([r.destination_lat, r.destination_lng], { radius: 8, color: '#fff', fillColor: color, fillOpacity: 1, weight: 2 })
        .bindPopup(`🏠 ${r.destination_name}`).addTo(map);
});

function showRoute(oLat, oLng, dLat, dLng, name) {
    if (!oLat || !dLat) return;
    map.flyToBounds([[oLat, oLng], [dLat, dLng]], { padding: [40, 40], duration: 1.2 });
}
</script>
</body>
</html>