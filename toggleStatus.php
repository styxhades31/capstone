<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

require_once 'dbConnCode.php';

// Fetch user ID and new status from the request
$userId = $_POST['user_id'];
$status = $_POST['status'];

// Sanitize input
$userId = intval($userId);
$status = ($status === 'Free' || $status === 'Occupied') ? $status : 'Free'; // Ensure valid status

// Update the user's status
$sql = "UPDATE users SET status = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $status, $userId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Status updated successfully";
} else {
    echo "Failed to update status";
}

$stmt->close();
?>
