<?php
include 'dbConnCode.php'; // Include your DB connection

try {
    // Query to update user statuses dynamically
    $updateStatusQuery = "
        UPDATE users AS u
        SET u.status = 'Free'
        WHERE u.id IN (
            SELECT u.id
            FROM users AS u
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS ongoing_count
                FROM assign_reviewer
                WHERE status = 'Ongoing'
                GROUP BY user_id
            ) AS ar ON u.id = ar.user_id
            WHERE COALESCE(ar.ongoing_count, 0) < u.number_of_reviews
        )
    ";
    $stmt = $conn->prepare($updateStatusQuery);
    $stmt->execute();
} catch (Exception $e) {
    echo "Error updating user statuses: " . $e->getMessage();
}
?>
