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

// Variables for edit state
$edit_mode = false;
$edit_showtime_id = 0;
$edit_movie_id = '';
$edit_audi_id = '';
$edit_show_date = '';
$edit_start_time = '';
$edit_economy_price = '';
$edit_standard_price = '';
$edit_premium_price = '';
$edit_status = 'Available';

// 2. HANDLE DELETE SHOWTIME
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM showtimes WHERE showtime_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $success = "Showtime deleted successfully!";
        } else {
            $error = "Error deleting showtime: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error = "Database error: " . $conn->error;
    }
}

// 3. HANDLE EDIT REQUEST (POPULATE FORM FIELDS)
if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_showtime_id = intval($_GET['edit']);
    
    $stmt = $conn->prepare("SELECT * FROM showtimes WHERE showtime_id = ?");
    $stmt->bind_param("i", $edit_showtime_id);
    $stmt->execute();
    $edit_result = $stmt->get_result();
    
    if ($edit_row = $edit_result->fetch_assoc()) {
        $edit_movie_id      = $edit_row['movie_id'];
        $edit_audi_id       = $edit_row['audi_id'];
        $edit_show_date     = $edit_row['show_date'];
        $edit_start_time    = $edit_row['start_time'];
        $edit_economy_price = $edit_row['economy_price'];
        $edit_standard_price= $edit_row['standard_price'];
        $edit_premium_price = $edit_row['premium_price'];
        $edit_status        = $edit_row['status'];
    } else {
        $error = "Showtime not found.";
        $edit_mode = false;
    }
    $stmt->close();
}

// 4. HANDLE ADD OR UPDATE SHOWTIME FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_showtime']) || isset($_POST['update_showtime']))) {
    $movie_id       = intval($_POST['movie_id'] ?? 0);
    $audi_id        = intval($_POST['audi_id'] ?? 0);
    $show_date      = trim($_POST['show_date'] ?? '');
    $start_time     = trim($_POST['start_time'] ?? '');
    $economy_price  = floatval($_POST['economy_price'] ?? 0);
    $standard_price = floatval($_POST['standard_price'] ?? 0);
    $premium_price  = floatval($_POST['premium_price'] ?? 0);
    $status         = $_POST['status'] ?? 'Available';

    if ($movie_id <= 0 || $audi_id <= 0 || $show_date === '' || $start_time === '' || $economy_price <= 0 || $standard_price <= 0 || $premium_price <= 0) {
        $error = "Please fill in all required fields and tier prices correctly.";
        if (isset($_POST['update_showtime'])) {
            $edit_mode = true;
            $edit_showtime_id = intval($_POST['showtime_id']);
            // Keep values posted back on error
            $edit_movie_id = $movie_id;
            $edit_audi_id = $audi_id;
            $edit_show_date = $show_date;
            $edit_start_time = $start_time;
            $edit_economy_price = $economy_price;
            $edit_standard_price = $standard_price;
            $edit_premium_price = $premium_price;
            $edit_status = $status;
        }
    } else {
        if (isset($_POST['update_showtime'])) {
            // UPDATE LOGIC
            $showtime_id = intval($_POST['showtime_id']);
            $stmt = $conn->prepare("UPDATE showtimes SET movie_id = ?, audi_id = ?, show_date = ?, start_time = ?, economy_price = ?, standard_price = ?, premium_price = ?, status = ? WHERE showtime_id = ?");
            if ($stmt) {
                $stmt->bind_param("iissdddsi", $movie_id, $audi_id, $show_date, $start_time, $economy_price, $standard_price, $premium_price, $status, $showtime_id);
                if ($stmt->execute()) {
                    $success = "Showtime updated successfully!";
                    $edit_mode = false;
                } else {
                    $error = "Error updating showtime: " . $stmt->error;
                    $edit_mode = true;
                    $edit_showtime_id = $showtime_id;
                }
                $stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        } else {
            // INSERT LOGIC
            $stmt = $conn->prepare("INSERT INTO showtimes (movie_id, audi_id, show_date, start_time, economy_price, standard_price, premium_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("iissddds", $movie_id, $audi_id, $show_date, $start_time, $economy_price, $standard_price, $premium_price, $status);
                if ($stmt->execute()) {
                    $success = "Showtime added successfully!";
                } else {
                    $error = "Error adding showtime: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

// 5. FETCH MOVIES AND AUDITORIUMS FOR THE DROPDOWN
$movies_result = $conn->query("SELECT movie_id, movie_name FROM movies ORDER BY movie_name ASC");
$audis_result = $conn->query("SELECT audi_id, audi_name FROM auditoriums ORDER BY audi_name ASC");

// 6. FETCH SHOWTIMES JOINING MOVIES AND AUDITORIUMS
$sql = "SELECT 
            s.showtime_id,
            s.show_date,
            s.start_time,
            s.economy_price,
            s.standard_price,
            s.premium_price,
            s.status,
            m.movie_name,
            a.audi_name
        FROM showtimes s
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN auditoriums a ON s.audi_id = a.audi_id
        ORDER BY s.show_date DESC, s.start_time DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Showtimes Management - RedCine</title>
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
    min-width: 180px;
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #aaa;
}

.form-group input, .form-group select {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #333;
    background: #1a1a1a;
    color: white;
    outline: none;
}

.form-group input:focus, .form-group select:focus {
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
    background: #444;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
    margin-left: 10px;
}
.btn-cancel:hover {
    background: #555;
}

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
    overflow-x: auto;
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

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}
.available { background: #198754; color: white; }
.cancelled { background: red; color: white; }
.completed { background: #6c757d; color: white; }

.price-badge {
    color: #4ecc6d;
    font-weight: bold;
}

.action-btn {
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 12px;
    text-decoration: none;
    font-weight: bold;
    margin-right: 5px;
    display: inline-block;
}
.edit-btn { background: #2196f3; color: white; }
.edit-btn:hover { background: #0b7dda; }
.delete-btn { background: #e74c3c; color: white; }
.delete-btn:hover { background: #c0392b; }
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
    <a href="showtimes.php" class="active">Showtimes</a>
    <a href="movies.php">Movies</a>
    <a href="payments.php">Payments</a>
    <a href="reports.php">Reports</a>
    <a href="admin_logout.php">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Showtimes Management</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php
    $total_showtimes = $result ? $result->num_rows : 0;
    $available_count = $conn->query("SELECT COUNT(*) as total FROM showtimes WHERE status='Available'")->fetch_assoc()['total'];
    $cancelled_count = $conn->query("SELECT COUNT(*) as total FROM showtimes WHERE status='Cancelled'")->fetch_assoc()['total'];
    ?>
    <div class="cards">
        <div class="card"><h3>Total Showtimes</h3><p><?php echo $total_showtimes; ?></p></div>
        <div class="card"><h3>Available</h3><p><?php echo $available_count; ?></p></div>
        <div class="card"><h3>Cancelled</h3><p><?php echo $cancelled_count; ?></p></div>
    </div>

    <!-- Add / Edit Showtime Form -->
    <div class="form-container">
        <h3><?php echo $edit_mode ? 'Edit Showtime #' . $edit_showtime_id : 'Add New Showtime'; ?></h3>
        <form method="POST" action="">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="showtime_id" value="<?php echo $edit_showtime_id; ?>">
            <?php endif; ?>
            <div class="form-row">
                <div class="form-group">
                    <label>Movie</label>
                    <select name="movie_id" required>
                        <option value="">Select Movie</option>
                        <?php 
                        // Reset pointer if already fetched previously or re-query if needed
                        $movies_result->data_seek(0);
                        if ($movies_result && $movies_result->num_rows > 0) {
                            while($movie = $movies_result->fetch_assoc()) {
                                $selected = ($edit_mode && $edit_movie_id == $movie['movie_id']) ? 'selected' : '';
                                echo "<option value='".$movie['movie_id']."' ".$selected.">".htmlspecialchars($movie['movie_name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Auditorium</label>
                    <select name="audi_id" required>
                        <option value="">Select Auditorium</option>
                        <?php 
                        $audis_result->data_seek(0);
                        if ($audis_result && $audis_result->num_rows > 0) {
                            while($audi = $audis_result->fetch_assoc()) {
                                $selected = ($edit_mode && $edit_audi_id == $audi['audi_id']) ? 'selected' : '';
                                echo "<option value='".$audi['audi_id']."' ".$selected.">".htmlspecialchars($audi['audi_name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Show Date</label>
                    <input type="date" name="show_date" value="<?php echo htmlspecialchars($edit_show_date); ?>" required>
                </div>
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="start_time" value="<?php echo htmlspecialchars($edit_start_time); ?>" required>
                </div>
                <div class="form-group">
                    <label>Economy Price (Rs.)</label>
                    <input type="number" step="0.01" name="economy_price" placeholder="150.00" value="<?php echo htmlspecialchars($edit_economy_price); ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label>Standard Price (Rs.)</label>
                    <input type="number" step="0.01" name="standard_price" placeholder="200.00" value="<?php echo htmlspecialchars($edit_standard_price); ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label>Premium Price (Rs.)</label>
                    <input type="number" step="0.01" name="premium_price" placeholder="300.00" value="<?php echo htmlspecialchars($edit_premium_price); ?>" min="0" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Available" <?php echo ($edit_mode && $edit_status == 'Available') ? 'selected' : ''; ?>>Available</option>
                        <option value="Cancelled" <?php echo ($edit_mode && $edit_status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="Completed" <?php echo ($edit_mode && $edit_status == 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
            </div>
            <?php if ($edit_mode): ?>
                <button type="submit" name="update_showtime" class="btn-submit">Update Showtime</button>
                <a href="showtimes.php" class="btn-cancel">Cancel</a>
            <?php else: ?>
                <button type="submit" name="add_showtime" class="btn-submit">Add Showtime</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search movie, auditorium, date...">
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Movie Title</th>
                    <th>Auditorium</th>
                    <th>Date</th>
                    <th>Start Time</th>
                    <th>Economy Price</th>
                    <th>Standard Price</th>
                    <th>Premium Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="showtimeTableBody">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $statusClass = strtolower($row['status']);
                        ?>
                        <tr>
                            <td>#<?php echo $row['showtime_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['movie_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['audi_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['show_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                            <td><span class="price-badge">Rs. <?php echo number_format($row['economy_price'], 2); ?></span></td>
                            <td><span class="price-badge">Rs. <?php echo number_format($row['standard_price'], 2); ?></span></td>
                            <td><span class="price-badge">Rs. <?php echo number_format($row['premium_price'], 2); ?></span></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                            <td>
                                <a href="showtimes.php?edit=<?php echo $row['showtime_id']; ?>" class="action-btn edit-btn">Edit</a>
                                <a href="showtimes.php?delete=<?php echo $row['showtime_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this showtime?');">Delete</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='10' style='text-align:center; padding: 20px; color:#666;'>No showtimes found in the database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const searchInput = document.getElementById("searchInput");
const tableBody = document.getElementById("showtimeTableBody");
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