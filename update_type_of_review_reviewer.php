<?php
require_once 'dbConnCode.php'; // Assuming this file handles your database connection

// Get the incoming request data
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['id'], $data['column'], $data['newValue'])) {
    $id = $data['id'];
    $column = $data['column'];
    $newValue = $data['newValue'];

    // Validate and update the record in the database
    if ($column === 'type_of_review') {
        $sql = "UPDATE researcher_title_informations SET type_of_review = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newValue, $id);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>
