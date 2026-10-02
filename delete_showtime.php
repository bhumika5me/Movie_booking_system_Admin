<?php
session_start();

$id = $_GET['id'] ?? 1;
?>

<!DOCTYPE html>
<html>
<head>
<title>Delete Showtime</title>

<style>
body {
    background:#0b0b0b;
    color:white;
    font-family:Arial;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.box {
    background:#111;
    padding:30px;
    border-radius:10px;
    text-align:center;
    border-top:3px solid red;
    width:400px;
}

button {
    padding:10px 15px;
    margin:10px;
    border:none;
    cursor:pointer;
    border-radius:6px;
    font-weight:bold;
}

.yes {
    background:red;
    color:white;
}

.no {
    background:gray;
    color:white;
}
</style>
</head>

<body>

<div class="box">
    <h2>Delete this showtime?</h2>
    <p>This action cannot be undone.</p>

    <button class="yes" onclick="deleteShowtime()">Yes, Delete</button>
    <button class="no" onclick="history.back()">Cancel</button>
</div>

<script>
function deleteShowtime() {
    alert("Showtime deleted successfully!");
    window.location.href = "showtimes.php";
}
</script>

</body>
</html>