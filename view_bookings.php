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

// 2. GET BOOKING ID FROM URL
$booking_id = intval($_GET['id'] ?? 0);

if ($booking_id <= 0) {
    header("Location: admin_bookings.php");
    exit();
}

// 3. FETCH DETAILED BOOKING INFORMATION
$sql = "SELECT 
            b.booking_id, 
            b.booking_code, 
            b.booking_date, 
            b.total_amount, 
            b.booking_status,
            u.user_id,
            u.name AS customer_name,
            u.email AS customer_email,
            u.phone AS customer_phone,
            m.movie_name AS movie_title,
            m.duration,
            m.language,
            s.show_date,
            s.start_time,
            a.audi_name AS auditorium_name
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.user_id
        LEFT JOIN showtimes s ON b.showtime_id = s.showtime_id
        LEFT JOIN movies m ON s.movie_id = m.movie_id
        LEFT JOIN auditoriums a ON s.audi_id = a.audi_id
        WHERE b.booking_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_bookings.php");
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();

// 4. FETCH ASSOCIATED SEATS FOR THIS BOOKING (Adjusted to general columns or fallback ID)
// Checking standard attributes or using seat_id if row/number columns aren't defined in your seats table
$seats_sql = "SELECT * 
              FROM booking_seats bs 
              JOIN seats s ON bs.seat_id = s.seat_id 
              WHERE bs.booking_id = ?";
$seats_stmt = $conn->prepare($seats_sql);
$seats_stmt->bind_param("i", $booking_id);
$seats_stmt->execute();
$seats_result = $seats_stmt->get_result();
$seats_list = [];
while($seat = $seats_result->fetch_assoc()) {
    // Falls back gracefully depending on what columns exist in your seats table (e.g., seat_name, seat_number, or seat_id)
    $seat_identifier = $seat['seat_name'] ?? ($seat['seat_number'] ?? ('Seat #' . $seat['seat_id']));
    $seats_list[] = $seat_identifier . (isset($seat['seat_type']) ? " (" . ucfirst($seat['seat_type']) . ")" : "");
}
$seats_stmt->close();

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Booking #<?php echo $booking['booking_id']; ?> - RedCine</title>
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

.back-link {
    display: inline-block;
    margin-bottom: 15px;
    color: #aaa;
    text-decoration: none;
    font-size: 14px;
}

.back-link:hover {
    color: white;
}

/* DETAILS CARD */
.details-container {
    background: #111;
    padding: 25px;
    border-radius: 10px;
    border: 1px solid #222;
    max-width: 800px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 15px;
}

.detail-item {
    background: #1a1a1a;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid red;
}

.detail-item label {
    display: block;
    font-size: 12px;
    color: #888;
    margin-bottom: 5px;
    text-transform: uppercase;
}

.detail-item span {
    font-size: 15px;
    color: #fff;
    font-weight: bold;
}

.status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    text-transform: capitalize;
}

.confirmed { background: green; color: white; }
.pending { background: orange; color: white; }
.cancelled { background: red; color: white; }
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
    <a href="users.php" class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>">Users</a>
    <a href="admin_bookings.php" class="<?php echo ($current_page == 'admin_bookings.php' || $current_page == 'view_bookings.php') ? 'active' : ''; ?>">Bookings</a>
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
        <h1>Booking Details</h1>
    </div>

    <a href="admin_bookings.php" class="back-link">&larr; Back to Bookings</a>

    <div class="details-container">
        <h3 style="color: red; border-bottom: 1px solid #222; padding-bottom: 10px;">Booking #<?php echo $booking['booking_id']; ?></h3>
        
        <div class="details-grid">
            <div class="detail-item">
                <label>Booking Code</label>
                <span><?php echo htmlspecialchars($booking['booking_code']); ?></span>
            </div>
            <div class="detail-item">
                <label>Booking Status</label>
                <span><span class="status <?php echo strtolower($booking['booking_status']); ?>"><?php echo htmlspecialchars($booking['booking_status']); ?></span></span>
            </div>
            <div class="detail-item">
                <label>Customer Name</label>
                <span><?php echo htmlspecialchars($booking['customer_name'] ?? 'Unknown User'); ?></span>
            </div>
            <div class="detail-item">
                <label>Customer Contact</label>
                <span><?php echo htmlspecialchars($booking['customer_email'] ?? 'N/A'); ?><br><small style="color:#aaa;"><?php echo htmlspecialchars($booking['customer_phone'] ?? ''); ?></small></span>
            </div>
            <div class="detail-item">
                <label>Movie Title</label>
                <span><?php echo htmlspecialchars($booking['movie_title'] ?? 'Unknown Movie'); ?></span>
            </div>
            <div class="detail-item">
                <label>Show Schedule</label>
                <span><?php echo htmlspecialchars($booking['show_date'] ?? 'N/A'); ?> at <?php echo htmlspecialchars($booking['start_time'] ?? ''); ?><br><small style="color:#aaa;">Auditorium: <?php echo htmlspecialchars($booking['auditorium_name'] ?? ''); ?></small></span>
            </div>
            <div class="detail-item">
                <label>Booked Seats</label>
                <span><?php echo !empty($seats_list) ? implode(', ', $seats_list) : 'No seats assigned'; ?></span>
            </div>
            <div class="detail-item">
                <label>Total Amount</label>
                <span style="color: #28a745;">Rs. <?php echo number_format($booking['total_amount'], 2); ?></span>
            </div>
            <div class="detail-item" style="grid-column: span 2;">
                <label>Transaction / Booking Timestamp</label>
                <span><?php echo htmlspecialchars($booking['booking_date']); ?></span>
            </div>
        </div>
    </div>

</div>

</body>
</html>