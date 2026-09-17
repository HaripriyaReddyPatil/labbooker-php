<?php
require 'config.php';
$equipment=$pdo->query("SELECT * FROM equipment ORDER BY id DESC")->fetchAll();
$upcoming=$pdo->query("SELECT b.*, e.name AS equipment_name FROM bookings b JOIN equipment e ON e.id=b.equipment_id ORDER BY b.start_at ASC LIMIT 8")->fetchAll();
$metrics=[
 'Equipment'=>(int)$pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn(),
 'Pending'=>(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='Pending'")->fetchColumn(),
 'Approved'=>(int)$pdo->query("SELECT COUNT(*) FROM bookings WHERE status='Approved'")->fetchColumn()
];
$f=flash();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>LabBooker</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="container">
<div class="nav"><div><div class="brand">LabBooker</div><div class="muted">Research equipment reservation system</div></div><div class="actions"><a class="btn" href="booking_form.php">+ Book Equipment</a><a class="btn secondary" href="equipment_form.php">+ Add Equipment</a></div></div>
<?php if($f): ?><div class="flash <?=$f[1]==='error'?'error':''?>"><?=e($f[0])?></div><?php endif; ?>
<div class="grid grid-3"><?php foreach($metrics as $k=>$v): ?><div class="card"><div class="muted"><?=e($k)?></div><div class="metric"><?=$v?></div></div><?php endforeach; ?></div>

<div class="card"><h2>Equipment Inventory</h2><table><thead><tr><th>Name</th><th>Category</th><th>Location</th><th>Status</th></tr></thead><tbody>
<?php foreach($equipment as $e): ?><tr><td><?=e($e['name'])?></td><td><?=e($e['category'])?></td><td><?=e($e['location'])?></td><td><span class="badge"><?=e($e['status'])?></span></td></tr><?php endforeach; ?>
</tbody></table></div>

<div class="card"><div class="nav"><h2>Bookings</h2><a class="btn secondary" href="bookings.php">Manage all</a></div>
<table><thead><tr><th>Equipment</th><th>Requester</th><th>Start</th><th>End</th><th>Status</th></tr></thead><tbody>
<?php if(!$upcoming): ?><tr><td colspan="5" class="muted">No bookings yet.</td></tr><?php endif; ?>
<?php foreach($upcoming as $b): ?><tr><td><?=e($b['equipment_name'])?></td><td><?=e($b['requester'])?></td><td><?=e($b['start_at'])?></td><td><?=e($b['end_at'])?></td><td><span class="badge"><?=e($b['status'])?></span></td></tr><?php endforeach; ?>
</tbody></table></div>
</div></body></html>
