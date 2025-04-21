<?php
require 'dbConnCode.php'; // Include your database connection
session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true); // Regenerate session id on first visit
}

// Check if the user is logged in and if their role is 'Researcher'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Researcher') {
    header("Location: login.php");
    exit();
}

// Start CSRF token generation if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logout logic
if (isset($_POST['logout'])) {
    // Validate CSRF token
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        session_destroy(); // Destroy the session to log the user out
        header("Location: login.php");
        exit();
    } else {
        // Handle CSRF token validation failure (optional)
        echo "<script>alert('Invalid CSRF token.');</script>";
    }
}
// Fetch the vision and mission statements from the database
$sql_vm = "SELECT * FROM vision_mission";
$result_vm = $conn->query($sql_vm);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vision and Mission</title>
    <link rel="stylesheet" href="styles.css"> <!-- Include your CSS file for styling -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .statement {
            margin: 20px 0;
        }
        .statement h2 {
            color: #555;
        }
        .statement p {
            font-size: 1.1em;
            line-height: 1.6;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Vision and Mission</h1>

    <?php
    // Check if any vision or mission exists
    if ($result_vm && $result_vm->num_rows > 0) {
        while ($row = $result_vm->fetch_assoc()) {
            echo "<div class='statement'>";
            echo "<h2>" . htmlspecialchars($row['statement_type']) . ":</h2>";
            echo "<p>" . htmlspecialchars($row['content']) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>No vision or mission found.</p>";
    }
    ?>

</div>

</body>
</html>
