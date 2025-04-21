<?php
include 'dbConnCode.php'; // Include your DB connection

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['reviewerId']) && isset($data['researchId'])) {
    try {
        $conn->begin_transaction();

        // Find the current reviewer for the given researcher_info_id
        $getCurrentReviewerQuery = "
            SELECT user_id
            FROM assign_reviewer
            WHERE researcher_info_id = ? AND status = 'Ongoing'
        ";
        $stmt = $conn->prepare($getCurrentReviewerQuery);
        $stmt->bind_param("i", $data['researchId']);
        $stmt->execute();
        $stmt->store_result();

        $currentReviewerId = null;
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($currentReviewerId);
            $stmt->fetch();
        }
        $stmt->close();

        // Remove existing assignment for the given researcher_info_id
        $removeAssignmentQuery = "
            DELETE FROM assign_reviewer
            WHERE researcher_info_id = ? AND status = 'Ongoing'
        ";
        $stmt = $conn->prepare($removeAssignmentQuery);
        $stmt->bind_param("i", $data['researchId']);
        $stmt->execute();
        $stmt->close();

        // If there was a previous reviewer, update their status based on ongoing reviews
        if ($currentReviewerId !== null) {
            // Count the ongoing reviews for the current reviewer
            $ongoingCountQuery = "
                SELECT COUNT(*) AS ongoing_count
                FROM assign_reviewer
                WHERE user_id = ? AND status = 'Ongoing'
            ";
            $stmt = $conn->prepare($ongoingCountQuery);
            $stmt->bind_param("i", $currentReviewerId);
            $stmt->execute();
            $ongoingCountResult = $stmt->get_result();
            $ongoingCountRow = $ongoingCountResult->fetch_assoc();
            $ongoingCount = $ongoingCountRow['ongoing_count'];
            $stmt->close();

            // Get the max number of reviews for the current reviewer
            $maxReviewsQuery = "
                SELECT number_of_reviews
                FROM users
                WHERE id = ?
            ";
            $stmt = $conn->prepare($maxReviewsQuery);
            $stmt->bind_param("i", $currentReviewerId);
            $stmt->execute();
            $maxReviewsResult = $stmt->get_result();
            $maxReviewsRow = $maxReviewsResult->fetch_assoc();
            $maxReviews = $maxReviewsRow['number_of_reviews'];
            $stmt->close();

            // Update the status of the current reviewer
            $newStatus = ($ongoingCount >= $maxReviews) ? 'Occupied' : 'Free';
            $updatePreviousReviewerStatusQuery = "
                UPDATE users
                SET status = ?
                WHERE id = ?
            ";
            $stmt = $conn->prepare($updatePreviousReviewerStatusQuery);
            $stmt->bind_param("si", $newStatus, $currentReviewerId);
            $stmt->execute();
            $stmt->close();
        }

        // Insert the new assignment
        $assignReviewerQuery = "
            INSERT INTO assign_reviewer (user_id, researcher_info_id, status)
            VALUES (?, ?, 'Ongoing')
        ";
        $stmt = $conn->prepare($assignReviewerQuery);
        $stmt->bind_param("ii", $data['reviewerId'], $data['researchId']);
        $stmt->execute();
        $stmt->close();

        // Update the new reviewer status based on their ongoing reviews
        $ongoingCountQuery = "
            SELECT COUNT(*) AS ongoing_count
            FROM assign_reviewer
            WHERE user_id = ? AND status = 'Ongoing'
        ";
        $stmt = $conn->prepare($ongoingCountQuery);
        $stmt->bind_param("i", $data['reviewerId']);
        $stmt->execute();
        $ongoingCountResult = $stmt->get_result();
        $ongoingCountRow = $ongoingCountResult->fetch_assoc();
        $ongoingCount = $ongoingCountRow['ongoing_count'];
        $stmt->close();

        $maxReviewsQuery = "
            SELECT number_of_reviews
            FROM users
            WHERE id = ?
        ";
        $stmt = $conn->prepare($maxReviewsQuery);
        $stmt->bind_param("i", $data['reviewerId']);
        $stmt->execute();
        $maxReviewsResult = $stmt->get_result();
        $maxReviewsRow = $maxReviewsResult->fetch_assoc();
        $maxReviews = $maxReviewsRow['number_of_reviews'];
        $stmt->close();

        $newStatus = ($ongoingCount >= $maxReviews) ? 'Occupied' : 'Free';
        $updateNewReviewerQuery = "
            UPDATE users
            SET status = ?
            WHERE id = ?
        ";
        $stmt = $conn->prepare($updateNewReviewerQuery);
        $stmt->bind_param("si", $newStatus, $data['reviewerId']);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Reviewer assigned successfully.']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
