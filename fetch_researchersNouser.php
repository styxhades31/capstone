<?php
require_once 'dbConnCode.php';

// Decode the incoming JSON request
$data = json_decode(file_get_contents('php://input'), true);

// Check if researcher_title_id is sent
if (!isset($data['researcher_title_id'])) {
    echo json_encode(['success' => false, 'message' => 'No researcher_title_id provided']);
    exit;
}

$researcherTitleId = $data['researcher_title_id'];

// Prepare the SQL query to fetch researchers
$query = "SELECT first_name, middle_initial, last_name, suffix 
          FROM ResearcherInvolved_NoUser 
          WHERE researcher_title_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $researcherTitleId);
$stmt->execute();
$result = $stmt->get_result();

// Check if researchers are found
if ($result->num_rows > 0) {
    $researchers = [];
    while ($row = $result->fetch_assoc()) {
        $researchers[] = [
            'first_name' => $row['first_name'],
            'middle_initial' => $row['middle_initial'],
            'last_name' => $row['last_name'],
            'suffix' => $row['suffix'],
        ];
    }
    echo json_encode(['success' => true, 'researchers' => $researchers]);
} else {
    echo json_encode(['success' => false, 'message' => 'No researchers found']);
}

$stmt->close();
$conn->close();
?>
