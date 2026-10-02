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

// 2. HANDLE ADD SEAT FORM SUBMISSION
// 2. HANDLE ADD SEAT FORM SUBMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_seat'])) {
    $audi_id     = intval($_POST['audi_id'] ?? 0);
    $row_name    = trim($_POST['row_name'] ?? '');
    $seat_number = intval($_POST['seat_number'] ?? 0);
    $seat_type   = $_POST['seat_type'] ?? 'Regular';
    $status      = $_POST['status'] ?? 'Available';

    if ($audi_id <= 0 || $row_name === '' || $seat_number <= 0) {
        $error = "Please fill in all required fields correctly.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO seats (audi_id, row_name, seat_number, seat_type, status) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("isiss", $audi_id, $row_name, $seat_number, $seat_type, $status);
                if ($stmt->execute()) {
                    $success = "Seat added successfully!";
                }
                $stmt->close();
            }
        } catch (mysqli_sql_exception $e) {
            // Check for MySQL duplicate entry error code (1062)
            if ($e->getCode() == 1062) {
                $error = "Seat '$row_name-$seat_number' already exists in this auditorium.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// 3. FETCH AUDITORIUMS FOR THE DROPDOWN
$audis_result = $conn->query("SELECT audi_id, audi_name FROM auditoriums ORDER BY audi_name");

// 4. FETCH SEATS JOINING AUDITORIUMS
$sql = "SELECT 
            s.seat_id,
            s.row_name,
            s.seat_number,
            s.seat_type,
            s.status,
            a.audi_name,
            a.audi_id
        FROM seats s
        JOIN auditoriums a ON s.audi_id = a.audi_id
        ORDER BY a.audi_name ASC, s.row_name ASC, s.seat_number ASC";

$result = $conn->query($sql);

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seats Management - RedCine</title>
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
    min-width: 200px;
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

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}
.available { background: #198754; color: white; }
.inactive { background: #6c757d; color: white; }

.type-badge {
    background: #222;
    border: 1px solid red;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 11px;
    color: #ff4d4d;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
    <a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">Users</a>
    <a href="admin_bookings.php" class="<?php echo ($current_page == 'admin_bookings.php') ? 'active' : ''; ?>">Bookings</a>
    <a href="booking_seats.php" class="<?php echo ($current_page == 'booking_seats.php') ? 'active' : ''; ?>">Booking Seats</a>
    <a href="seats.php" class="<?php echo ($current_page == 'seats.php') ? 'active' : ''; ?>">Seats</a>
    <a href="auditoriums.php" class="<?php echo ($current_page == 'auditoriums.php') ? 'active' : ''; ?>">Auditoriums</a>
    <a href="showtimes.php" class="<?php echo ($current_page == 'showtimes.php') ? 'active' : ''; ?>">Showtimes</a>
    <a href="movies.php" class="<?php echo ($current_page == 'movies.php') ? 'active' : ''; ?>">Movies</a>
    <a href="payments.php" class="<?php echo ($current_page == 'payments.php') ? 'active' : ''; ?>">Payments</a>
    <a href="reports.php" class="<?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>">Reports</a>
    <a href="admin_logout.php" class="<?php echo ($current_page == 'admin_logout.php') ? 'active' : ''; ?>">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Seats Management</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php
    $total_seats = $result ? $result->num_rows : 0;
    $available_count = $conn->query("SELECT COUNT(*) as total FROM seats WHERE status='Available'")->fetch_assoc()['total'];
    $inactive_count = $conn->query("SELECT COUNT(*) as total FROM seats WHERE status='Inactive'")->fetch_assoc()['total'];
    ?>
    <div class="cards">
        <div class="card"><h3>Total Seats</h3><p><?php echo $total_seats; ?></p></div>
        <div class="card"><h3>Available Seats</h3><p><?php echo $available_count; ?></p></div>
        <div class="card"><h3>Inactive Seats</h3><p><?php echo $inactive_count; ?></p></div>
    </div>

    <!-- Add Seat Form -->
    <div class="form-container">
        <h3>Add New Seat</h3>
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Auditorium</label>
                    <select name="audi_id" required>
                        <option value="">Select Auditorium</option>
                        <?php 
                        // Re-query auditoriums if needed or iterate pointer
                        $audis_result->data_seek(0);
                        if ($audis_result && $audis_result->num_rows > 0) {
                            while($audi = $audis_result->fetch_assoc()) {
                                echo "<option value='".$audi['audi_id']."'>".htmlspecialchars($audi['audi_name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Row Name (e.g., A, B, VIP)</label>
                    <input type="text" name="row_name" placeholder="A" required>
                </div>
                <div class="form-group">
                    <label>Seat Number</label>
                    <input type="number" name="seat_number" placeholder="1" min="1" required>
                </div>
                <div class="form-group">
                    <label>Seat Type</label>
                    <select name="seat_type">
                        <option value="Regular">Regular</option>
                        <option value="Premium">Premium</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="Available">Available</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_seat" class="btn-submit">Add Seat</button>
        </form>
    </div>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search auditorium, row, or seat number...">
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Auditorium</th>
                    <th>Row</th>
                    <th>Seat Number</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody id="seatTableBody">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $statusClass = strtolower($row['status']);
                        ?>
                        <tr>
                            <td>#<?php echo $row['seat_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['audi_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['row_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['seat_number']); ?></td>
                            <td><span class="type-badge"><?php echo htmlspecialchars($row['seat_type']); ?></span></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='6' style='text-align:center; padding: 20px; color:#666;'>No seats found in the database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const searchInput = document.getElementById("searchInput");
const tableBody = document.getElementById("seatTableBody");
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