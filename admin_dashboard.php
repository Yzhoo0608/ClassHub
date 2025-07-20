<?php
// Get the 'page' parameter from the URL query string 
$page = $_GET['page'] ?? 'manage_booking';  // Default to 'manage_booking' if 'page' is not set
?>

<html>
<head>
    <title>Admin Panel - ClassHub</title>
    <!-- Import Google Fonts and icons -->
    <link rel = "stylesheet"  href = "https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Raleway:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Basic body styling */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color:rgba(207, 207, 207, 0.96);
        }
        /* Sidebar styling */
        .sidebar {
            height: 100vh;
            width: 240px;
            background: rgb(32, 49, 72);
            color: white;
            position: fixed;
            display: flex;
            flex-direction: column;
            padding: 20px;
        }
        /* Logo styling */
        .logo {
            font-family: 'Poppins', sans-serif;
            font-size: 40px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 30px;
            margin-right: 5px;
            margin-left: 10px;
            color: #ffffff; 
            letter-spacing: 1.5px;
            text-shadow: 10px 8px 5px rgba(0, 0, 0, 0.25);
            1px 1px 2px rgba(0, 0, 0, 0.6),
            2px 2px 4px rgba(0, 0, 0, 0.4);
        }
        /* Welcome text below logo */
        .sidebar b {
            font-size: 13px;
            font-family: 'Raleway', sans-serif;
            margin-right: 10px;
            margin-left: 32px;
            margin-bottom: 20px;
            color:rgba(202, 223, 244, 0.72);
        }
        /* Sidebar links styling */
        .sidebar a {
            color: white;
            padding: 12px 0;
            text-decoration: none;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        /* Highlight active and hover links */
        .sidebar a:hover,
        .sidebar a.active {
            background-color: #34495e;
            padding-left: 10px;
        }
        /* Submenu container */
        .has-submenu {
            display: flex;
            flex-direction: column;
        }
        /* Hide submenu links by default */
        .submenu {
            display: none;
            flex-direction: column;
            margin-left: 20px;
        }
        /* Show submenu when parent has "active" class */
        .has-submenu.active .submenu {
            display: flex;
        }
        /* Submenu links styling */
        .submenu a {
            font-size: 15px;
            padding: 8px 0 8px 10px;
            text-decoration: none;
            color: white;
            transition: background-color 0.3s, padding-left 0.3s;
        }
        /* Highlight submenu active and hover */
        .submenu a:hover,
        .submenu a.active {
            background-color: #2c3e50;
            padding-left: 20px;
        }
        /* Main content area styling (beside sidebar) */
        .main-content {
            margin-left: 260px; /* Offset for sidebar width */
            padding: 30px;
            justify-content: left;
            align-items: left;
        }
        /* Logout link styling at the bottom of sidebar */
        .logout-link {
            position: absolute;
            bottom: 80px;
            left: 20px;
            color: white;
            text-decoration: none;
            padding: 12px 10px;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .logout-link:hover {
            background-color: #34495e;
            padding-left: 10px;
        }

    </style>
</head>
<body>
<!-- Sidebar with navigation -->
<div class="sidebar">
    <div class="logo">ClassHub</div>
    <b>Welcome to Admin Panel</b>
    <br>
    <!-- Main menu links -->
    <a href="?page=manage_booking" class="<?= $page === 'manage_booking' ? 'active' : '' ?>">Manage Booking</a>
    <!-- Manage User submenu -->
    <div class="has-submenu <?= in_array($page, ['add_user', 'remove_user' ]) ? 'active' : '' ?>">
        <a href="#" onclick="toggleSubmenu(event)">Manage User</a>
        <div class="submenu">
            <a href="?page=add_user" class="<?= $page === 'add_user' ? 'active' : '' ?>">Add New User</a>
            <a href="?page=remove_user" class="<?= $page === 'remove_user'  ? 'active' : '' ?>">Remove User</a>
        </div>
    </div>
    <!-- Manage Admin submenu -->
    <div class="has-submenu <?= in_array($page, ['add_admin', 'remove_admin' ]) ? 'active' : '' ?>">
        <a href="#" onclick="toggleSubmenu(event)">Manage Admin</a>
        <div class="submenu">
            <a href="?page=add_admin" class="<?= $page === 'add_admin' ? 'active' : '' ?>">Add New Admin</a>
            <a href="?page=remove_admin" class="<?= $page === 'remove_admin'  ? 'active' : '' ?>">Remove Admin</a>
        </div>
    </div>
    <!-- Logout link -->
    <a href="login.php" class="logout-link"> Logout <i class="fas fa-sign-out-alt"></i></a>
</div>
<!-- Main content area: Loads PHP pages dynamically based on $page -->
<div class="main-content">
    <?php
        if ($page === 'manage_booking') {
            include 'manage_booking.php';
        } elseif ($page === 'remove_user') {
            include 'remove_user.php';
        } elseif ($page === 'add_user') {
            include 'add_user.php';
        } elseif ($page === 'add_admin') {
            include 'add_admin.php'; 
        } elseif ($page === 'remove_admin') {
            include 'remove_admin.php';   
        } else {
            echo "<h2>Page not found.</h2>";
        }
    ?>
</div>
<!-- JavaScript to toggle submenu visibility -->
<script>
function toggleSubmenu(event) {
    event.preventDefault(); // Prevent default anchor behavior
    const parent = event.target.closest('.has-submenu'); // Get parent submenu container
    parent.classList.toggle('active'); // Toggle 'active' class to show/hide submenu
}
</script>

</body>
</html>
