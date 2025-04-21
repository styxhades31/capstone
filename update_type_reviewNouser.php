<?php
include 'dbConnCode.php'; // Include your database connection file

header('Content-Type: application/json'); // Set header for JSON response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $type_of_review = $_POST['type_of_review'];

    $stmt = $conn->prepare("UPDATE ResearcherTitleInfo_NoUser SET type_of_review = ? WHERE id = ?");
    $stmt->bind_param("si", $type_of_review, $id);

    // Prepare response array
    $response = [];

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Type of review updated successfully!";
    } else {
        $response['success'] = false;
        $response['message'] = "Error updating type of review: " . $stmt->error;
    }

    $stmt->close();

    // Return the JSON response
    echo json_encode($response);
}
?>
