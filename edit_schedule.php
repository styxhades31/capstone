<?php
require_once 'dbConnCode.php'; // Database connection file

$message = ''; // Initialize message variable
$message_type = ''; // Initialize message type variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the schedule ID and uploaded picture
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    $uploaded_picture = $_FILES['schedule_picture'];

    // Check if the uploaded picture exists and is valid
    if (isset($uploaded_picture) && $uploaded_picture['error'] === UPLOAD_ERR_OK) {
        $upload_directory = 'Schedules/';
        $new_picture_name = 'schedule_' . $schedule_id . '_' . time() . '_' . basename($uploaded_picture['name']);
        $upload_path = $upload_directory . $new_picture_name;

        // Check if a record already exists for this schedule
        $query = "SELECT `id`, `picture` FROM `schedule` WHERE `id` = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($current_id, $current_picture);
        $stmt->fetch();

        if ($stmt->num_rows > 0) {
            // Record exists, update the picture
            if ($current_picture && file_exists($upload_directory . $current_picture)) {
                // Delete the old picture from the server
                unlink($upload_directory . $current_picture);
            }

            // Move the new uploaded file to the directory
            if (move_uploaded_file($uploaded_picture['tmp_name'], $upload_path)) {
                // Update the picture name in the database
                $update_query = "UPDATE `schedule` SET `picture` = ?, `updated_at` = NOW() WHERE `id` = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("si", $new_picture_name, $schedule_id);
                $update_stmt->execute();

                // Success message
                $message = "Schedule display updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error uploading the picture!";
                $message_type = "error";
            }
        } else {
            // Record doesn't exist, insert a new record
            if (move_uploaded_file($uploaded_picture['tmp_name'], $upload_path)) {
                // Insert the new picture name in the database
                $insert_query = "INSERT INTO `schedule` (`id`, `picture`, `created_at`, `updated_at`) VALUES (?, ?, NOW(), NOW())";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("is", $schedule_id, $new_picture_name);
                $insert_stmt->execute();

                // Success message
                $message = "New schedule display created successfully!";
                $message_type = "success";
            } else {
                $message = "Error uploading the picture!";
                $message_type = "error";
            }
        }
    } else {
        $message = "No picture uploaded or error occurred!";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <!-- Include SweetAlert CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <!-- Your page content goes here -->

    <!-- Handle SweetAlert here using PHP variables -->
    <?php if ($message != ''): ?>
        <script>
            Swal.fire({
                title: "<?php echo $message; ?>",
                icon: "<?php echo $message_type; ?>",
                confirmButtonText: 'OK'
            }).then(function() {
                // Optionally redirect after the alert
                window.location.href = "adminHome.php";
            });
        </script>
    <?php endif; ?>

</body>
</html>
