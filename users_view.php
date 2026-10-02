<?php
// user_view.php
// frontend only (later you can fetch by ID using $_GET['id'])

$id = $_GET['id'] ?? 1; // dummy data for now
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Details - RedCine Admin</title>

<style>
body {
    margin: 0;
    font-family: Arial;
    background: #0b0b0b;
    color: white;
}

.container {
    width: 500px;
    margin: 80px auto;
    background: #111;
    padding: 25px;
    border-radius: 10px;
    border-left: 4px solid red;
}

h2 {
    color: red;
    text-align: center;
}

.info {
    margin-top: 20px;
    line-height: 2;
}

.label {
    color: #aaa;
}

.back {
    display: inline-block;
    margin-top: 20px;
    padding: 8px 12px;
    background: red;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}
</style>
</head>

<body>

<div class="container">

    <h2>User Details</h2>

    <div class="info">
        <p><span class="label">User ID:</span> <?= $id ?></p>
        <p><span class="label">Name:</span> Ram Sharma</p>
        <p><span class="label">Email:</span> ram@gmail.com</p>
        <p><span class="label">Mobile:</span> 9800000000</p>
        <p><span class="label">Date of Birth:</span> 2001-05-10</p>
        <p><span class="label">Status:</span> Active</p>
    </div>

    <a class="back" href="users.php">← Back</a>

</div>

</body>
</html>