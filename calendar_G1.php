<?php

// DATABASE CONNECTION
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname = "ClassHubDB";

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection and stop execution if failed
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
$room = "Room G1"; 
$message = "";


// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'processBooking') {
    $name   = htmlspecialchars($_POST['name']);
    $email  = htmlspecialchars($_POST['email']);
    $room   = htmlspecialchars($_POST['room']);
    $date   = htmlspecialchars($_POST['date']);
    $slot   = htmlspecialchars($_POST['slot']);
    $reason = htmlspecialchars($_POST['reason']);

    // Check if the selected date is in the past
    if ($date < date("Y-m-d")) {
        $message = "Error: You cannot book a classroom for a past date.";
    } else {
        // Get classroom_id for the selected room
        $stmt = $conn->prepare("SELECT classroom_id FROM classrooms WHERE room_name = ? LIMIT 1");
        $stmt->bind_param("s", $room);
        $stmt->execute();
        $stmt->bind_result($classroom_id);
        $stmt->fetch();
        $stmt->close();

        if (!$classroom_id) {
            // If classroom not found, display error
            $message = "Error: Classroom not found.";
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE classroom_id = ? AND booking_date = ? AND time_slot = ? AND status IN ('Pending', 'Approved')");
            $stmt->bind_param("iss", $classroom_id, $date, $slot);
            $stmt->execute();
            $stmt->bind_result($bookingCount);
            $stmt->fetch();
            $stmt->close();

            if ($bookingCount > 0) {
                // If already booked, show message
                $message = "Sorry, this slot is already booked.";
            } else {
                // Verify if user email is registered
                $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($user_id);
                $stmt->fetch();
                $stmt->close();

                if (!$user_id) {
                    // If user not registered
                    $message = "Only registered users can book. Please contact admin.";
                } else {
                    // Proceed to insert booking inside a transaction
                    $conn->autocommit(false);
                    try {
                        $stmt = $conn->prepare("INSERT INTO bookings (user_id, classroom_id, name, email, booking_date, time_slot, reason, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
                        $stmt->bind_param("iisssss", $user_id, $classroom_id, $name, $email, $date, $slot, $reason);
                        $stmt->execute();
                        $stmt->close();

                        $conn->commit();
                        $message = "Booking submitted for $name! (Status: Pending)";
                    } catch (Exception $e) {
                        // Rollback on failure
                        $conn->rollback();
                        $message = "Booking failed: " . $e->getMessage();
                    } finally {
                        // Restore autocommit
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
        /* Body styling */
        body {
            background-color: rgba(207, 207, 207, 0.96);
            font-family: Arial, sans-serif;
            margin: 0;
        }
        /* Main content container */
        .main-content {
            margin-left: 150px;
            padding: 30px;
        }
        /* Inner container for calendar and messages */
        .container {
            background: rgba(255, 250, 250, 0.86);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        /* Heading styles */
        h1, h2 {
            color: #2c3e50;
        }
        
        /* Message box for success/error */
        .message {
            margin-bottom: 10px;
            padding: 10px;
            background: #333;
            color: white;
            border-radius: 5px;
        }

        /* Table for calendar */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        
        /* Table cell styling */
        td, th {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }
        
        /* Header styling */
        th {
            background: rgb(32, 49, 72);
            color: white;
        }
        /* Booked slot style */
        .booked {
            background-color: rgb(68, 78, 89);
            color: rgb(164, 177, 192);
            font-weight: bold;
        }
        
        /* Available slot link style */
        .available a {
            color: rgb(2, 144, 85);
            text-decoration: none;
            font-weight: bold;
        }
        /* Hover effect for available slots */
        .available a:hover {
            text-decoration: underline;
        }
        /* Past date slot style */
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
        <!-- Display Page Title -->
        <h1>Classroom Availability Calendar</h1>

        <!-- Show feedback message if exists -->
        <?php if ($message): ?>
            <div class="message"><?= $message ?></div>
        <?php endif; ?>

        <!-- Calendar Table -->
        <div class="calendar-container">
            <h2>Room: <?= htmlspecialchars($room) ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <?php foreach ($timeSlots as $slot): ?>
                            <th><?= $slot ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Generate calendar rows
                    $current = strtotime($startDate);
                    $end = strtotime($endDate);
                    while ($current <= $end):
                        $dateStr = date("Y-m-d", $current);
                        $formattedDate = date("M j, Y", strtotime($dateStr));
                        echo "<tr><td>$formattedDate</td>";
                        foreach ($timeSlots as $slot) {
                            // Check if this slot is booked
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings b
                                INNER JOIN classrooms c ON b.classroom_id = c.classroom_id
                                WHERE c.room_name = ? AND b.booking_date = ? AND b.time_slot = ? AND b.status IN ('Pending', 'Approved')");
                            $stmt->bind_param("sss", $room, $dateStr, $slot);
                            $stmt->execute();
                            $stmt->bind_result($count);
                            $stmt->fetch();
                            $stmt->close();

                            if (strtotime($dateStr) < strtotime(date("Y-m-d"))) {
                                // Past date = Unavailable
                                echo "<td class='unavailable'>Unavailable</td>";
                            } elseif ($count > 0) {
                                echo "<td class='booked'>Booked</td>";
                            } else {
                                // Slot available for booking
                                $url = "calendar_booking.php?room=" . urlencode($room) . "&date=" . urlencode($dateStr) . "&slot=" . urlencode($slot);
                                echo "<td class='available'><a href='$url'>Available</a></td>";
                            }

                        }
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