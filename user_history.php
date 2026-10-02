<?php
// user_history.php
require_once 'db.php';

// Get user ID securely via GET with a fallback to 1
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 1;

// 1. FETCH AGGREGATED STATISTIC COUNTS FOR THE CARDS
$statsQuery = "
    SELECT 
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN seat_status = 'booked' OR payment_status = 'paid' THEN 1 ELSE 0 END) AS total_paid,
        SUM(CASE WHEN seat_status = 'reserved' AND payment_status = 'pending' THEN 1 ELSE 0 END) AS total_reserved,
        SUM(CASE WHEN seat_status = 'cancelled' THEN 1 ELSE 0 END) AS total_cancelled
    FROM bookings 
    WHERE user_id = ?
";
$stmtStats = mysqli_prepare($conn, $statsQuery);
mysqli_stmt_bind_param($stmtStats, "i", $userId);
mysqli_stmt_execute($stmtStats);
$statsResult = mysqli_stmt_get_result($stmtStats);
$stats = mysqli_fetch_assoc($statsResult);
mysqli_stmt_close($stmtStats);

// Set default fallback values if no records exist yet
$totalBookings  = $stats['total_bookings'] ?? 0;
$totalPaid      = $stats['total_paid'] ?? 0;
$totalReserved  = $stats['total_reserved'] ?? 0;
$totalCancelled = $stats['total_cancelled'] ?? 0;

// 2. FETCH DETAILED HISTORY ROWS
// Grouping by booking_code, movie, and showtime to combine seats seamlessly
$historyQuery = "
    SELECT 
        b.booking_code,
        n.movie_name,
        GROUP_CONCAT(b.seat_no ORDER BY b.seat_no SEPARATOR ', ') AS seats,
        b.show_date,
        b.show_time,
        b.seat_status,
        b.payment_status
    FROM bookings b
    JOIN nowshowing n ON n.id = b.movie_id
    WHERE b.user_id = ?
    GROUP BY b.booking_code, n.movie_name, b.show_date, b.show_time, b.seat_status, b.payment_status
    ORDER BY b.show_date DESC, b.show_time DESC
";
$stmtHistory = mysqli_prepare($conn, $historyQuery);
mysqli_stmt_bind_param($stmtHistory, "i", $userId);
mysqli_stmt_execute($stmtHistory);
$historyRows = mysqli_fetch_all(mysqli_stmt_get_result($stmtHistory), MYSQLI_ASSOC);
mysqli_stmt_close($stmtHistory);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User History - RedCine Admin</title>
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
    padding: 20px;
}

/* HEADER */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.topbar h1 {
    color: white;
}

/* CARDS */
.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.card {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid orange;
}

.card h3 {
    font-size: 14px;
    color: #aaa;
}

.card p {
    font-size: 22px;
    margin-top: 8px;
    color: white;
}

/* TABLE */
.table-box {
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
    color: orange;
}

/* STATUS CLASSES mapped natively from the database */
.status {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    text-transform: uppercase;
    font-weight: bold;
}

.status-paid, .status-booked { background: green; color: white; }
.status-pending, .status-reserved { background: orange; color: black; }
.status-failed, .status-cancelled { background: red; color: white; }
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
    <a href="payments.php">Payments</a>
    <a href="admin_login.php">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>User History (ID: <?= htmlspecialchars($userId) ?>)</h1>
    </div>

    <div class="cards">

        <div class="card" style="border-left-color: #2196F3;">
            <h3>Total Bookings</h3>
            <p><?= $totalBookings ?></p>
        </div>

        <div class="card" style="border-left-color: green;">
            <h3>Paid / Booked</h3>
            <p><?= $totalPaid ?></p>
        </div>

        <div class="card" style="border-left-color: orange;">
            <h3>Reserved</h3>
            <p><?= $totalReserved ?></p>
        </div>

        <div class="card" style="border-left-color: red;">
            <h3>Cancelled</h3>
            <p><?= $totalCancelled ?></p>
        </div>

    </div>

    <div class="table-box">

        <table>
            <thead>
                <tr>
                    <th>Booking Code</th>
                    <th>Movie</th>
                    <th>Seats</th>
                    <th>Showtime Date &amp; Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historyRows)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #888; padding: 30px;">
                            No history transactions found for this customer.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historyRows as $row): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: bold; color: #ff3333;">
                                <?= htmlspecialchars($row['booking_code']) ?>
                            </td>
                            <td><strong><?= htmlspecialchars($row['movie_name']) ?></strong></td>
                            <td><?= htmlspecialchars($row['seats']) ?></td>
                            <td>
                                <?= htmlspecialchars(date('d M Y', strtotime($row['show_date']))) ?> — 
                                <span style="color: #aaa; font-size: 13px;"><?= htmlspecialchars($row['show_time']) ?></span>
                            </td>
                            <td>
                                <?php 
                                    // Determine display class priority based on active statuses
                                    $displayStatus = $row['seat_status'];
                                    if ($row['seat_status'] === 'booked' || $row['payment_status'] === 'paid') {
                                        $displayStatus = 'paid';
                                    }
                                ?>
                                <span class="status status-<?= htmlspecialchars($displayStatus) ?>">
                                    <?= htmlspecialchars($displayStatus) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

</div>

</body>
</html>