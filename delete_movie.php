<?php
session_start();
require_once 'db.php';

// Check if ID is provided and is a valid number
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage_movies.php");
    exit();
}

$movieId = (int) $_GET['id'];

// Use $conn instead of $mysqli to match your database connection variable
$stmt = $conn->prepare("SELECT movie_name, poster FROM nowshowing WHERE id = ?");
$stmt->bind_param("i", $movieId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: manage_movies.php");
    exit();
}

$movie = $result->fetch_assoc();
$stmt->close();

// Handle deletion when confirmed via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Optional: Delete poster file from server if it exists and isn't a default
    if (!empty($movie['poster']) && file_exists($movie['poster']) && $movie['poster'] !== 'assets/images/movie1.jpg') {
        @unlink($movie['poster']);
    }

    // Delete record from database using $conn prepared statement
    $delStmt = $conn->prepare("DELETE FROM nowshowing WHERE id = ?");
    $delStmt->bind_param("i", $movieId);
    
    if ($delStmt->execute()) {
        $delStmt->close();
        header("Location: manage_movies.php?msg=deleted");
        exit();
    } else {
        $error = "Failed to delete movie from database.";
        $delStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete Movie - RedCine</title>

<style>
body {
    background: #0b0b0b;
    color: white;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.box {
    background: #111;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    border-top: 3px solid red;
    width: 400px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.5);
}

h2 {
    margin-bottom: 10px;
    font-size: 22px;
}

.movie-title {
    color: red;
    margin-bottom: 20px;
    font-size: 18px;
}

.error-msg {
    color: #ff4d4d;
    margin-bottom: 15px;
    font-size: 14px;
}

.btn-group {
    display: flex;
    justify-content: center;
    gap: 10px;
}

button, .no-btn {
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    border-radius: 6px;
    font-weight: bold;
    text-decoration: none;
    font-size: 14px;
    transition: 0.3s;
}

.yes {
    background: red;
    color: white;
}

.yes:hover {
    background: #cc0000;
}

.no {
    background: #555;
    color: white;
    display: inline-block;
}

.no:hover {
    background: #666;
}
</style>
</head>

<body>

<div class="box">
    <h2>Are you sure you want to delete?</h2>
    <div class="movie-title">"<?php echo htmlspecialchars($movie['movie_name']); ?>"</div>

    <?php if (isset($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="btn-group">
            <button type="submit" class="yes">Yes, Delete</button>
            <a href="manage_movies.php" class="no">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>