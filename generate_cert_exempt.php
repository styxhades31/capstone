<?php
require 'vendor/autoload.php';
include 'dbConnCode.php';

$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Organization');
$pdf->SetTitle('Certificate of Exemption');

// Set zero margins to remove default page margins
$pdf->SetMargins(0, 0, 0, true);
$pdf->SetAutoPageBreak(TRUE, 0); // Set auto page break
$pdf->AddPage();

$bg_file = 'Certficate Of Exemption template.png';


$pdf->Image($bg_file, 0, 0, 210, 297, 'PNG', '', '', true, 300, '', false, false, 0, false, false, true);

header('Content-Type: application/json'); // Ensure JSON response
$response = []; // Prepare a single response object
// Debug: Check if user_id is passed
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    $response['success'] = false;
    $response['message'] = 'user_id is missing in the request.';
    $response['debug'] = $_GET;
    echo json_encode($response);
    exit; // Prevent further execution
}


$user_id = $_GET['user_id'];


$researcher_title_id =$user_id;
try {
    $conn->begin_transaction();
    $stmt = $conn->prepare("SELECT first_name, last_name, middle_initial, suffix FROM researcher_involved WHERE researcher_title_id = ?");
    $stmt->bind_param("i", $researcher_title_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        throw new Exception('No researchers found for this study.');
    }
    $researchers = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

     // Fetch the research title
$stmt = $conn->prepare("SELECT study_protocol_title FROM researcher_title_informations WHERE id = ?");
$stmt->bind_param("i", $researcher_title_id);
$stmt->execute();
$stmt->bind_result($study_protocol_title);
$stmt->fetch();
$stmt->close();

// Adjust font size based on character count
$baseFontSize1 = 14; // Default font size

if (strlen($study_protocol_title) > 211) {
    $fontSize = $baseFontSize1 - 6; // Reduce font size by 6 if character count exceeds 211
} elseif (strlen($study_protocol_title) > 145) {
    $fontSize = $baseFontSize1 - 4; // Reduce font size by 4 if character count exceeds 145 but not 211
} else {
    $fontSize = $baseFontSize1; // Keep default size
}


$pdf->SetFont('Swanseab', '', $fontSize); // Set adjusted font size
$pdf->SetTextColor(0, 0, 0); // Set font color to black

$maxWidth = 183 - 64; // Maximum width for text wrapping
$x = 65; // Starting x-coordinate
$y = 164; // Starting y-coordinate
$lineSpacing = 4; // Adjust line spacing for tighter control

// Split the title manually into lines based on max width
$words = explode(' ', strtoupper($study_protocol_title));
$currentLine = '';
$lines = [];

foreach ($words as $word) {
    $testLine = $currentLine ? "$currentLine $word" : $word;
    if ($pdf->GetStringWidth($testLine) > $maxWidth) {
        $lines[] = $currentLine; // Add the current line to the lines array
        $currentLine = $word; // Start a new line
    } else {
        $currentLine = $testLine;
    }
}
if ($currentLine) {
    $lines[] = $currentLine; // Add the last line
}

// Render each line manually
foreach ($lines as $line) {
    $pdf->SetXY($x, $y);
    $pdf->Cell(0, 0, $line, 0, 1, 'L'); // Add the line (left-aligned)
    $y += $lineSpacing; // Move down by the adjusted line spacing
}





 

    $baseFontSize = 40;
    $fontSize = $baseFontSize;
   

     // Adjust font size based on the number of researchers
     if (count($researchers) > 3) {
        $fontReduction = count($researchers) * 2;
        $fontSize -= $fontReduction;
    }



    $fontSize = max($fontSize, 12); // Ensure the font size does not go below 12

    $pageWidth = $pdf->GetPageWidth();
    $y = 90.45; // Starting y-coordinate

    foreach ($researchers as $researcher) {
        $fullName = $researcher['first_name'] . ' ' . ($researcher['middle_initial'] ? $researcher['middle_initial'] . '. ' : '') . $researcher['last_name'];
        $fullName .= $researcher['suffix'] ? ', ' . $researcher['suffix'] : '';

        $pdf->SetFont('NautilusPompilius', '', $fontSize);

         // Set font color to #784b32
         $pdf->SetTextColor(120, 75, 50);

        $textWidth = $pdf->GetStringWidth($fullName);
        $x = ($pageWidth - $textWidth) / 2; // Center the name

        $pdf->SetXY($x, $y);
        $pdf->Cell($textWidth, 10, $fullName, 0, 1, 'C');
        $y += -4 + ($fontSize / 2); // Smaller gap between lines, adjusted with font size
    } 

  // Get the current date and format it
$currentDate = date('j'); // Day of the month without leading zero
$currentMonth = date('F'); // Full month name
$currentYear = date('Y'); // Full year

// Determine the ordinal suffix (st, nd, rd, th)
if ($currentDate % 10 == 1 && $currentDate != 11) {
    $ordinal = '<sup>st</sup>';
} elseif ($currentDate % 10 == 2 && $currentDate != 12) {
    $ordinal = '<sup>nd</sup>';
} elseif ($currentDate % 10 == 3 && $currentDate != 13) {
    $ordinal = '<sup>rd</sup>';
} else {
    $ordinal = '<sup>th</sup>';
}

// Construct the "Date Issued" string with superscript
$dateIssued = "{$currentDate}{$ordinal} day of {$currentMonth} {$currentYear}";

// Add the "Date Issued" to the PDF with superscript
$pdf->SetFont('montserratlight', '', 14); // Set font to Montserrat Light
$pdf->SetTextColor(0, 0, 0); // Set font color to black
$pdf->SetXY(64, 238.39); // Set position
$pdf->writeHTMLCell(0, 0, 63.50, 238.39, $dateIssued, 0, 1, 0, true, 'L', true);

// Fetch the research category
$stmt = $conn->prepare("SELECT research_category FROM researcher_title_informations WHERE id = ?");
$stmt->bind_param("i", $researcher_title_id);
$stmt->execute();
$stmt->bind_result($research_category);
$stmt->fetch();
$stmt->close();

// Determine the research code based on the category
$code_acronym = '';
$code_id = 0;
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

    // Construct the code format: current year - code_acronym - padded code number
    $currentYear = date('Y');
    $formattedCodeNumber = str_pad($code_number, 4, '0', STR_PAD_LEFT); // Pad to 4 digits
    $finalCode = "{$currentYear}-{$code_acronym}-{$formattedCodeNumber}";

    // Print the code in the PDF
    $pdf->SetFont('Swanseab', '', 14); // Set font to Swan Bold, size 14
    $pdf->SetTextColor(0, 0, 0); // Set font color to black
    $pdf->SetXY(80, 186.70); // Set position
    $pdf->Cell(0, 0, $finalCode, 0, 1, 'L'); // Print the final code
} else {
    // Handle case where no matching code_acronym is found
    $pdf->SetFont('Swanseab', '', 14);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(80, 188);
    $pdf->Cell(0, 0, 'No Code Available', 0, 1, 'L'); // Print fallback message
}


// Fetch the dynamic data from the database
$query = "SELECT certificate_version, date_effective FROM reoc_dynamic_data LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $certificate_version = $data['certificate_version'];
    $date_effective = $data['date_effective'];

    // Display the certificate version
    $pdf->SetFont('montserratlight', '', 8);  // Font: montserratlight, size: 14
    $pdf->SetTextColor(0, 0, 0);               // Set font color to black
    $pdf->SetXY(192.50, 16);                      // Set position (adjust as needed)
    $pdf->Cell(0, 0,$certificate_version, 0, 1, 'L');

    // Display the effective date
    $pdf->SetXY(183, 19.5);                      // Set position for date (adjust as needed)
    $pdf->Cell(0, 0, $date_effective, 0, 1, 'L');
} else {
    $pdf->SetFont('montserratlight', '', 8);
    $pdf->SetTextColor(255, 0, 0);             // Set font color to red for error
    $pdf->SetXY(50, 100);
    $pdf->Cell(0, 10, "No data found in REOC Dynamic Data table.", 0, 1, 'L');
}

// Fetch the Signature from the reoc_dynamic_data table
$query = "SELECT Signature FROM reoc_dynamic_data LIMIT 1"; // Fetch the first row of Signature
$result = $conn->query($query);
$signature = '';
if ($result && $row = $result->fetch_assoc()) {
    $signature = $row['Signature'];
}

// Calculate the width of the text and set the X position to center the signature
$signature_width = $pdf->GetStringWidth($signature);
$centered_x = (210 - $signature_width) / 2; // 210 is the standard width of A4 paper in mm

// Set Y position and font
$pdf->SetY(268); // Set the Y position
$pdf->SetX($centered_x); // Set the X position (centered)
$pdf->SetFont('dejavusansb', '', 16); // Use DejaVu Sans Bold with size 16

// Print the signature
$pdf->Cell($signature_width, 10, $signature, 0, 1, 'C'); // Print the signature centered



$outputPath = $_SERVER['DOCUMENT_ROOT'] . '/pdfs/Certificate_' . $researcher_title_id . $finalCode . '_' . date('Y-m-d') . '.pdf';
$outputPathsl = 'pdfs/Certificate_' . $researcher_title_id . $finalCode . '_' . date('Y-m-d') . '.pdf';



    // Insert into the Certificate_generated table
$stmt = $conn->prepare("INSERT INTO certificate_generated (rti_id, file_path, file_type, status) VALUES (?, ?, ?, ?)");
$file_type = 'Certificate of Exemption'; // Set file type
$status = 'Hide'; // Default status
$stmt->bind_param("isss", $researcher_title_id, $outputPathsl, $file_type, $status);
$stmt->execute();
$stmt->close();

$conn->commit(); // Commit the transaction
    $pdf->Output($outputPath, 'F');

    // Success response
    $response['success'] = true;
    $response['message'] = 'PDF generated successfully.' ;
} catch (Exception $e) {
    $conn->rollback();

    // Error response
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(500); // Set error code
} finally {
    $conn->close();
}

// Return the final JSON response
echo json_encode($response);

?>
