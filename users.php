<?php
session_start();

// 1. DATABASE CONNECTION
$host = "localhost";
$user = "root";
$password = "";
$dbname = "movie_booking"; // Your database name

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. FETCH REGISTERED USERS ACCORDING TO YOUR SCHEMA SPECIFICATION
$sql = "SELECT user_id, name, email, phone, role, status, created_at FROM users ORDER BY user_id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users - RedCine Admin</title>
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

.topbar h1 {
    font-size: 22px;
}

/* SEARCH BOX */
.search-box {
    padding: 10px 12px;
    width: 250px;
    border-radius: 6px;
    border: 1px solid #333;
    outline: none;
    background: #1a1a1a;
    color: #fff;
    font-size: 14px;
}

.search-box::placeholder {
    color: #aaa;
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
    text-align: left;
}

table th {
    background: #1a1a1a;
    color: red;
}

/* ROLE STATUS STYLE */
.status {
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.role-admin {
    background: red;
    color: white;
}

.role-customer {
    background: #333;
    color: #ccc;
}

/* BUTTONS */
.btn {
    display: inline-block;
    padding: 6px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-right: 5px;
    margin-bottom: 3px;
    text-decoration: none;
    color: white;
    font-size: 13px;
    transition: opacity 0.2s;
}

.btn:hover {
    opacity: 0.9;
}

.view {
    background: #2b7bf4;
}

.delete {
    background: red;
}

.history {
    background: orange;
}

.msg-btn {
    background: #00b359;
}
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="users.php" class="active">Users</a>
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
        <h1>User Management</h1>
        <input type="text" class="search-box" placeholder="Search name, email, phone...">
    </div>

    <div class="table-section">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email Address</th>
                    <th>Phone Number</th>
                    <th>System Role</th>
                    <th>Account Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Map explicit CSS styles onto individual dynamic Enum roles
                        $roleClass = ($row['role'] === 'admin') ? 'role-admin' : 'role-customer';
                        ?>
                        <tr>
                            <td>#<?php echo $row['user_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone'] ?? 'N/A'); ?></td>
                            <td><span class="status <?php echo $roleClass; ?>"><?php echo htmlspecialchars($row['role']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['status']); ?></td>
                            <td><?php echo date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                            <td>
                                <a class="btn view" href="edit_user.php?id=<?php echo $row['user_id']; ?>">Modify</a>
                                <a class="btn delete" href="users_delete.php?id=<?php echo $row['user_id']; ?>" onclick="return confirm('Are you sure you want to delete this profile?');">Delete</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='8' style='text-align:center; padding: 30px; color:#666;'>No registered users found in the database.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function logout(event) {
    event.preventDefault();
    alert("Logged out successfully!");
    window.location.href = "admin_login.php";
}

// LIVE JAVASCRIPT INSTANT SEARCH FILTER SYSTEM
const searchInput = document.querySelector(".search-box");
const rows = document.querySelectorAll("table tbody tr");

searchInput.addEventListener("input", function () {
    const value = this.value.toLowerCase().trim();

    rows.forEach(row => {
        // Fallback optimization if table shows the empty row notice
        if (row.cells.length < 8) return;

        const name = row.cells[1].innerText.toLowerCase();
        const email = row.cells[2].innerText.toLowerCase();
        const phone = row.cells[3].innerText.toLowerCase();

        if (name.includes(value) || email.includes(value) || phone.includes(value)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});
</script>

</body>
</html>