<?php
require_once 'dbConnCode.php';

// Get the date parameter from the GET request
$date = isset($_GET['date']) ? $_GET['date'] : '';

// If the date is empty, return an error
if (empty($date)) {
    echo 'Invalid date';
    exit();
}

// Check if the date is in the `notavail_appointment` table (unavailable dates)
$query = "SELECT COUNT(*) FROM notavail_appointment WHERE unavailable_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $date);
$stmt->execute();
$stmt->bind_result($isUnavailable);
$stmt->fetch();
$stmt->close();

// If the date is unavailable, return 'unavailable'
if ($isUnavailable > 0) {
    echo 'unavailable';
    exit();
}

// Check if the date is fully booked (20 or more appointments)
$query = "SELECT COUNT(*) FROM appointments WHERE appointment_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $date);
$stmt->execute();
$stmt->bind_result($appointmentsCount);
$stmt->fetch();
$stmt->close();

// If the date is fully booked, return 'fully-booked'
if ($appointmentsCount >= 20) {
    echo 'fully-booked';
    exit();
}

// If the date is neither unavailable nor fully booked, return 'available'
echo 'available';
?>
