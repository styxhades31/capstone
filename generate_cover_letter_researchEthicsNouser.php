<?php
require_once('vendor/autoload.php');
include 'dbConnCode.php';
$user_id = intval($_GET['user_id']);
$researcher_title_id = $user_id;
// Use the FPDI namespace
use setasign\Fpdi\Fpdi;

// Create a new FPDI object
$pdf = new FPDI();
$pdf->SetAuthor('Your Organization');
$pdf->SetTitle('Cover Letter');

// Add a page to the PDF
$pdf->AddPage();

// Path to the PDF file
$bg_file = 'C:\\xampp\\htdocs\\REOC\\Research Ethic clearance Cover Letter.pdf';

// Import the first page of the PDF
$pageCount = $pdf->setSourceFile($bg_file);
$tplId = $pdf->importPage(1);

// Use the imported page as a template
$pdf->useTemplate($tplId, 0, 0, 210, 297);

// Set the font to Arial Bold, size 11
$pdf->SetFont('Arial', 'B', 14);

// Fetch the study protocol title
$stmt = $conn->prepare("SELECT study_protocol_title FROM ResearcherTitleInfo_NoUser WHERE id = ?");
$stmt->bind_param("i", $researcher_title_id);
$stmt->execute();
$stmt->bind_result($study_protocol_title);
$stmt->fetch();
$stmt->close();

// Initial position for the title
$x = 67;
$y = 130;

// Write the study protocol title, handling long text with line wrapping
$lineLength = 183 - $x; // Maximum width for text before wrapping (in mm)

// Split the title into words
$words = explode(' ', $study_protocol_title);
$pdf->SetXY($x, $y); // Set the initial position

$currentLine = ''; // To accumulate words for the current line

foreach ($words as $word) {
    $wordWidth = $pdf->GetStringWidth($word . ' '); // Calculate the width of the word with a space
    $lineWidth = $pdf->GetStringWidth($currentLine . $word . ' '); // Calculate the width of the line with the new word

    if ($lineWidth > $lineLength) {
        // If the line exceeds the max length, write the current line and move to the next line
        $pdf->Write(10, $currentLine); // Write the current line
        $y += 4.25; // Move down by 4.25mm
        $pdf->SetXY($x, $y); // Reset to the left margin
        $currentLine = $word . ' '; // Start a new line with the current word
    } else {
        // Add the word to the current line
        $currentLine .= $word . ' ';
    }
}

// Write the last line if there's any remaining text
if (!empty(trim($currentLine))) {
    $pdf->Write(10, $currentLine);
}
// Fetch the research category
$stmt = $conn->prepare("SELECT research_category FROM ResearcherTitleInfo_NoUser WHERE id = ?");
$stmt->bind_param("i", $researcher_title_id);
$stmt->execute();
$stmt->bind_result($research_category);
$stmt->fetch();
$stmt->close();

// Determine the research code based on the category
$code_acronym = '';
if ($research_category === "WMSU Undergraduate Thesis - 300.00") {
    $code_acronym = 'UG';
    $code_id = 1;
} elseif ($research_category === "WMSU Master's Thesis - 700.00" || $research_category === "WMSU Dissertation - 1,500.00") {
    $code_acronym = 'GS';
    $code_id = 2;
} elseif ($research_category === "WMSU Institutionally Funded Research - 2,000.00") {
    $code_acronym = 'IF';
    $code_id = 3;
} elseif ($research_category === "Externally Funded Research / Other Institution - 3,000.00") {
    $code_acronym = 'EF';
     $code_id = 4;
}

if ($code_acronym !== '') {
    // Fetch the current code number and name from research_codes
    $stmt = $conn->prepare("SELECT code_number FROM research_codes WHERE code_acronym = ?");
    $stmt->bind_param("s", $code_acronym);
    $stmt->execute();
    $stmt->bind_result($code_number);
    $stmt->fetch();
    $stmt->close();


     // Increment the code number
     $new_code_number = $code_number + 1;

     // Update the code number in the research_codes table
     $stmt = $conn->prepare("UPDATE research_codes SET code_number = ? WHERE id = ?");
     $stmt->bind_param("is", $new_code_number, $code_id);
     $stmt->execute();
     $stmt->close();
 
     $conn->commit(); // Commit the transaction

    // Construct the code format: current year - code_acronym - padded code number
    $currentYear = date('Y');
    $formattedCodeNumber = str_pad($code_number, 4, '0', STR_PAD_LEFT); // Pad to 4 digits
    $finalCode = "{$currentYear}-{$code_acronym}-{$formattedCodeNumber}";

    // Print the code in the PDF
    $pdf->SetFont('Arial', 'B', 11); // Set font to Arial Bold, size 11
    $pdf->SetTextColor(0, 0, 0); // Set font color to black
    $pdf->SetXY(67, 129.50); // Set position
    $pdf->Cell(0, 0, $finalCode, 0, 1, 'L'); // Print the final code
} else {
    // Handle case where no matching code_acronym is found
    $pdf->SetFont('Arial', 'B', 11); // Set font to Arial Bold, size 11
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(67, 127); // Set position
    $pdf->Cell(0, 0, 'No Code Available', 0, 1, 'L'); // Print fallback message
}


// Get the current date
$currentDate = date('F j, Y'); // Example: "November 26, 2024"
// Calculate the current date + 1 year
$nextYearDate = date('F j, Y', strtotime('+1 year')); // Example: "November 26, 2025"

// Set the position for the current date
$pdf->SetXY(67, 156);
$pdf->Write(10, $currentDate);

// Set the position for the next year's date
$pdf->SetXY(67, 160.50);
$pdf->Write(10, $nextYearDate);

// Retrieve and display researchers' names
header('Content-Type: application/json');
$response = [];

// Check if user_id is passed
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    $response['success'] = false;
    $response['message'] = 'user_id is missing in the request.';
    $response['debug'] = $_GET;
    echo json_encode($response);
    exit;
}


try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT first_name, last_name, middle_initial, suffix FROM  ResearcherInvolved_NoUser  WHERE researcher_title_id = ?");
    $stmt->bind_param("i", $researcher_title_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        throw new Exception('No researchers found for this study.');
    }

    // Fetch all researchers
    $researchers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Set font for researchers
    $pdf->SetFont('Arial', 'B', 11);

    // Initial position for researchers
    $x = 67;
    $y = 167.25;

    // Counter to track the number of researchers
    $counter = 0;
    foreach ($researchers as $researcher) {
        $fullName = $researcher['first_name'] . ' ' .
                    $researcher['middle_initial'] . '.' . ' ' .
                    $researcher['last_name'] . ' ' .
                    $researcher['suffix'];

        // Set position
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fullName);

        // Increment counter and adjust positions
        $counter++;
        $y += 4.25; // Move to the next line

        // If more than 5 researchers, create a new column
        if ($counter % 5 == 0) {
            $x += 50; // Move to the right
            $y = 167.25; // Reset the y-coordinate
        }
    }
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT first_name, last_name, middle_initial, suffix FROM  ResearcherInvolved_NoUser  WHERE researcher_title_id = ?");
    $stmt->bind_param("i", $researcher_title_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception('No researchers found for this study.');
    }
    
    // Fetch all researchers
    $researchers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Set font for researchers
    $pdf->SetFont('Arial', 'B', 11);
    
    // Initial position for researchers
    $x = 25;
    $y = 47;
    
    // Counter to track the number of researchers
    $counter = 0;
    foreach ($researchers as $researcher) {
        $fullName = $researcher['first_name'] . ' ' .
                    $researcher['middle_initial'] . '.' . ' ' .
                    $researcher['last_name'] . ' ' .
                    $researcher['suffix'];
    
        // Set position
        $pdf->SetXY($x, $y);
        $pdf->Write(10, $fullName);
    
        // Increment counter and adjust positions
        $counter++;
        $y += 4.25; // Move to the next line
    
        // If more than 5 researchers, create a new column
        if ($counter % 5 == 0) {
            $x += 50; // Move to the right
            $y = 47; // Reset the y-coordinate for the new column
        }
    }
    // Get the current date
$currentDate = date('F j, Y'); // Example: "November 26, 2024"
    // Set the position for the current date
$pdf->SetXY(25, 37);
$pdf->Write(10, $currentDate);


// Fetch the dynamic data from the database
$query = "SELECT let_code, date_effective FROM reoc_dynamic_data LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $certificate_version = $data['let_code'];
    $date_effective = $data['date_effective'];

    // Display the certificate version
    $pdf->SetFont('Arial', '', 6); // Font: Arial, size: 6
    $pdf->SetTextColor(0, 0, 0);    // Set font color to black
    $pdf->SetXY(185, 33);          // Set position for certificate version
    $pdf->Cell(0, 0, $certificate_version, 0, 1, 'L');

    // Display the effective date
    $pdf->SetXY(180, 35.50);          // Set position for effective date
    $pdf->Cell(0, 0, $date_effective, 0, 1, 'L');
} else {
    // Handle case when no data is found
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetTextColor(255, 0, 0); // Set font color to red for error message
    $pdf->SetXY(50, 100);
    $pdf->Cell(0, 10, "No data found in REOC Dynamic Data table.", 0, 1, 'L');
}
$conn->commit(); // Commit the transaction
    // Save the new PDF
    $outputPath = 'C:/xampp/htdocs/REOC/pdfs/Cover Letter_' . $researcher_title_id .  $finalCode . '_' . date('Y-m-d') . '.pdf';
    $pdf->Output($outputPath, 'F');
 // Insert the path into the Certificate_generated table
 $outputPathsql= 'Cover Letter_' . $researcher_title_id .  $finalCode . '_' . date('Y-m-d') . '.pdf';
 $stmt = $conn->prepare("INSERT INTO Certificate_generatedNouser (rti_id, file_path, file_type, status) VALUES (?, ?, ?, ?)");
 $file_type = 'Cover Letter'; // Set file type
 $status = 'Hide'; // Default status
 $stmt->bind_param("isss", $researcher_title_id, $outputPathsql, $file_type, $status);
 $stmt->execute();
 $stmt->close();


    

    // Success response
    $response['success'] = true;
    $response['message'] = 'PDF generated successfully.';
    $response['file_path'] = $outputPath;
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(500);
} finally {
    $conn->close();
}

// Return JSON response
echo json_encode($response);
?>
