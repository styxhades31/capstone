<?php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in and their role is 'Researcher'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

require_once 'dbConnCode.php'; // Include your database connection file

// Initialize message variable for SweetAlert
$message = ''; // Empty message initially

// Check if the form is submitted and the file is uploaded
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['revision_file'])) {
    $researcherTitleId = $_POST['researcher_title_id']; // Get researcher title ID from hidden field

    // Get file details
    $fileTmpPath = $_FILES['revision_file']['tmp_name'];
    $fileName = $_FILES['revision_file']['name'];
    $fileSize = $_FILES['revision_file']['size'];
    $fileType = $_FILES['revision_file']['type'];

    // Get file extension
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Define allowed file types (only PDFs)
    $allowedExtensions = ['pdf'];

    // Check if file extension is allowed
    if (in_array($fileExtension, $allowedExtensions)) {
        // Check for file size (for example, max 30MB)
        if ($fileSize <= 30 * 1024 * 1024) { // 30MB
            // Generate unique filename to avoid overwriting
            $newFileName = 'revision_' . time() . '.' . $fileExtension;
            $uploadPath = 'pdfs/' . $newFileName;

            // Begin a database transaction
            $conn->begin_transaction();

            try {
                // Check if a current revised document exists for this research ID
                $getCurrentFileQuery = "
                    SELECT Revised_document 
                    FROM researcher_title_informations 
                    WHERE id = ?
                ";
                $stmt = $conn->prepare($getCurrentFileQuery);
                $stmt->bind_param("i", $researcherTitleId);
                $stmt->execute();
                $result = $stmt->get_result();
                $currentFile = null;

                if ($row = $result->fetch_assoc()) {
                    $currentFile = $row['Revised_document']; // Get the current file path
                }
                $stmt->close();

                // If a current file exists, add it to the Revised_Document_History table
                if (!empty($currentFile)) {
                    $insertHistoryQuery = "
                        INSERT INTO revised_document_history (researcher_info_id, old_revised_document) 
                        VALUES (?, ?)
                    ";
                    $stmt = $conn->prepare($insertHistoryQuery);
                    $stmt->bind_param("is", $researcherTitleId, $currentFile);
                    $stmt->execute();
                    $stmt->close();
                }

                // Move the uploaded file to the target directory
                if (move_uploaded_file($fileTmpPath, $uploadPath)) {
                    // Update the Revised_document column in the database with the new file path
                    $updateQuery = "
                        UPDATE researcher_title_informations 
                        SET Revised_document = ?, Revision_status = 'Uploaded revisions' 
                        WHERE id = ?
                    ";
                    $stmt = $conn->prepare($updateQuery);
                    $stmt->bind_param("si", $uploadPath, $researcherTitleId);
                    $stmt->execute();
                    $stmt->close();

                    // Optional: Update Revision_Upload_button to "No" after file upload
                    $updateRevisionButtonQuery = "
                        UPDATE researcher_title_informations 
                        SET Revision_Upload_button = 'No' 
                        WHERE id = ?
                    ";
                    $stmt = $conn->prepare($updateRevisionButtonQuery);
                    $stmt->bind_param("i", $researcherTitleId);
                    $stmt->execute();
                    $stmt->close();

                    // Commit the transaction
                    $conn->commit();

                    // Set success message for SweetAlert
                    $message = "success|Revision uploaded successfully.";
                    $redirectUrl = 'admin-researcherViewApps.php'; // Redirection URL after success
                } else {
                    throw new Exception("Error uploading file to the target directory.");
                }
            } catch (Exception $e) {
                // Rollback the transaction in case of an error
                $conn->rollback();
                $message = "error|" . $e->getMessage();
            }
        } else {
            // Set error message for file size exceeding the limit
            $message = "error|File size exceeds the limit. Max allowed size is 30MB.";
        }
    } else {
        // Set error message for invalid file type
        $message = "error|Only PDF files are allowed.";
    }
} else {
    // Set error message if the form is not submitted properly
    $message = "error|No file selected or the form was not submitted correctly.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Upload</title>

    <!-- Include SweetAlert library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <!-- Display SweetAlert based on the message -->
    <?php if ($message): ?>
        <script>
            <?php 
                // Separate the message type (success/error) and content
                list($type, $content) = explode('|', $message);
                $redirectUrl = isset($redirectUrl) ? $redirectUrl : ''; // Check if redirect URL exists
            ?>
            Swal.fire({
                title: '<?php echo ucfirst($type); ?>',
                html: '<?php echo addslashes($content); ?>', // Ensure proper escaping of content
                icon: '<?php echo $type; ?>',
                confirmButtonText: 'OK'
            }).then(function() {
                // If the action was successful, redirect to 'viewApplications.php'
                if ('<?php echo $type; ?>' === 'success' && '<?php echo $redirectUrl; ?>') {
                    window.location.href = '<?php echo $redirectUrl; ?>'; // Redirect after success
                }
            });
        </script>
    <?php endif; ?>

</body>
</html>
