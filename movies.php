<?php
// 1. DATABASE CONNECTION & SESSION
session_start();
$host = "localhost";
$user = "root";
$password = "";
$dbname = "movie_booking";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";
$success = "";

// 2. HANDLE DELETE MOVIE
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM movies WHERE movie_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $success = "Movie deleted successfully!";
        } else {
            $error = "Error deleting movie: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// 3. HANDLE ADD MOVIE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    $movie_name   = trim($_POST['movie_name'] ?? '');
    $genre        = trim($_POST['genre'] ?? '');
    $language     = trim($_POST['language'] ?? '');
    $duration     = intval($_POST['duration'] ?? 0);
    $release_date = trim($_POST['release_date'] ?? '');
    $director     = trim($_POST['director'] ?? '');
    $cast         = trim($_POST['cast'] ?? '');
    $trailer_url  = trim($_POST['trailer_url'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $status       = $_POST['status'] ?? 'Upcoming';
    $poster       = trim($_POST['poster_url'] ?? '');

    if ($movie_name === '' || $duration <= 0) {
        $error = "Please fill in all required movie fields correctly.";
    } else {
        $stmt = $conn->prepare("INSERT INTO movies (movie_name, poster, genre, language, duration, release_date, description, director, cast, trailer_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssissssss", $movie_name, $poster, $genre, $language, $duration, $release_date, $description, $director, $cast, $trailer_url, $status);
            if ($stmt->execute()) {
                $success = "Movie added successfully!";
            } else {
                $error = "Error adding movie: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// 4. HANDLE UPDATE MOVIE FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_movie'])) {
    $movie_id     = intval($_POST['movie_id'] ?? 0);
    $movie_name   = trim($_POST['movie_name'] ?? '');
    $genre        = trim($_POST['genre'] ?? '');
    $language     = trim($_POST['language'] ?? '');
    $duration     = intval($_POST['duration'] ?? 0);
    $release_date = trim($_POST['release_date'] ?? '');
    $director     = trim($_POST['director'] ?? '');
    $cast         = trim($_POST['cast'] ?? '');
    $trailer_url  = trim($_POST['trailer_url'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $status       = $_POST['status'] ?? 'Upcoming';
    $poster       = trim($_POST['poster_url'] ?? '');

    if ($movie_id <= 0 || $movie_name === '' || $duration <= 0) {
        $error = "Please fill in all required movie fields correctly.";
    } else {
        $stmt = $conn->prepare("UPDATE movies SET movie_name=?, poster=?, genre=?, language=?, duration=?, release_date=?, description=?, director=?, cast=?, trailer_url=?, status=? WHERE movie_id=?");
        if ($stmt) {
            $stmt->bind_param("ssssissssssi", $movie_name, $poster, $genre, $language, $duration, $release_date, $description, $director, $cast, $trailer_url, $status, $movie_id);
            if ($stmt->execute()) {
                $success = "Movie updated successfully!";
                header("Location: movies.php");
                exit();
            } else {
                $error = "Error updating movie: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// 5. FETCH MOVIE DATA FOR EDITING IF edit_id IS SET
$edit_mode = false;
$edit_movie = null;
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    $edit_stmt = $conn->prepare("SELECT * FROM movies WHERE movie_id = ?");
    if ($edit_stmt) {
        $edit_stmt->bind_param("i", $edit_id);
        $edit_stmt->execute();
        $edit_result = $edit_stmt->get_result();
        if ($edit_result->num_rows > 0) {
            $edit_movie = $edit_result->fetch_assoc();
            $edit_mode = true;
        }
        $edit_stmt->close();
    }
}

// 6. FETCH ALL MOVIES FOR DISPLAY
$sql = "SELECT movie_id, movie_name, poster, genre, language, duration, release_date, director, description, cast, trailer_url, status, created_at FROM movies ORDER BY movie_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Movies Management - RedCine</title>
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
    top: 0;
    left: 0;
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

.sidebar a:hover, .sidebar a.active {
    background: red;
}

/* MAIN */
.main {
    margin-left: 260px;
    padding: 25px;
}

/* TOP BAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding: 15px;
    background: #111;
    border-left: 4px solid red;
    border-radius: 10px;
}

.topbar h1 {
    color: white;
}

/* CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.card {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid red;
}

/* ALERTS */
.alert-success {
    background: #102316;
    border-left: 4px solid #28a745;
    color: #d4edda;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-error {
    background: #2a1215;
    border-left: 4px solid red;
    color: #f8d7da;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
}

/* FORM WRAPPER */
.form-container {
    background: #111;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    border: 1px solid #222;
}

.form-container h3 {
    margin-bottom: 15px;
    color: red;
}

.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 220px;
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #aaa;
}

.form-group input, .form-group select, .form-group textarea {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #333;
    background: #1a1a1a;
    color: white;
    outline: none;
}

.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
    border-color: red;
}

.btn-submit {
    background: red;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}

.btn-submit:hover {
    background: darkred;
}

.btn-cancel {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-left: 10px;
    transition: 0.3s;
}

.btn-cancel:hover {
    background: #5a6268;
}

/* ACTION BUTTONS IN TABLE */
.btn-edit {
    background: #ffc107;
    color: black;
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    margin-right: 5px;
    display: inline-block;
}
.btn-edit:hover { background: #e0a800; }

.btn-delete {
    background: #dc3545;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 11px;
    font-weight: bold;
    display: inline-block;
}
.btn-delete:hover { background: #bd2130; }

/* FILTER */
.filter-bar {
    margin-bottom: 15px;
}

.filter-bar input {
    padding: 10px;
    width: 300px;
    border-radius: 6px;
    border: none;
    background: #1a1a1a;
    color: white;
    outline: none;
}

/* TABLE */
.table-section {
    background: #111;
    padding: 20px;
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    padding: 12px;
    border-bottom: 1px solid #333;
    text-align: left;
}

table th {
    background: #1a1a1a;
    color: red;
}

.movie-poster {
    width: 40px;
    height: 55px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid #333;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}
.upcoming { background: #ffc107; color: black; }
.now-showing { background: #198754; color: white; }
.ended { background: #6c757d; color: white; }
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="users.php">Users</a>
    <a href="admin_bookings.php">Bookings</a>
    <a href="booking_seats.php">Booking Seats</a>
    <a href="seats.php">Seats</a>
    <a href="auditoriums.php">Auditoriums</a>
    <a href="showtimes.php">Showtimes</a>
    <a href="movies.php" class="active">Movies</a>
    <a href="payments.php">Payments</a>
    <a href="reports.php">Reports</a>
    <a href="admin_logout.php">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Movies Management</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php
    $total_movies = $result ? $result->num_rows : 0;
    $now_showing_count = $conn->query("SELECT COUNT(*) as total FROM movies WHERE status='Now Showing'")->fetch_assoc()['total'];
    $upcoming_count = $conn->query("SELECT COUNT(*) as total FROM movies WHERE status='Upcoming'")->fetch_assoc()['total'];
    ?>
    <div class="cards">
        <div class="card"><h3>Total Movies</h3><p><?php echo $total_movies; ?></p></div>
        <div class="card"><h3>Now Showing</h3><p><?php echo $now_showing_count; ?></p></div>
        <div class="card"><h3>Upcoming</h3><p><?php echo $upcoming_count; ?></p></div>
    </div>

    <!-- Dynamic Form: Add or Edit Movie -->
    <div class="form-container">
        <h3><?php echo $edit_mode ? 'Edit Movie' : 'Add New Movie'; ?></h3>
        <form method="POST" action="">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="movie_id" value="<?php echo $edit_movie['movie_id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Movie Name</label>
                    <input type="text" name="movie_name" placeholder="Movie title" value="<?php echo htmlspecialchars($edit_movie['movie_name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Genre</label>
                    <input type="text" name="genre" placeholder="Action, Thriller, Sci-Fi" value="<?php echo htmlspecialchars($edit_movie['genre'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Language</label>
                    <input type="text" name="language" placeholder="English, Nepali" value="<?php echo htmlspecialchars($edit_movie['language'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Duration (Minutes)</label>
                    <input type="number" name="duration" placeholder="120" min="1" value="<?php echo htmlspecialchars($edit_movie['duration'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Release Date</label>
                    <input type="date" name="release_date" value="<?php echo htmlspecialchars($edit_movie['release_date'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Director</label>
                    <input type="text" name="director" placeholder="Director name" value="<?php echo htmlspecialchars($edit_movie['director'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Cast</label>
                    <input type="text" name="cast" placeholder="Actor 1, Actor 2" value="<?php echo htmlspecialchars($edit_movie['cast'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Trailer URL (Embed or YouTube link)</label>
                    <input type="text" name="trailer_url" placeholder="https://youtube.com/..." value="<?php echo htmlspecialchars($edit_movie['trailer_url'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Poster Image URL</label>
                    <input type="url" name="poster_url" placeholder="https://example.com/poster.jpg" value="<?php echo htmlspecialchars($edit_movie['poster'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <?php $currentStatus = $edit_movie['status'] ?? 'Upcoming'; ?>
                        <option value="Upcoming" <?php echo ($currentStatus === 'Upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="Now Showing" <?php echo ($currentStatus === 'Now Showing') ? 'selected' : ''; ?>>Now Showing</option>
                        <option value="Ended" <?php echo ($currentStatus === 'Ended') ? 'selected' : ''; ?>>Ended</option>
                    </select>
                </div>
            </div>
            <div class="form-group" style="width:100%;">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Movie plot summary..."><?php echo htmlspecialchars($edit_movie['description'] ?? ''); ?></textarea>
            </div>

            <?php if ($edit_mode): ?>
                <button type="submit" name="update_movie" class="btn-submit">Update Movie</button>
                <a href="movies.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_movie" class="btn-submit">Add Movie</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search movie name, genre, language...">
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>ID</th>
                    <th>Movie Title</th>
                    <th>Genre</th>
                    <th>Language</th>
                    <th>Duration</th>
                    <th>Director</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="movieTableBody">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $statusClass = strtolower(str_replace(' ', '-', $row['status']));
                        $posterPath = !empty($row['poster']) ? $row['poster'] : '../assets/images/default_poster.png';
                        ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($posterPath); ?>" class="movie-poster" alt="Poster"></td>
                            <td>#<?php echo $row['movie_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['movie_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['genre']); ?></td>
                            <td><?php echo htmlspecialchars($row['language']); ?></td>
                            <td><?php echo htmlspecialchars($row['duration']); ?> mins</td>
                            <td><?php echo htmlspecialchars($row['director']); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <a href="movies.php?edit_id=<?php echo $row['movie_id']; ?>" class="btn-edit">Edit</a>
                                <a href="movies.php?delete_id=<?php echo $row['movie_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this movie?');">Delete</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='9' style='text-align:center; padding: 20px; color:#666;'>No movies found in the database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const searchInput = document.getElementById("searchInput");
const tableBody = document.getElementById("movieTableBody");
const rows = tableBody.getElementsByTagName("tr");

searchInput.addEventListener("input", function() {
    const filter = searchInput.value.toLowerCase();
    for (let i = 0; i < rows.length; i++) {
        const textValue = rows[i].textContent || rows[i].innerText;
        rows[i].style.display = textValue.toLowerCase().indexOf(filter) > -1 ? "" : "none";
    }
});
</script>

</body>
</html>