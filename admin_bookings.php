<?php
// 1. DATABASE CONNECTION
$host = "localhost";
$user = "root";
$password = "";
$dbname = "movie_booking";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. FETCH BOOKINGS JOINING USERS, SHOWTIMES, MOVIES, AND AUDITORIUMS
$sql = "SELECT 
            b.booking_id, 
            b.booking_code, 
            b.booking_date, 
            b.total_amount, 
            b.booking_status,
            u.user_id,
            u.name AS customer_name,
            m.movie_name AS movie_title,
            s.show_date,
            s.start_time,
            a.audi_name AS auditorium_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
        LEFT JOIN movies m ON s.movie_id = m.movie_id
        LEFT JOIN auditoriums a ON s.audi_id = a.audi_id
        ORDER BY b.booking_date DESC";

$result = $conn->query($sql);

// Fetch unique movies for the filter dropdown
$movie_filter_sql = "SELECT DISTINCT m.movie_id, m.movie_name 
                     FROM bookings b 
                     JOIN showtimes s ON b.showtime_id = s.showtime_id
                     JOIN movies m ON s.movie_id = m.movie_id"; 
$movie_filter_result = $conn->query($movie_filter_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Bookings - RedCine</title>
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

/* FILTER */
.filter-bar {
    margin-bottom: 15px;
}

.filter-bar input,
.filter-bar select {
    padding: 10px;
    margin-right: 10px;
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

/* STATUS STYLES */
.status {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    text-transform: capitalize;
    font-weight: bold;
}

.confirmed { background: green; color: white; }
.pending { background: orange; color: white; }
.cancelled { background: red; color: white; }

/* BUTTON */
.btn {
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    color: white;
    display: inline-block;
}

.view { background: #2b7bf4; }
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="users.php">Users</a>
    <a href="admin_bookings.php" class="active">Bookings</a>
    <a href="booking_seats.php">Booking Seats</a>
    <a href="seats.php">Seats</a>
    <a href="auditoriums.php">Auditoriums</a>
    <a href="showtimes.php">Showtimes</a>
    <a href="movies.php">Movies</a>
    <a href="payments.php">Payments</a>
    <a href="reports.php">Reports</a>
    <a href="admin_logout.php">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Bookings Management</h1>
    </div>

    <?php
    $total_count = $result->num_rows;
    $pending_count = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE booking_status='Pending'")->fetch_assoc()['total'];
    $confirmed_count = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE booking_status='Confirmed'")->fetch_assoc()['total'];
    ?>
    <div class="cards">
        <div class="card"><h3>Total Bookings</h3><p><?php echo $total_count; ?></p></div>
        <div class="card"><h3>Pending</h3><p><?php echo $pending_count; ?></p></div>
        <div class="card"><h3>Confirmed</h3><p><?php echo $confirmed_count; ?></p></div>
    </div>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search customer or code...">

        <select id="statusFilter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <select id="movieFilter">
            <option value="">All Movies</option>
            <?php 
            if ($movie_filter_result && $movie_filter_result->num_rows > 0) {
                while($movie_row = $movie_filter_result->fetch_assoc()) {
                    $m_title = !empty($movie_row['movie_name']) ? $movie_row['movie_name'] : "Unknown Movie";
                    echo "<option value='".htmlspecialchars(strtolower($m_title))."'>".htmlspecialchars($m_title)."</option>";
                }
            }
            ?>
        </select>
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Code</th>
                    <th>Customer</th>
                    <th>Movie Title</th>
                    <th>Show Schedule</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $customer = !empty(trim($row['customer_name'])) ? $row['customer_name'] : "Unknown User (ID: ".$row['user_id'].")";
                        $movie = !empty($row['movie_title']) ? $row['movie_title'] : "Unknown Movie";
                        $statusClass = strtolower($row['booking_status']);
                        ?>
                        <tr class="booking-row">
                            <td>#<?php echo $row['booking_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['booking_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer); ?></td>
                            <td><?php echo htmlspecialchars($movie); ?></td>
                            <td>
                                <?php echo htmlspecialchars($row['show_date'] ?? 'N/A'); ?> 
                                <br><small style="color: #bbb;"><?php echo htmlspecialchars($row['start_time'] ?? ''); ?> (<?php echo htmlspecialchars($row['auditorium_name'] ?? ''); ?>)</small>
                            </td>
                            <td>Rs. <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['booking_status']); ?></span></td>
                            <td><a class="btn view" href="view_bookings.php?id=<?php echo $row['booking_id']; ?>">View</a></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center; padding: 20px; color:#666;'>No bookings found in the database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const searchInput = document.getElementById("searchInput");
const statusFilter = document.getElementById("statusFilter");
const movieFilter = document.getElementById("movieFilter");

const rows = document.querySelectorAll(".booking-row");

function filterTable() {
    const searchValue = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value.toLowerCase();
    const movieValue = movieFilter.value.toLowerCase();

    rows.forEach(row => {
        const bookingCode = row.cells[1].innerText.toLowerCase();
        const customerName = row.cells[2].innerText.toLowerCase();
        const movieTitle = row.cells[3].innerText.toLowerCase();
        const bookingStatus = row.cells[6].innerText.toLowerCase();

        const matchSearch = bookingCode.includes(searchValue) || customerName.includes(searchValue);
        const matchStatus = !statusValue || bookingStatus.includes(statusValue);
        const matchMovie = !movieValue || movieTitle.includes(movieValue);

        row.style.display = (matchSearch && matchStatus && matchMovie) ? "" : "none";
    });
}

searchInput.addEventListener("input", filterTable);
statusFilter.addEventListener("change", filterTable);
movieFilter.addEventListener("change", filterTable);
</script>

</body>
</html>