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

// 2. FETCH BOOKING SEATS JOINING BOOKINGS, USERS, SHOWTIMES, MOVIES, AND SEATS
$sql = "SELECT 
            bs.booking_seat_id,
            bs.booking_id,
            bs.price,
            b.booking_code,
            b.booking_status,
            u.name AS customer_name,
            m.movie_name AS movie_title,
            s.show_date,
            s.start_time,
            st.row_name,
            st.seat_number,
            st.seat_type
        FROM booking_seats bs
        JOIN bookings b ON bs.booking_id = b.booking_id
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
        LEFT JOIN movies m ON s.movie_id = m.movie_id
        JOIN seats st ON bs.seat_id = st.seat_id
        ORDER BY bs.booking_seat_id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Seats Management - RedCine</title>
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
    grid-template-columns: repeat(2, 1fr);
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

.seat-badge {
    background: #222;
    border: 1px solid red;
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
    color: #ff4d4d;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="users.php">Users</a>
    <a href="admin_bookings.php">Bookings</a>
    <a href="booking_seats.php" class="active">Booking Seats</a>
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
        <h1>Booking Seats Management</h1>
    </div>

    <?php
    $total_assigned_seats = $result ? $result->num_rows : 0;
    $total_revenue_query = $conn->query("SELECT SUM(price) as rev FROM booking_seats")->fetch_assoc();
    $total_revenue = $total_revenue_query['rev'] ?? 0;
    ?>
    <div class="cards">
        <div class="card"><h3>Total Seats Assigned</h3><p><?php echo $total_assigned_seats; ?></p></div>
        <div class="card"><h3>Revenue from Seats</h3><p>Rs. <?php echo number_format($total_revenue, 2); ?></p></div>
    </div>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search by booking code, customer, or seat...">
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Booking Code</th>
                    <th>Customer</th>
                    <th>Movie</th>
                    <th>Seat Details</th>
                    <th>Seat Type</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody id="seatTableBody">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $customer = !empty($row['customer_name']) ? $row['customer_name'] : "Guest/Unknown";
                        $movie = !empty($row['movie_title']) ? $row['movie_title'] : "N/A";
                        $seatLabel = "Row " . $row['row_name'] . " - Seat " . $row['seat_number'];
                        ?>
                        <tr>
                            <td>#<?php echo $row['booking_seat_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['booking_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer); ?></td>
                            <td><?php echo htmlspecialchars($movie); ?></td>
                            <td><span class="seat-badge"><?php echo htmlspecialchars($seatLabel); ?></span></td>
                            <td><?php echo htmlspecialchars($row['seat_type']); ?></td>
                            <td>Rs. <?php echo number_format($row['price'], 2); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='7' style='text-align:center; padding: 20px; color:#666;'>No assigned booking seats found.</td></tr>";
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