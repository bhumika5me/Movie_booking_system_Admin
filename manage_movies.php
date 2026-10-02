<?php
require __DIR__ . '/db.php';

// ---- Optional: guard this page to logged-in admins only ----
// if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$movies = [];
$result = mysqli_query($conn, "SELECT * FROM nowshowing ORDER BY created_at DESC");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $movies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Movies - RedCine</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background: #0b0b0b;
    color: white;
}

/* SIDEBAR */
.sidebar {
    width: 250px;
    height: 100vh;
    background: #111;
    position: fixed;
    padding: 20px;
    border-right: 2px solid red;
}

.sidebar h2 {
    color: red;
    text-align: center;
    margin-bottom: 30px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 12px;
    margin: 8px 0;
    background: #1a1a1a;
    border-radius: 6px;
}

.sidebar a:hover {
    background: red;
}

/* MAIN */
.main {
    margin-left: 260px;
    padding: 25px;
}

/* TOPBAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #111;
    padding: 15px 20px;
    border-radius: 10px;
    border-left: 4px solid red;
    margin-bottom: 25px;
}

.topbar h1 {
    font-size: 22px;
}

.search-wrapper {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-box {
    padding: 10px;
    width: 250px;
    border: none;
    border-radius: 6px;
    outline: none;
    background: #1a1a1a;
    color: white;
}

.add-btn {
    background: red;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 6px;
    cursor: pointer;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-success {
    background: #123b1e;
    border-left: 4px solid #2ecc71;
    color: #b7f5c8;
}

.alert-error {
    background: #3b1212;
    border-left: 4px solid red;
    color: #ffc2c2;
}

.empty-state {
    background: #111;
    border: 1px dashed #333;
    border-radius: 10px;
    padding: 40px;
    text-align: center;
    color: #999;
}

/* MOVIE GRID */
.movie-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

/* MOVIE CARD */
.movie-card {
    background: #1a1a1a;
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid red;
    position: relative;
}

.status-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    padding: 4px 10px;
    border-radius: 20px;
}

.status-now { background: #2ecc71; color: #063312; }
.status-soon { background: #f1c40f; color: #3a2e00; }

/* POSTER */
.movie-poster {
    width: 100%;
    height: 260px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
    background: #262626;
}

.movie-poster.placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #555;
    font-size: 13px;
}

/* TEXT */
.movie-title {
    font-size: 18px;
    margin-bottom: 5px;
}

.movie-info {
    font-size: 13px;
    color: #aaa;
    margin-bottom: 4px;
}

.movie-desc {
    font-size: 12.5px;
    color: #888;
    margin: 8px 0 12px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* BUTTONS */
.btn-group button {
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 5px;
}

.edit { background: orange; }
.delete { background: red; color: white; }

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>

    <a href="admin_dashboard.php">Dashboard</a>
    <a href="manage_movies.php">Manage Movies</a>
    <a href="admin_bookings.php">Bookings</a>
    <a href="showtimes.php">Showtimes</a>
    <a href="users.php">Users</a>
    <a href="admin_payments.php">Payments</a>
    <a href="admin_reports.php">Reports</a>
    <a href="admin_login.php">Logout</a>
</div>

<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <h1>Manage Movies</h1>

        <div class="search-wrapper">
            <input class="search-box" type="text" id="searchInput" placeholder="Search movie..." onkeyup="searchMovies()">

            <a href="add_movie.php">
                <button class="add-btn">+ Add Movie</button>
            </a>
        </div>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Movie deleted successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success">Movie saved successfully.</div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($_GET['error']) ?></div>
    <?php endif; ?>

    <!-- MOVIE GRID -->
    <?php if (empty($movies)): ?>
        <div class="empty-state">
            No movies yet. Click <strong>+ Add Movie</strong> to create one.
        </div>
    <?php else: ?>
    <div class="movie-grid">

        <?php foreach ($movies as $movie): ?>
            <div class="movie-card">

                <span class="status-badge <?= $movie['status'] === 'Now Showing' ? 'status-now' : 'status-soon' ?>">
                    <?= htmlspecialchars($movie['status']) ?>
                </span>

                <?php if (!empty($movie['poster'])): ?>
                    <img src="<?= htmlspecialchars($movie['poster']) ?>" class="movie-poster" alt="<?= htmlspecialchars($movie['movie_name']) ?>"
                         referrerpolicy="no-referrer"
                         onerror="this.onerror=null; this.outerHTML='<div class=\'movie-poster placeholder\'>Image unavailable</div>';">
                <?php else: ?>
                    <div class="movie-poster placeholder">No Poster</div>
                <?php endif; ?>

                <div class="movie-title"><?= htmlspecialchars($movie['movie_name']) ?></div>
                <div class="movie-info">
                    <?= htmlspecialchars($movie['genre']) ?> | <?= htmlspecialchars($movie['duration']) ?>
                    <?php if (!empty($movie['language'])): ?> | <?= htmlspecialchars($movie['language']) ?><?php endif; ?>
                </div>
                <?php if (!empty($movie['director'])): ?>
                    <div class="movie-info">Dir: <?= htmlspecialchars($movie['director']) ?></div>
                <?php endif; ?>
                <?php if (!empty($movie['release_date'])): ?>
                    <div class="movie-info">Release: <?= htmlspecialchars($movie['release_date']) ?></div>
                <?php endif; ?>

                <?php if (!empty($movie['description'])): ?>
                    <div class="movie-desc"><?= htmlspecialchars($movie['description']) ?></div>
                <?php endif; ?>

                <div class="btn-group">
                    <a href="edit_movie.php?id=<?= (int)$movie['id'] ?>">
                        <button class="edit">Edit</button>
                    </a>
                    <a href="delete_movie.php?id=<?= (int)$movie['id'] ?>"
                       onclick="return confirm('Delete this movie? This cannot be undone.');">
                        <button class="delete">Delete</button>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
    <?php endif; ?>
</div>

<script>
function searchMovies() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let movies = document.getElementsByClassName("movie-card");

    for (let i = 0; i < movies.length; i++) {
        let title = movies[i].getElementsByClassName("movie-title")[0];
        if (!title) continue;

        movies[i].style.display = title.innerText.toLowerCase().includes(input)
            ? "block"
            : "none";
    }
}
</script>

</body>
</html>