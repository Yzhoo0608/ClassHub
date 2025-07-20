<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ClassHubDB";
// Create a new connection to MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);
// Check if the connection to the database was successful
if ($conn->connect_error) {
    // Stop script execution and display an error if connection fails
    die("Connection failed: " . $conn->connect_error);
}
// Initialize variables for feedback message and CSS class for styling the message
$message = "";
$messageClass = "";

// Check if the form was submitted using POST method
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get and sanitize form input values
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_plain = $_POST['password']; // Raw password entered by user
    // Check if all fields are filled
    if ($name && $email && $password_plain) {
        // Securely hash the user's password using bcrypt
        $hashed_password = password_hash($password_plain, PASSWORD_BCRYPT);
        // Prepare a SQL statement to check if a user with the same email already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?"); // Bind the email parameter to the query
        $stmt->bind_param("s", $email); 
        $stmt->execute(); // Execute the query
        $result = $stmt->get_result(); // Get query result

        if ($result->num_rows > 0) {
            // User with the same email already exists
            $message = "User with this email already exists!";
            $messageClass = "warning";
        } else {
            // Insert the new user into the database
            $stmt_insert = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt_insert->bind_param("sss", $name, $email, $hashed_password);
            if ($stmt_insert->execute()) {
                // Successfully added user
                $message = "User added successfully!";
                $messageClass = "success";
            } else {
                // Error occurred while inserting user
                $message = "Error adding user: " . $stmt_insert->error;
                $messageClass = "error";
            }
            $stmt_insert->close(); // Close the insert statement
        }

        $stmt->close(); // Close the select statement
    } else {
        // Display warning if any field is empty
        $message = "Please fill all fields.";
        $messageClass = "warning";
    }
}
// Close the database connection
$conn->close();
?>

<html>
<head>
    <title>Admin Panel - Register User</title>
    <style>
        /* Main content styling */
        .main-content {
            margin-left: 150px;
            padding: 30px;
        }
        /* Container for the add user form */
        .add_container {
            background: rgba(255, 250, 250, 0.86);
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(255,255,255,0.1);
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
            color: rgb(32, 49, 72);
            font-family: Arial, sans-serif;
        }
        /* Title styling */
        h1 {
            color:rgb(0, 0, 0);
            text-align: center;
            margin-bottom: 20px;
        }
        /* Feedback message styling */
        .add_container .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            color: #f39c12;
        }
        /* Form styling */
        .add_container form {
            display: flex;
            flex-direction: column;
        }
        /* Label styling */
        .add_container label {
            margin: 8px 0 4px;
            font-weight: bold;
        }
        /* Input field styling */
        .add_container input {
            padding: 8px;
            border-radius: 5px;
            border: none;
            margin-bottom: 15px;
            font-size: 14px;
        }
        /* Style for text, email, and password inputs */
        .add_container input[type="text"], input[type="email"], input[type="password"] {
            background-color: rgba(162, 162, 162, 0.3);
        }
        /* Button styling */
        .add_container button {
            padding: 10px;
            border: none;
            border-radius: 5px;
            background-color:rgb(32, 49, 72);
            color: white;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .add_container button:hover {
            background-color: rgba(48, 56, 65, 0.65);
        }
        /* Message box styles for different alert types */
        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            font-size: 15px;
        }

        .message.success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }

        .message.warning {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
        }

        .message.error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="main-content">
            <div class="add_container">
            <h1>Add User</h1>
            <!-- User registration form -->
            <form method="POST" action="">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <button type="submit">Add User</button>
            </form>
            <!-- Display feedback message if set -->
            <?php if ($message): ?>
                <div class="message <?= $messageClass ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>



