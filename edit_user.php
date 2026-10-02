<?php
session_start();
require __DIR__ . '/../db.php'; // Reliable database connection path

// ── LOGIN GUARD ──
if (empty($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if user ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$user_id = intval($_GET['id']);
$error = '';

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || empty($email)) {
        $error = "Name and Email fields are required.";
    } else {
        // Update user query matching your database schema
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE user_id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $role, $status, $user_id);
        
        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            // Redirect immediately to users.php upon success
            header("Location: users.php");
            exit;
        } else {
            $error = "Error updating record: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch current user details
$stmt = $conn->prepare("SELECT user_id, name, email, phone, role, status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User - RedCine Admin</title>
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

/* FORM CONTAINER */
.form-container {
    margin-top: 30px;
    background: #111;
    padding: 30px;
    border-radius: 10px;
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #aaa;
    font-size: 14px;
}

.form-group input, .form-group select {
    width: 100%;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #333;
    background: #1a1a1a;
    color: #fff;
    font-size: 14px;
    outline: none;
}

.form-group input:focus, .form-group select:focus {
    border-color: red;
}

.btn-submit {
    background: red;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 15px;
    font-weight: bold;
    text-decoration: none;
    display: inline-block;
}

.btn-submit:hover {
    opacity: 0.9;
}

.btn-back {
    background: #333;
    color: white;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    margin-left: 10px;
    font-size: 15px;
}

.alert-error {
    background: rgba(255, 0, 0, 0.2);
    border-left: 4px solid red;
    padding: 12px;
    margin-bottom: 20px;
    color: #ff8080;
    border-radius: 4px;
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
    <a href="payments.php">Payments</a>
    <a href="admin_logout.php">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Modify User Profile (#<?= $user['user_id'] ?>)</h1>
    </div>

    <div class="form-container">
        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="edit_user.php?id=<?= $user['user_id'] ?>" method="POST">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="role">System Role</label>
                <select id="role" name="role">
                    <option value="customer" <?= ($user['role'] === 'customer') ? 'selected' : '' ?>>Customer</option>
                    <option value="admin" <?= ($user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Account Status</label>
                <select id="status" name="status">
                    <option value="active" <?= ($user['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($user['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <button type="submit" class="btn-submit">Update User</button>
            <a href="users.php" class="btn-back">Cancel</a>
        </form>
    </div>

</div>

</body>
</html>