<?php
session_start();
require_once 'config.db.php';

$centers = $pdo->query("SELECT * FROM evacuation_centers ORDER BY status ASC, current_occupancy DESC")->fetchAll();

// Add new center
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO evacuation_centers 
        (name, address, country, latitude, longitude, capacity, current_occupancy, status, contact_number) 
        VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['name'], $_POST['address'], $_POST['country'],
        $_POST['latitude'], $_POST['longitude'],
        $_POST['capacity'], $_POST['current_occupancy'],
        $_POST['status'], $_POST['contact_number']
    ]);
    header('Location: centers.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evacuation Centers — RescuerNet AI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        .centers-wrap { max-width:1300px; margin:0 auto; padding:24px; display:flex; flex-direction:column; gap:20px; }
        #centers-map { height:380px; border-radius:0 0 12px 12px; }
        .centers-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
        .center-card { background:var(--bg-card2); border-radius:12px; padding:16px; border:1px solid var(--border); border-top:3px solid; }
        .center-card.open { border-top-color:#34c759; }
        .center-card.full { border-top-color:#ff9500; }
        .center-card.closed { border-top-color:#ff3b3b; }
        .center-name { font-weight:700; font-size:0.92rem; margin-bottom:6px; }
        .center-meta { font-size:0.78rem; color:var(--text-muted); margin-bottom:10px; line-height:1.8; }
        .cap-bar { background:var(--bg); border-radius:4px; height:8px; overflow:hidden; margin-bottom:6px; }
        .cap-fill { height:100%; border-radius:4px; transition:width 0.5s; }
        .cap-text { font-size:0.75rem; display:flex; justify-content:space-between; color:var(--text-muted); }
        .add-form { padding:16px; display:flex; flex-direction:column; gap:12px; }
        .add-form input, .add-form select { background:var(--bg-card2); border:1px solid var(--border); color:var(--text); padding:9px 12px; border-radius:8px; font-size:0.85rem; width:100%; outline:none; }
        .add-form input:focus, .add-form select:focus { border-color:var(--accent); }
        .form-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .form-label { font-size:0.78rem; color:var(--text-muted); margin-bottom:4px; }
        .btn-add { background:var(--accent); color:var(--bg); border:none; padding:11px; border-radius:8px; font-weight:700; cursor:pointer; font-size:0.9rem; }
        .centers-bottom { display:grid; grid-template-columns:1fr 360px; gap:20px; align-items:start; }
        @media(max-width:900px){ .centers-grid{grid-template-columns:1fr 1fr;} .centers-bottom{grid-template-columns:1fr;} }
        @media(max-width:600px){ .centers-grid{grid-template-columns:1fr;} }
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
        <a href="routes.php"><i class="fas fa-route"></i> Routes</a>
        <a href="centers.php" class="active"><i class="fas fa-hospital"></i> Centers</a>
    </div>
    <div class="nav-status"><span class="status-dot pulse"></span><span>Live Monitoring</span></div>
</nav>

<div class="centers-wrap">

    <!-- Stats -->
    <div class="stats-row" style="grid-template-columns:repeat(4,1fr);">
        <div class="stat-card safe">
            <div class="stat-icon"><i class="fas fa-house-chimney"></i></div>
            <div class="stat-info">
                <span class="stat-num"><?= count(array_filter($centers, fn($c) => $c['status']==='open')) ?></span>
                <span class="stat-label">Open Centers</span>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon"><i class="fas fa-person-shelter"></i></div>
            <div class="stat-info">
                <span class="stat-num"><?= count(array_filter($centers, fn($c) => $c['status']==='full')) ?></span>
                <span class="stat-label">Full Centers</span>
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon"><i class="fas fa-people-roof"></i></div>
            <div class="stat-info">
                <span class="stat-num"><?= number_format(array_sum(array_column($centers,'capacity'))) ?></span>
                <span class="stat-label">Total Capacity</span>
            </div>
        </div>
        <div class="stat-card critical">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <span class="stat-num"><?= number_format(array_sum(array_column($centers,'current_occupancy'))) ?></span>
                <span class="stat-label">Current Evacuees</span>
            </div>
        </div>
    </div>

    <!-- Map -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-map-location-dot"></i> Centers Map</h2>
        </div>
        <div id="centers-map"></div>
    </div>

    <!-- Centers Grid + Add Form -->
    <div class="centers-bottom">
        <div>
            <div class="centers-grid">
                <?php foreach($centers as $c):
                    $pct = $c['capacity'] > 0 ? round(($c['current_occupancy'] / $c['capacity']) * 100) : 0;
                    $barColor = $pct >= 90 ? '#ff3b3b' : ($pct >= 70 ? '#ff9500' : '#34c759');
                ?>
                <div class="center-card <?= $c['status'] ?>">
                    <div class="center-name">🏠 <?= htmlspecialchars($c['name']) ?></div>
                    <div class="center-meta">
                        <i class="fas fa-location-dot"></i> <?= htmlspecialchars($c['country']) ?><br>
                        <?php if($c['address']): ?><?= htmlspecialchars($c['address']) ?><br><?php endif; ?>
                        <?php if($c['contact_number']): ?><i class="fas fa-phone"></i> <?= htmlspecialchars($c['contact_number']) ?><?php endif; ?>
                    </div>
                    <div class="cap-bar"><div class="cap-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>;"></div></div>
                    <div class="cap-text">
                        <span><?= number_format($c['current_occupancy']) ?> evacuees</span>
                        <span><?= $pct ?>% · <?= number_format($c['capacity']) ?> cap</span>
                    </div>
                    <div style="margin-top:8px;">
                        <span class="badge <?= $c['status'] === 'open' ? 'safe' : ($c['status'] === 'full' ? 'warning' : 'critical') ?>">
                            <?= strtoupper($c['status']) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add Form -->
        <div class="card" style="height:fit-content;">
            <div class="card-header">
                <h2><i class="fas fa-plus"></i> Add Center</h2>
            </div>
            <form method="POST" class="add-form">
                <div>
                    <div class="form-label">Center Name *</div>
                    <input type="text" name="name" placeholder="e.g. Mandalay Sports Complex" required>
                </div>
                <div>
                    <div class="form-label">Country *</div>
                    <select name="country" required>
                        <option value="">Select</option>
                        <option>Myanmar</option><option>Philippines</option><option>Vietnam</option>
                        <option>Thailand</option><option>Indonesia</option><option>Malaysia</option>
                        <option>Laos</option><option>Cambodia</option>
                    </select>
                </div>
                <div>
                    <div class="form-label">Address</div>
                    <input type="text" name="address" placeholder="Full address">
                </div>
                <div>
                    <div class="form-label">Coordinates</div>
                    <div class="form-row-2">
                        <input type="number" step="any" name="latitude" placeholder="Latitude" required>
                        <input type="number" step="any" name="longitude" placeholder="Longitude" required>
                    </div>
                </div>
                <div class="form-row-2">
                    <div>
                        <div class="form-label">Capacity</div>
                        <input type="number" name="capacity" placeholder="5000" required>
                    </div>
                    <div>
                        <div class="form-label">Current Occupancy</div>
                        <input type="number" name="current_occupancy" placeholder="0" value="0">
                    </div>
                </div>
                <div>
                    <div class="form-label">Contact Number</div>
                    <input type="text" name="contact_number" placeholder="+95 9...">
                </div>
                <div>
                    <div class="form-label">Status</div>
                    <select name="status">
                        <option value="open">✅ Open</option>
                        <option value="full">⚠️ Full</option>
                        <option value="closed">🚫 Closed</option>
                    </select>
                </div>
                <button type="submit" class="btn-add"><i class="fas fa-plus"></i> Add Center</button>
            </form>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('centers-map').setView([18.0, 100.0], 4);
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(map);

const centers = <?= json_encode($centers) ?>;
centers.forEach(c => {
    if (!c.latitude) return;
    const pct = Math.round((c.current_occupancy / c.capacity) * 100);
    const color = pct >= 90 ? '#ff3b3b' : pct >= 70 ? '#ff9500' : '#34c759';
    const icon = L.divIcon({
        html: `<div style="background:${color};color:white;width:32px;height:32px;border-radius:8px;border:2px solid white;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 2px 8px rgba(0,0,0,0.4);">🏠</div>`,
        iconSize:[32,32], className:''
    });
    L.marker([c.latitude, c.longitude], {icon})
        .bindPopup(`<b>${c.name}</b><br>${c.country}<br>Occupancy: <b style="color:${color}">${pct}%</b><br>${c.current_occupancy.toLocaleString()} / ${c.capacity.toLocaleString()}`)
        .addTo(map);
});
</script>
</body>
</html>
