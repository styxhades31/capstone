<?php
// Include Composer's autoloader and FPDI library
require_once('vendor/autoload.php');

use setasign\Fpdi\Fpdi;

// Database connection
require_once 'dbConnCode.php';

// Decode JSON input to get user_id
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'];

try {
    // Fetch study protocol title and researcher names
    $titleQuery = $conn->prepare("
        SELECT rti.id AS researcher_title_id, rti.study_protocol_title
        FROM Researcher_title_informations rti
        WHERE rti.user_id = ?
    ");
    $titleQuery->bind_param("i", $user_id);
    $titleQuery->execute();
    $titleResult = $titleQuery->get_result();

    if ($titleResult->num_rows === 0) {
        throw new Exception("No study protocol found for the user.");
    }

    $titleRow = $titleResult->fetch_assoc();
    $studyProtocolTitle = $titleRow['study_protocol_title'];
    $researcherTitleId = $titleRow['researcher_title_id'];

    // Get researcher names from Researcher_involved table
    $researchersQuery = $conn->prepare("
        SELECT first_name, middle_initial, last_name, suffix
        FROM Researcher_involved
        WHERE researcher_title_id = ?
    ");
    $researchersQuery->bind_param("i", $researcherTitleId);
    $researchersQuery->execute();
    $researchersResult = $researchersQuery->get_result();

    $researcherNames = [];
    while ($row = $researchersResult->fetch_assoc()) {
        $name = $row['first_name'] . ' ' . ($row['middle_initial'] ? $row['middle_initial'] . '.' : '') . ' ' . $row['last_name'];
        $name .= $row['suffix'] ? ', ' . $row['suffix'] : '';
        $researcherNames[] = $name;
    }

    // Determine if the title and list exceed length limits for sample.pdf
    $useSample2 = strlen($studyProtocolTitle) > 40 || count($researcherNames) > 6;

    // Define template paths and coordinates
    $pdfPath = $useSample2 ? 'C:/xampp/htdocs/REOC/sample2.pdf' : 'C:/xampp/htdocs/REOC/sample.pdf';

    $coordinates = [
        'sample' => [
            'approvalDate' => [65, 137],
            'expiryDate' => [65, 141.6],
            'currentDate' => [25, 37],
            'dearResearcher' => [25, 44.5],
            'protocolTitle' => [65, 130],
            'researcherField' => [65, 150]
        ],
        'sample2' => [
            'approvalDate' => [65, 132], // Example coordinates for sample2.pdf
            'expiryDate' => [65, 136.6],
            'currentDate' => [25, 37],
            'dearResearcher' => [25, 44.5],
            'protocolTitle' => [65, 116.6],
            'researcherField' => [65, 144.2]
        ]
    ];

    // Choose coordinates based on selected template
    $coords = $useSample2 ? $coordinates['sample2'] : $coordinates['sample'];

    // Load PDF template
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile($pdfPath);
    $template = $pdf->importPage(1);
    $pdf->useTemplate($template);

    // Set font
    $pdf->SetFont('Arial', '', 11);

    // Set current date for approval period
    $approvalDate = new DateTime();
    $formattedApprovalDate = $approvalDate->format('F d, Y');

    // Set expiry date to one year after approval date
    $expiryDate = clone $approvalDate;
    $expiryDate->modify('+1 year');
    $formattedExpiryDate = $expiryDate->format('F d, Y');

    // Insert Current Date
    $pdf->SetXY($coords['currentDate'][0], $coords['currentDate'][1]);
    $pdf->MultiCell(0, 10, $formattedApprovalDate, 0, 'L');

    // Insert researcher names below "Dear Researcher,"
    $pdf->SetXY($coords['dearResearcher'][0], $coords['dearResearcher'][1]);
    foreach ($researcherNames as $name) {
        $pdf->MultiCell(0, 3.5, $name, 0, 'L');
        $pdf->SetX($coords['dearResearcher'][0]);
    }

    // Insert study protocol title
    $pdf->SetXY($coords['protocolTitle'][0], $coords['protocolTitle'][1]);
    $pdf->MultiCell(121, 4, $studyProtocolTitle, 0, 'L');

    // Insert researcher names again in the "Researcher" field in the table
    $pdf->SetXY($coords['researcherField'][0], $coords['researcherField'][1]);
    foreach ($researcherNames as $name) {
        $pdf->MultiCell(0, 3.5, $name, 0, 'L');
        $pdf->SetX($coords['researcherField'][0]);
    }

    // Insert Approval Period and Expiry Date
    $pdf->SetXY($coords['approvalDate'][0], $coords['approvalDate'][1]);
    $pdf->MultiCell(0, 10, $formattedApprovalDate, 0, 'L');

    $pdf->SetXY($coords['expiryDate'][0], $coords['expiryDate'][1]);
    $pdf->MultiCell(0, 10, $formattedExpiryDate, 0, 'L');

    // Save the updated PDF
    $outputPath = 'C:/xampp/htdocs/REOC/Generated Certificates/certification_' . $user_id . '.pdf';
    $pdf->Output($outputPath, 'F');

    // Response back to AJAX
    echo json_encode([
        'success' => true,
        'message' => 'Certification generated successfully.',
        'file_path' => $outputPath
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
