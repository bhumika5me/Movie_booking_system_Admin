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

// Prevent an admin from accidentally deleting their own currently logged-in account
if ($user_id === intval($_SESSION['admin_id'])) {
    $_SESSION['error_msg'] = "You cannot delete your own active administrator account.";
    header("Location: users.php");
    exit;
}

// Delete user query matching your database schema
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $_SESSION['success_msg'] = "User profile deleted successfully.";
} else {
    $_SESSION['error_msg'] = "Error deleting record: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: users.php");
exit;
?>