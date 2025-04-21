<?php
require_once 'dbConnCode.php'; // Include your database connection script

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['rti_id'])) {
    $rti_id = intval($_GET['rti_id']); // Sanitize the input

    // Query to get all certificates for the given rti_id
    $query = "SELECT * FROM Certificate_generatedNouser WHERE rti_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $rti_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $certificates = [];
        $basePath = 'pdfs/'; // Use relative path from your PHP directory

        while ($row = $result->fetch_assoc()) {
            $certificates[] = [
                'file_name' => basename($row['file_path']), // Extract only the file name
                'file_url' => $basePath . basename($row['file_path']), // Prepend base directory
                'generated_at' => $row['generated_at'], // Include generation time
                'file_type' => $row['file_type'] // Include file type
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => 'Certificates found for this title.',
            'certificates' => $certificates // Return all certificates
        ]);
    } else {
        // No certificate found
        echo json_encode(['success' => false, 'message' => 'No certificates found.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
