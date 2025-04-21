<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Include the database connection file
    include('dbConnCode.php'); // Assumes $conn is the connection variable in dbConnCode.php

    // Get user_id and number_of_reviews from the POST request
    $userId = $_POST['user_id'];
    $numberOfReviews = $_POST['number_of_reviews'];

    // Validate inputs
    if (is_numeric($userId) && is_numeric($numberOfReviews)) {
        // Prepare the SQL statement
        $stmt = $conn->prepare("UPDATE users SET number_of_reviews = ? WHERE id = ?");
        $stmt->bind_param("ii", $numberOfReviews, $userId);

        // Execute the statement and return JSON response
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

        // Close the statement
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }

    // Close the connection
    $conn->close();
}
?>
