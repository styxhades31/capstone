<?php
session_start();
require_once 'dbConnCode.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    echo json_encode(['error' => 'User not logged in or invalid user ID.']);
    exit;
}

try {
    // Query to fetch pending appointment dates for the logged-in user
    $query = "
        SELECT a.appointment_date
        FROM appointments a
        JOIN Researcher_title_informations rti ON a.researcher_title_id = rti.id
        WHERE rti.user_id = ? AND a.status = 'pending'
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $pendingDates = [];
    while ($row = $result->fetch_assoc()) {
        $pendingDates[] = $row['appointment_date'];
    }

    echo json_encode(['pendingDates' => $pendingDates]);
} catch (Exception $e) {
    error_log("Error fetching pending appointments: " . $e->getMessage());
    echo json_encode(['error' => 'Database error']);
}
?>
