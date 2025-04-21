<?php
session_start();
require_once 'dbConnCode.php';

header('Content-Type: application/json');

// Get the raw POST data from the request
$data = json_decode(file_get_contents('php://input'), true);
$researcherTitleId = $data['researcher_title_id'] ?? null;

$response = ['success' => false, 'files' => []];

if (isset($researcherTitleId)) {
    // Fetch files associated with the given researcher_title_id
    $stmt = $conn->prepare("
        SELECT file_type, filename, file_path
        FROM researcher_files
        WHERE researcher_title_id = ?
    ");
    $stmt->bind_param("i", $researcherTitleId); // Use researcher_title_id in the query
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($file = $result->fetch_assoc()) {
            $response['files'][] = [
                'file_type' => $file['file_type'],
                'filename' => $file['filename'],
                'file_path' => $file['file_path']
            ];
        }
        $response['success'] = true;
    }
}

echo json_encode($response);
?>
