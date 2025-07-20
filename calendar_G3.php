<?php

// Database connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname = "ClassHubDB";

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Define start and end dates
$startDate = "2025-07-01";
$endDate = "2025-07-31";

// Time slots
$timeSlots = [
    "8:00 AM - 10:00 AM",
    "10:00 AM - 12:00 PM",
    "1:00 PM - 3:00 PM",
    "3:00 PM - 5:00 PM"
];

// Get all distinct room names
$room = "Room G3"; 
$message = "";


// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'processBooking') {
     // Sanitize and collect form input
    $name   = htmlspecialchars($_POST['name']);
    $email  = htmlspecialchars($_POST['email']);
    $room   = htmlspecialchars($_POST['room']);
    $date   = htmlspecialchars($_POST['date']);
    $slot   = htmlspecialchars($_POST['slot']);
    $reason = htmlspecialchars($_POST['reason']);

    // Prevent booking for past dates
    if ($date < date("Y-m-d")) {
        $message = "Error: You cannot book a classroom for a past date.";
    } else {
        // Get the classroom ID from the database
        $stmt = $conn->prepare("SELECT classroom_id FROM classrooms WHERE room_name = ? LIMIT 1");
        $stmt->bind_param("s", $room);
        $stmt->execute();
        $stmt->bind_result($classroom_id);
        $stmt->fetch();
        $stmt->close();

        // Check if the classroom exists
        if (!$classroom_id) {
            $message = "Error: Classroom not found.";
        } else {
             // Check if the slot is already booked
            $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE classroom_id = ? AND booking_date = ? AND time_slot = ? AND status IN ('Pending', 'Approved')");
            $stmt->bind_param("iss", $classroom_id, $date, $slot);
            $stmt->execute();
            $stmt->bind_result($bookingCount);
            $stmt->fetch();
            $stmt->close();

            // If the slot is taken will display message
            if ($bookingCount > 0) {
                $message = "Sorry, this slot is already booked.";
            } else {
                // Retrieve user ID from email
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($user_id);
                $stmt->fetch();
                $stmt->close();

                // Check if user exists
                if (!$user_id) {
                    $message = "Only registered users can book. Please contact admin.";
                } else {
                    $conn->autocommit(false);
                    try {
                         // Insert the booking into database
                        $stmt = $conn->prepare("INSERT INTO bookings (user_id, classroom_id, name, email, booking_date, time_slot, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
                        $stmt->bind_param("iisssss", $user_id, $classroom_id, $name, $email, $date, $slot, $reason);
                        $stmt->execute();
                        $stmt->close();

                        // Commit changes
                        $conn->commit();
                        $message = "Booking submitted for $name! (Status: Pending)";
                    } catch (Exception $e) {
                        $conn->rollback();
                        $message = "Booking failed: " . $e->getMessage();
                    } finally {
                        $conn->autocommit(true);
                    }
                }
            }
        }
    }
}
?>

<html>
<head>
    <title>Classroom Calendar</title>
    <style>
        body {
            background-color: rgba(207, 207, 207, 0.96);
            font-family: Arial, sans-serif;
            margin: 0;
        }
        .main-content {
            margin-left: 150px;
            padding: 30px;
        }
        .container {
            background: rgba(255, 250, 250, 0.86);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #2c3e50;
        }
        .message {
            margin-bottom: 10px;
            padding: 10px;
            background: #333;
            color: white;
            border-radius: 5px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        td, th {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }
        th {
            background: rgb(32, 49, 72);
            color: white;
        }
        .booked {
            background-color: rgb(68, 78, 89);
            color: rgb(164, 177, 192);
            font-weight: bold;
        }
        .available a {
            color: rgb(2, 144, 85);
            text-decoration: none;
            font-weight: bold;
        }
        .available a:hover {
            text-decoration: underline;
        }
        .unavailable {
            background-color: #ccc;
            color: #666;
            font-weight: bold;
        }
    </style>
</head>

<body>
<div class="main-content">
    <div class="container">
        <h1>Classroom Availability Calendar</h1>

        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <div class="calendar-container">
            <!-- Show the selected room name -->
            <h2>Room: <?= htmlspecialchars($room) ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <!-- Display all time slots as column headers -->
                        <?php foreach ($timeSlots as $slot): ?>
                            <th><?= $slot ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                     // Initialize loop from start date to end date
                    $current = strtotime($startDate);
                    $end = strtotime($endDate);
                    while ($current <= $end):
                        // Format the current date
                        $dateStr = date("Y-m-d", $current);
                        $formattedDate = date("M j, Y", strtotime($dateStr));
                        // Begin table row with formatted date
                        echo "<tr><td>$formattedDate</td>";
                        // Loop through each time slot to check availability
                        foreach ($timeSlots as $slot) {
                             // Prepare SQL to check if the current time slot is booked
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings b
                                INNER JOIN classrooms c ON b.classroom_id = c.classroom_id
                                WHERE c.room_name = ? AND b.booking_date = ? AND b.time_slot = ? AND b.status IN ('Pending', 'Approved')");
                            $stmt->bind_param("sss", $room, $dateStr, $slot);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $stmt->close();

                             // Display "Unavailable" if the date is in the past
                            if (strtotime($dateStr) < strtotime(date("Y-m-d"))) {
                                echo "<td class='unavailable'>Unavailable</td>";
                            // If the time slot is booked (count > 0), show as booked
                            } elseif ($count > 0) {
                                echo "<td class='booked'>Booked</td>";
                            // If available, show "Available" with booking link
                            } else {
                                $url = "calendar_booking.php?room=" . urlencode($room) . "&date=" . urlencode($dateStr) . "&slot=" . urlencode($slot);
                                echo "<td class='available'><a href='$url'>Available</a></td>";
                            }

                        }
                         // End table row
                        echo "</tr>";
                        $current = strtotime("+1 day", $current);
                    endwhile;
                    ?>
                </tbody>
            </table>
        </div>
</div>
</body>
</html>