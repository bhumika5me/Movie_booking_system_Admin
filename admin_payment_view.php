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

// 2. CHECK IF ID IS SET
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Missing transaction parameter ID.");
}

$payment_id = intval($_GET['id']);

// 3. FETCH COMPREHENSIVE PAYMENT RECORD
$sql = "SELECT 
            p.*, 
            CONCAT(u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), ''), ' ', u.last_name) AS customer_name,
            u.email AS customer_email,
            u.mobile AS customer_mobile,
            b.show_date,
            b.show_time,
            b.audi,
            b.seat_no,
            m.movie_name AS movie_title
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN bookings b ON p.booking_code = b.booking_code
        LEFT JOIN nowshowing m ON b.movie_id = m.id
        WHERE p.id = ? 
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Error: Transaction record not found.");
}

$payment = $result->fetch_assoc();

// Clean UI Status mapping
$display_status = $payment['status'];
if($display_status == 'success') $display_status = 'Paid';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt View #<?php echo $payment['id']; ?> - RedCine</title>
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
    padding: 25px;
}

/* TOPBAR */
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

/* GRID SETUP */
.receipt-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.receipt-card {
    background: #111;
    padding: 20px;
    border-radius: 10px;
    border-top: 3px solid #222;
}

.receipt-card h3 {
    color: red;
    margin-bottom: 15px;
    border-bottom: 1px solid #222;
    padding-bottom: 8px;
    font-size: 15px;
    text-transform: uppercase;
}

.row-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #1a1a1a;
}

.row-item span:first-child {
    color: #888;
}

.row-item span:last-child {
    font-weight: bold;
}

/* UTILITIES BUTTONS */
.btn-group {
    display: flex;
    gap: 10px;
}

.btn {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-decoration: none;
    color: white;
    font-weight: bold;
    font-size: 14px;
}

.back { background: #333; }
.back:hover { background: #444; }
.print { background: red; }
.print:hover { background: darkred; }

/* REVENUE HIGHLIGHT BANNER */
.invoice-total-banner {
    grid-column: span 2;
    background: #1a1a1a;
    border-left: 4px solid lime;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 12px;
    text-transform: uppercase;
    font-weight: bold;
}
.status-success { background: green; color: white; }
.status-pending { background: orange; color: black; }
.status-failed { background: red; color: white; }
.status-refunded { background: gray; color: white; }

/* PRINT RULES */
@media print {
    .sidebar, .topbar, .btn-group { display: none !important; }
    .main { margin-left: 0 !important; padding: 0 !important; }
    body { background: white; color: black; }
    .receipt-card { background: #fff; color: #000; border: 1px solid #ccc; }
    .row-item { border-bottom: 1px solid #ccc; }
    .row-item span:first-child { color: #555; }
    .invoice-total-banner { background: #eee; color: #000; border: 1px solid #000; }
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
        <h1>Transaction Details</h1>
        <div class="btn-group">
            <a href="admin_payments.php" class="btn back">← Back to Payments</a>
            <button onclick="window.print()" class="btn print">Print Statement</button>
        </div>
    </div>

    <div class="receipt-grid">

        <div class="receipt-card">
            <h3>Payment Gateway Audit</h3>
            <div class="row-item">
                <span>System Payment ID</span>
                <span>#PAY-<?php echo $payment['id']; ?></span>
            </div>
            <div class="row-item">
                <span>Gateway Trans Code (tcode)</span>
                <span style="font-family: monospace; color: #ccc;"><?php echo htmlspecialchars($payment['tcode']); ?></span>
            </div>
            <div class="row-item">
                <span>Linked Booking Reference</span>
                <span style="color:red;"><?php echo htmlspecialchars($payment['booking_code']); ?></span>
            </div>
            <div class="row-item">
                <span>Payment Channel Method</span>
                <span style="text-transform: uppercase;"><?php echo htmlspecialchars($payment['method']); ?></span>
            </div>
            <div class="row-item">
                <span>Processing Timestamp</span>
                <span><?php echo date('d M Y - h:i A', strtotime($payment['created_at'])); ?></span>
            </div>
        </div>

        <div class="receipt-card">
            <h3>Payer Information</h3>
            <div class="row-item">
                <span>Customer Profile Name</span>
                <span><?php echo htmlspecialchars(!empty(trim($payment['customer_name'])) ? $payment['customer_name'] : "Unknown Payer (ID: " . $payment['user_id'] . ")"); ?></span>
            </div>
            <div class="row-item">
                <span>Mobile Contact</span>
                <span><?php echo htmlspecialchars($payment['customer_mobile'] ?? 'N/A'); ?></span>
            </div>
            <div class="row-item">
                <span>Email Registration Address</span>
                <span><?php echo htmlspecialchars($payment['customer_email'] ?? 'N/A'); ?></span>
            </div>
            <div class="row-item">
                <span>Database User ID Block</span>
                <span>User Account #<?php echo htmlspecialchars($payment['user_id']); ?></span>
            </div>
        </div>

        <div class="receipt-card">
            <h3>Associated Product Allocation</h3>
            <div class="row-item">
                <span>Assigned Movie Title</span>
                <span><?php echo htmlspecialchars($payment['movie_title'] ?? 'N/A (No active linked booking schedule found)'); ?></span>
            </div>
            <div class="row-item">
                <span>Assigned Seats</span>
                <span style="color:red;"><?php echo htmlspecialchars($payment['seat_no'] ?? 'N/A'); ?></span>
            </div>
            <div class="row-item">
                <span>Auditorium Hall</span>
                <span><?php echo htmlspecialchars($payment['audi'] ?? 'N/A'); ?></span>
            </div>
            <div class="row-item">
                <span>Scheduled Show Details</span>
                <span><?php echo htmlspecialchars($payment['show_date'] ?? 'N/A'); ?> (<?php echo htmlspecialchars($payment['show_time'] ?? 'N/A'); ?>)</span>
            </div>
        </div>

        <div class="invoice-total-banner">
            <div>
                <p style="color:#aaa; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Transaction Settlement Status</p>
                <span class="status-badge status-<?php echo $payment['status']; ?>" style="margin-top:5px; display:inline-block;">
                    <?php echo htmlspecialchars($display_status); ?>
                </span>
            </div>
            <div style="text-align: right;">
                <p style="color:#aaa; font-size:12px; text-transform:uppercase;">Total Collected Net Amount</p>
                <h2 style="color: lime; font-size: 26px; margin-top:2px;">Rs. <?php echo number_format($payment['amount'], 2); ?></h2>
            </div>
        </div>

    </div>

</div>

</body>
</html>