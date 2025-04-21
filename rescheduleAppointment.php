<?php
session_start();
require_once 'dbConnCode.php';  // Ensure this includes your mysqli connection code

header('Content-Type: application/json');

// Check if the request is POST and CSRF token is valid
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token for security
    if (!isset($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token.']);
        exit;
    }

    // Extract and validate the new appointment date
    $newDate = $_POST['newDate'];
    if (!$newDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $newDate)) {
        echo json_encode(['error' => 'Invalid date format. Date must be in YYYY-MM-DD format.']);
        exit;
    }

    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        echo json_encode(['error' => 'User not logged in or invalid user ID.']);
        exit;
    }

    // SQL to update the appointment date for pending appointments only
    $query = "UPDATE appointments a
              JOIN researcher_title_informations rti ON a.researcher_title_id = rti.id
              SET a.appointment_date = ?
              WHERE rti.user_id = ? 
              AND a.status = 'pending' 
              AND a.appointment_date != ?";

    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sis", $newDate, $userId, $newDate);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Appointment rescheduled successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes made. Please check if the new date is the same as the current date, the appointment is already completed, or no such appointment exists.', 'affectedRows' => $stmt->affected_rows]);
        }
        $stmt->close();
    } else {
        echo json_encode(['error' => 'Failed to prepare statement', 'dbError' => $conn->error]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method.']);
}
?>
