<?php
session_start();

// 1. DATABASE CONNECTION
$host = "localhost";
$user = "root";
$password = "";
$dbname = "one"; // Your database name

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. DYNAMIC ANALYTICS QUERIES

// Total Users
$user_query = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $user_query->fetch_assoc()['total'] ?? 0;

// Total Movies
$movie_query = $conn->query("SELECT COUNT(*) as total FROM nowshowing");
$total_movies = $movie_query->fetch_assoc()['total'] ?? 0;

// Total Bookings
$booking_query = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $booking_query->fetch_assoc()['total'] ?? 0;

// Total Net Revenue (Successful payment sums)
$revenue_query = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'success'");
$total_revenue = $revenue_query->fetch_assoc()['total'] ?? 0.00;

// Quick Summary List Items
$paid_payments_query = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'success'");
$paid_payments = $paid_payments_query->fetch_assoc()['total'] ?? 0;

$pending_payments_query = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
$pending_payments = $pending_payments_query->fetch_assoc()['total'] ?? 0;

$failed_payments_query = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'failed'");
$failed_payments = $failed_payments_query->fetch_assoc()['total'] ?? 0;

// Most Active Movie (Top Booked Movie)
$active_movie_query = $conn->query("
    SELECT m.movie_name, COUNT(b.id) as booking_count 
    FROM bookings b 
    JOIN nowshowing m ON b.movie_id = m.id 
    GROUP BY b.movie_id 
    ORDER BY booking_count DESC 
    LIMIT 1
");
$active_movie_row = $active_movie_query->fetch_assoc();
$most_active_movie = $active_movie_row['movie_name'] ?? 'No bookings recorded';
$most_active_count = isset($active_movie_row['booking_count']) ? " (" . $active_movie_row['booking_count'] . " tickets)" : "";

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Reports - RedCine</title>
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
    color: #fff;
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
    margin-bottom: 30px;
    text-align: center;
}

.sidebar a {
    display: block;
    color: #fff;
    padding: 12px;
    margin: 8px 0;
    text-decoration: none;
    background: #1a1a1a;
    border-radius: 6px;
    transition: 0.3s;
}

.sidebar a:hover {
    background: red;
}

/* MAIN */
.main {
    margin-left: 260px;
    padding: 20px;
}

/* TOPBAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #111;
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid red;
}

.topbar h1 {
    font-size: 22px;
}

/* EXPORT BUTTON */
.export-btn {
    background: red;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 14px;
    cursor: pointer;
    border: none;
    font-weight: bold;
}

.export-btn:hover {
    background: darkred;
}

/* CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-top: 20px;
}

.card {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid red;
    text-align: center;
}

.card h3 {
    color: #ccc;
    margin-bottom: 10px;
}

.card p {
    font-size: 24px;
    color: red;
    font-weight: bold;
}

/* REPORT SECTION */
.report-section {
    margin-top: 30px;
    background: #111;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #222;
}

.report-section h2 {
    color: red;
    margin-bottom: 15px;
}

.report-list {
    line-height: 2.2;
    color: #ddd;
    font-size: 16px;
}

.report-list strong {
    color: #fff;
}

/* PRINT WINDOW CONFIGURATION */
@media print {
    .sidebar, .topbar {
        display: none !important;
    }
    .main {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    body {
        background: white;
        color: black;
    }
    .card, .report-section {
        background: #fff !important;
        color: #000 !important;
        border: 1px solid #ccc !important;
    }
    .card p, .report-section h2 {
        color: black !important;
    }
}
</style>
</head>

<body>

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

<div class="main">

    <div class="topbar">
        <h1>Reports & Analytics</h1>
        <button onclick="window.print()" class="export-btn">
            📄 Export PDF / Print
        </button>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Total Users</h3>
            <p><?php echo $total_users; ?></p>
        </div>

        <div class="card">
            <h3>Total Movies</h3>
            <p><?php echo $total_movies; ?></p>
        </div>

        <div class="card">
            <h3>Total Bookings</h3>
            <p><?php echo $total_bookings; ?></p>
        </div>

        <div class="card">
            <h3>Total Revenue</h3>
            <p>Rs. <?php echo number_format($total_revenue, 2); ?></p>
        </div>
    </div>

    <div class="report-section">
        <h2>Quick Report Summary</h2>
        <div class="report-list">
            ✔ <strong>Most Popular Movie:</strong> <?php echo htmlspecialchars($most_active_movie) . $most_active_count; ?> <br>
            ✔ <strong>Total Settled Payments (Paid):</strong> <?php echo $paid_payments; ?> transactions <br>
            ✔ <strong>Awaiting Settlements (Pending):</strong> <?php echo $pending_payments; ?> transactions <br>
            ✔ <strong>Dropped Transactions (Failed):</strong> <?php echo $failed_payments; ?> transactions <br>
            ✔ <strong>System Health Status:</strong> <span style="color: lime; font-weight: bold;">Operational / Active</span> <br>
        </div>
    </div>

</div>

</body>
</html>