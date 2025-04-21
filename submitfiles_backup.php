<?php
session_start();
// Default value for success is false
$success = false;
// Include database connection
require_once 'dbConnCode.php';  // This file contains the $conn variable for mysqli
require 'vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Start CSRF token generation if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in and is a researcher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Researcher') {
    header("Location: login.php");
    exit();
}
// Logout logic
if (isset($_POST['logout'])) {
    // Validate CSRF token
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        session_destroy(); // Destroy the session to log the user out
        header("Location: login.php");
        exit();
    } else {
        echo "<script>alert('Invalid CSRF token.');</script>";
    }
}

// Fetch all colleges from the database
$query = "SELECT college_name_and_color FROM colleges";
$result = mysqli_query($conn, $query);

// Check if the query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
// Get user_id from session
$user_id = $_SESSION['user_id'];  // Get the current user's ID

// Query to get the study protocol titles submitted by the researcher
$title_query = "SELECT study_protocol_title FROM Researcher_title_informations WHERE user_id = ?";
$stmt = $conn->prepare($title_query);
$stmt->bind_param("i", $user_id);  // Bind the user_id parameter
$stmt->execute();
$title_result = $stmt->get_result();  // Execute and get the result

// Fetch user email from the database
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($email);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
     // Rate limiting
     if (isset($_SESSION['last_submission_time']) && (time() - $_SESSION['last_submission_time']) < 10) {
        // Redirect to researcherHome.php if submission is too quick
        header("Location: researcherHome.php");
        exit();
    }
    $_SESSION['last_submission_time'] = time(); // Update the last submission time
    

    // Sanitize inputs
    $study_protocol_title = filter_input(INPUT_POST, 'study_protocol_title', FILTER_SANITIZE_STRING);
    $adviser_name = filter_input(INPUT_POST, 'adviser_name', FILTER_SANITIZE_STRING);
    $college_dropdown = filter_input(INPUT_POST, 'college_dropdown', FILTER_SANITIZE_STRING);
    $other_college = filter_input(INPUT_POST, 'other_college', FILTER_SANITIZE_STRING);
    $research_category_dropdown = filter_input(INPUT_POST, 'research_category_dropdown', FILTER_SANITIZE_STRING);
    $other_category = filter_input(INPUT_POST, 'other_category', FILTER_SANITIZE_STRING);
    $hidden_research_category = filter_input(INPUT_POST, 'hidden_research_category', FILTER_SANITIZE_STRING); // Get the value from the hidden input

    // Get the college value (use the "Other" field if applicable)
    $college = $college_dropdown === 'Other' ? $other_college . ' - Brown' : $college_dropdown;

   // Determine the research category value
if (!empty($hidden_research_category)) {
    // Use the hidden research category if it's set (this is when the dropdown is disabled)
    $research_category = $hidden_research_category;
} else {
    // Otherwise, fall back to the regular dropdown value
    $research_category = $research_category_dropdown === 'Other' ? $other_category : $research_category_dropdown;
}

// Add logic to decode the apostrophe entity if present
if (strpos($research_category, '&#39;') !== false) {
    $research_category = html_entity_decode($research_category, ENT_QUOTES);
}

    // Insert into Researcher_title_informations
    $stmt = $conn->prepare("INSERT INTO Researcher_title_informations (user_id, study_protocol_title, college, research_category, adviser_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $study_protocol_title, $college, $research_category, $adviser_name);
    $stmt->execute();

    // Get the ID of the inserted researcher title information
    $researcher_title_id = $conn->insert_id;

    // Handle insertion of researcher names (including co-researchers)
    $researcher_first_names = $_POST['researcher_first_name'];
    $researcher_last_names = $_POST['researcher_last_name'];
    $researcher_middle_initials = $_POST['researcher_middle_initial'];
    $researcher_suffixes = $_POST['researcher_suffix'];

    for ($i = 0; $i < count($researcher_first_names); $i++) {
        $first_name = filter_var($researcher_first_names[$i], FILTER_SANITIZE_STRING);
        $last_name = filter_var($researcher_last_names[$i], FILTER_SANITIZE_STRING);
        $middle_initial = !empty($researcher_middle_initials[$i]) ? filter_var($researcher_middle_initials[$i], FILTER_SANITIZE_STRING) : null;
        $suffix = !empty($researcher_suffixes[$i]) ? filter_var($researcher_suffixes[$i], FILTER_SANITIZE_STRING) : null;

        // Insert into Researcher_involved
        $stmt = $conn->prepare("INSERT INTO Researcher_involved (researcher_title_id, first_name, last_name, middle_initial, suffix) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $researcher_title_id, $first_name, $last_name, $middle_initial, $suffix);
        $stmt->execute();
    }

    // Handle file uploads
    $upload_dir = 'uploads/';  // Directory where files will be saved
    $files = ['application_form', 'research_protocol', 'technical_review', 'data_instruments', 'informed_consent', 'cv', 'study_protocol_form', 'informed_consent_form', 'exempt_review_form'];

    foreach ($files as $file) {
        if (!empty($_FILES[$file]['name'])) {
            // Check file size (20 MB = 20 * 1024 * 1024 bytes)
            if ($_FILES[$file]['size'] > 20 * 1024 * 1024) {
                die("Error: The file size for $file exceeds the maximum limit of 20 MB.");
            }

            $file_name = basename($_FILES[$file]['name']);
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES[$file]['tmp_name'], $file_path)) {
                // Insert into the database using mysqli
                $stmt = $conn->prepare("INSERT INTO researcher_files (researcher_title_id, file_type, filename, file_path) VALUES (?, ?, ?, ?)");
                $file_type = ucfirst(str_replace('_', ' ', $file));  // Format the file type
                $stmt->bind_param("isss", $researcher_title_id, $file_type, $file_name, $file_path);
                $stmt->execute();
            }
        }
    }

    // Handle other files (additional documents)
    if (!empty($_FILES['other_files']['name'][0])) {  // Check if at least one other file is uploaded
        foreach ($_FILES['other_files']['name'] as $key => $other_file_name) {
            if (!empty($other_file_name)) {
                // Check file size (20 MB = 20 * 1024 * 1024 bytes)
                if ($_FILES['other_files']['size'][$key] > 20 * 1024 * 1024) {
                    die("Error: The file size for $other_file_name exceeds the maximum limit of 20 MB.");
                }

                $file_path = $upload_dir . basename($other_file_name);
                if (move_uploaded_file($_FILES['other_files']['tmp_name'][$key], $file_path)) {
                    $stmt = $conn->prepare("INSERT INTO researcher_files (researcher_title_id, file_type, filename, file_path) VALUES (?, 'Other', ?, ?)");
                    $stmt->bind_param("iss", $researcher_title_id, $other_file_name, $file_path);
                    $stmt->execute();
                }
            }
        }
    }

    // After successful insertions into the researcher and file tables
    // Handle automatic appointment scheduling

    // Get the current date and add 5 days to set the minimum appointment date
    $start_date = date('Y-m-d', strtotime('+5 days'));

    // Function to check if the date is valid (Monday to Friday)
    function isValidAppointmentDate($date) {
        $day_of_week = date('N', strtotime($date)); // 1 = Monday, 7 = Sunday
        return $day_of_week >= 1 && $day_of_week <= 5; // Monday to Friday
    }

    // Loop through the dates until an available one is found with less than 20 appointments
    $appointment_date = $start_date;
    while (true) {
        // Check if the date is a valid appointment day (Monday to Friday)
        if (isValidAppointmentDate($appointment_date)) {
            // Query to count the number of appointments for this date
            $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
            $stmt->bind_param("s", $appointment_date);
            $stmt->execute();
            $stmt->bind_result($appointment_count);
            $stmt->fetch();
            $stmt->close();

            // If the number of appointments is less than 20, assign this date
            if ($appointment_count < 20) {
                // Insert the appointment for the user
                $stmt = $conn->prepare("INSERT INTO appointments (researcher_title_id, appointment_date) VALUES (?, ?)");
                $stmt->bind_param("is", $researcher_title_id, $appointment_date);
                $stmt->execute();
                $stmt->close();
                
                // Appointment successfully assigned
                break;
            }
        }
        
        // Move to the next day
        $appointment_date = date('Y-m-d', strtotime($appointment_date . ' +1 day'));
    }

    // Send appointment confirmation email
    $mail = new PHPMailer(true);
    try {
        // Server settings (ensure this is set up correctly)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Replace with your email host
        $mail->SMTPAuth = true;
        $mail->Username = ''; // Replace with your email
        $mail->Password = ''; // Replace with your email password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('westkiria@gmail.com', 'West Kiria');
        $mail->addAddress($email); // Add the recipient's email

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Appointment Confirmation';
        $mail->Body    = "Your appointment has been scheduled for <strong>$appointment_date</strong>.";
        
        $mail->send();
    } catch (Exception $e) {
        echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

$success = true; // Set success variable to true
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Research Files</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    

    <style>
        .other-documents-container {
            display: flex;
            align-items: center;
        }
        .other-documents-container input[type="file"] {
            margin-right: 10px;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
          /* Add any additional styles for your header, footer, and navbar here */
          .header {
            background-color: #800000;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-content {
            display: flex;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            margin-right: 20px;
        }
        .navbar {
            display: flex;
            gap: 10px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 10px;
            transition: color 0.3s;
        }
        .navbar a:hover {
            color: #dc3545;
        }
        .logout-button {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 20px;
            transition: background-color 0.3s;
        }
        .logout-button:hover {
            background-color: #c82333;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .footer {
            background-color: #800000;
            color: white;
            text-align: center;
            padding: 10px;
        }
        .titles-container {
    position: fixed; /* Keep it fixed on the screen */
    top: 20px;
    right: 20px;
    background-color: rgba(0, 0, 0, 0.5); /* Optional: semi-transparent background */
    color: white;
    padding: 10px;
    border-radius: 5px;
    max-width: 300px; /* Optional: limits the width */
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.2); /* Optional: adds a shadow */
    z-index: 10; /* Ensures it appears on top of other elements */
    overflow-y: auto; /* Ensure the list doesn't overflow the container */
}

.titles-container ul {
    list-style-type: none;
    padding-left: 0;
    margin: 0;
}

.titles-container li {
    margin-bottom: 5px;
}
    </style>
    <script>
        function addOtherFile() {
            const container = document.getElementById('other-files-container');
            const div = document.createElement('div');
            div.innerHTML = '<input type="file" name="other_files[]" required>';
            container.appendChild(div);
        }
        // Adds fields for co-researchers (same structure as main researcher)
        function addCoResearcher() {
            const container = document.getElementById('co-researcher-container');
            const div = document.createElement('div');
            div.innerHTML = `
                <label>Co-researcher Name</label>
                <input type="text" name="researcher_first_name[]" placeholder="First Name" required>
                <input type="text" name="researcher_last_name[]" placeholder="Last Name" required>
                <input type="text" name="researcher_middle_initial[]" placeholder="M.I." maxlength="2">
                <input type="text" name="researcher_suffix[]" placeholder="Suffix (optional)"><br>
            `;
            container.appendChild(div);
        }
        function toggleOtherInput(selectElement, otherInputId) {
    var otherInput = document.getElementById(otherInputId);
    if (selectElement.value === 'Other') {
        otherInput.style.display = 'inline';
    } else {
        otherInput.style.display = 'none';
        otherInput.value = ''; // Reset the input field when not used
    }

    // Call handleCollegeChange if the College dropdown is involved
    if (selectElement.id === 'college_dropdown') {
        handleCollegeChange();
    }
}

function handleCollegeChange() {
    const collegeDropdown = document.getElementById("college_dropdown");
    const researchCategoryDropdown = document.getElementById("research_category_dropdown");
    const hiddenResearchCategory = document.getElementById("hidden_research_category");

    if (collegeDropdown.value === "Other") {
        // Set Research Category to "Externally Funded Research / Other Institution - 3,000.00"
        researchCategoryDropdown.value = "Externally Funded Research / Other Institution - 3,000.00";
        // Disable the dropdown to prevent changes
        researchCategoryDropdown.disabled = true;

        // Set the value of the hidden input
        hiddenResearchCategory.value = "Externally Funded Research / Other Institution - 3,000.00";
    } else {
        // Re-enable the Research Category dropdown if College is not "Other"
        researchCategoryDropdown.disabled = false;

        // Reset hidden input value if necessary
        hiddenResearchCategory.value = '';
    }
}

function toggleAdviserInput() {
    var categoryDropdown = document.getElementById('research_category_dropdown');
    var adviserInput = document.getElementById('adviser_name');
    var selectedValue = categoryDropdown.value;

    // Check if the selected value is one of the required categories
    if (selectedValue === "WMSU Undergraduate Thesis - 300.00" || 
        selectedValue === "WMSU Master's Thesis - 700.00" || 
        selectedValue === "WMSU Dissertation - 1,500.00") {
        adviserInput.setAttribute('required', 'required');
    } else {
        adviserInput.removeAttribute('required');
    }
}

// Call toggleAdviserInput on page load in case there is a default selected value
document.addEventListener("DOMContentLoaded", function() {
    toggleAdviserInput();
});
    </script>
</head>
<body>


    <!-- Header Section -->
<div class="header">
    <div class="header-content">
        <h1>Research Ethics Oversight Committee Portal</h1>
        <div class="navbar">
            <a href="researcherHome.php">Home</a>
            <a href="SubmitFiles.php">Submit Paper</a>
            <a href="downloadables.php">Downloadables</a>
            <a href="Account.php">Account</a>
        </div>
    </div>
    <!-- Logout Button -->
    <form method="POST" action="downloadables.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button type="submit" name="logout" class="logout-button">Logout</button>
    </form>
</div>


</form>
<div class="main-content">
    <h2>Submit Research Ethics Review Documents</h2>

   
    <div class="titles-container">
        <h3>Your Submitted Study Protocol Titles</h3>
        <ul>
            <?php
            // Check if there are titles and display them
            if ($title_result->num_rows > 0) {
                while ($row = $title_result->fetch_assoc()) {
                    echo '<li>' . htmlspecialchars($row['study_protocol_title']) . '</li>';
                }
            } else {
                echo '<li>No titles found.</li>';
            }
            ?>
        </ul>
    </div>
</div>
        
    <form method="POST" enctype="multipart/form-data">
    <ul>
    <li> Application Form for Research Ethics Review - WMSU-REOC-FR-001 (with researcher/s signature in pdf file)
        <input type="file" name="application_form" accept=".pdf" required>
    </li>
    <li> Research Protocol/Proposal (with page and line number in pdf file)
        <input type="file" name="research_protocol" accept=".pdf" required>
    </li>
    <li> Technical Review Clearance - (pdf file)
        <input type="file" name="technical_review" accept=".pdf" required>
    </li>
    <li> Data collection instrument/s (with page and line number in pdf file)
        <input type="file" name="data_instruments" accept=".pdf" required>
    </li>
    <li> Informed Consent/Assent (with page and line number in pdf file)
        <input type="file" name="informed_consent" accept=".pdf" required>
    </li>
    <li> Curriculum Vitae of Researcher/s (pdf file)
        <input type="file" name="cv" accept=".pdf" required>
    </li>
    <li> Completed Study Protocol Assessment Form - WMSU-REOC-FR-004 (fill up the required details with asterisks in word file)
        <input type="file" name="study_protocol_form" accept=".doc,.docx" required>
    </li>
    <li> Completed Informed Consent Assessment Form - WMSU-REOC-FR-005 (fill up the required details with asterisks in word file)
        <input type="file" name="informed_consent_form" accept=".doc,.docx" required>
    </li>
    <li> Completed Exempt Review Assessment Form - WMSU-REOC-FR-006 (fill up the required details with asterisks in word file)
        <input type="file" name="exempt_review_form" accept=".doc,.docx" required>
    </li>
    
    <li class="other-documents-container">Other documents (NCIP Clearance, MOA, MOU, etc. in pdf file)
    <div id="other-files-container">
        <input type="file" name="other_files[]" accept=".pdf">
        <button type="button" onclick="addOtherFile()">Add More</button>

    </ul>
    <label>Study Protocol Title</label>
        <input type="text" name="study_protocol_title" required><br>

        <label>Researcher Name</label>
        <input type="text" name="researcher_first_name[]" placeholder="First Name" required>
        <input type="text" name="researcher_last_name[]" placeholder="Last Name" required>
        <input type="text" name="researcher_middle_initial[]" placeholder="M.I." maxlength="2">
        <input type="text" name="researcher_suffix[]" placeholder="Suffix (optional)"><br>

        <label>Co-researchers</label>
        <div id="co-researcher-container"></div>
        <button type="button" onclick="addCoResearcher()">Add Co-researcher</button><br>

        <div class="wrap-input1001 validate-input m-b-26" data-validate="Research Category is required"></div>
           <span class="label-input100">College</span>
             <span class="choice">
               <select class="input1001" name="college_dropdown" id="college_dropdown" onchange="toggleOtherInput(this, 'other_college_input'); handleCollegeChange()" required>
                          <?php
                       // Fetch each row and create an option for each college
                       while ($row = mysqli_fetch_assoc($result)) {
                       echo '<option value="' . htmlspecialchars($row['college_name_and_color']) . '">' . htmlspecialchars($row['college_name_and_color']) . '</option>';
                       }
                         ?>
                   <option value="Other">Other (Please specify)</option>
              </select>
                   <input type="text" id="other_college_input" name="other_college" placeholder="Specify Other College" style="display:none;"><br>
            </span>
        </div>

        <label>Research Category and Fee</label>
<select name="research_category_dropdown" id="research_category_dropdown" onchange="toggleOtherInput(this, 'other_category_input'); toggleAdviserInput()" required>
    <option value="WMSU Undergraduate Thesis - 300.00">WMSU Undergraduate Thesis - 300.00</option>
    <option value="WMSU Master's Thesis - 700.00">WMSU Master's Thesis - 700.00</option>
    <option value="WMSU Dissertation - 1,500.00">WMSU Dissertation - 1,500.00</option>
    <option value="WMSU Institutionally Funded Research - 2,000.00">WMSU Institutionally Funded Research - 2,000.00</option>
    <option value="Externally Funded Research / Other Institution - 3,000.00">Externally Funded Research / Other Institution - 3,000.00</option>
</select>
<!-- Hidden input to store the selected research category value -->
<input type="hidden" name="hidden_research_category" id="hidden_research_category">

<input type="text" id="other_category_input" name="other_category" placeholder="Specify Other Category" style="display:none;"><br>

<label>Name of Adviser</label>
<input type="text" id="adviser_name" name="adviser_name"><br>
    </div>
    </li>
        <button type="submit">Submit</button>
    </form>
    </div>
  
 
    <script>
        
    // Check if the PHP success variable is true
    var success = <?php echo json_encode($success); ?>; // Encode the PHP variable to JavaScript
    if (success) {
    Swal.fire({
        title: 'Success!',
        text: 'Files uploaded successfully. You will be redirected to the homepage shortly. Please check your email for confirmation.',
        icon: 'success',
        timer: 3000, // Auto close after 3 seconds
        showConfirmButton: false
    }).then(() => {
        window.location.href = 'researcherHome.php'; // Redirect after the alert
    });
}
</script>
<div class="footer">
    <p>Research Ethics Compliance Portal © 2024</p>
</div>
</body>
</html>
