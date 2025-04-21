<?php
session_start();
require_once 'dbConnCode.php'; // Database connection

// Get the raw POST data from the request
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['researcher_title_id'])) {
    $researcher_title_id = $data['researcher_title_id'];

    // Fetch researchers involved in the selected title
    $query = "
        SELECT first_name, last_name, middle_initial, suffix
        FROM researcher_involved
        WHERE researcher_title_id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $researcher_title_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if any researchers were found
    if ($result->num_rows > 0) {
        $researchers = [];
        while ($row = $result->fetch_assoc()) {
            // Check if middle_initial is NULL or empty, and replace it with an empty string if so
            if (empty($row['middle_initial'])) {
                $row['middle_initial'] = '';  // You can set a placeholder if needed, such as 'N/A'
            }

            $researchers[] = $row;
        }

        // Return the data in JSON format
        echo json_encode(['success' => true, 'researchers' => $researchers]);
    } else {
        // No researchers found for the given title
        echo json_encode(['success' => false]);
    }
} else {
    // Invalid request
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
