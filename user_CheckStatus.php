<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ClassHubDB";

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id']; // Get current user ID from session

// Fetch user bookings with classroom details
$sql = "SELECT b.booking_id, b.classroom_id, c.room_name AS classroom_name, 
               b.booking_date, b.time_slot, b.reason, b.status
        FROM bookings b
        JOIN classrooms c ON b.classroom_id = c.classroom_id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC, b.time_slot ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($booking_id, $classroom_id, $classroom_name, $booking_date, $time_slot, $reason, $status);

?>


<html>
<head>
    <title>Bookings Status</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background-color:rgba(207, 207, 207, 0.96);
        }
        .main-content {
            margin-left: 150px;
            padding: 30px;
        }
        .container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background:rgba(255, 250, 250, 0.86);
            width: 100%;
            height: 100vh;
            box-sizing: border-box;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
        }
        table { 
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
            background:rgba(255, 250, 250, 0.86);
        }
        th, td { 
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            color: black;
        }
        th { 
            background:rgb(32, 49, 72);
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
            color:  white;
        }
        .status-approved { /* Approved */
            color: rgb(2, 144, 85); 
            font-weight: bold; 
        }
        .status-rejected { /* Reject */
            color: rgb(255, 5, 5);
            font-weight: bold; 
        }
        .status-pending { /* Pending */
            color: rgb(229, 141, 25);
            font-weight: bold; 
        }
    </style>
</head>

<body>
<div class="main-content">
    <div class="container">
        <h1>Booking Status</h1>
        
        <!-- The Check Booking Status Table -->
        <table>
            <tr>
                <th>BOOKING ID</th> 
                <th>CLASSROOM</th> 
                <th>DATE</th> 
                <th>TIME SLOT</th> 
                <th>REASON</th> 
                <th>STATUS</th>
            </tr>

            <?php if ($stmt->num_rows > 0): ?>

                <?php while ($stmt->fetch()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking_id); ?></td> <!-- Shows booking id -->
                        <td><?php echo htmlspecialchars($classroom_name); ?></td> <!-- Shows classroom name -->
                        <td><?php echo htmlspecialchars($booking_date); ?></td> <!-- Shows booking date -->
                        <td><?php echo htmlspecialchars($time_slot); ?></td> <!-- Shows booking time slot -->
                        <td><?php echo htmlspecialchars($reason); ?></td> <!-- Shows booking reason -->
                        <!-- Shows booking status (Pending, Approve, or Reject) -->
                        <td class="status-<?php echo strtolower($status); ?>"> 
                            <?php echo htmlspecialchars($status); ?>
                        </td>
                    </tr>
                <?php } ?>

            <?php else: ?>

                <!-- If new users or empty booking record will shows "No Booking Records Found" -->
                <tr>
                    <td colspan="6" style="text-align: center; font-style: italic; color: gray;">
                        No booking records found.
                    </td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>