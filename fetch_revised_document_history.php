<?php
include 'dbConnCode.php'; // Include your database connection

header('Content-Type: application/json');

// Get researcher_info_id from the request
if (isset($_GET['researcher_info_id'])) {
    $researcherInfoId = $_GET['researcher_info_id'];

    try {
        // Fetch revised document history
        $query = "
            SELECT old_revised_document, updated_at 
            FROM revised_document_history 
            WHERE researcher_info_id = ?
            ORDER BY updated_at DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $researcherInfoId);
        $stmt->execute();
        $result = $stmt->get_result();

        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }

        echo json_encode(['success' => true, 'history' => $history]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch history.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
