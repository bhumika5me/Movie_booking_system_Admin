<?php
$id = isset($_GET['id']) ? $_GET['id'] : 1;

/* MOVIE DATA */

if($id == 1){

    $title = "Future Earth";
    $genre = "Sci-Fi | Adventure";
    $duration = "2h 15m";
    $release = "July 15, 2026";

    $poster = "assets/images/movie2.jpg";
    $banner = "assets/images/movie2.jpg";

    $story = "In the year 2150, humanity faces extinction as Earth becomes uninhabitable. A group of astronauts embarks on a dangerous mission to discover a new planet for mankind.";

    $trailer = "https://www.youtube.com/embed/ScMzIvxBSi4";
}
elseif($id == 2){

    $title = "The Last Kingdom";
    $genre = "Action | Fantasy";
    $duration = "2h 30m";
    $release = "August 02, 2026";

    $poster = "assets/images/movie3.jpg";
    $banner = "assets/images/movie3.jpg";

    $story = "An ancient kingdom stands on the edge of destruction as a fearless warrior rises to defend his people against dark forces.";
}
else{

    $title = "Shadow City";
    $genre = "Crime | Thriller";
    $duration = "2h 05m";
    $release = "August 18, 2026";

    $poster = "assets/images/movie1.jpg";
    $banner = "assets/images/movie1.jpg";

    $story = "A detective investigates a series of mysterious crimes that uncover the darkest secrets hidden within the city.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title; ?> - RedCine Cinemas</title>
<link rel="stylesheet" href="style.css">

<style>

/* GLOBAL */
body{
    margin:0;
    font-family:Arial, sans-serif;
    background:#0b0b0b;
    color:#fff;
}

/* HEADER (SAME AS INDEX.PHP STYLE) */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #000;
    border-bottom: 2px solid red;
}

/* LOGO (RED + WHITE) */
.logo {
    font-size: 28px;
    font-weight: bold;
    color: red;
}

.logo span {
    color: white;
}

/* NAV */
.nav-links {
    display: flex;
    gap: 25px;
}

.nav-links a {
    color: white;
    text-decoration: none;
    font-size: 16px;
    transition: 0.3s;
}

.nav-links a:hover {
    color: red;
}

/* LOGIN BUTTON */
.auth-btn a {
    padding: 8px 18px;
    border: 2px solid red;
    color: white;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.auth-btn a:hover {
    background: red;
    box-shadow: 0 0 12px red;
}

/* MOVIE SECTION */
.movie-container{
    display:flex;
    gap:50px;
    padding:60px;
    align-items:flex-start;
}

/* TRAILER */
.trailer{
    flex:1;
}

.trailer iframe{
    width:100%;
    height:450px;
    border:none;
    border-radius:10px;
}

/* DETAILS */
.movie-details{
    flex:1;
}

.movie-details h1{
    color:red;
    margin-bottom:15px;
}

.movie-details p{
    line-height:1.8;
    margin-bottom:12px;
}

/* RESPONSIVE */
@media(max-width:900px){

.movie-container{
    flex-direction:column;
}

.trailer iframe{
    height:300px;
}

}

</style>
</head>

<body>

<!-- HEADER -->
<header class="header">

    <div class="logo">RED<span>CINE</span></div>

    <nav class="nav-links">
        <a href="index.php">Home</a>
        <a href="nowshowing.php">Now Showing</a>
        <a href="comingsoon.php">Coming Soon</a>
        <a href="contact.php">Contact</a>
    </nav>

    <!-- LOGIN BUTTON (NO AUTH.JS) -->
    <div class="auth-btn">
        <a href="login.php">Login</a>
    </div>

</header>

<!-- MOVIE SECTION -->
<div class="movie-container">

    <!-- TRAILER -->
    <div class="trailer">
        <iframe 
            src="<?php echo $trailer; ?>" 
            allowfullscreen>
        </iframe>
    </div>

    <!-- DETAILS -->
    <div class="movie-details">
        <h1><?php echo $title; ?></h1>

        <p><strong>Genre:</strong> <?php echo $genre; ?></p>
        <p><strong>Duration:</strong> <?php echo $duration; ?></p>
        <p><strong>Release Date:</strong> <?php echo $release; ?></p>
        <p><strong>Language:</strong> English</p>
        <p><strong>Director:</strong> RedCine Studios</p>
        <p><strong>Synopsis:</strong><br><?php echo $story; ?></p>
    </div>

</div>

<!-- FOOTER -->
<?php include 'footer.php'; ?>

</body>
</html>