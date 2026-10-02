<?php
session_start();

// 1. DATABASE CONNECTION
$host = "localhost";
$user = "root";
$password = "";
$dbname = "one"; 

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. GET USER ID FROM URL & SANITIZE
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($userId <= 0) {
    echo "<script>alert('Invalid User Specification.'); window.location.href='users.php';</script>";
    exit;
}

// 3. FETCH USER METADATA (To show their name at the top)
$userSql = "SELECT first_name, middle_name, last_name, email FROM users WHERE id = ?";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    echo "<script>alert('User profile not found.'); window.location.href='users.php';</script>";
    exit;
}

$userRow = $userResult->fetch_assoc();
$customerName = trim($userRow['first_name'] . ' ' . $userRow['middle_name'] . ' ' . $userRow['last_name']);
$customerName = str_replace('  ', ' ', $customerName);
$userStmt->close();

// 4. FETCH MESSAGES ASSOCIATED WITH THIS USER (Prepared Statement)
$msgSql = "SELECT id, email, subject, message, created_at FROM messages WHERE user_id = ? ORDER BY id DESC";
$msgStmt = $conn->prepare($msgSql);
$msgStmt->bind_param("i", $userId);
$msgStmt->execute();
$messagesResult = $msgStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Messages Inbox - RedCine Admin</title>
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

/* MAIN CONTENT */
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

.back-btn {
    padding: 8px 16px;
    background: #333;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
    font-weight: bold;
    border: 1px solid #444;
    transition: background 0.2s;
}

.back-btn:hover {
    background: red;
    border-color: red;
}

/* MESSAGE CARD DISPLAY PANEL */
.messages-section {
    margin-top: 30px;
}

.message-card {
    background: #111;
    border: 1px solid #222;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
}

.message-header {
    display: flex;
    justify-content: space-between;
    border-bottom: 1px solid #222;
    padding-bottom: 12px;
    margin-bottom: 12px;
}

.message-subject {
    font-size: 18px;
    color: red;
    font-weight: bold;
}

.message-meta {
    font-size: 13px;
    color: #aaa;
    text-align: right;
}

.message-body {
    font-size: 15px;
    line-height: 1.6;
    color: #ddd;
    background: #161616;
    padding: 15px;
    border-radius: 6px;
    white-space: pre-wrap; /* Maintains line-breaks from textareas cleanly */
}

.no-messages {
    background: #111;
    text-align: center;
    padding: 40px;
    border-radius: 10px;
    color: #666;
    font-size: 16px;
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
    <a href="admin_login.php" onclick="logout(event)">Logout</a>
</div>

<div class="main">

    <div class="topbar">
        <h1>Messages Inbox &rarr; <span style="color:red;"><?php echo htmlspecialchars($customerName); ?></span></h1>
        <a href="users.php" class="back-btn">&larr; Back to Users</a>
    </div>

    <div class="messages-section">
        <?php
        if ($messagesResult && $messagesResult->num_rows > 0) {
            while ($msgRow = $messagesResult->fetch_assoc()) {
                ?>
                <div class="message-card">
                    <div class="message-header">
                        <div>
                            <div class="message-subject"><?php echo htmlspecialchars($msgRow['subject']); ?></div>
                            <small style="color: #888;">Submitted via contact email: <strong><?php echo htmlspecialchars($msgRow['email']); ?></strong></small>
                        </div>
                        <div class="message-meta">
                            <div>Message ID: #<?php echo $msgRow['id']; ?></div>
                            <div>Received: <?php echo date('d M Y, h:i A', strtotime($msgRow['created_at'])); ?></div>
                        </div>
                    </div>
                    <div class="message-body"><?php echo htmlspecialchars($msgRow['message']); ?></div>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="no-messages">
                <p>This user has not submitted any messages or support requests yet.</p>
            </div>
            <?php
        }
        $msgStmt->close();
        $conn->close();
        ?>
    </div>

</div>

<script>
function logout(event) {
    event.preventDefault();
    alert("Logged out successfully!");
    window.location.href = "admin_login.php";
}
</script>

</body>
</html>