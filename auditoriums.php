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

// Variable to hold data when editing
$edit_mode = false;
$edit_id = 0;
$edit_audi_name = "";
$edit_total_seats = "";
$edit_audi_type = "2D";
$edit_status = "Available";


// =========================================================
// 2. HANDLE DELETE AUDITORIUM
// =========================================================

if (isset($_GET['delete'])) {

    $delete_id = intval($_GET['delete']);

    if ($delete_id > 0) {

        // Start transaction
        $conn->begin_transaction();

        try {

            // -------------------------------------------------
            // STEP 1: DELETE SEATS OF THIS AUDITORIUM
            // -------------------------------------------------

            $stmt = $conn->prepare(
                "DELETE FROM seats WHERE audi_id = ?"
            );

            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("i", $delete_id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();


            // -------------------------------------------------
            // STEP 2: DELETE SHOWTIMES OF THIS AUDITORIUM
            // -------------------------------------------------

            $stmt = $conn->prepare(
                "DELETE FROM showtimes WHERE audi_id = ?"
            );

            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("i", $delete_id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();


            // -------------------------------------------------
            // STEP 3: DELETE AUDITORIUM
            // -------------------------------------------------

            $stmt = $conn->prepare(
                "DELETE FROM auditoriums WHERE audi_id = ?"
            );

            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("i", $delete_id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            if ($stmt->affected_rows > 0) {

                // Everything worked
                $conn->commit();

                $success = "Auditorium deleted successfully!";

            } else {

                $conn->rollback();

                $error = "Auditorium not found.";
            }

            $stmt->close();

        } catch (Exception $e) {

            // Undo all previous deletions if something fails
            $conn->rollback();

            $error = "Error deleting auditorium: " . $e->getMessage();
        }
    }
}


// =========================================================
// 3. HANDLE FETCH FOR EDIT
// =========================================================

if (isset($_GET['edit'])) {

    $edit_mode = true;
    $edit_id = intval($_GET['edit']);

    $stmt = $conn->prepare(
        "SELECT audi_name, total_seats, audi_type, status
         FROM auditoriums
         WHERE audi_id = ?"
    );

    if ($stmt) {

        $stmt->bind_param("i", $edit_id);

        $stmt->execute();

        $result_edit = $stmt->get_result();

        if ($row_edit = $result_edit->fetch_assoc()) {

            $edit_audi_name = $row_edit['audi_name'];
            $edit_total_seats = $row_edit['total_seats'];
            $edit_audi_type = $row_edit['audi_type'];
            $edit_status = $row_edit['status'];
        }

        $stmt->close();
    }
}


// =========================================================
// 4. HANDLE ADD / UPDATE AUDITORIUM
// =========================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    (
        isset($_POST['add_auditorium']) ||
        isset($_POST['update_auditorium'])
    )
) {

    $audi_name = trim($_POST['audi_name'] ?? '');
    $total_seats = intval($_POST['total_seats'] ?? 0);
    $audi_type = $_POST['audi_type'] ?? '2D';
    $status = $_POST['status'] ?? 'Available';


    if ($audi_name === '' || $total_seats <= 0) {

        $error = "Please fill in all required fields correctly.";

    } else {

        // -------------------------------------------------
        // UPDATE
        // -------------------------------------------------

        if (isset($_POST['update_auditorium'])) {

            $audi_id = intval($_POST['audi_id']);

            $stmt = $conn->prepare(
                "UPDATE auditoriums
                 SET audi_name = ?,
                     total_seats = ?,
                     audi_type = ?,
                     status = ?
                 WHERE audi_id = ?"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "sissi",
                    $audi_name,
                    $total_seats,
                    $audi_type,
                    $status,
                    $audi_id
                );

                if ($stmt->execute()) {

                    $success = "Auditorium updated successfully!";
                    $edit_mode = false;

                } else {

                    $error =
                        "Error updating auditorium: " .
                        $stmt->error;
                }

                $stmt->close();
            }

        }

        // -------------------------------------------------
        // ADD
        // -------------------------------------------------

        else {

            $stmt = $conn->prepare(
                "INSERT INTO auditoriums
                (audi_name, total_seats, audi_type, status)
                VALUES (?, ?, ?, ?)"
            );

            if ($stmt) {

                $stmt->bind_param(
                    "siss",
                    $audi_name,
                    $total_seats,
                    $audi_type,
                    $status
                );

                if ($stmt->execute()) {

                    $success = "Auditorium added successfully!";

                } else {

                    $error =
                        "Error adding auditorium (Name might already exist): " .
                        $stmt->error;
                }

                $stmt->close();
            }
        }
    }
}


// =========================================================
// 5. FETCH AUDITORIUMS
// =========================================================

$sql = "
    SELECT
        audi_id,
        audi_name,
        total_seats,
        audi_type,
        status,
        created_at
    FROM auditoriums
    ORDER BY audi_name ASC
";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<title>Auditoriums Management - RedCine</title>

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>


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


/* =========================================================
   SIDEBAR
   ========================================================= */

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

.sidebar a:hover,
.sidebar a.active {
    background: red;
}


/* =========================================================
   MAIN
   ========================================================= */

.main {
    margin-left: 260px;
    padding: 25px;
}


/* =========================================================
   TOP BAR
   ========================================================= */

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


/* =========================================================
   CARDS
   ========================================================= */

.cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.card {
    background: #1a1a1a;
    padding: 20px;
    border-radius: 10px;
    border-left: 4px solid red;
}


/* =========================================================
   ALERTS
   ========================================================= */

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


/* =========================================================
   FORM WRAPPER
   ========================================================= */

.form-container {
    background: #111;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    border: 1px solid #222;
}

.form-container h3 {
    margin-bottom: 15px;
    color: red;
}

.form-row {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.form-group {
    flex: 1;
    min-width: 200px;
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 13px;
    color: #aaa;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #333;
    background: #1a1a1a;
    color: white;
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: red;
}


/* =========================================================
   BUTTONS
   ========================================================= */

.btn-submit {
    background: red;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
}

.btn-submit:hover {
    background: darkred;
}

.btn-cancel {
    background: #444;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: bold;
    margin-left: 10px;
    display: inline-block;
}

.btn-cancel:hover {
    background: #555;
}


/* =========================================================
   FILTER
   ========================================================= */

.filter-bar {
    margin-bottom: 15px;
}

.filter-bar input {
    padding: 10px;
    width: 300px;
    border-radius: 6px;
    border: none;
    background: #1a1a1a;
    color: white;
    outline: none;
}


/* =========================================================
   TABLE
   ========================================================= */

.table-section {
    background: #111;
    padding: 20px;
    border-radius: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th,
table td {
    padding: 12px;
    border-bottom: 1px solid #333;
    text-align: left;
}

table th {
    background: #1a1a1a;
    color: red;
}


/* =========================================================
   STATUS
   ========================================================= */

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.available {
    background: #198754;
    color: white;
}

.maintenance {
    background: #ffc107;
    color: black;
}


/* =========================================================
   TYPE
   ========================================================= */

.type-badge {
    background: #222;
    border: 1px solid red;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 11px;
    color: #ff4d4d;
}


/* =========================================================
   ACTION BUTTONS
   ========================================================= */

.action-btn {
    padding: 5px 10px;
    border-radius: 4px;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
    margin-right: 5px;
}

.edit-btn {
    background: #ffc107;
    color: black;
}

.delete-btn {
    background: #dc3545;
    color: white;
}

.edit-btn:hover {
    background: #e0a800;
}

.delete-btn:hover {
    background: #bd2130;
}

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
     ========================================================= -->

<div class="sidebar">

    <h2>
        RED<span style="color:white;">CINE</span>
    </h2>

    <a href="admin_dashboard.php">
        Dashboard
    </a>

    <a href="users.php">
        Users
    </a>

    <a href="admin_bookings.php">
        Bookings
    </a>

    <a href="booking_seats.php">
        Booking Seats
    </a>

    <a href="seats.php">
        Seats
    </a>

    <a href="auditoriums.php" class="active">
        Auditoriums
    </a>

    <a href="showtimes.php">
        Showtimes
    </a>

    <a href="movies.php">
        Movies
    </a>

    <a href="payments.php">
        Payments
    </a>

    <a href="reports.php">
        Reports
    </a>

    <a href="admin_logout.php">
        Logout
    </a>

</div>


<!-- =========================================================
     MAIN
     ========================================================= -->

<div class="main">


    <!-- TOP BAR -->

    <div class="topbar">

        <h1>
            Auditoriums Management
        </h1>

    </div>


    <!-- SUCCESS MESSAGE -->

    <?php if (!empty($success)): ?>

        <div class="alert-success">

            <?php echo htmlspecialchars($success); ?>

        </div>

    <?php endif; ?>


    <!-- ERROR MESSAGE -->

    <?php if (!empty($error)): ?>

        <div class="alert-error">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- =====================================================
         STATISTICS
         ===================================================== -->

    <?php

    $total_audis = $result ? $result->num_rows : 0;

    $available_result = $conn->query(
        "SELECT COUNT(*) as total
         FROM auditoriums
         WHERE status='Available'"
    );

    $available_count =
        $available_result->fetch_assoc()['total'];


    $maintenance_result = $conn->query(
        "SELECT COUNT(*) as total
         FROM auditoriums
         WHERE status='Maintenance'"
    );

    $maintenance_count =
        $maintenance_result->fetch_assoc()['total'];

    ?>


    <div class="cards">

        <div class="card">

            <h3>
                Total Auditoriums
            </h3>

            <p>
                <?php echo $total_audis; ?>
            </p>

        </div>


        <div class="card">

            <h3>
                Available
            </h3>

            <p>
                <?php echo $available_count; ?>
            </p>

        </div>


        <div class="card">

            <h3>
                Under Maintenance
            </h3>

            <p>
                <?php echo $maintenance_count; ?>
            </p>

        </div>

    </div>


    <!-- =====================================================
         ADD / EDIT FORM
         ===================================================== -->

    <div class="form-container">

        <h3>

            <?php

            echo $edit_mode
                ? "Edit Auditorium #" . $edit_id
                : "Add New Auditorium";

            ?>

        </h3>


        <form method="POST" action="">


            <?php if ($edit_mode): ?>

                <input
                    type="hidden"
                    name="audi_id"
                    value="<?php echo $edit_id; ?>"
                >

            <?php endif; ?>


            <div class="form-row">


                <!-- AUDITORIUM NAME -->

                <div class="form-group">

                    <label>
                        Auditorium Name
                    </label>

                    <input
                        type="text"
                        name="audi_name"
                        value="<?php echo htmlspecialchars($edit_audi_name); ?>"
                        placeholder="e.g., Audi 1 / Screen A"
                        required
                    >

                </div>


                <!-- TOTAL SEATS -->

                <div class="form-group">

                    <label>
                        Total Seats Capacity
                    </label>

                    <input
                        type="number"
                        name="total_seats"
                        value="<?php echo htmlspecialchars($edit_total_seats); ?>"
                        placeholder="100"
                        min="1"
                        required
                    >

                </div>


                <!-- TYPE -->

                <div class="form-group">

                    <label>
                        Auditorium Type
                    </label>

                    <select name="audi_type">

                        <option
                            value="2D"
                            <?php
                            echo ($edit_audi_type === '2D')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            2D
                        </option>

                        <option
                            value="3D"
                            <?php
                            echo ($edit_audi_type === '3D')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            3D
                        </option>

                        <option
                            value="IMAX"
                            <?php
                            echo ($edit_audi_type === 'IMAX')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            IMAX
                        </option>

                    </select>

                </div>


                <!-- STATUS -->

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option
                            value="Available"
                            <?php
                            echo ($edit_status === 'Available')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Available
                        </option>

                        <option
                            value="Maintenance"
                            <?php
                            echo ($edit_status === 'Maintenance')
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Maintenance
                        </option>

                    </select>

                </div>


            </div>


            <?php if ($edit_mode): ?>

                <button
                    type="submit"
                    name="update_auditorium"
                    class="btn-submit"
                >
                    Update Auditorium
                </button>

                <a
                    href="auditoriums.php"
                    class="btn-cancel"
                >
                    Cancel
                </a>

            <?php else: ?>

                <button
                    type="submit"
                    name="add_auditorium"
                    class="btn-submit"
                >
                    Add Auditorium
                </button>

            <?php endif; ?>


        </form>

    </div>


    <!-- =====================================================
         SEARCH
         ===================================================== -->

    <div class="filter-bar">

        <input
            type="text"
            id="searchInput"
            placeholder="Search auditorium name or type..."
        >

    </div>


    <!-- =====================================================
         TABLE
         ===================================================== -->

    <div class="table-section">

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Auditorium Name</th>

                    <th>Total Seats</th>

                    <th>Type</th>

                    <th>Status</th>

                    <th>Created At</th>

                    <th>Actions</th>

                </tr>

            </thead>


            <tbody id="audiTableBody">


                <?php

                if ($result && $result->num_rows > 0) {

                    while ($row = $result->fetch_assoc()) {

                        $statusClass =
                            strtolower($row['status']);

                ?>


                <tr>


                    <td>
                        #<?php echo $row['audi_id']; ?>
                    </td>


                    <td>

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $row['audi_name']
                            );
                            ?>

                        </strong>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row['total_seats']
                        );
                        ?>

                    </td>


                    <td>

                        <span class="type-badge">

                            <?php
                            echo htmlspecialchars(
                                $row['audi_type']
                            );
                            ?>

                        </span>

                    </td>


                    <td>

                        <span
                            class="status-badge <?php echo $statusClass; ?>"
                        >

                            <?php
                            echo htmlspecialchars(
                                $row['status']
                            );
                            ?>

                        </span>

                    </td>


                    <td>

                        <?php
                        echo htmlspecialchars(
                            $row['created_at']
                        );
                        ?>

                    </td>


                    <td>


                        <!-- EDIT -->

                        <a
                            href="auditoriums.php?edit=<?php echo $row['audi_id']; ?>"
                            class="action-btn edit-btn"
                        >
                            Edit
                        </a>


                        <!-- DELETE -->

                        <a
                            href="auditoriums.php?delete=<?php echo $row['audi_id']; ?>"
                            class="action-btn delete-btn"
                            onclick="return confirm('Are you sure you want to delete this auditorium? This will also delete its seats and showtimes.');"
                        >
                            Delete
                        </a>


                    </td>


                </tr>


                <?php

                    }

                } else {

                    echo "
                    <tr>
                        <td
                            colspan='7'
                            style='text-align:center;
                                   padding:20px;
                                   color:#666;'
                        >
                            No auditoriums found in the database.
                        </td>
                    </tr>
                    ";

                }

                ?>


            </tbody>

        </table>

    </div>


</div>


<!-- =========================================================
     SEARCH JAVASCRIPT
     ========================================================= -->

<script>

const searchInput =
    document.getElementById("searchInput");

const tableBody =
    document.getElementById("audiTableBody");

const rows =
    tableBody.getElementsByTagName("tr");


searchInput.addEventListener(
    "input",
    function () {

        const filter =
            searchInput.value.toLowerCase();


        for (
            let i = 0;
            i < rows.length;
            i++
        ) {

            const textValue =
                rows[i].textContent ||
                rows[i].innerText;


            rows[i].style.display =
                textValue
                    .toLowerCase()
                    .indexOf(filter) > -1
                    ? ""
                    : "none";

        }

    }
);

</script>


</body>

</html>


<?php

$conn->close();

?>