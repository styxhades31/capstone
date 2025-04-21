<?php
session_start(); // Start the session
require 'dbConnCode.php'; // Include your database connection

$content = $_POST['content'];
$ids = $_POST['id'];
$success = true; // Initialize success variable

foreach ($content as $key => $text) {
    $text = mysqli_real_escape_string($conn, $text); // Escape for security

    // Check the 'id' to determine whether it's a new Vision, Mission, or Goal
    if ($ids[$key] == 'new_vision') {
        // Insert new vision
        $sql_insert = "INSERT INTO vision_mission (statement_type, content) VALUES ('Vision', '$text')";
        if (!$conn->query($sql_insert)) {
            $success = false; // Mark as failed if insert fails
        }
    } elseif ($ids[$key] == 'new_mission') {
        // Insert new mission
        $sql_insert = "INSERT INTO vision_mission (statement_type, content) VALUES ('Mission', '$text')";
        if (!$conn->query($sql_insert)) {
            $success = false; // Mark as failed if insert fails
        }
    } elseif ($ids[$key] == 'new_goals') {
        // Insert new goals
        $sql_insert = "INSERT INTO vision_mission (statement_type, content) VALUES ('Goals', '$text')";
        if (!$conn->query($sql_insert)) {
            $success = false; // Mark as failed if insert fails
        }
    } else {
        // Update existing Vision, Mission, or Goal
        $id = mysqli_real_escape_string($conn, $ids[$key]);
        $sql_update = "UPDATE vision_mission SET content = '$text' WHERE id = '$id'";
        if (!$conn->query($sql_update)) {
            $success = false; // Mark as failed if update fails
        }
    }
}

// Determine message based on success or failure
if ($success) {
    $message = "Vision, Mission, and Goals updated successfully.";
} else {
    $message = "An error occurred while updating Vision, Mission, and Goals.";
}

// Display SweetAlert using HTML
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <title>Update Status</title>
</head>
<body>
    <script>
        Swal.fire({
            icon: '" . ($success ? "success" : "error") . "',
            title: '" . ($success ? "Success!" : "Error!") . "',
            text: '$message',
            confirmButtonText: 'Okay'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'adminHome.php'; // Redirect after clicking okay
            }
        });
    </script>
</body>
</html>
";

exit();
?>
