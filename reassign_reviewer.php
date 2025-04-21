<?php
include 'dbConnCode.php'; // Include your DB connection

header('Content-Type: application/json'); // Ensure the response is JSON

// Get the request payload
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['reviewerId'], $data['researchId'])) {
    $reviewerId = $data['reviewerId'];
    $researchId = $data['researchId'];

    // Start transaction for reassignment
    $conn->begin_transaction();
    try {
        // Update status of the current reviewer to 'Complete'
        $updateQuery = "
            UPDATE assign_reviewer 
            SET status = 'Complete'
            WHERE researcher_info_id = ? AND status = 'Ongoing'
        ";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("i", $researchId);
        $stmt->execute();

        // Insert the new reviewer assignment
        $assignQuery = "
            INSERT INTO assign_reviewer (user_id, researcher_info_id, status)
            VALUES (?, ?, 'Ongoing')
        ";
        $stmt = $conn->prepare($assignQuery);
        $stmt->bind_param("ii", $reviewerId, $researchId);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Reviewer reassigned successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
