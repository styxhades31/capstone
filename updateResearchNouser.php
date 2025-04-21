<?php
require_once 'dbConnCode.php';

// Initialize a message variable for SweetAlert
$message = '';
$redirectUrl = 'admin_applicationforms.php'; // URL to redirect after SweetAlert

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data
    $id = $_POST['id'];
    $study_protocol_title = $_POST['study_protocol_title'];
    $college = $_POST['college'];
    $college_other = isset($_POST['college_other']) ? $_POST['college_other'] : null;
    $research_category = $_POST['research_category'];
    $adviser_name = $_POST['adviser_name'];

    // Handle college information (if 'Other' is selected, use the 'college_other' field)
    if ($college == 'Other' && $college_other) {
        $college = $college_other; // Use the custom value
    }

    // Prepare SQL query to update the Researcher_title_informations table
    $updateQuery = "UPDATE ResearcherTitleInfo_NoUser
                    SET study_protocol_title = ?, college = ?, research_category = ?, adviser_name = ?
                    WHERE id = ?";
    
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssssi", $study_protocol_title, $college, $research_category, $adviser_name, $id);
    
    // Execute the query and check if the update was successful
    if ($stmt->execute()) {
        // Update the researchers involved
        if (isset($_POST['researcher_first_name'])) {
            foreach ($_POST['researcher_first_name'] as $researcher_id => $first_name) {
                $middle_initial = $_POST['researcher_middle_initial'][$researcher_id];
                $last_name = $_POST['researcher_last_name'][$researcher_id];
                $suffix = $_POST['researcher_suffix'][$researcher_id];

                // Prepare query to update researchers involved
                $updateResearcherQuery = "UPDATE  ResearcherInvolved_NoUser
                                          SET first_name = ?, middle_initial = ?, last_name = ?, suffix = ?
                                          WHERE id = ?";
                
                $researcherStmt = $conn->prepare($updateResearcherQuery);
                $researcherStmt->bind_param("ssssi", $first_name, $middle_initial, $last_name, $suffix, $researcher_id);
                $researcherStmt->execute();
            }
        }

        $replaceCertificates = $_FILES['replace_certificate'] ?? [];
        $certificateStatuses = $_POST['certificate_status'] ?? [];


        // Directory where the files are stored
        $uploadDir = 'C:/xampp/htdocs/REOC/pdfs/';

        foreach ($certificateStatuses as $certificateId => $status) {
            // Update the status in the database
            $updateStatusQuery = "UPDATE Certificate_generatedNouser SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($updateStatusQuery);
            $stmt->bind_param("si", $status, $certificateId);
            $stmt->execute();
        }
    
        // Process each file replacement
        foreach ($replaceCertificates['tmp_name'] as $certificateId => $tmpName) {
            if (!empty($tmpName)) { // Check if a file has been uploaded
                $newFileName = $replaceCertificates['name'][$certificateId];
                $uploadPath = $uploadDir . basename($newFileName); // Full path for the new file
    
                // Retrieve the old file name from the database
                $oldFileQuery = "SELECT file_path FROM Certificate_generatedNouser WHERE id = ?";
                $stmt = $conn->prepare($oldFileQuery);
                $stmt->bind_param("i", $certificateId);
                $stmt->execute();
                $result = $stmt->get_result();
    
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $oldFileName = $row['file_path']; // Old file name from the database
                    $oldFilePath = $uploadDir . $oldFileName; // Construct full path using the directory
    
                    // Check if the old file exists and remove it
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath); // Deletes the old file
                    }
                } else {
                    echo "No record found for certificate ID: " . htmlspecialchars($certificateId) . "<br>";
                    continue;
                }
    
                // Move the new uploaded file to the desired directory
                if (move_uploaded_file($tmpName, $uploadPath)) {
                    // Update the database with the new file name (not full path)
                    $updateCertificateQuery = "UPDATE Certificate_generatedNouser SET file_path = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateCertificateQuery);
                    $stmt->bind_param("si", $newFileName, $certificateId); // Save only the file name
                    $stmt->execute();
    
                  
                } else {
                    
                }
            }
        }
    
        

        // Set success message for SweetAlert
        $message = 'Record updated successfully!';
    } else {
        // Set error message for SweetAlert
        $message = 'Error updating record: ' . $stmt->error;
    }

    // Close the statement
    $stmt->close();
} else {
    $message = "Invalid request!";
}

// Close the database connection
$conn->close();
?>

<!-- HTML for SweetAlert -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Researcher Application</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

    <!-- Display SweetAlert message -->
    <script>
        <?php if ($message): ?>
            Swal.fire({
                icon: '<?php echo (strpos($message, 'Error') !== false) ? 'error' : 'success'; ?>',
                title: '<?php echo $message; ?>',
                showConfirmButton: true,
                timer: 3000 // 3 seconds timer
            }).then((result) => {
                // Redirect after the SweetAlert
                if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                    window.location.href = '<?php echo $redirectUrl; ?>';
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>
