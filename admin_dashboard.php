<?php
session_start();
require __DIR__ . '/../db.php'; // Reliable path regardless of how this script is invoked

// ── LOGIN GUARD ──
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

// ── 1. DYNAMIC SUMMARY COUNTERS FROM DATABASE ──

// Total Movies Count (using the 'movies' table from your schema)
$movies_query = $conn->query("SELECT COUNT(*) as total FROM movies");
$total_movies = $movies_query ? $movies_query->fetch_assoc()['total'] : 0;

// Total Bookings Count
$bookings_query = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $bookings_query ? $bookings_query->fetch_assoc()['total'] : 0;

// Total Revenue Count (Sum of successful transactions using 'payments' table and 'payment_status')
$revenue_query = $conn->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'Success'");
$total_revenue = $revenue_query ? ($revenue_query->fetch_assoc()['total'] ?? 0.00) : 0.00;


// ── 2. FETCH USERS LIST FROM DATABASE ──
$users = [];
$sql = "SELECT user_id, name, email, phone, role, status, created_at
        FROM users
        ORDER BY user_id ASC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard - RedCine</title>
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

/* TOP BAR */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #111;
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid red;
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
}

.card h3 {
    font-size: 14px;
    color: #aaa;
    margin-bottom: 5px;
}

.card p {
    font-size: 24px;
    font-weight: bold;
    color: red;
}

/* TABLE */
.table-section {
    margin-top: 30px;
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
    white-space: nowrap;
}

table th {
    background: #1a1a1a;
    color: red;
}

.role-badge {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 12px;
    text-transform: capitalize;
}

.role-admin {
    background: red;
    color: #fff;
}

.role-customer {
    background: #333;
    color: #ccc;
}

.empty-row td {
    text-align: center;
    color: #888;
    padding: 20px;
}

/* BUTTONS */
.btn {
    padding: 6px 10px;
    border: none;
    cursor: pointer;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-size: 13px;
}

.edit {
    background: orange;
}

.delete {
    background: red;
}

.add-btn {
    background: red;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    float: right;
    margin-bottom: 10px;
    text-decoration: none;
}

.clearfix::after {
    content: "";
    display: table;
    clear: both;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php"class="active">Dashboard</a>
    <a href="users.php">Users</a>
    <a href="admin_bookings.php">Bookings</a>
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
    <h1>Admin Dashboard</h1>
    <div>Welcome, <?= htmlspecialchars($adminUsername) ?> 🎬</div>
</div>

<div class="cards">
    <div class="card">
        <h3>Total Movies</h3>
        <p><?= $total_movies ?></p>
    </div>
    <div class="card">
        <h3>Total Bookings</h3>
        <p><?= $total_bookings ?></p>
    </div>
    <div class="card">
        <h3>Registered Users</h3>
        <p><?= count($users) ?></p>
    </div>
    <div class="card">
        <h3>Total Revenue</h3>
        <p>Rs. <?= number_format($total_revenue, 2) ?></p>
    </div>
</div>

    <div class="table-section">
        <div class="clearfix">
            <h2 style="float: left; color: red; margin-bottom: 15px;">System Users</h2>
            <a href="add_user.php" class="add-btn">+ Add New User</a>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone Number</th>
                    <th>Role Status</th>
                    <th>Account Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= $user['user_id'] ?></td>
                            <td><strong><?= htmlspecialchars($user['name']) ?></strong></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone'] ?? 'N/A') ?></td>
                            <td>
                                <span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['status']) ?></td>
                            <td><?= date('d M Y, h:i A', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="btn edit">Edit</a>
                                <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="btn delete" onclick="return confirm('Are you sure you want to remove this profile?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="8">No user records loaded.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>