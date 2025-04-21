<?php
// Include database connection
include('dbConnCode.php');

// Ensure the user is logged in (you can adjust this check based on your authentication system)
session_start();
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if user is not logged in
    header("Location: login.php");
    exit();
}

// Get the logged-in user ID
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the selected dates as an array
    $unavailable_dates = $_POST['dates'];

    // Insert each unavailable date into the database for the logged-in user
    foreach ($unavailable_dates as $date) {
        // Trim any extra spaces and sanitize the input
        $date = trim($date);
        if (!empty($date)) {
            // Prepare the query to insert the unavailable date
            $query = "INSERT INTO user_unavailable_dates (user_id, unavailable_date) VALUES ('$user_id', '$date')";
            mysqli_query($conn, $query);
        }
    }

    // Redirect back to the user profile or another page with a success message
    $_SESSION['message'] = "Unavailable dates marked successfully.";
    header("Location: SubmitFiles.php");
    exit();
}

// Close the database connection
mysqli_close($conn);
?>
