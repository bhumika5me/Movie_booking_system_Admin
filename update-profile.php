<?php
// update-profile.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile Updated</title>

<style>
body{
    background:#0b0b0b;
    color:white;
    font-family:Arial,sans-serif;
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
}

.success-box{
    width:450px;
    background:#111;
    border:1px solid #222;
    border-radius:10px;
    padding:40px;
    text-align:center;
}

.success-box h1{
    color:#ff0000;
    margin-bottom:15px;
}

.success-box p{
    color:#bbb;
    margin-bottom:25px;
}

.back-btn{
    display:inline-block;
    padding:12px 25px;
    background:red;
    color:white;
    text-decoration:none;
    border-radius:6px;
}

.back-btn:hover{
    background:#cc0000;
}
</style>
</head>
<body>

<div class="success-box">
    <h1>Profile Updated</h1>
    <p>Your profile information has been submitted successfully.</p>

    <a href="myaccount.php" class="back-btn">
        Back to My Account
    </a>
</div>

</body>
</html>