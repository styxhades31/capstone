<?php
include 'dbConnCode.php'; // Include your database connection file

$response = ['success' => false, 'message' => '']; // Initialize response

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $payment = $_POST['payment'];

    $stmt = $conn->prepare("UPDATE ResearcherTitleInfo_NoUser SET payment = ? WHERE id = ?");
    $stmt->bind_param("si", $payment, $id);

    if ($stmt->execute()) {
        $response['success'] = true; // Set success to true
        $response['message'] = "Payment updated successfully!";
    } else {
        $response['message'] = "Error updating payment: " . $stmt->error;
    }

    $stmt->close();
}

// Set the content type to JSON and output the response
header('Content-Type: application/json');
echo json_encode($response);
?>
