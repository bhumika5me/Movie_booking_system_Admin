<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $raw_pass = $_POST['password'] ?? '';

    if ($email === '' || $raw_pass === '') {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, password, role, status FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($raw_pass, $user['password'])) {
                    if ($user['role'] === 'admin') {
                        if ($user['status'] === 'active') {
                            $_SESSION['admin_id'] = $user['user_id'];
                            $_SESSION['admin_name'] = $user['name'];
                            $_SESSION['admin_email'] = $email;

                            header("Location: admin_dashboard.php");
                            exit();
                        } else {
                            $error = "Your admin account has been deactivated.";
                        }
                    } else {
                        $error = "Access denied. Not an administrator account.";
                    }
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login - RedCine</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- FontAwesome for Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
body {
    background: radial-gradient(circle at center, #1a1a1a 0%, #0b0b0b 100%);
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    padding: 20px;
}
.auth-container {
    background: rgba(17, 17, 17, 0.95);
    padding: 40px 30px;
    border-radius: 16px;
    width: 100%;
    max-width: 440px;
    border: 1px solid rgba(255, 0, 0, 0.2);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
}
.avatar-icon {
    width: 70px;
    height: 70px;
    background: rgba(255, 0, 0, 0.1);
    border: 2px solid red;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto 20px auto;
    color: red;
    font-size: 28px;
    box-shadow: 0 0 15px rgba(255, 0, 0, 0.3);
}
.auth-container h2 {
    color: white;
    text-align: center;
    margin-bottom: 5px;
    font-size: 24px;
    letter-spacing: 1px;
}
.auth-container h2 span {
    color: red;
}
.subtitle {
    text-align: center;
    color: #888;
    margin-bottom: 25px;
    font-size: 13px;
}
.form-group {
    margin-bottom: 18px;
    position: relative;
}
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 12px;
    font-weight: 600;
    color: #ccc;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.input-icon-wrapper {
    position: relative;
}
.input-icon-wrapper i {
    position: absolute;
    top: 50%;
    left: 14px;
    transform: translateY(-50%);
    color: #666;
    font-size: 14px;
}
.form-group input {
    width: 100%;
    padding: 12px 14px 12px 42px;
    border-radius: 8px;
    border: 1px solid #333;
    background: #151515;
    color: white;
    font-size: 14px;
    outline: none;
    transition: all 0.3s ease;
}
.form-group input:focus {
    border-color: red;
    background: #1a1a1a;
    box-shadow: 0 0 8px rgba(255, 0, 0, 0.25);
}
.form-group input:focus + i, .input-icon-wrapper i:focus-within {
    color: red;
}
.btn-submit {
    background: linear-gradient(135deg, #ff0000 0%, #b30000 100%);
    color: white;
    border: none;
    padding: 13px;
    width: 100%;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    font-size: 15px;
    letter-spacing: 0.5px;
    transition: all 0.3s ease;
    margin-top: 10px;
    box-shadow: 0 4px 15px rgba(255, 0, 0, 0.3);
}
.btn-submit:hover {
    background: linear-gradient(135deg, #e60000 0%, #990000 100%);
    box-shadow: 0 6px 20px rgba(255, 0, 0, 0.5);
    transform: translateY(-1px);
}
.alert-error {
    background: rgba(42, 18, 21, 0.8);
    border-left: 4px solid red;
    color: #f8d7da;
    padding: 12px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 13px;
}
.auth-links {
    text-align: center;
    margin-top: 20px;
    font-size: 13px;
    color: #888;
}
.auth-links a {
    color: red;
    text-decoration: none;
    font-weight: 600;
}
.auth-links a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="auth-container">
    <div class="avatar-icon">
        <i class="fa-solid fa-right-to-bracket"></i>
    </div>
    <h2>RED<span>CINE</span></h2>
    <div class="subtitle">Sign in to Management Portal</div>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label>Gmail Address</label>
            <div class="input-icon-wrapper">
                <i class="fa-solid fa-envelope"></i>
                <input type="email" name="email" placeholder="admin@gmail.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Password</label>
            <div class="input-icon-wrapper">
                <i class="fa-solid fa-lock"></i>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
        </div>
        <button type="submit" class="btn-submit">Login Portal</button>
    </form>

    <div class="auth-links">
        Need an admin account? <a href="admin_signup.php">Sign up</a>
    </div>
</div>

</body>
</html