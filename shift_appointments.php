<?php
// Include database connection
include('dbConnCode.php');

// Fetch all unavailable dates from the notavail_appointment table
$unavailable_dates_query = "SELECT unavailable_date FROM notavail_appointment";
$unavailable_result = mysqli_query($conn, $unavailable_dates_query);
$unavailable_dates = [];
while ($row = mysqli_fetch_assoc($unavailable_result)) {
    $unavailable_dates[] = $row['unavailable_date'];
}

// Define a list of holidays (add your holidays here)
$holidays = ['2024-12-25', '2024-01-01']; // Example holidays: Christmas and New Year
$unavailable_dates = array_merge($unavailable_dates, $holidays); // Combine unavailable dates and holidays

// Function to find the next available date (if a date is unavailable or fully booked)
function getNextAvailableDate($current_date, $conn, $unavailable_dates) {
    $next_date = date('Y-m-d', strtotime($current_date . ' +1 day')); // Increment the current date by one day


    // Check if the next date is a weekend
    $day_of_week = date('N', strtotime($next_date)); // 6 = Saturday, 7 = Sunday

     // Fetch the appointment capacity from the database (no default value)
     $capacity_query = "SELECT appointment_capacity FROM reoc_dynamic_data LIMIT 1";
     $capacity_result = mysqli_query($conn, $capacity_query);
 
     // Check if the query was successful and get the appointment capacity
     if ($capacity_result && $row = mysqli_fetch_assoc($capacity_result)) {
         $appointment_capacity = (int)$row['appointment_capacity'];
     } else {
         return false; // If no capacity is found, return false or handle the error as needed
     }

    // Check if the next date is unavailable or fully booked
    $check_query = "
        SELECT COUNT(id) AS appointment_count 
        FROM appointments 
        WHERE appointment_date = '$next_date'
    ";
    $check_result = mysqli_query($conn, $check_query);
    $appointment_count = mysqli_fetch_assoc($check_result)['appointment_count'];

    if (
        in_array($next_date, $unavailable_dates) || // Check if it's in the unavailable list
        $day_of_week >= 6 || // Skip weekends
        $appointment_count >= $appointment_capacity // Skip fully booked dates
    ) {
        return getNextAvailableDate($next_date, $conn, $unavailable_dates); // Recursively find the next available date
    } else {
        return $next_date;
    }
}

// Move appointments from unavailable or fully booked dates, only for those with status 'pending'
$appointments_query = "
    SELECT * 
    FROM appointments 
    WHERE appointment_date IN ('" . implode("', '", $unavailable_dates) . "') 
    AND status = 'pending'
";
$appointments_result = mysqli_query($conn, $appointments_query);

// Process each appointment and move it to the next available date
while ($row = mysqli_fetch_assoc($appointments_result)) {
    $original_date = $row['appointment_date'];
    $new_date = getNextAvailableDate($original_date, $conn, $unavailable_dates); // Get the next available date

    // Update the appointment with the new date
    $update_query = "UPDATE appointments SET appointment_date = '$new_date' WHERE id = " . $row['id'];
    mysqli_query($conn, $update_query);
}

// Close the database connection
mysqli_close($conn);

// Set a message for SweetAlert
$message = "Appointments successfully moved to the next available date.";

// Directly output the SweetAlert script in HTML
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Admin Home</title>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>
<body>
    <script>
        Swal.fire({
            title: 'Success!',
            text: '$message',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(function() {
            // Redirect to adminHome.php after closing the alert
            window.location.href = 'adminHome.php';
        });
    </script>
</body>
</html>";
exit();
?>
