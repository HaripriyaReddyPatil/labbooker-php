<?php
require 'config.php';
if(isset($_GET['action'],$_GET['id'])){
  $id=(int)$_GET['id']; $action=$_GET['action'];
  $map=['approve'=>'Approved','reject'=>'Rejected','cancel'=>'Cancelled'];
  if(isset($map[$action])){$stmt=$pdo->prepare("UPDATE bookings SET status=? WHERE id=?");$stmt->execute([$map[$action],$id]);flash('Booking status updated.');redirect('bookings.php');}
}
$rows=$pdo->query("SELECT b.*, e.name AS equipment_name FROM bookings b JOIN equipment e ON e.id=b.equipment_id ORDER BY b.id DESC")->fetchAll();
$f=flash();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Bookings</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="container"><div class="nav"><div class="brand">Manage Bookings</div><a class="btn secondary" href="index.php">Back</a></div>
<?php if($f): ?><div class="flash"><?=e($f[0])?></div><?php endif; ?>
<div class="card"><table><thead><tr><th>Equipment</th><th>Requester</th><th>Time</th><th>Purpose</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(!$rows): ?><tr><td colspan="6" class="muted">No bookings yet.</td></tr><?php endif; ?>
<?php foreach($rows as $b): ?><tr><td><?=e($b['equipment_name'])?></td><td><?=e($b['requester'])?><br><span class="muted"><?=e($b['requester_email'])?></span></td>
<td><?=e($b['start_at'])?><br><span class="muted">to <?=e($b['end_at'])?></span></td><td><?=e($b['purpose'])?></td><td><span class="badge"><?=e($b['status'])?></span></td>
<td class="actions"><a class="btn" href="?action=approve&id=<?=$b['id']?>">Approve</a><a class="btn danger" href="?action=reject&id=<?=$b['id']?>">Reject</a></td></tr><?php endforeach; ?>
</tbody></table></div></div></body></html>
