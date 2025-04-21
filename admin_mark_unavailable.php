<?php


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the selected dates as a comma-separated string
    $unavailable_dates = explode(',', $_POST['dates']); // Split the dates into an array

    // Connect to the database
    include('dbConnCode.php');
    
    // Insert each unavailable date into the database with one day incremented
    foreach ($unavailable_dates as $date) {
        // Trim any extra spaces around the date
        $date = trim($date);
        
        if (!empty($date)) {
            // Increment the date by one day
            $new_date = date('Y-m-d', strtotime($date . ' +1 day'));
            
            // Insert the incremented date into the database
            $query = "INSERT INTO notavail_appointment (unavailable_date) VALUES ('$new_date')";
            mysqli_query($conn, $query);
        }
    }

    // Redirect back to the shift_appointments page
    header("Location: shift_appointments.php");
    exit();
}
?>
