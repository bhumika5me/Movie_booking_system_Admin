document.addEventListener("DOMContentLoaded", function () {

    const authArea = document.getElementById("authArea");
    if (!authArea) return;

    const userEmail = localStorage.getItem("userEmail");
    let userName = localStorage.getItem("userName");

    // NOT LOGGED IN
    if (!userEmail) {
        authArea.innerHTML = `
            <a href="login.php" class="login-btn">Login</a>
        `;
        return;
    }

    // fallback username
    if (!userName) {
        userName = userEmail.split("@")[0];
    }

    authArea.innerHTML = `
        <div class="user-dropdown">

            <div class="user-btn">
                <div class="user-avatar">
                    ${userName.charAt(0).toUpperCase()}
                </div>

                <div class="user-info">
                    <span>Hello,</span>
                    <strong>${userName}</strong>
                </div>

                <span class="arrow">▼</span>
            </div>

            <div class="dropdown-menu">
                <a href="myaccount.php">My Account</a>
                <a href="#" id="logoutBtn">Logout</a>
            </div>

        </div>
    `;

    document.getElementById("logoutBtn").addEventListener("click", function (e) {
        e.preventDefault();

        localStorage.removeItem("userEmail");
        localStorage.removeItem("userName");

        window.location.href = "index.php";
    });

});