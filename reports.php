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
$report_data = null;
$active_report_meta = null;

// 2. HANDLE CREATE/GENERATE REPORT FORM SUBMISSION & DATA FETCHING
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type  = $_POST['report_type'] ?? 'Booking';
    $from_date    = trim($_POST['from_date'] ?? '');
    $to_date      = trim($_POST['to_date'] ?? '');
    $generated_by = intval($_POST['generated_by'] ?? 1);

    if ($from_date === '' || $to_date === '') {
        $error = "Please specify both from and to dates for the report.";
    } else {
        // Save report metadata
        $stmt = $conn->prepare("INSERT INTO reports (report_type, generated_by, from_date, to_date) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("siss", $report_type, $generated_by, $from_date, $to_date);
            $stmt->execute();
            $stmt->close();
            $success = "Report generated successfully!";

            // Fetch specific metrics based on report type & date range, explicitly binding `$report_type`
            $active_report_meta = ['type' => $report_type, 'from' => $from_date, 'to' => $to_date];
            
            if ($report_type === 'Booking') {
                $q = $conn->prepare("SELECT booking_status, COUNT(*) as count, SUM(total_amount) as revenue FROM bookings WHERE DATE(booking_date) BETWEEN ? AND ? GROUP BY booking_status");
                $q->bind_param("ss", $from_date, $to_date);
                $q->execute();
                $report_data = $q->get_result();
            } elseif ($report_type === 'Revenue') {
                $q = $conn->prepare("SELECT DATE(booking_date) as b_date, COUNT(*) as total_bookings, SUM(total_amount) as daily_revenue FROM bookings WHERE booking_status = 'Confirmed' AND DATE(booking_date) BETWEEN ? AND ? GROUP BY DATE(booking_date)");
                $q->bind_param("ss", $from_date, $to_date);
                $q->execute();
                $report_data = $q->get_result();
            } elseif ($report_type === 'Payment') {
                $q = $conn->prepare("SELECT p.payment_method, p.payment_status, COUNT(*) as count, SUM(p.amount) as total FROM payments p WHERE DATE(p.payment_date) BETWEEN ? AND ? GROUP BY p.payment_method, p.payment_status");
                $q->bind_param("ss", $from_date, $to_date);
                $q->execute();
                $report_data = $q->get_result();
            } elseif ($report_type === 'Movie') {
                $q = $conn->prepare("SELECT m.movie_name, COUNT(b.booking_id) as total_bookings, SUM(b.total_amount) as movie_revenue FROM movies m JOIN showtimes s ON m.movie_id = s.movie_id JOIN bookings b ON s.showtime_id = b.showtime_id WHERE DATE(b.booking_date) BETWEEN ? AND ? GROUP BY m.movie_id");
                $q->bind_param("ss", $from_date, $to_date);
                $q->execute();
                $report_data = $q->get_result();
            } elseif ($report_type === 'Showtime') {
                $q = $conn->prepare("SELECT s.show_date, s.start_time, m.movie_name, a.audi_name, s.status FROM showtimes s JOIN movies m ON s.movie_id = m.movie_id JOIN auditoriums a ON s.audi_id = a.audi_id WHERE s.show_date BETWEEN ? AND ?");
                $q->bind_param("ss", $from_date, $to_date);
                $q->execute();
                $report_data = $q->get_result();
            } elseif ($report_type === 'Customer') {
                $q = $conn->prepare("SELECT u.name, u.email, COUNT(b.booking_id) as total_bookings, SUM(b.total_amount) as total_spent FROM users u LEFT JOIN bookings b ON u.user_id = b.user_id WHERE u.role = 'customer' AND DATE(b.booking_date) BETWEEN ? AND ? GROUP BY u.user_id");
                $q->bind_param("ss", $from_date, $to_date);
                $q->execute();
                $report_data = $q->get_result();
            }
        }
    }
}

// 3. FETCH ADMIN USERS FOR DROPDOWN
$admins_result = $conn->query("SELECT user_id, name FROM users WHERE role='admin' ORDER BY name ASC");

// 4. FETCH ALL GENERATED REPORT LOGS FOR HISTORY TABLE
$sql = "SELECT r.report_id, r.report_type, r.from_date, r.to_date, r.generated_at, u.name AS admin_name 
        FROM reports r LEFT JOIN users u ON r.generated_by = u.user_id ORDER BY r.report_id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Reports Management - RedCine</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial, sans-serif; }
body { background: #0b0b0b; color: white; }
.sidebar { width: 250px; height: 100vh; background: #111; position: fixed; top: 0; left: 0; padding: 20px; border-right: 2px solid red; overflow-y: auto; }
.sidebar h2 { color: red; text-align: center; margin-bottom: 30px; }
.sidebar a { display: block; color: white; text-decoration: none; padding: 12px; margin: 8px 0; background: #1a1a1a; border-radius: 6px; }
.sidebar a:hover, .sidebar a.active { background: red; }
.main { margin-left: 260px; padding: 25px; }
.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding: 15px; background: #111; border-left: 4px solid red; border-radius: 10px; }
.cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px; }
.card { background: #1a1a1a; padding: 20px; border-radius: 10px; border-left: 4px solid red; }
.card h3 { font-size: 14px; color: #aaa; margin-bottom: 8px; }
.card p { font-size: 24px; font-weight: bold; color: white; }
.alert-success { background: #102316; border-left: 4px solid #28a745; color: #d4edda; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
.alert-error { background: #2a1215; border-left: 4px solid red; color: #f8d7da; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
.form-container, .report-results { background: #111; padding: 20px; border-radius: 10px; margin-bottom: 25px; border: 1px solid #222; }
.form-container h3, .report-results h3 { margin-bottom: 15px; color: red; }
.form-row { display: flex; gap: 15px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 6px; font-size: 13px; color: #aaa; }
.form-group input, .form-group select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #333; background: #1a1a1a; color: white; outline: none; }
.btn-submit { background: red; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; }
.btn-submit:hover { background: darkred; }
.filter-bar { margin-bottom: 15px; }
.filter-bar input { padding: 10px; width: 300px; border-radius: 6px; border: 1px solid #333; background: #1a1a1a; color: white; outline: none; }
.table-section { background: #111; padding: 20px; border-radius: 10px; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
table th, table td { padding: 12px; border-bottom: 1px solid #333; text-align: left; }
table th { background: #1a1a1a; color: red; }
table tr:hover { background: #161616; }
.type-badge { background: #222; border: 1px solid red; padding: 4px 8px; border-radius: 4px; font-size: 11px; color: #ff4d4d; font-weight: bold; }
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
    <a href="payments.php">Payments</a>
    <a href="reports.php" class="active">Reports</a>
    <a href="admin_logout.php">Logout</a>
</div>

<div class="main">
    <div class="topbar">
        <h1>Reports Management</h1>
    </div>

    <?php if (!empty($success)): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>

    <div class="cards">
        <div class="card"><h3>Total Generated Reports History</h3><p><?php echo $result ? $result->num_rows : 0; ?></p></div>
        <div class="card"><h3>System Status</h3><p style="color: #28a745;">Active</p></div>
    </div>

    <!-- Generate Report Form -->
    <div class="form-container">
        <h3>Generate New Report</h3>
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label>Report Type</label>
                    <select name="report_type">
                        <option value="Booking">Booking</option>
                        <option value="Revenue">Revenue</option>
                        <option value="Payment">Payment</option>
                        <option value="Movie">Movie</option>
                        <option value="Showtime">Showtime</option>
                        <option value="Customer">Customer</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="from_date" required>
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="to_date" required>
                </div>
                <div class="form-group">
                    <label>Generated By (Admin)</label>
                    <select name="generated_by" required>
                        <?php 
                        if ($admins_result && $admins_result->num_rows > 0) {
                            while($adm = $admins_result->fetch_assoc()) {
                                echo "<option value='".$adm['user_id']."'>".htmlspecialchars($adm['name'])."</option>";
                            }
                        } else {
                            echo "<option value='1'>Default Admin</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="generate_report" class="btn-submit">Generate Report & View Data</button>
        </form>
    </div>

    <!-- Live Output Results Box -->
    <?php if ($active_report_meta && $report_data): ?>
    <div class="report-results">
        <h3>Report Output: <?php echo $active_report_meta['type']; ?> (<?php echo $active_report_meta['from']; ?> to <?php echo $active_report_meta['to']; ?>)</h3>
        <table>
            <thead>
                <tr>
                    <?php if ($active_report_meta['type'] === 'Booking'): ?>
                        <th>Status</th><th>Total Count</th><th>Total Revenue</th>
                    <?php elseif ($active_report_meta['type'] === 'Revenue'): ?>
                        <th>Date</th><th>Total Bookings</th><th>Daily Revenue</th>
                    <?php elseif ($active_report_meta['type'] === 'Payment'): ?>
                        <th>Method</th><th>Status</th><th>Transaction Count</th><th>Total Amount</th>
                    <?php elseif ($active_report_meta['type'] === 'Movie'): ?>
                        <th>Movie Name</th><th>Total Bookings</th><th>Total Revenue</th>
                    <?php elseif ($active_report_meta['type'] === 'Showtime'): ?>
                        <th>Show Date</th><th>Start Time</th><th>Movie</th><th>Auditorium</th><th>Status</th>
                    <?php elseif ($active_report_meta['type'] === 'Customer'): ?>
                        <th>Customer Name</th><th>Email</th><th>Total Bookings</th><th>Total Spent</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($report_data->num_rows > 0): ?>
                    <?php while($row = $report_data->fetch_assoc()): ?>
                        <tr>
                            <?php if ($active_report_meta['type'] === 'Booking'): ?>
                                <td><?php echo $row['booking_status']; ?></td><td><?php echo $row['count']; ?></td><td>Rs. <?php echo number_format($row['revenue'] ?? 0, 2); ?></td>
                            <?php elseif ($active_report_meta['type'] === 'Revenue'): ?>
                                <td><?php echo $row['b_date']; ?></td><td><?php echo $row['total_bookings']; ?></td><td>Rs. <?php echo number_format($row['daily_revenue'] ?? 0, 2); ?></td>
                            <?php elseif ($active_report_meta['type'] === 'Payment'): ?>
                                <td><?php echo $row['payment_method']; ?></td><td><?php echo $row['payment_status']; ?></td><td><?php echo $row['count']; ?></td><td>Rs. <?php echo number_format($row['total'] ?? 0, 2); ?></td>
                            <?php elseif ($active_report_meta['type'] === 'Movie'): ?>
                                <td><?php echo htmlspecialchars($row['movie_name']); ?></td><td><?php echo $row['total_bookings']; ?></td><td>Rs. <?php echo number_format($row['movie_revenue'] ?? 0, 2); ?></td>
                            <?php elseif ($active_report_meta['type'] === 'Showtime'): ?>
                                <td><?php echo $row['show_date']; ?></td><td><?php echo $row['start_time']; ?></td><td><?php echo htmlspecialchars($row['movie_name']); ?></td><td><?php echo htmlspecialchars($row['audi_name']); ?></td><td><?php echo $row['status']; ?></td>
                            <?php elseif ($active_report_meta['type'] === 'Customer'): ?>
                                <td><?php echo htmlspecialchars($row['name']); ?></td><td><?php echo htmlspecialchars($row['email']); ?></td><td><?php echo $row['total_bookings'] ?? 0; ?></td><td>Rs. <?php echo number_format($row['total_spent'] ?? 0, 2); ?></td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#777; padding:15px;">No data found for this date range.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- History Log Table -->
    <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search report history...">
    </div>

    <div class="table-section">
        <h3>Generated Reports History</h3>
        <br>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Report Type</th>
                    <th>Date Range</th>
                    <th>Generated By</th>
                    <th>Generated At</th>
                </tr>
            </thead>
            <tbody id="reportTableBody">
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $adminName = !empty($row['admin_name']) ? $row['admin_name'] : "Admin ID: " . $row['generated_by'];
                        ?>
                        <tr>
                            <td>#<?php echo $row['report_id']; ?></td>
                            <td><span class="type-badge"><?php echo htmlspecialchars($row['report_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['from_date']) . " to " . htmlspecialchars($row['to_date']); ?></td>
                            <td><?php echo htmlspecialchars($adminName); ?></td>
                            <td><?php echo htmlspecialchars($row['generated_at']); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='5' style='text-align:center; padding: 20px; color:#666;'>No reports found in the database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const searchInput = document.getElementById("searchInput");
const tableBody = document.getElementById("reportTableBody");
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