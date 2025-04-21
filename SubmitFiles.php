<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
// Fetch application status from the database
$query = "SELECT status FROM application_status WHERE id = 1";  // Assuming status is stored in row with id 1
$result = mysqli_query($conn, $query);

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

$row = mysqli_fetch_assoc($result);
$application_status = $row['status']; // Get the status (open or closed)
// Fetch all colleges from the database
$query = "SELECT college_name_and_color FROM colleges";
$result = mysqli_query($conn, $query);

// Check if the query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}
// Get user_id from session
$user_id = $_SESSION['user_id'];  // Get the current user's ID



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
    $study_protocol_title = htmlspecialchars($_POST['study_protocol_title'] ?? '', ENT_QUOTES, 'UTF-8');
$adviser_name = htmlspecialchars($_POST['adviser_name'] ?? '', ENT_QUOTES, 'UTF-8');
$college_dropdown = htmlspecialchars($_POST['college_dropdown'] ?? '', ENT_QUOTES, 'UTF-8');
$other_college = htmlspecialchars($_POST['other_college'] ?? '', ENT_QUOTES, 'UTF-8');
$research_category_dropdown = htmlspecialchars($_POST['research_category_dropdown'] ?? '', ENT_QUOTES, 'UTF-8');
$other_category = htmlspecialchars($_POST['other_category'] ?? '', ENT_QUOTES, 'UTF-8');
$hidden_research_category = htmlspecialchars($_POST['hidden_research_category'] ?? '', ENT_QUOTES, 'UTF-8');


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
    $stmt = $conn->prepare("INSERT INTO researcher_title_informations (user_id, study_protocol_title, college, research_category, adviser_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $study_protocol_title, $college, $research_category, $adviser_name);
    $stmt->execute();

    // Get the ID of the inserted researcher title information
    $researcher_title_id = $conn->insert_id;

    // Handle insertion of researcher names (including co-researchers)
    $researcher_first_names = $_POST['researcher_first_name'];
    $researcher_last_names = $_POST['researcher_last_name'];
    $researcher_middle_initials = $_POST['researcher_middle_initial'];
  

    for ($i = 0; $i < count($researcher_first_names); $i++) {
       $first_name = htmlspecialchars($researcher_first_names[$i] ?? '', ENT_QUOTES, 'UTF-8');
$last_name = htmlspecialchars($researcher_last_names[$i] ?? '', ENT_QUOTES, 'UTF-8');
$middle_initial = !empty($researcher_middle_initials[$i]) 
    ? htmlspecialchars($researcher_middle_initials[$i], ENT_QUOTES, 'UTF-8') 
    : null;

    

        // Insert into Researcher_involved
        $stmt = $conn->prepare("INSERT INTO researcher_involved (researcher_title_id, first_name, last_name, middle_initial ) VALUES (?, ?, ?, ? )");
        $stmt->bind_param("isss", $researcher_title_id, $first_name, $last_name, $middle_initial, );
        $stmt->execute();
    }

    // Handle file uploads
    $upload_dir = 'uploads/';  // Directory where files will be saved
    $files = ['research_study', 'application_form', 'research_protocol', 'data_instruments', 'informed_consent', 'cv', 'study_protocol_form', 'informed_consent_form', 'exempt_review_form'];

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
    // Get the current date and add 5 days to set the minimum appointment date
$start_date = date('Y-m-d', strtotime('+5 days'));

// Function to check if the date is valid (Monday to Friday)
function isValidAppointmentDate($date) {
    $day_of_week = date('N', strtotime($date)); // 1 = Monday, 7 = Sunday
    return $day_of_week >= 1 && $day_of_week <= 5; // Monday to Friday
}

// Loop through the dates until an available one is found with less than appointment_capacity
$appointment_date = $start_date;
while (true) {
    // Fetch the appointment capacity from the reoc_dynamic_data table
    $capacity_query = "SELECT appointment_capacity FROM reoc_dynamic_data LIMIT 1";
    $capacity_result = $conn->query($capacity_query);

    // Check if the query was successful and retrieve the appointment capacity
    if ($capacity_result && $row = $capacity_result->fetch_assoc()) {
        $appointment_capacity = (int)$row['appointment_capacity'];
    } else {
        $appointment_capacity = 20; // Fallback to 20 if the query fails
    }

    // Check if the date is a valid appointment day (Monday to Friday)
    if (isValidAppointmentDate($appointment_date)) {
        // Check if the appointment date is unavailable (exists in notavail_appointment table)
        $unavailable_query = "SELECT DISTINCT unavailable_date FROM notavail_appointment WHERE unavailable_date = ?";
        $stmt = $conn->prepare($unavailable_query);
        $stmt->bind_param("s", $appointment_date);
        $stmt->execute();
        $stmt->bind_result($unavailable_count);
        $stmt->fetch();
        $stmt->close();

        // If the date is unavailable, skip it and move to the next day
        if ($unavailable_count > 0) {
            $appointment_date = date('Y-m-d', strtotime($appointment_date . ' +1 day'));
            continue;
        }

        // Query to count the number of appointments for this date
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
        $stmt->bind_param("s", $appointment_date);
        $stmt->execute();
        $stmt->bind_result($appointment_count);
        $stmt->fetch();
        $stmt->close();

        // If the number of appointments is less than appointment_capacity, assign this date
        if ($appointment_count < $appointment_capacity) {
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
	<title>Application Form</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">
	<link rel="stylesheet" type="text/css" href="./css/login1.css">
	<link rel="stylesheet" type="text/css" href="./css/login2.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
</head>
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
            background-color: #aa3636;
            color: white;
            font-size:16px;
            padding: 5px 9px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-left: 20px; /* Space between navbar and logout button */
            transition: background-color 0.3s;
        }
        .logout-button:hover {
            background-color: #c82333;
        }

        .main-content {
            flex: 1;
            padding: 20px;
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




















.limiter {
    width: 100%;
    margin: 0 auto;
  }
  
  .container-login100 {
    width: 100%;  
    min-height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    padding: 15px;
    background: #ebeeef;
  }


  .container-login1001 {
    width: 100%;  
    min-height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    padding: 15px;
    background: linear-gradient(rgba(255, 255, 255, 0.5), rgba(187, 52, 52, 0.5)), 
                url('img/REOCBG.jpg') no-repeat center center;
    background-size: cover; /* Ensures the image covers the entire viewport */
   

  }
  
  .footer{
    background:rgba(6, 145, 192, 0);
  }
  
  
  .wrap-login100 {
    width: 670px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }



  .wrap-login1001 {
    width: 1000px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }
  
  
  /*==================================================================
  [ Title form ]*/
  .login100-form-title {
    width: 100%;
    position: relative;
    z-index: 1;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    flex-direction: column;
    align-items: center;
  
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
  
    padding: 70px 15px 74px 15px;
  }
  
  .login100-form-title-1 {
    font-family: Poppins-Bold;
    font-size: 30px;
    color: #fff;
    text-transform: uppercase;
    line-height: 1.2;
    text-align: center;
  }
  
  .login100-form-title::before {
    content: "";
    display: block;
    position: absolute;
    z-index: -1;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(190, 41, 41, 0.562), rgba(51, 39, 39, 0.712));
  }




    /*==================================================================
  [ Title form1 ]*/
  .login100-form1-title {
    width: 100%;
    position: relative;
    z-index: 1;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    flex-direction: column;
    align-items: center;
  
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
  
    padding: 70px 15px 74px 15px;
  }
  
  .login100-form1-title-1 {
    font-family: Poppins-Bold;
    font-size: 30px;
    color: #fff;
    text-transform: uppercase;
    line-height: 1.2;
    text-align: center;
  }
  
  .login100-form1-title::before {
    content: "";
    display: block;
    position: absolute;
    z-index: -1;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(190, 41, 41, 0.562), rgba(51, 39, 39, 0.712));
  }
  
  
  
  /*==================================================================
  [ Form ]*/
  
  .login100-form {
    width: 100%;

    top: 20px;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 43px 88px 93px 190px;
  }

  .login100-form1 {
    width: 100%;
    position: relative;
margin-left:55px;
    margin-top: 50px;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 20px 88px 93px 55px;
  }


  
  
  
  /*------------------------------------------------------------------
  [ Input ]*/
  
  .wrap-input100 {
    top: 20px;
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }

  .wrap-input200 {
    top: 20px;
    width: 100%;
    position: relative;
    border-bottom: 1px solid #0a8b5a00;
  }



  .wrap-input1001 {
    top: 20px;
    width: 100%;

    position: relative;
    border-radius: 5px;
  }


  .wrap-input100SN {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }



  .wrap-input100FN {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }


.opt{
  color: #660707;
}

  .wrap-input100MI {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }
  
  .label-input100 {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #000000;
    line-height: 1.2;
    text-align: left;
  
    position: absolute;
    top: 14px;
    left: -105px;
    width: 80px;
  
  }
  



  .label-input200 {
    font-family: Poppins-Regular;
    font-size: 13px;
    color: #000000;
    line-height: 1.2;
    text-align: left;
  
    position: absolute;
  
    left: -105px;
    width: 270px;
  
  }





  /*---------------------------------------------*/
  .input100 {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }


  .input200 {
    position: relative;
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    left: 180px;
    background: transparent;
    padding: 0 5px;
  }



.input1001 {
  position: relative;
  top: -7px;
  padding: 10px;
  border-radius: 5px;
  border: 1px solid #ccc;
  width: 100%;

  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  font-size: 16px;
  color: #333;

}


select.input1001 option:hover {
  background-color: #ff0000; 
  color: red; 
}


select.input1001 option:checked {
  background-color: #a83939; 
  color: rgb(255, 255, 255); 
}

.input1001 {
  background: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="gray"><path d="M7 10l5 5 5-5H7z"/></svg>') no-repeat right;
  background-size: 16px;
  height: 50px;
}




.login100-form-btn:hover {
  background-color: #a30707;
}


.login100-form-btn2:hover {
  background-color: #a30707;
}

.login100-form-btn1:hover {
  background-color: #a30707;
}


.login100-form1-btn:hover {
  background-color: #a30707;
}


.login100-form1-btn2:hover {
  background-color: #a30707;
}

.login100-form1-btn1:hover {
  background-color: #a30707;
}






  .inputsign {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }



  .inputsignSN {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }




  .name-fields {
    display: flex;
    justify-content: space-between;
}

.name-fields .wrap-input100 {
    width: 100%; 
}


.name-fields .wrap-input200 {
  width: 100%; 
}






.name-fields .wrap-input100SN {
    width: 35%; 
}

.name-fields .wrap-input100FN {
    width: 45%; 
}



.name-fields .wrap-input100MI {
    width: 8%; 
    height: 5%;
}

  
  .focus-input100 {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }





  .focus-input200 {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }







  .focus-input100FN {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  
  .focus-input100SN {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  
  .focus-input100MI {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  





  .focus-input100::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  
  

  .focus-input200::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  
  

  .focus-input100FN::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  

  .focus-input100SN::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  

  .focus-input100MI::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  


  /*---------------------------------------------*/
  input.input100 {
    height: 45px;
  }



  input.input200 {
    height: 45px;
  }




  input.inputsign {
    height: 45px;
  }
  
  
  input.inputsignSN {
    height: 45px;
  }
  


  input.inputsignFN {
    height: 45px;
  }
  
  input.inputsignMI {
    height: 45px;
  }
  

  
  .input100:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.input100 + .focus-input100::before {
    width: 100%;
  }
  

  .input100FN:focus + .focus-input100FN::before {
    width: 100%;
  }
  
  .has-val.input100FN + .focus-input100FN::before {
    width: 100%;
  }
  



  .input200:focus + .focus-input200::before {
    width: 100%;
  }
  
  .has-val.input200 + .focus-input200::before {
    width: 100%;
  }
  
















  .input100SN:focus + .focus-input100SN::before {
    width: 100%;
  }
  
  .has-val.input100SN + .focus-input100SN::before {
    width: 100%;
  }
  

  .input100MI:focus + .focus-input100MI::before {
    width: 100%;
  }
  
  .has-val.input100MI + .focus-input100MI::before {
    width: 100%;
  }
  








  .inputsign:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.inputsign + .focus-input100::before {
    width: 100%;
  }




  .inputsign:focus + .focus-input200::before {
    width: 100%;
  }
  
  .has-val.inputsign + .focus-input200::before {
    width: 100%;
  }












  .inputsignSN:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.inputsignSN + .focus-input100::before {
    width: 100%;
  }











  /*==================================================================
  [ Restyle Checkbox ]*/
  
  .input-checkbox100 {
    display: none;
  }
  
  .label-checkbox100 {
    font-family: Poppins-Regular;
    font-size: 13px;
    color: #999999;
    line-height: 1.4;
  
    display: block;
    position: relative;
    padding-left: 26px;
    cursor: pointer;
  }
  
  .label-checkbox100::before {
    content: "\f00c";
    font-family: FontAwesome;
    font-size: 13px;
    color: transparent;
  
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 2px;
    background: #fff;
    border: 1px solid #e6e6e6;
    left: 0;
    top: 50%;
    -webkit-transform: translateY(-50%);
    -moz-transform: translateY(-50%);
    -ms-transform: translateY(-50%);
    -o-transform: translateY(-50%);
    transform: translateY(-50%);
  }
  
  .input-checkbox100:checked + .label-checkbox100::before {
    color: #57b846;
  }
  
  /*------------------------------------------------------------------
  [ Button ]*/
  .container-login100-form-btn {
    
    position: relative;
    margin-left: 350px;
    margin-top: 100px;
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }




  .container-login100-form-btn2 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }

  .container-login1001-form-btn {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }




  .container-login1001-form-btn2 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }





  
  .login100-form-btn {
     position: relative;
right: 20px;
    display: flex;
    gap: 10px;
    justify-content: center;
    align-items: center;
    padding: 20 20px;
    min-width: 160px;
    height: 50px;
    background-color: #751111;
    border-radius: 25px;
  
    font-family: Poppins-Regular;
    font-size: 16px;
    color: #fff;
    line-height: 1.2;
  
    -webkit-transition: all 0.4s;
    -o-transition: all 0.4s;
    -moz-transition: all 0.4s;
    transition: all 0.4s;
  }



  .login100-form-btn {
    position: relative;
   top:-20px;
   right: 222px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20px 20px;
   min-width: 160px;
   height: 50px;
   background-color: #751111;
   border-radius: 25px;
 
   font-family: Poppins-Regular;
   font-size: 16px;
   color: #fff;
   line-height: 1.2;
 
   -webkit-transition: all 0.4s;
   -o-transition: all 0.4s;
   -moz-transition: all 0.4s;
   transition: all 0.4s;
 }
 
  
  .login100-form-btn:hover {
    background-color: #a30707;
  }


  .login100-form1-btn:hover {
    background-color: #a30707;
  }




  
  .login100-form-btn2 {
    position: relative;
left:65px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20 20px;
   min-width: 160px;
   height: 50px;
   background-color: #751111;
   border-radius: 25px;
 
   font-family: Poppins-Regular;
   font-size: 16px;
   color: #fff;
   line-height: 1.2;
 
   -webkit-transition: all 0.4s;
   -o-transition: all 0.4s;
   -moz-transition: all 0.4s;
   transition: all 0.4s;
 }
 
 

 .login100-form1-btn2 {
  position: relative;
left:65px;
 display: flex;
 gap: 10px;
 justify-content: center;
 align-items: center;
 padding: 20 20px;
 min-width: 160px;
 height: 50px;
 background-color: #751111;
 border-radius: 25px;

 font-family: Poppins-Regular;
 font-size: 16px;
 color: #fff;
 line-height: 1.2;

 -webkit-transition: all 0.4s;
 -o-transition: all 0.4s;
 -moz-transition: all 0.4s;
 transition: all 0.4s;
}










  .container-login100-form-btn1 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }
  

  .container-login1001-form-btn1 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }
  



  .login100-form-btn1 {
    position: relative;
  margin-left: 250px;
  margin-top: 90px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20px;
   min-width: 160px;
   height: 50px;
   background-color: #751111;
   border-radius: 25px;
 
   font-family: Poppins-Regular;
   font-size: 16px;
   color: #fff;
   line-height: 1.2;
 
   -webkit-transition: all 0.4s;
   -o-transition: all 0.4s;
   -moz-transition: all 0.4s;
   transition: all 0.4s;
 }

 .login100-form-btn1 {
  position: relative;
margin-left: 250px;
margin-top: 90px;
 display: flex;
 gap: 10px;
 justify-content: center;
 align-items: center;
 padding: 20px;
 min-width: 160px;
 height: 50px;
 background-color: #751111;
 border-radius: 25px;

 font-family: Poppins-Regular;
 font-size: 16px;
 color: #fff;
 line-height: 1.2;

 -webkit-transition: all 0.4s;
 -o-transition: all 0.4s;
 -moz-transition: all 0.4s;
 transition: all 0.4s;
}



   
 
  
  .login100-form-btn1:hover {
    background-color: #a30707;
  }
  
  .login100-form-btn2:hover {
    background-color: #a30707;
  }


    
  .login100-form1-btn1:hover {
    background-color: #a30707;
  }
  
  .login100-form1-btn2:hover {
    background-color: #a30707;
  }
  
  
.move{
    position: relative;
    left: 100px;
}



.addbtn{
    position: relative;
    left: 320px;
    padding: 6px 6px ;
            font-size: 11px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
         margin-top: 20px;

}



.addbtn:hover{
    background-color: #802c2c;
}




.cobtn{
  position: relative;

    padding: 6px 6px ;
            font-size: 11px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
         margin-top: 20px;
}


.cobtn:hover{
  background-color: #802c2c;
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
               
                <input type="text" name="researcher_first_name[]" placeholder="First Name" required>
                <input type="text" name="researcher_last_name[]" placeholder="Last Name" required>
                <input type="text" name="researcher_middle_initial[]" placeholder="M.I." maxlength="2">
             
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
    <header>
  <a href="#" class="brand">
    <img src="img/logos.png" class="logo">
    <span class="reoc">Research Ethics Oversite Committee Portal</span>
  </a>

  <div class="menu-btn">
    <div class="navigation">
      <div class="navigation-items">
        <a href="researcherHome.php"><strong>Home</strong></a>
        <div class="dropdown1">
          <a href="#"><strong>Applications</strong></a>
          <div class="dropdown-content1">
            <div class="file-item1">
              <a href="SubmitFiles.php"><strong>Submit Application</strong></a>
            </div>
            <div class="file-item1">
              <a href="viewApplications.php"><strong>View Applications</strong></a>
            </div>
          </div>
        </div>

        <div class="dropdown">
          <a href="#"><strong>Downloadables</strong></a>
          <div class="dropdown-content">
            <div class="file-item">
              <span><strong>Application Form (WMSU-REOC-FR-001)</strong></span>
              <a href="./files/2-FR.002-Application-Form.doc" download>Download</a>
            </div>
            <div class="file-item">
              <span><strong>Study Protocol Assessment Form (WMSU-REOC-FR-004)</strong></span>
              <a href="./files/4-FR.004-Study-Protocol-Assessment-Form-Copy.docx" download>Download</a>
            </div>
            <div class="file-item">
              <span><strong>Informed Consent Assessment Form (WMSU-REOC-FR-005)</strong></span>
              <a href="./files/5-FR.005-Informed-Consent-Assessment-Form (1).docx" download>Download</a>
            </div>
            <div class="file-item">
              <span><strong>Exempt Review Assessment Form (WMSU-REOC-FR-006)</strong></span>
              <a href="./files/6-FR.006-EXEMPT-REVIEW-ASSESSMENT-FORM (1).docx" download>Download</a>
            </div>
          </div>
        </div>

        <a href="./instructions.html"><strong>Instructions</strong></a>


        <!-- Logout Button -->
        <form method="POST" action="researcherHome.php" style="display: inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>
  
</div>

</form>


<div class="limiter">
		<div class="container-login1001">
			<div class="wrap-login1001">
				<div class="login100-form1-title" style="background-image: url(./img/wmsu5.jpg);">
					<span class="login100-form1-title-1">
						reoc-wmsu portal
					</span>
					<h4 class="sign">APPLICATION FORM </h4>
				</div>
	
        <?php if ($application_status === 'open'): ?>
    <form method="POST" enctype="multipart/form-data" class="login100-form1 validate-form">
    <div id="pageContent" class="page">

    <h3>Research Information</h3>
 <div class="wrap-input100 validate-input m-b-26" data-validate="Title is required">
        <span class="label-input100">Study Protocol Title</span>
        <input class="input100" type="text" name="study_protocol_title" required placeholder="Enter Title">
        <span class="focus-input100"></span>
	</div>
   
    <div class="wrap-input1001 validate-input m-b-26" data-validate="Research Category is required">
<span class="label-input100">Research Category & Fees</span>
<span class="choice">
<select class="input1001" name="research_category_dropdown" id="research_category_dropdown" onchange="toggleOtherInput(this, 'other_category_input'); toggleAdviserInput()" required>
    <option value="WMSU Undergraduate Thesis - 300.00">WMSU Undergraduate Thesis - 300.00</option>
    <option value="WMSU Master's Thesis - 700.00">WMSU Master's Thesis - 700.00</option>
    <option value="WMSU Dissertation - 1,500.00">WMSU Dissertation - 1,500.00</option>
    <option value="WMSU Institutionally Funded Research - 2,000.00">WMSU Institutionally Funded Research - 2,000.00</option>
    <option value="Externally Funded Research / Other Institution - 3,000.00">Externally Funded Research / Other Institution - 3,000.00</option>
</select>
</span>

                       <!-- Hidden input to store the selected research category value -->
                        <input type="hidden" name="hidden_research_category" id="hidden_research_category">
                        <input type="text" id="other_category_input" name="other_category" placeholder="Specify Other Category" style="display:none;"><br>

</div>


<br>
<br>
<br>
<h3>Researcher Information</h3>

<form class="login100-form validate-form">
<div class="name-fields">


<div class="wrap-input100SN validate-input m-b-18" data-validate="Required">
  <span class="label-input100">Full Name</span>
        <input class="input100" type="text" name="researcher_first_name[]" placeholder="First Name" required>
  <span class="focus-input100"></span>
</div>



<div class="wrap-input100FN validate-input m-b-18" data-validate="Required">
    <input class="input100" type="text" name="researcher_last_name[]" placeholder="Last Name" required>
    <span class="focus-input100"></span>
 </div>



 <div class="wrap-input100MI">
        <input class="input100" type="text" name="researcher_middle_initial[]" placeholder="M.I." maxlength="2">
        <span class="focus-input100"></span>
</div>













</div>





        
        <label>Co-researchers</label>
        <div id="co-researcher-container"></div>
        <button type="button" onclick="addCoResearcher()" class="cobtn">Add Co-researcher</button><br>







    <div class="wrap-input1001 validate-input m-b-26" data-validate="Research Category is required">
     <span class="label-input100">College</span>
        <span class="choice">
        <select class="input1001" name="college_dropdown" id="college_dropdown" onchange="toggleOtherInput(this, 'other_college_input'); handleCollegeChange()" required>

 
     </span>
    </div>

    <?php



    // Fetch each row and create an option for each college
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<option value="' . htmlspecialchars($row['college_name_and_color']) . '">' . htmlspecialchars($row['college_name_and_color']) . '</option>';
    }
    ?>
    <option value="Other">Other (Please specify)</option>
</select>

      <input   type="text" id="other_college_input" name="other_college" placeholder="Specify Other College" style="display:none;"><br>
     




<div class="wrap-input100 validate-input m-b-26" >
    <span class="label-input100">Name of Adviser</span>
    <input class="input100" type="text" id="adviser_name" name="adviser_name">
     <span class="focus-input100"></span>
</div>

 
<br>
<br>
<br>
<h3>Necessary Files</h3>
<br>
<br>
<h4 style="margin-left:-5px;">Upload the Soft Copy of the research here:</h4>
<div class="move">
<form class="login100-form validate-form">


<form class="login100-form validate-form">
           
                        <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                            <span class="label-input200"><strong>Research Study </strong>(Word file)</span>
                            <input class="input200" type="file" name="research_study" accept=".doc,.docx" required>
                        
                        </div>

                        <form class="login100-form validate-form">
                <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                    <span class="label-input200"><strong>Application Form For Research Ethics Review - WMSU-REOC-FR-001 </strong>(with researcher/s signature in pdf file)</span>
                    <input class="input200" type="file" name="application_form" accept=".pdf" required>
                  
                </div>
    
                <form class="login100-form validate-form">
            
                    <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                        <span class="label-input200"><strong>Research Protocol/Proposal </strong>(with page and line number in pdf file)</span>
                        <input class="input200" type="file"name="research_protocol" accept=".pdf" required>
                        
        
                    


                        <form class="login100-form validate-form">
            
                            <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                                <span class="label-input200"><strong>Data Collection Instruments </strong>(with page and line number in pdf file)</span>
                                <input class="input200" type="file" name="data_instruments" accept=".pdf" required>
                           
                            </div>





                            <form class="login100-form validate-form">
            
                                <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                                    <span class="label-input200"><strong>Informed Consent/Assent </strong>(with page and line number in pdf file)</span>
                                    <input class="input200" type="file" name="informed_consent" accept=".pdf" required>
                            
                                </div>

                                <form class="login100-form validate-form">
            
                                    <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                                        <span class="label-input200"><strong>Curriculum Vitae of Researcher/s</strong>(pdf file)</span>
                                        <input class="input200" type="file" name="cv" accept=".pdf" required>
                                   
                                    </div>


                                    <form class="login100-form validate-form">
            
                                        <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                                            <span class="label-input200"><strong>Completed Study Protocol Assessment Form - WMSU-REOC-FR-004 </strong>(fill up the required details with asterisk in the word file)</span>
                                            <input class="input200" type="file"name="study_protocol_form" accept=".doc,.docx" required>
                                        
                                        </div>


                                        <form class="login100-form validate-form">
            
                                            <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                                                <span class="label-input200"><strong>Completed Informed Consent Assessment Form - WMSU-REOC-FR-005 </strong>(fill up the required details with asterisk in the word file)</span>
                                                <input class="input200" type="file" name="informed_consent_form" accept=".doc,.docx" required>
                                              
                                            </div>



                                            <form class="login100-form validate-form">
            
                                                <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                                                    <span class="label-input200"><strong>Completed Exempt Review Assessment Form - WMSU-REOC-FR-006 </strong>(fill up the required details with asterisk in the word file)</span>
                                                    <input class="input200" type="file" name="exempt_review_form" accept=".doc,.docx" required>
                                                
                                                </div>
    
                                               

                            

                                    <form class="login100-form validate-form">
                                        <div class="wrap-input200 validate-input m-b-26" data-validate="Email Address is required">
                                        <span class="label-input200"><strong>Other documents (NCIP Clearance, MOA, MOU, etc. in pdf file)</strong>(with researcher/s signature in pdf file)</span>
    <div id="other-files-container">
        <input class="input200" type="file" name="other_files[]" accept=".pdf">
        <button  class="addbtn " type="button" onclick="addOtherFile()">Add More</button>

        </div>











            </form>

            <div class="container-login100-form-btn" style="display: flex; gap: 10px;">
                <button class="login100-form-btn" type="submit" id="prevButton">
                    Submit
                </button>
            
            </div>
            <?php else: ?>
    <!-- Message when application is closed -->
    <p>Application is currently closed due to processing of bulk documents. Please check back later.</p>
<?php endif; ?>
        </div>
    </div>
</div>



        </form>
    </div>
</div>
</div>




    </div>
</div>
</form>

</div>

</form>










    </div>
    </li>
   
    </form>
    </div>
 
    </div>
  
 </div>
 </div>
 </div>
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

<footer class="footer">
		<div class="owl-carousel">
	  
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu55.jpg" alt="" />
		 
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu11.jpg" alt="" />
		  
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/reoc11.jpg" alt="" />
		   
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu22.jpg" alt="" />
		  
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/reoc22.jpg" alt="" />
		   
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu44.jpg" alt="" />
		   
		  </a>
	  
		</div>
		<div class="footer__redes">
		  <ul class="footer__redes-wrapper">
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				Normal Road, Baliwasan, Z.C.
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				09112464566
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				wmsureoc@gmail.com
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class="fab fa-phone-alt"></i>
				
			  </a>
			</li>
		  </ul>
		</div>
		<div class="separador"></div>
		<p class="footer__texto">RESEARCH ETHICS OVERSITE COMMITTEE - WMSU</p>
	  </footer>
	

	  
   
  
  
  
  <!-- partial -->

  
	<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
	<script src='https://unpkg.com/feather-icons'></script>
	  <script src="./js/main.js"></script>
	  <script src="./js/swiper.js"></script>
	  <script src="./js/footer.js"></script>
	  <script src="./js/faq.js"></script>
	

	<script src="./js/fonts.js"></script>
  
  
</div>
</body>
</html>
