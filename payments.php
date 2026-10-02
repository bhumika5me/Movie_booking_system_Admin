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

// 2. HANDLE ADD PAYMENT FORM SUBMISSION (Optional manual entry for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $booking_id     = intval($_POST['booking_id'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    $transaction_id = trim($_POST['transaction_id'] ?? '');
    $amount         = floatval($_POST['amount'] ?? 0);
    $payment_status = $_POST['payment_status'] ?? 'Pending';

    if ($booking_id <= 0 || $amount <= 0) {
        $error = "Please fill in all required payment details correctly.";
    } else {
        // If transaction ID is empty for manual entries, make it null or unique
        $txn = ($transaction_id === '') ? null : $transaction_id;
        
        $stmt = $conn->prepare("INSERT INTO payments (booking_id, payment_method, transaction_id, amount, payment_status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issds", $booking_id, $payment_method, $txn, $amount, $payment_status);
            if ($stmt->execute()) {
                $success = "Payment recorded successfully!";
            } else {
                $error = "Error recording payment (Transaction ID might already exist): " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// 3. FETCH BOOKINGS FOR DROPDOWN
$bookings_result = $conn->query("SELECT booking_id, booking_code, total_amount FROM bookings ORDER BY booking_id DESC");

// 4. FETCH PAYMENTS JOINING BOOKINGS AND USERS
$sql = "SELECT 
            p.payment_id,
            p.payment_method,
            p.transaction_id,
            p.amount,
            p.payment_status,
            p.payment_date,
            b.booking_code,
            u.name AS customer_name
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        LEFT JOIN users u ON b.user_id = u.user_id
        ORDER BY p.payment_id DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments Management - RedCine</title>
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
.success { background: #198754; color: white; }
.pending { background: #ffc107; color: black; }
.failed { background: red; color: white; }
.refunded { background: #6c757d; color: white; }

.method-badge {
    background: #222;
    border: 1px solid red;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 11px;
    color: #ff4d4d;
}

.amount-text {
    color: #4ecc6d;
    font-weight: bold;
}
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
    <a href="showtimes.php">Showtimes</a>
    <a href="movies.php">Movies</a>
    <a href="payments.php" class="active">Payments</a>
    <a href="reports.php">Reports</a>
    <a href="admin_logout.php">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Payments Management</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php
    $total_payments = $result ? $result->num_rows : 0;
    $success_revenue_query = $conn->query("SELECT SUM(amount) as rev FROM payments WHERE payment_status='Success'")->fetch_assoc();
    $total_revenue = $success_revenue_query['rev'] ?? 0;
    $pending_count = $conn->query("SELECT COUNT(*) as total FROM payments WHERE payment_status='Pending'")->fetch_assoc()['total'];
    ?>
    <div class="cards">
        <div class="card"><h3>Total Transactions</h3><p><?php echo $total_payments; ?></p></div>
        <div class="card"><h3>Successful Revenue</h3><p>Rs. <?php echo number_format($total_revenue, 2); ?></p></div>
        <div class="card"><h3>Pending Payments</h3><p><?php echo $pending_count; ?></p></div>
    </div>

    <!-- Record Payment Form -->
    <div class="form-container">
        <h3>Record New Payment</h3>
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Booking Code</label>
                    <select name="booking_id" required>
                        <option value="">Select Booking</option>
                        <?php 
                        if ($bookings_result && $bookings_result->num_rows > 0) {
                            while($bk = $bookings_result->fetch_assoc()) {
                                echo "<option value='".$bk['booking_id']."'>".$bk['booking_code']." (Rs. ".$bk['total_amount'].")</option>";
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Method</label>
                    <select name="payment_method">
                        <option value="eSewa">eSewa</option>
                        <option value="Khalti">Khalti</option>
                        <option value="Card">Card</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Transaction ID</label>
                    <input type="text" name="transaction_id" placeholder="e.g., TXN123456789">
                </div>
                <div class="form-group">
                    <label>Amount (Rs.)</label>
                    <input type="number" step="0.01" name="amount" placeholder="0.00" min="0" required>
                </div>
                <div class="form-group">
                    <label>Payment Status</label>
                    <select name="payment_status">
                        <option value="Pending">Pending</option>
                        <option value="Success">Success</option>
                        <option value="Failed">Failed</option>
                        <option value="Refunded">Refunded</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_payment" class="btn-submit">Record Payment</button>
        </form>
    </div>

    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search booking code, customer, or transaction ID...">
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Booking Code</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Transaction ID</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="paymentTableBody">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $statusClass = strtolower($row['payment_status']);
                        $customer = !empty($row['customer_name']) ? $row['customer_name'] : "Guest/Unknown";
                        $txnId = !empty($row['transaction_id']) ? $row['transaction_id'] : "N/A";
                        ?>
                        <tr>
                            <td>#<?php echo $row['payment_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['booking_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer); ?></td>
                            <td><span class="method-badge"><?php echo htmlspecialchars($row['payment_method']); ?></span></td>
                            <td><?php echo htmlspecialchars($txnId); ?></td>
                            <td><span class="amount-text">Rs. <?php echo number_format($row['amount'], 2); ?></span></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['payment_status']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['payment_date']); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center; padding: 20px; color:#666;'>No payments found in the database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const searchInput = document.getElementById("searchInput");
const tableBody = document.getElementById("paymentTableBody");
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