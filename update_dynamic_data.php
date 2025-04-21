<?php
require_once 'dbConnCode.php';
header('Content-Type: application/json'); // Return response as JSON

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['id'], $_POST['column'], $_POST['value'])) {
        $id = intval($_POST['id']);
        $column = $_POST['column'];
        $value = $_POST['value'];

        // Validate column to prevent SQL injection
        $validColumns = ['certificate_version', 'date_effective', 'let_code', 'code_acronym', 'code_number'];
        if (!in_array($column, $validColumns)) {
            $response['message'] = 'Invalid column specified.';
            echo json_encode($response);
            exit;
        }

        // Prepare the SQL query
        if (in_array($column, ['code_acronym', 'certificate_version', 'date_effective', 'let_code'])) {
            $updateQuery = "UPDATE research_codes SET $column = ? WHERE id = ?";
        } else {
            $updateQuery = "UPDATE reoc_dynamic_data SET $column = ? WHERE id = ?";
        }

        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $value, $id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Data updated successfully.';
        } else {
            $response['message'] = 'Failed to update data.';
        }
        $stmt->close();
    }
}

echo json_encode($response);
?>
