<?php
declare(strict_types=1);
session_start();

$pdo = new PDO('sqlite:' . __DIR__ . '/data/labbooker.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("CREATE TABLE IF NOT EXISTS equipment (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 name TEXT NOT NULL,
 category TEXT NOT NULL,
 location TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'Available'
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS bookings (
 id INTEGER PRIMARY KEY AUTOINCREMENT,
 equipment_id INTEGER NOT NULL,
 requester TEXT NOT NULL,
 requester_email TEXT NOT NULL,
 start_at TEXT NOT NULL,
 end_at TEXT NOT NULL,
 purpose TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'Pending',
 created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(equipment_id) REFERENCES equipment(id)
)");

if((int)$pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn()===0){
  $seed = [
    ['3D Printer','Fabrication','Engineering Lab 204'],
    ['Oscilloscope','Electronics','ECE Lab 118'],
    ['GPU Workstation','Computing','AI Lab 310']
  ];
  $stmt=$pdo->prepare("INSERT INTO equipment(name,category,location) VALUES(?,?,?)");
  foreach($seed as $row) $stmt->execute($row);
}

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $p): never { header("Location: $p"); exit; }
function flash(?string $m=null,string $t='ok'): ?array {
  if($m!==null){$_SESSION['flash']=[$m,$t]; return null;}
  if(!empty($_SESSION['flash'])){$f=$_SESSION['flash'];unset($_SESSION['flash']);return $f;}
  return null;
}
?>
