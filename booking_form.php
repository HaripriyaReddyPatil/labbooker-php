<?php
require 'config.php';
$equipment=$pdo->query("SELECT * FROM equipment WHERE status='Available' ORDER BY name")->fetchAll();
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $equipment_id=(int)($_POST['equipment_id']??0);
  $requester=trim($_POST['requester']??'');
  $email=trim($_POST['requester_email']??'');
  $start=trim($_POST['start_at']??'');
  $end=trim($_POST['end_at']??'');
  $purpose=trim($_POST['purpose']??'');
  if(!$equipment_id||$requester===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$start===''||$end===''||$purpose==='') $errors[]='Please complete all fields with a valid email.';
  if($start!=='' && $end!=='' && strtotime($end) <= strtotime($start)) $errors[]='End time must be after start time.';
  if(!$errors){
    $stmt=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE equipment_id=? AND status IN ('Pending','Approved') AND NOT (end_at <= ? OR start_at >= ?)");
    $stmt->execute([$equipment_id,$start,$end]);
    if((int)$stmt->fetchColumn()>0) $errors[]='That equipment is already booked during the selected time.';
  }
  if(!$errors){
    $stmt=$pdo->prepare("INSERT INTO bookings(equipment_id,requester,requester_email,start_at,end_at,purpose) VALUES(?,?,?,?,?,?)");
    $stmt->execute([$equipment_id,$requester,$email,$start,$end,$purpose]); flash('Booking request submitted.'); redirect('index.php');
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Book Equipment</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="container"><div class="nav"><div class="brand">Book Equipment</div><a class="btn secondary" href="index.php">Back</a></div>
<div class="card"><?php if($errors): ?><div class="flash error"><?=e(implode(' ',$errors))?></div><?php endif; ?><form method="post">
<label>Equipment</label><select name="equipment_id" required><option value="">Choose...</option><?php foreach($equipment as $e): ?><option value="<?=$e['id']?>"><?=e($e['name'])?> — <?=e($e['location'])?></option><?php endforeach; ?></select>
<div class="grid grid-2"><div><label>Requester</label><input name="requester" required></div><div><label>Email</label><input type="email" name="requester_email" required></div></div>
<div class="grid grid-2"><div><label>Start</label><input type="datetime-local" name="start_at" required></div><div><label>End</label><input type="datetime-local" name="end_at" required></div></div>
<label>Purpose</label><textarea name="purpose" required></textarea><br><button>Submit Booking</button>
</form></div></div></body></html>
