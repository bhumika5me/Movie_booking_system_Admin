<?php
session_start();

// fake data (later comes from DB using $_GET['id'])
$id = $_GET['id'] ?? 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Showtime - RedCine</title>

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

.form-box {
    background:#111;
    padding:25px;
    width:400px;
    border-radius:10px;
    border-top:3px solid orange;
}

input, select {
    width:100%;
    padding:10px;
    margin:8px 0;
    background:#1a1a1a;
    border:none;
    color:white;
    border-radius:6px;
    outline:none;
}

button {
    width:100%;
    padding:10px;
    background:orange;
    border:none;
    color:white;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}

button:hover {
    opacity:0.9;
}
</style>
</head>

<body>

<div class="form-box">
    <h2>Edit Showtime</h2>

    <select>
        <option selected>Avengers Endgame</option>
        <option>Joker</option>
        <option>Interstellar</option>
    </select>

    <select>
        <option selected>Audi 1</option>
        <option>Audi 2</option>
        <option>Cube 1</option>
    </select>

    <input type="date" value="2026-06-10">
    <input type="time" value="10:00">

    <input type="number" value="500" placeholder="Ticket Price">

    <button onclick="updateShowtime()">Update Showtime</button>
</div>

<script>
function updateShowtime() {
    alert("Showtime updated successfully!");
    window.location.href = "showtimes.php";
}
</script>

</body>
</html>