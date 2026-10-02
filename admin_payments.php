<?php
session_start();

// 1. DATABASE CONNECTION
$host = "localhost";
$user = "root";
$password = "";
$dbname = "one"; // Database name

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. FETCH REAL-TIME SUMMARY STATS
$total_payments_query = $conn->query("SELECT COUNT(*) as total FROM payments");
$total_payments = $total_payments_query->fetch_assoc()['total'] ?? 0;

$total_revenue_query = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'success'");
$total_revenue = $total_revenue_query->fetch_assoc()['total'] ?? 0.00;

$paid_count_query = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'success'");
$paid_count = $paid_count_query->fetch_assoc()['total'] ?? 0;

$pending_count_query = $conn->query("SELECT COUNT(*) as total FROM payments WHERE status = 'pending'");
$pending_count = $pending_count_query->fetch_assoc()['total'] ?? 0;


// 3. FETCH COMPREHENSIVE PAYMENTS LOG (FIXED TO PREVENT DUPLICATION BY GROUPING BOOKINGS FIRST)
$sql = "SELECT 
            p.*, 
            CONCAT(u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), ''), ' ', u.last_name) AS customer_name,
            m.movie_name AS movie_title
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN (
            SELECT booking_code, movie_id 
            FROM bookings 
            GROUP BY booking_code
        ) b ON p.booking_code = b.booking_code
        LEFT JOIN nowshowing m ON b.movie_id = m.id
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Payments - RedCine</title>
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
    z-index: 100;
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

/* TABLE */
.table-section {
    margin-top: 30px;
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
    text-align: center;
}

table th {
    background: #1a1a1a;
    color: red;
}

/* STATUS HIGHLIGHTING ENGINE */
.status-text {
    font-weight: bold;
    text-transform: capitalize;
}
.status-success { color: lime; }
.status-pending { color: orange; }
.status-failed { color: red; }
.status-refunded { color: #888; }

/* VIEW BUTTON */
.view-btn {
    padding: 6px 12px;
    background: red;
    color: white;
    border: none;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
}

.view-btn:hover {
    background: darkred;
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
        <h1>Payment Management</h1>
        <div>Welcome Admin 🎬</div>
    </div>

    <div class="cards">
        <div class="card">
            <h3>Total Payments</h3>
            <p><?php echo $total_payments; ?></p>
        </div>
        <div class="card">
            <h3>Total Revenue</h3>
            <p>Rs. <?php echo number_format($total_revenue, 2); ?></p>
        </div>
        <div class="card">
            <h3>Paid</h3>
            <p><?php echo $paid_count; ?></p>
        </div>
        <div class="card">
            <h3>Pending</h3>
            <p><?php echo $pending_count; ?></p>
        </div>
    </div>

    <div class="table-section">
        <h2>All Payments</h2>
        <br>
        <table>
            <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Transaction Code</th>
                    <th>Booking Code</th>
                    <th>User</th>
                    <th>Movie</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Resolve system labels fallback
                        $customer = !empty(trim($row['customer_name'])) ? $row['customer_name'] : "User ID: " . $row['user_id'];
                        $movie = !empty($row['movie_title']) ? $row['movie_title'] : "N/A (No Linked Booking)";
                        
                        // Parse status safely for UI mapping
                        $ui_status = $row['status'];
                        $css_class = "status-" . $ui_status;
                        if($ui_status == 'success') $ui_status = 'Paid';
                        ?>
                        <tr>
                            <td>#PAY-<?php echo $row['id']; ?></td>
                            <td><small style="color:#aaa; font-family:monospace;"><?php echo htmlspecialchars($row['tcode']); ?></small></td>
                            <td><strong><?php echo htmlspecialchars($row['booking_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer); ?></td>
                            <td><?php echo htmlspecialchars($movie); ?></td>
                            <td style="color: lime; font-weight: bold;">Rs. <?php echo number_format($row['amount'], 2); ?></td>
                            <td style="text-transform: uppercase; font-size: 13px;">
                                <?php echo htmlspecialchars($row['method'] === 'bank' ? 'IME Pay' : $row['method']); ?>
                            </td>
                            <td class="status-text <?php echo $css_class; ?>"><?php echo htmlspecialchars($ui_status); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a href="admin_payment_view.php?id=<?php echo $row['id']; ?>" class="view-btn">View</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='10' style='text-align:center; padding: 20px; color:#aaa;'>No payment history records found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>