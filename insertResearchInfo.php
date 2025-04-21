<?php
require_once 'dbConnCode.php';  // Database connection file

$message = ''; // Initialize message variable
$type = ''; // Initialize type variable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['insertData'])) {
    // Collect input data
    $study_protocol_title = $_POST['study_protocol_title'];
    $research_category = $_POST['research_category'];
    $adviser_name = $_POST['adviser_name'];
    $type_of_review = $_POST['type_of_review'];
    $payment = $_POST['payment'];
    $college = ($_POST['collegeSelect'] === 'other') ? $_POST['collegeText'] : $_POST['collegeSelect'];

    // Validation and security checks
    if (empty($study_protocol_title) || empty($college) || empty($research_category)) {
        $message = "Please fill all required fields correctly.";
        $type = "error";
    } else {
        // SQL Insertion statement for main research info
        $stmt = $conn->prepare("INSERT INTO ResearcherTitleInfo_NoUser (study_protocol_title, college, research_category, adviser_name, type_of_review, payment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $study_protocol_title, $college, $research_category, $adviser_name, $type_of_review, $payment);
        $stmt->execute();
        $researcher_title_id = $conn->insert_id;  // Capture the last inserted id to use as a foreign key

        // Insert each researcher
        if (isset($_POST['first_name'])) {  // Check if researcher data exists
            $researcherCount = count($_POST['first_name']);
            for ($i = 0; $i < $researcherCount; $i++) {
                if (!empty($_POST['first_name'][$i]) && !empty($_POST['last_name'][$i])) { // Make sure we don't insert empty names
                    $stmt = $conn->prepare("INSERT INTO ResearcherInvolved_NoUser (researcher_title_id, first_name, last_name, middle_initial, suffix) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("issss", $researcher_title_id, $_POST['first_name'][$i], $_POST['last_name'][$i], $_POST['middle_initial'][$i], $_POST['suffix'][$i]);
                    $stmt->execute();
                }
            }
        }

        // Check if the main and researchers info were inserted successfully
        if ($stmt->affected_rows > 0) {
            $message = "Data inserted successfully.";
            $type = "success";
        } else {
            $message = "Error inserting data.";
            $type = "error";
        }
        $stmt->close();
       
       
    }
} else {
    header("Location: admin_applicationforms.php?message=Unauthorized access&type=error");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Submission</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<script>
    window.onload = function() {
        <?php if (!empty($message) && !empty($type)): ?>
        Swal.fire({
            title: '<?php echo $type === "success" ? "Success" : "Error"; ?>',
            text: '<?php echo $message; ?>',
            icon: '<?php echo $type; ?>',
            confirmButtonText: 'Ok'
        }).then((result) => {
            // Redirect when the user clicks 'Ok'
            if (result.isConfirmed) {
                window.location.href = 'admin_applicationforms.php';
            }
        });
        <?php endif; ?>
    };
</script>

</body>
</html>

