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

// 2. HANDLE FORM SUBMISSION FOR ADDING A USER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $raw_pass = $_POST['password'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');
    $role     = $_POST['role'] ?? 'customer';
    $status   = $_POST['status'] ?? 'active';

    if ($name === '' || $email === '' || $raw_pass === '') {
        $error = "Please fill in all required fields (Name, Email, Password).";
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error = "An account with this email address already exists.";
        } else {
            $check_stmt->close();

            // Hash the password securely
            $hashed_password = password_hash($raw_pass, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssssss", $name, $email, $hashed_password, $phone, $role, $status);
                if ($stmt->execute()) {
                    $success = "New user added successfully!";
                    // Clear fields after success
                    $name = $email = $phone = "";
                } else {
                    $error = "Error adding user: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add New User - RedCine</title>
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
    padding: 25px;
    border-radius: 10px;
    border: 1px solid #222;
    max-width: 700px;
}

.form-container h3 {
    margin-bottom: 20px;
    color: red;
}

.form-group {
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
    margin-top: 10px;
}

.btn-submit:hover {
    background: darkred;
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
</style>
</head>

<body>

<div class="sidebar">
    <h2>RED<span style="color:white;">CINE</span></h2>
    <a href="admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">Dashboard</a>
    <a href="users.php" class="<?php echo ($current_page == 'users.php' || $current_page == 'add_user.php') ? 'active' : ''; ?>">Users</a>
    <a href="admin_bookings.php" class="<?php echo ($current_page == 'admin_bookings.php') ? 'active' : ''; ?>">Bookings</a>
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
        <h1>User Management</h1>
    </div>

    <a href="users.php" class="back-link">&larr; Back to Users List</a>

    <?php if (!empty($success)): ?>
        <div class="alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <h3>Add New User Account</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" placeholder="Enter full name" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" placeholder="Enter email address" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter secure password" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="e.g., 9800000000">
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="customer">Customer</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Account Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <button type="submit" name="add_user" class="btn-submit">Create User</button>
        </form>
    </div>

</div>

</body>
</html>