<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include('updateAppointments.php');
// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true);
}

// Check if the user is logged in and if their role is 'Admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Start CSRF token generation if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logout logic
if (isset($_POST['logout'])) {
    // Validate CSRF token
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        session_destroy();
        header("Location: login.php");
        exit();
    } else {
        echo "<script>alert('Invalid CSRF token.');</script>";
    }
}

// Database connection
require_once 'dbConnCode.php'; // Replace with your actual database connection file



$faculty_id = 1; // Replace with dynamic ID based on the schedule being edited

// Query to fetch the current picture for the faculty
$query = "SELECT `picture` FROM `faculty_members` WHERE `id` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($current_picture1);
$stmt->fetch();
$stmt->close();

$schedule_id = 1; // Replace with dynamic ID based on the schedule being edited

// Query to fetch the current picture for the schedule
$query = "SELECT `picture` FROM `schedule` WHERE `id` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $schedule_id); // Bind the schedule_id dynamically
$stmt->execute();
$stmt->bind_result($current_picture2); // Get the current picture filename
$stmt->fetch();
$stmt->close();

// Fetch available months dynamically based on uploaded_at
$query = "SELECT DISTINCT DATE_FORMAT(uploaded_at, '%Y-%m-01') AS month 
          FROM researcher_title_informations 
          ORDER BY month DESC";
$result = $conn->query($query);
$availableMonths = [];
while ($row = $result->fetch_assoc()) {
    $availableMonths[] = $row['month'];
}

// Handle month selection with validation for empty $availableMonths
if (!empty($availableMonths)) {
    // Set selectedMonth from the URL parameter or default to the first available month
    $selectedMonth = $_GET['month'] ?? $availableMonths[0];
} else {
    // Handle the case where no months are available
    $selectedMonth = null; // Or any other fallback logic you prefer
}

// Fetch data for the selected month
$collegeDataQuery = "
    SELECT college, COUNT(*) AS count 
    FROM researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    GROUP BY college";
$collegeDataResult = $conn->query($collegeDataQuery);

$collegeData = [];
while ($row = $collegeDataResult->fetch_assoc()) {
    $collegeData[] = [
        'college' => $row['college'],
        'count' => $row['count']
    ];
}

// Fetch data for Exempt research categories for the selected month
$exemptDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Exempt'
    GROUP BY research_category";
$exemptDataResult = $conn->query($exemptDataQuery);

$exemptData = [];
while ($row = $exemptDataResult->fetch_assoc()) {
    $exemptData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
}


// Fetch data for Expedited research categories for the selected month
$expeditedDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Expedited'
    GROUP BY research_category";
$expeditedDataResult = $conn->query($expeditedDataQuery);

$expeditedData = [];
while ($row = $expeditedDataResult->fetch_assoc()) {
    $expeditedData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
}

// Fetch data for Expedited research categories for the selected month
$expeditedDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Expedited'
    GROUP BY research_category";
$expeditedDataResult = $conn->query($expeditedDataQuery);

$expeditedData = [];
while ($row = $expeditedDataResult->fetch_assoc()) {
    $expeditedData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
}
// Fetch data for Full Review research categories for the selected month
$fullReviewDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Full Review'
    GROUP BY research_category";
$fullReviewDataResult = $conn->query($fullReviewDataQuery);

$fullReviewData = [];
while ($row = $fullReviewDataResult->fetch_assoc()) {
    $fullReviewData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
} 
// Query to get research categories and their counts
$query = "SELECT research_category, COUNT(*) as count FROM researcher_title_informations GROUP BY research_category";
$result = $conn->query($query);

// Initialize arrays for categories and counts
$researchCategories = [];
$researchCounts = [];

while ($row = $result->fetch_assoc()) {
    $researchCategories[] = $row['research_category'];
    $researchCounts[] = $row['count'];
}

// Convert PHP arrays to JSON for use in JavaScript
$researchCategories = json_encode($researchCategories);
$researchCounts = json_encode($researchCounts);

// Prepare data for the chart (Full Review category)
$fullReviewCategories = json_encode(array_column($fullReviewData, 'research_category'));
$fullReviewCounts = json_encode(array_column($fullReviewData, 'count'));
// Prepare data for the chart (Expedited category)
$expeditedCategories = json_encode(array_column($expeditedData, 'research_category'));
$expeditedCounts = json_encode(array_column($expeditedData, 'count'));
// Prepare data for the chart (Exempt category)
$exemptCategories = json_encode(array_column($exemptData, 'research_category'));
$exemptCounts = json_encode(array_column($exemptData, 'count'));
// Prepare data for the chart
$collegeNames = json_encode(array_column($collegeData, 'college'));
$collegeCounts = json_encode(array_column($collegeData, 'count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin-Analytics</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">
	<link rel="stylesheet" type="text/css" href="./css/piechart.css">
	<link rel="stylesheet" type="text/css" href="./css/admin-form.css" />
	<link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script defer src="./js/table.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    </head>
    <style>
        
       body {
  background-color:#f0f0f0; 
  position: relative; 
}



  
        .header-content {
            display: flex;
            align-items: center;
        }





        .filter-container {
            margin-bottom: 20px;
            text-align: center;
        }

        .manage-colleges {
            margin-bottom: 20px;
            text-align: center;
            
            
        }


        .chartss-container {
    display: flex;
    justify-content: space-around; /* Evenly distribute charts */
    gap: 40px; /* Space between the charts */
    flex-wrap: wrap; /* Wrap if screen is too small */
    align-items: flex-start; /* Align items at the top */
    background-color: #F8F7F4;
    border-radius: 20px;
    padding: 30px;
    margin: 30px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Add a subtle shadow */
}

.charts-container {
    display: flex;
    justify-content: space-around; /* Evenly distribute charts */
    gap: 40px; /* Space between the charts */
    flex-wrap: wrap; /* Wrap if screen is too small */
    align-items: flex-start; /* Align items at the top */
    background-color: #F8F7F4;
    border-radius: 20px;
    padding: 30px;
    margin: 30px;

}

.chart-item {
    flex: 1; /* Equal space for each chart */
    max-width: 300px; /* Ensure a consistent width */
    min-width: 250px; /* Minimum width to maintain layout */
    height: 400px; /* Fixed height for all charts */
    display: flex;
    flex-direction: column;
    align-items: center; /* Center content */
    text-align: center; /* Align chart titles centrally */
}

.chart-item h3 {
    margin-bottom: 10px; /* Add some spacing below the title */
}

.footer{
    background-color: #F8F7F4;
 
}
    
     
        h3 {
        text-align: left;  /* Center-align the text */
        color: #333;         /* Set text color */
        font-size: 24px;     /* Set font size */
        margin-bottom: 20px; /* Add some space below the header */
    }
     /* Print-specific styles */
     @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }

            .header, .footer {
                display: none; /* Hide header and footer for printing */
                visibility: hidden !important; /* Redundant but safe */
              
            }

        .header-content{
                display: none; /* Hide header and footer for printing */
                visibility: hidden !important; /* Redundant but safe */
            }

            .charts-container {
                display: block;
                width: 100%;
                
            }

            .chart-item {
                width: 100%;
                max-width: 500px;
                margin: 0 auto 20px;
                page-break-inside: avoid; /* Prevent charts from being split across pages */
            }

            .chart-item canvas {
                width: 100% !important; /* Ensure the canvas fits the page */
                height: auto !important;
            }

            .filter-container {
                display: none; /* Hide the filter dropdown for printing */
            }

            .manage-colleges {
                display: none; /* Hide Manage Colleges button for printing */
            }

        


.schedapp{
    display: none;
}

.button-container{
    display: none;
}


.button-container button {
    display: none;
}



.printbtn{
    display: none;
}

.card-boxes{
    display: none;
    visibility: hidden !important; /* Redundant but safe */
}


        }
    
    .cover {
        position: absolute;
        background-color: rgba(0, 255, 0, 0.5); /* Semi-transparent green */
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        pointer-events: none; /* Allow interaction with the input beneath */
        display: none; /* Initially hidden */
    }

    .wrapper {
        position: relative; /* To position the cover relative to the input */
        display: inline-block; /* Match the size of the input field */
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


/* Modal Styles */
.modal {
  display: none;
  position: fixed;
  
  left: 500px;
  width: 100%;
  height: 100%;

  justify-content: center;
  align-items: center;
  animation: fadeIn 0.5s ease-in-out;
}

.modal.show {
    top: 10%;
    left: 100px;
  display: flex; /* Display modal when 'show' class is added */
}
/* Modal Content */
dialog::backdrop {
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(4px); /* Blurs the background */
  -webkit-backdrop-filter: blur(4px); /* Safari compatibility */
}

dialog {
  border: none;
  position: relative;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  padding: 20px;
  top: 20%;
  left: 800px;

  height: 50%;
  background: white;
  justify-content: center;
  align-items: center;
  width: 400px;
  animation: slideDown 0.5s ease-in-out;
}

.modal-content img {
  object-fit: contain;
  max-width: 100%;
  max-height: 200px;
  margin-bottom: 15px;
}

/* Button Styles */
.action-button {
  background-color: #007bff;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.action-button:hover {
  background-color: #0056b3;
}

/* Slide-down Animation */
@keyframes slideDown {
  from {
    transform: translateY(-50%);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

	  
	.modal1 {
		display: none  ;
		position: fixed;
		top: 350%;
		left: 500px;
		width: 100%;
		height: 100%;
		
		display: flex;
		justify-content: center;
		align-items: center;

	  }
	  .modal-content1 {
		background: white;
		padding: 20px;
		border-radius: 8px;
		text-align: center;
		width: 500px;
		height: auto;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
	
	  }
	  .modal-content1 img {
		object-fit: contain;
		max-width: 100%;
		max-height: 200px;
		margin-bottom: 15px;
	  }
	  .btn1 {
		margin: 10px 5px;
		padding: 10px 20px;
		background: #007BFF;
		color: white;
		border: none;
		border-radius: 5px;
		cursor: pointer;
	  }

	  .modal2 {
		
		position: fixed;
        
		margin-top: 420px;
		margin-left: 510px;
		width: 100%;
		height: 1900%;
	
		display: flex;
		justify-content: center;
		align-items: center;
	  }
	  .modal-content2 {
		background: white;
		padding: 20px;
		border-radius: 8px;
		text-align: center;

		box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
	  }
	  .modal-content2 input {
		
		justify-content: center;
	grid-row: 4;

		width: 100%;
		height: 200px;
		padding: 10px;
		margin: 10px 0;
		border: 1px solid #ccc;
		border-radius: 5px;
	  }
	  .modal-content2 .btn {
		margin: 10px 5px;
		padding: 10px 20px;
		background:  #aa3636;
		color: white;
		border: none;
		border-radius: 5px;
		cursor: pointer;
		transition: background-color 0.3s;
	  }
	  .modal-content2 .btn:hover {
		background:  #802c2c;
	  }

	  textarea {
		width: 100%;
		height: 170px; /* Adjust height as required */
		resize: none;  /* Disable resizing if you want a fixed size */
		padding: 10px;
		font-family:Arial, Helvetica, sans-serif 
      }


		
      .button-container {
			position: fixed; /* Keeps the buttons fixed on the screen */
            top: 130px; /* Center vertically in the viewport */
            left: 200px; /* Aligns the buttons close to the left edge */
            transform: translateY(-50%); /* Centers the stack vertically */
            display: flex;
			
            flex-direction: row; /* Stacks the buttons vertically */
            gap: 20px; /* Adds spacing between buttons */
			z-index: 2;
        }

        .button-container button {
		
			
            padding: 10px 8px ;
            font-size: 13px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .button-container button:hover {
            background-color: #802c2c;
        }




		.printcont{
			position: fixed;
			top: 120px; 
			right: 200px; 
			z-index:1; 
		}

		.printbtn{
		 font-size: 16px; cursor: pointer;    background-color: #aa3636; /* Blue color */
          color: white; /* Text color */
          border: none; /* Remove border */
          border-radius: 10px; /* Rounded corners */
          padding: 5px 9px; /* Button size */
          font-size: 16px; /* Font size */
		  transition: background-color 0.3s;

	
	}

	.printbtn:hover {
		background-color: #802c2c;

	}
    .card-boxes {
  position: relative;
  display: grid;
  justify-content: center; /* Centers items within each column */
  align-items: center;     /* Centers items vertically within each row */
  width: 82%;
  margin: 20px auto 20px auto;          /* Centers the entire container horizontally */
  padding: 1rem 1.5rem;
  grid-template-columns: repeat(4, 1fr);
  grid-gap: 30px;
  top: -20px;
}



.schedapp{
    width: fit-content;
    padding: 10px;
    border-style: solid;
    border-radius: 10px;
    border-color:#aa3636;
    position: relative;
    z-index: 1;
    top: 100px;
    left: 200px;
}


.date1{
    padding: 10px 8px ;
            font-size: 11px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
         margin-top: 20px;

}



.date1:hover{
    background-color: #802c2c;
}


.vision2 {
  background-color:rgba(248, 247, 244, 0);
  position: relative;
  padding-top: 10px;
  padding-bottom: 50px;
  text-align: center; 
  margin-top:20px;
}


.dropdown {
    display: inline-block;
    position: relative;
}


.dropdown-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    background-color: white;
    border: 1px solid #ccc;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    border-radius: 4px;
    z-index: 1000;
}

.dropdown-menu .dropdown-item {
    padding: 10px 20px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dropdown-menu .dropdown-item:hover {
    background-color: #f0f0f0;
}





/* General styling */
.button-container button {
		
			
        padding: 10px 8px ;
        font-size: 13px;
        border: none;
        border-radius: 10px;
        background-color:  #aa3636;
        color: white;
        cursor: pointer;
        transition: background-color 0.3s;
    }
    
    .button-container button:hover {
        background-color: #802c2c;
    }
    
    
    
    .dropdown {
        display: inline-block;
        position: relative;
    }
    
    .dropdown-toggle {
        padding: 10px 20px;
        background-color:rgb(41, 138, 57);
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .hamburger-icon {
        font-size: 16px;
        display: inline-block;
    }
    
    .dropdown-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        background-color: white;
        border: 1px solid #ccc;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        border-radius: 4px;
        z-index: 1000;
    }
    
    .dropdown-menu li {
        padding: 0;
    }
    
    .dropdown-menu .dropdown-item {
        padding: 10px 20px;
        text-decoration: none;
        color: black;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
    }
    
    .dropdown-menu .dropdown-item:hover {
        background-color: #f0f0f0;
    }
    
    
    
    /* Show dropdown menu */
    .dropdown:hover .dropdown-menu {
        display: block;
    }
    
    .dot {
        height: 10px;         /* Adjust the size as needed */
        width: 10px;          /* Adjust the size as needed */
        background-color: green; /* Set the color to green */
        border-radius: 50%;   /* Make it circular */
        display: inline-block; /* Ensure it's inline */
      }
    

    </style>
</head>

    <!-- Header Section -->
    
<header>
  <a href="#" class="brand">
    <img src="img/logos.png" class="logo">
    <span class="reoc">Research Ethics Oversite Committee Portal</span>
  </a>

  <div class="menu-btn">
    <div class="navigation">
      <div class="navigation-items">
      <a href="adminHome.php">Home</a>
      <a href="admin_applicationforms.php">Application Forms</a>
      <a href="Account.php">Account Verifications</a>
      <a href="admin_activateSuperuser.php">Super Users</a>
      
   
   
       

        <!-- Logout Button -->
        <form method="POST" action="researcherHome.php" style="display: inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
      </div>
    </div>
  </div>
  </header>

  
        
 
<!-- Unavailable Dates -->
 <div class="background"> 
<form action="admin_mark_unavailable.php" method="POST" class="schedapp">
    <label for="dates"><strong>Select Unavailable Dates:</strong></label><br>
    <input type="text" name="dates" id="dates" class="datepicker" placeholder="Click to select dates"><br>
    <button class="date1" type="submit">Submit Unavailable Dates</button>
</form>


    <!-- Main Content -->

    <div class="button-container">
	
    <div class="dropdown">
        <button class="dropdown-toggle">
            <span class="hamburger-icon">☰</span> Switch View
        </button>
        <ul class="dropdown-menu">
            <li>
                <a href="adminHome.php" class="dropdown-item active" data-choice="view1">
                    Admin <span class="dot"></span>
                </a>
            </li>
            <li>
                <a href="admin-reviewerHome.php" class="dropdown-item" data-choice="view2">
                    Reviewer 
                </a>
            </li>
            <li>
                <a href="admin-researcherHome.php" class="dropdown-item" data-choice="view3">
                    Researcher 
                </a>
            </li>
        </ul>
    </div>


    <br>
<!-- Button to Open Modal -->
<button onclick="openFacultyModal()">Edit Faculty</button>

<!-- Faculty Modal -->
<dialog id="facultyModal">
  <div class="modal-content">
    <span class="close" onclick="closeFacultyModal()" style="cursor:pointer; margin-left:350px;">&times;</span>
    <h2>Edit Faculty Display</h2>
    <form id="facultyForm" action="edit_faculty.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="faculty_id" value="1">
      <div id="current-picture-container" style="margin-left:70px; ">
        <?php if ($current_picture1): ?>
          <img id="current-picture" src="Faculty Members/<?php echo $current_picture1; ?>" alt="Current Picture" style="width: 200px; height: auto;">
        <?php else: ?>
          <p>No picture available</p>
        <?php endif; ?>
      </div>
      <label for="faculty_picture">Upload New Picture:</label>
      <input type="file" name="faculty_picture" id="faculty_picture"><br>
      <div style="margin-top: 20px; margin-left:60px;">
        <button type="button" id="remove-picture" onclick="removePicture()" class="action-button">Remove Picture</button>
        <button type="submit" class="action-button">Save Changes</button>
      </div>
    </form>
  </div>
</dialog>


<!-- Edit Schedule Display Button -->
<button class="printbtn" onclick="openScheduleModal()">Edit Schedule</button>

<!-- Schedule Modal -->
<dialog id="scheduleModal"  >
    <div class="modal-content">
        <span class="close" onclick="closeScheduleModal()"   style="cursor:pointer; margin-left:350px;">&times;</span>
        <h2>Edit Schedule Display</h2>

        <!-- Form for uploading picture -->
        <form id="scheduleForm" action="edit_schedule.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="schedule_id" value="1"> <!-- Default ID for the schedule -->
            
            <!-- Current Picture -->
            <div id="current-picture-container">
                <?php if ($current_picture2): ?>
                    <img id="current-picture"  style="width:1000%; height: 1000%;"  src="Schedules/<?php echo $current_picture2; ?>" alt="Current Picture" style="width: 200px;">

                <?php else: ?>
                    <p>No picture available</p>
                <?php endif; ?>
            </div>
            
            <!-- New Picture Upload -->
            <label for="schedule_picture">Upload New Picture:</label>
            <input type="file" name="schedule_picture" id="schedule_picture"><br>
            
            <!-- Submit Button -->
            <button type="button" id="remove-picture" onclick=" removeSchedulePicture()" style="margin-top:20px; margin-left:60px;">Remove Picture</button>
            <button type="submit" class="action-button">Save Changes</button>
        </form>
    </div>
</dialog>

<button  type="button"  onclick="openVmForm()">Edit Vision and Mission</button>

<!-- Form to edit Vision, Mission, and Goals -->
 
<dialog id="vmForm" style="height:830px; width:600px; margin-left:-145px; margin-top:-100px;" >
<div class="modal-content">
<span class="close" onclick="closeVmForm()" style="cursor:pointer; margin-left:540px; " >&times;</span>
<h2>Edit Vision, Mission, & Goals</h2>

    <form action="edit_vm.php" method="post">
        <?php
        // Fetch the vision, mission, and goals from the database
        require 'dbConnCode.php'; // Include your database connection

        $sql_vm = "SELECT * FROM vision_mission";
        $result_vm = $conn->query($sql_vm);

        // Check if any Vision, Mission, or Goals exist
        if ($result_vm && $result_vm->num_rows > 0) {
            while ($row = $result_vm->fetch_assoc()) {
                // Check for Vision, Mission, or Goals and display accordingly
                if ($row['statement_type'] == 'Vision') {
                    echo "<label>Vision:</label><br>";
                    echo "<textarea name='content[]' rows='4' cols='50'>" . htmlspecialchars($row['content']) . "</textarea><br>";
                    echo "<input type='hidden' name='id[]' value='" . $row['id'] . "'><br>";
                } elseif ($row['statement_type'] == 'Mission') {
                    echo "<label>Mission:</label><br>";
                    echo "<textarea name='content[]' rows='4' cols='50'>" . htmlspecialchars($row['content']) . "</textarea><br>";
                    echo "<input type='hidden' name='id[]' value='" . $row['id'] . "'><br>";
                } elseif ($row['statement_type'] == 'Goals') {
                    echo "<label>Goals:</label><br>";
                    echo "<textarea name='content[]' rows='4' cols='50'>" . htmlspecialchars($row['content']) . "</textarea><br>";
                    echo "<input type='hidden' name='id[]' value='" . $row['id'] . "'><br>";
                }
            }
        } else {
            // No Vision, Mission, or Goals exist, allow user to create new entries
            echo "<label>Vision:</label><br>";
            echo "<textarea name='content[]' rows='4' cols='50' placeholder='Enter your vision here...'></textarea><br>";
            echo "<input type='hidden' name='id[]' value='new_vision'><br>"; // Placeholder for new Vision ID
            
            echo "<label>Mission:</label><br>";
            echo "<textarea name='content[]' rows='4' cols='50' placeholder='Enter your mission here...'></textarea><br>";
            echo "<input type='hidden' name='id[]' value='new_mission'><br>"; // Placeholder for new Mission ID
            
            echo "<label>Goals:</label><br>";
            echo "<textarea name='content[]' rows='4' cols='50' placeholder='Enter your goals here...'></textarea><br>";
            echo "<input type='hidden' name='id[]' value='new_goals'><br>"; // Placeholder for new Goals ID
        }
        ?>
           <button type="submit" class="action-button" style=" margin-left:230px;">Save Changes</button>
    </form>
    </div>
</dialog>






<a href="colleges.php">
        <button  >Manage Colleges</button>
	    </a>

        <a href="dynamic_datas.php">
        <button  >Manage Datas</button>
	    </a>
        </div>
    </div>

        






	  <div class="printcont">
		<button onclick="window.print()" class="printbtn" >
		  Print
		</button>
	  </div>

      <h1 class="vision2">Analytics</h1>
        <!-- Filter Dropdown -->
        <div class="filter-container">
            <form method="GET" action="adminHome.php">
                <label for="month"></label>
                <select name="month" id="month" onchange="this.form.submit()" style="		padding: 10px 20px; font-size: 16px; cursor: pointer;    background-color: #aa3636; /* Blue color */
          color: white; /* Text color */
          border: none; /* Remove border */
          border-radius: 10px; /* Rounded corners */
          padding: 5px 9px; /* Button size */
          font-size: 16px; /* Font size */
		  transition: background-color 0.3s; 
          top:-30px;
          position:relative;
"  >
                    <?php foreach ($availableMonths as $month): ?>
                        <option value="<?php echo $month; ?>" <?php echo ($month === $selectedMonth) ? 'selected' : ''; ?>>
                            <?php echo date("F Y", strtotime($month)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
       
        <!-- Chart Container -->
        <div class="chartss-container">
<div class="charts-container">
    <div class="chart-item">
    <h3>Colleges/Institutions</h3>
        <canvas id="collegePieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3>Exempt Review</h3>
        <canvas id="exemptPieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3>Expedited Research</h3>
        <canvas id="expeditedPieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3>Full Review</h3>
        <canvas id="fullReviewPieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3 style="font-size:17px;">Number of Research per Category</h3>
        <canvas id="researchCategoryPieChart"></canvas>
    </div>
</div>
    </div>
    </div>
                    </div>
    <!-- Footer Section -->
   
	  
	  <!-- partial -->
		<script src='https://code.jquery.com/jquery-2.2.4.min.js'></script>
	  <script src='https://codepen.io/MaciejCaputa/pen/EmMooZ.js'></script><script  src="./script.js"></script>
	  
   


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
	
	  <script src="./js/footer.js"></script>
	  
	

	<script src="./js/fonts.js"></script>
	<script src="./js/piechart.js"></script>
	<script src="./js/admin-form.js"></script>


    <script>
    const ctx = document.getElementById('collegePieChart').getContext('2d');

// Modify the college names to exclude the part after the '-'
const modifiedCollegeNames = <?php echo $collegeNames; ?>.map(name => {
    // Split the name by '-' and take the first part (before the dash)
    return name.split(' -')[0];
});

const collegeCounts = <?php echo $collegeCounts; ?>; // Assuming this is an array with counts corresponding to the college names

const collegePieChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: modifiedCollegeNames,  // Modified names without '-'
        datasets: [{
            data: collegeCounts, // Counts corresponding to the college names
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#dc3545',
                '#ffc107',
                '#6c757d'
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Modify the label text to append the count (collegeCounts[index])
                            label.text = `${label.text} (${collegeCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});


    const ctxExempt = document.getElementById('exemptPieChart').getContext('2d');

// Modify the exempt categories names to ensure proper display
const exemptCategories = <?php echo $exemptCategories; ?>; // e.g., ["WMSU - Category A", "WMSU - Category B"]
const exemptCounts = <?php echo $exemptCounts; ?>;         // e.g., [15, 25]

const exemptPieChart = new Chart(ctxExempt, {
    type: 'pie',
    data: {
        labels: exemptCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: exemptCounts,
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#dc3545',
                '#ffc107',
                '#6c757d'
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists

                            // Append the count to the label
                            label.text = `${labelName} (${exemptCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});


const ctxExpedited = document.getElementById('expeditedPieChart').getContext('2d');

// Modify the exempt categories names to ensure proper display
const expeditedCategories = <?php echo $expeditedCategories; ?>; // e.g., ["WMSU - Category A", "WMSU - Category B"]
const expeditedCounts = <?php echo $expeditedCounts; ?>;         // e.g., [15, 25]

const expeditedPieChart = new Chart(ctxExpedited, {
    type: 'pie',
    data: {
        labels: expeditedCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: expeditedCounts,
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#dc3545',
                '#ffc107',
                '#6c757d'
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists

                            // Append the count to the label
                            label.text = `${labelName} (${expeditedCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});

const ctxFullReview = document.getElementById('fullReviewPieChart').getContext('2d');

// Modify the full review categories names to ensure proper display
const fullReviewCategories = <?php echo $fullReviewCategories; ?>; // e.g., ["WMSU - Category A", "WMSU - Category B"]
const fullReviewCounts = <?php echo $fullReviewCounts; ?>;         // e.g., [10, 20]

const fullReviewPieChart = new Chart(ctxFullReview, {
    type: 'pie',
    data: {
        labels: fullReviewCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: fullReviewCounts,
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#dc3545',
                '#ffc107',
                '#6c757d'
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists

                            // Append the count to the label
                            label.text = `${labelName} (${fullReviewCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});

const ctxResearchCategory = document.getElementById('researchCategoryPieChart').getContext('2d');

// Data for the research category chart
const researchCategories = <?php echo $researchCategories; ?>; // e.g., ["WMSU - Biology", "WMSU - Chemistry"]
const researchCounts = <?php echo $researchCounts; ?>;         // e.g., [10, 20]

const researchCategoryPieChart = new Chart(ctxResearchCategory, {
    type: 'pie',
    data: {
        labels: researchCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: researchCounts,
            backgroundColor: [
                '#007bff',
                '#28a745',
                '#dc3545',
                '#ffc107',
                '#6c757d'
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                align: 'start',
                labels: {
                    boxWidth: 20,
                    padding: 10,
                    font: {
                        size: 12
                    },
                    usePointStyle: true,
                    boxHeight: 15,
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists
                            label.text = `${labelName} (${researchCounts[index]})`; // Add count to the label
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});

function openFacultyModal() {
    

    // Show the modal
    document.getElementById("facultyModal").style.display = "block";
}

// Close Modal
function closeFacultyModal() {
    document.getElementById("facultyModal").style.display = "none";
}


function removePicture() {
    const facultyId = document.querySelector('input[name="faculty_id"]').value;

    // Show SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure you want to remove the current picture?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to remove the picture
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "remove_picture.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove the image from the modal and hide the remove button
                        document.getElementById("current-picture").remove();
                        document.getElementById("remove-picture").style.display = 'none';
                        Swal.fire({
                            title: 'Picture removed successfully.',
                            icon: 'success'
                        }).then(function() {
                            // Redirect to adminHome.php after success
                            window.location.href = "adminHome.php"; // Redirect to the admin home page
                        });
                    } else {
                        Swal.fire({
                            title: 'Error removing the picture.',
                            icon: 'error'
                        });
                    }
                }
            };
            xhr.send("faculty_id=" + facultyId);
        }
    });
}

// Function to open the Schedule Modal
function openScheduleModal() {
    // Show the modal
    document.getElementById("scheduleModal").style.display = "block";
}

// Function to close the Schedule Modal
function closeScheduleModal() {
    document.getElementById("scheduleModal").style.display = "none";
}










// Function to remove the picture for a schedule
function removeSchedulePicture() {
    const scheduleId = document.querySelector('input[name="schedule_id"]').value;

    // Show SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure you want to remove the current picture?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to remove the picture
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "remove_schedule_picture.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove the image from the modal and hide the remove button
                        document.getElementById("current-picture").remove();
                        document.getElementById("remove-picture").style.display = 'none';
                        Swal.fire({
                            title: 'Picture removed successfully.',
                            icon: 'success'
                        }).then(function() {
                            // Refresh the page after success
                            window.location.href = "adminHome.php"; // Redirect to adminHome.php
                        });
                    } else {
                        Swal.fire({
                            title: response.message || 'Error removing the picture.',
                            icon: 'error'
                        });
                    }
                }
            };
            xhr.send("schedule_id=" + scheduleId);
        }
    });
}

flatpickr("#dates", {
    mode: "multiple",           // Allow multiple date selection
    dateFormat: "Y-m-d",        // Date format
    allowInput: false,          // Prevent manual typing
    defaultDate: null,          // Remove default date
    disableMobile: true,        // Disable mobile UI
    minDate: "today",           // Disable past dates
    onChange: function(selectedDates) {
        // Store the selected dates in a hidden input field (for POST submission)
        document.getElementById("dates").value = selectedDates.map(function(date) {
            return date.toISOString().split('T')[0]; // Convert date to YYYY-MM-DD format
        }).join(',');
    }
});


function openFacultyModal() {
  const modal = document.getElementById('facultyModal');
  modal.showModal(); // Open the dialog
}

function closeFacultyModal() {
  const modal = document.getElementById('facultyModal');
  modal.close(); // Close the dialog
}

function removePicture() {
  const currentPictureContainer = document.getElementById('current-picture-container');
  currentPictureContainer.innerHTML = '<p>No picture available</p>';
}


function openScheduleModal() {
  const modal = document.getElementById('scheduleModal');
  modal.showModal(); // Open the dialog
}

function closeScheduleModal() {
  const modal = document.getElementById('scheduleModal');
  modal.close(); // Close the dialog
}

function removeSchedulePicture() {
  const currentPictureContainer = document.getElementById('current-picture-container');
  currentPictureContainer.innerHTML = '<p>No picture available</p>';
}



function openVmForm() {
  const vmForm = document.getElementById('vmForm');
  vmForm.showModal(); // Open the dialog
}

function closeVmForm() {
  const vmForm = document.getElementById('vmForm');
  vmForm.close(); // Close the dialog
}


document.querySelectorAll('.dropdown-item').forEach(item => {
    item.addEventListener('click', function () {
        // Remove 'active' class from all items
        document.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
        
        // Add 'active' class to the clicked item
        this.classList.add('active');
        
        // Optionally handle the choice
        console.log(`Selected choice: ${this.dataset.choice}`);
    });
});
</script>

</body>
</html>
