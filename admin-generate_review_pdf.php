<?php
session_start();

// Check if the user is logged in and has the 'Reviewer' role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("You must be logged in as a reviewer to access this page.");
}

require_once('vendor/autoload.php'); // Include FPDI and FPDF libraries
include 'dbConnCode.php';  // Assuming this file handles your database connection


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use setasign\Fpdi\Fpdi;

// Ensure the researcher ID is provided in the POST request
if (!isset($_POST['researcher_title_id'])) {
    die("No researcher ID provided.");
}

$researcherTitleId = $_POST['researcher_title_id']; // Accessing the ID from POST

// Get the form data from POST request
$recommendedAction = $_POST['recommended_action'];
$recommendations = [
    $_POST['recommendation_1'],
    $_POST['recommendation_2'],
    $_POST['recommendation_3'],
    $_POST['recommendation_4'],
    $_POST['recommendation_5']
];

// Fetch the current Revision_document and user email
$sql = "SELECT rti.user_id, u.email 
        FROM researcher_title_informations rti 
        INNER JOIN users u ON rti.user_id = u.id 
        WHERE rti.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $researcherTitleId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($userId, $userEmail);
$stmt->fetch();
$stmt->close();

// Fetch the current Revision_document from the database
$sql = "SELECT Revision_document FROM researcher_title_informations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $researcherTitleId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($existingFilePath);
$stmt->fetch();
$stmt->close();

// If there is an existing file in the Revision_document column, delete it
if ($existingFilePath && file_exists($existingFilePath)) {
    unlink($existingFilePath); // Delete the existing file
}
// Fetch the reviewer's full name from the reviewer_profiles table
$sql = "
    SELECT CONCAT(rp.first_name, ' ', 
              IFNULL(CONCAT(rp.middle_initial, '.'), ''), ' ', 
              rp.last_name) AS reviewer_name
FROM reviewer_profiles rp
INNER JOIN users u ON rp.user_id = u.id
WHERE u.id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($reviewerName);
$stmt->fetch();
$stmt->close();


if (!$reviewerName) {
    $reviewerName = "Unknown Reviewer";  // Fallback if reviewer name is not found
}

// Fetch the research title from the database
$sql = "SELECT study_protocol_title FROM researcher_title_informations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $researcherTitleId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($studyProtocolTitle);
$stmt->fetch();
$stmt->close();

$title = $studyProtocolTitle ? $studyProtocolTitle : "Title not found.";

// Load the REVIEWER FORM.pdf template (relative path)
$templatePath = 'REVIEW FORM.pdf'; // Relative path to the template file in the same directory

if (!file_exists($templatePath)) {
    die("Template file not found: " . $templatePath);
}

$pdf = new Fpdi();
$pageCount = $pdf->setSourceFile($templatePath); // Load the original PDF

// Add page for the first recommendation before looping through the recommendations
$pdf->AddPage();

for ($i = 0; $i < 5; $i++) {
    if (!empty($recommendations[$i])) {
        // Add a new page for each recommendation
        if ($i > 0) {
            $pdf->AddPage();
        }

        // Import the template for each new page (optional)
        $templateId = $pdf->importPage(1); // Assuming page 1 of the template for each new page
        $pdf->useTemplate($templateId, 0, 0, 210, 297); // Use the imported template

        // Set font for the text
        $pdf->SetFont('Arial', '', 12);
        $pdf->SetTextColor(0, 0, 0); // Black color

        // Add research title
        $pdf->SetXY(5, 19);
        $pdf->MultiCell(0, 4, "Research Title: " . $title);

        // Set position for Recommended Action checkboxes
        if ($recommendedAction == "Qualified for Certification") {
            // Draw a checkbox for "Qualified for Certification"
            $pdf->SetXY(50, 40); // Position for "Qualified for Certification"
            $pdf->Rect(13, 32, 5, 5);  // Draw an empty checkbox
            $pdf->Text(14.3, 36, "X");   // Check the box by placing "X" inside the box
        } else {
            $pdf->SetXY(50, 40); // Position for "Qualified for Certification"
            $pdf->Rect(13, 32, 5, 5);  // Draw an empty checkbox
        }

        if ($recommendedAction == "Not Qualified for Certification") {
            // Draw a checkbox for "Not Qualified for Certification"
            $pdf->SetXY(50, 50); // Position for "Not Qualified for Certification"
            $pdf->Rect(13, 40, 5, 5);  // Draw an empty checkbox
            $pdf->Text(14.3, 44, "X");   // Check the box by placing "X" inside the box
        } else {
            $pdf->SetXY(50, 50); // Position for "Not Qualified for Certification"
            $pdf->Rect(13, 40, 5, 5);  // Draw an empty checkbox
        }

        // Add current recommendation
        $yPos = 51; // Starting Y position for the first recommendation
        $pdf->SetXY(8, $yPos);
        $pdf->MultiCell(0, 5, $recommendations[$i]);

        // Add reviewer name only on the last page (for simplicity)
        
            $pdf->SetXY(65, 270); // Position for reviewer name (adjust as necessary)
            $pdf->MultiCell(0, 4, "Name of Reviewer: " . $reviewerName);
       
    }
}
// Define the folder path and save the new reviewed PDF in the 'pdfs' folder
$pdfFolderPath = 'pdfs';  // Path to save PDFs
if (!is_dir($pdfFolderPath)) {
    mkdir($pdfFolderPath, 0777, true);  // Create the directory if it doesn't exist
}
$currentDate = date('Y-m-d');
// Add the current date to the file name
$newFilePath = $pdfFolderPath . '/reviewed_form_' . $researcherTitleId . '_' . $currentDate . '.pdf'; // Save path with current date
$pdf->Output('F', $newFilePath); // Save the PDF to the server

// Determine the status for Revision_Upload_button based on recommended action
$revisionUploadButton = ($recommendedAction == "Not Qualified for Certification") ? 'Yes' : 'None';

// Now, store the file path in the Revision_document column, update Revision_status and Revision_Upload_button
$revisionStatus = ($recommendedAction == "Not Qualified for Certification") ? 'To Comply' : 'None';

$sql = "UPDATE researcher_title_informations
        SET Revision_document = ?, Revision_status = ?, Revision_Upload_button = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $newFilePath, $revisionStatus, $revisionUploadButton, $researcherTitleId);
$stmt->execute();
$stmt->close();


// Now, send the email based on the Revision_status
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Replace with your email host
    $mail->SMTPAuth = true;
    $mail->Username = ''; // Replace with your email
    $mail->Password = ''; // Replace with your email password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('westkiria@gmail.com', 'West Kiria');
    $mail->addAddress($userEmail); // Add the recipient's email

    // Compose the email based on Revision_status
    if ($revisionStatus == 'To Comply') {
        // Not Qualified for Certification message
        $mail->Subject = 'Review Completed - Action Required';
        $mail->Body    = "Dear User, The review process for your document has been completed. Recommendations have been uploaded. Please log in to your portal to review the feedback and make the necessary revisions.
        
        
        Best regards,Your Review Team";
    } else {
        // Qualified for Certification message
        
        $mail->Subject = 'Document Approved';
        $mail->Body    = "Dear User,  Your document has been reviewed and is approved. Congratulations on completing the review process. 
        
        
        Best regards, Your Review Team";
    }

    $mail->send();
} catch (Exception $e) {
    echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submitted</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
    Swal.fire({
        icon: 'success',
        title: 'Review Submitted',
        text: 'Your review has been added successfully. You will be redirected shortly.',
        showConfirmButton: false,
        timer: 3000 // 3 seconds delay before redirect
    }).then(function() {
        window.location.href = 'admin-reviewerReviewDocs.php'; // Redirect to reviewDocuments.php after 3 seconds
    });
</script>

</body>
</html>
