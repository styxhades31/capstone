<?php
require_once 'dbConnCode.php'; // Database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    
    if ($schedule_id) {
        // Query to get the current picture filename
        $query = "SELECT `picture` FROM `schedule` WHERE `id` = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $stmt->bind_result($current_picture);
        $stmt->fetch();
        $stmt->close();
        
        if ($current_picture) {
            // Delete the picture file from the folder
            $file_path = 'Schedules/' . $current_picture;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Remove the picture record from the database
            $update_query = "UPDATE `schedule` SET `picture` = NULL WHERE `id` = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $schedule_id);
            $update_stmt->execute();
            
            // Return success response
            echo json_encode(['success' => true]);
        } else {
            // Return error response if no picture found
            echo json_encode(['success' => false]);
        }
    }
}
?>
