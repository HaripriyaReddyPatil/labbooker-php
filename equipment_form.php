<?php
require 'config.php';
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $name=trim($_POST['name']??''); $category=trim($_POST['category']??''); $location=trim($_POST['location']??'');
  if($name===''||$category===''||$location==='') $errors[]='All fields are required.';
  if(!$errors){
    $stmt=$pdo->prepare("INSERT INTO equipment(name,category,location) VALUES(?,?,?)");
    $stmt->execute([$name,$category,$location]); flash('Equipment added.'); redirect('index.php');
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Equipment</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="container"><div class="nav"><div class="brand">Add Equipment</div><a class="btn secondary" href="index.php">Back</a></div>
<div class="card"><?php if($errors): ?><div class="flash error"><?=e(implode(' ',$errors))?></div><?php endif; ?><form method="post">
<label>Name</label><input name="name" required><label>Category</label><input name="category" required><label>Location</label><input name="location" required><br><br><button>Add Equipment</button>
</form></div></div></body></html>
