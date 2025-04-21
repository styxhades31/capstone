<?php
// Include database connection
include('dbConnCode.php');

// Get today's date in 'Y-m-d' format
$today = date('Y-m-d');

// Update the `status` column to 'completed' for today's appointments
$updateQuery = "
    UPDATE appointments
    SET status = 'completed'
    WHERE appointment_date = '$today'
    AND status = 'pending'
";

// Execute the query without outputting any messages
mysqli_query($conn, $updateQuery);

// DO NOT close the connection here, as it is reused later in the same script
// mysqli_close($conn);
?>
