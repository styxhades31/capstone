<?php
include 'dbConnCode.php'; // Include your DB connection

header('Content-Type: application/json'); // Ensure the response is JSON

// Get the request payload
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['researchId'])) {
    try {
        // Query to get the current assigned reviewer, including all statuses
        $assignedReviewerQuery = "
            SELECT rp.user_id, rp.first_name, rp.middle_initial, rp.last_name, ar.status
            FROM assign_reviewer AS ar
            JOIN reviewer_profiles AS rp ON ar.user_id = rp.user_id
            WHERE ar.researcher_info_id = ?
        ";
        $stmt = $conn->prepare($assignedReviewerQuery);
        $stmt->bind_param("i", $data['researchId']);
        $stmt->execute();
        $assignedReviewerResult = $stmt->get_result();
        $assignedReviewer = $assignedReviewerResult->fetch_assoc();

        // Get the user_id of the assigned reviewer, if available
        $assignedReviewerId = $assignedReviewer ? $assignedReviewer['user_id'] : null;

        // Query to get available reviewers and their ongoing review count, excluding the currently assigned reviewer
        $availableReviewersQuery = "
            SELECT rp.user_id, 
                   rp.first_name, 
                   rp.middle_initial, 
                   rp.last_name, 
                   u.number_of_reviews AS max_reviews,
                   (
                       SELECT COUNT(*)
                       FROM assign_reviewer
                       WHERE user_id = rp.user_id AND status = 'Ongoing'
                   ) AS ongoing_reviews
            FROM reviewer_profiles AS rp
            JOIN users AS u ON rp.user_id = u.id
            WHERE u.status = 'Free'
            AND rp.user_id NOT IN (
                SELECT ar.user_id
                FROM assign_reviewer AS ar
                WHERE ar.researcher_info_id = ?
            )
        ";
        $stmt = $conn->prepare($availableReviewersQuery);
        $stmt->bind_param("i", $data['researchId']);
        $stmt->execute();
        $availableReviewersResult = $stmt->get_result();

        $reviewers = [];
        while ($row = $availableReviewersResult->fetch_assoc()) {
            // Only include reviewers who haven't reached their max reviews
            if ($row['ongoing_reviews'] < $row['max_reviews']) {
                $reviewers[] = $row;
            }
        }

        echo json_encode([
            'success' => true,
            'assignedReviewer' => $assignedReviewer,
            'reviewers' => $reviewers
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
