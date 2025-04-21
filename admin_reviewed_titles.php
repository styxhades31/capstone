<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true); // Regenerate session id on first visit
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

// Database connection
require_once 'dbConnCode.php';
include 'statusUpdateRev.php'; // This will execute the script when the page loads
// Fetch uploaded research title information (including 'uploaded_at' field)
// Fetch uploaded research title information (including 'uploaded_at' and 'id' fields)

$query_not_done_count = "
  SELECT COUNT(*) AS not_done_count
  FROM researcher_title_informations
  WHERE Revision_status = 'Done'
";

// Execute the query
$result_not_done_count = $conn->query($query_not_done_count);

// Fetch the result
if ($result_not_done_count->num_rows > 0) {
    $row = $result_not_done_count->fetch_assoc();
    $not_done_count = $row['not_done_count'];
} else {
    $not_done_count = 0;
}

$query1 = "
 SELECT rti.id, 
         rti.user_id, 
         rti.uploaded_at, 
         rti.study_protocol_title, 
         rti.research_category, 
         rti.college,
         rti.adviser_name, 
         rti.payment,                -- Added payment column
         rti.type_of_review,         -- Added type_of_review column
         a.appointment_date,
         rp.mobile_number,
         u.email
  FROM researcher_title_informations AS rti
  LEFT JOIN appointments AS a ON rti.id = a.researcher_title_id  -- Change user_id to researcher_title_id
  LEFT JOIN researcher_profiles AS rp ON rti.user_id = rp.user_id
  LEFT JOIN users AS u ON rti.user_id = u.id
  LEFT JOIN assign_reviewer AS ar ON rti.id = ar.researcher_info_id
  WHERE rti.Revision_status = 'Done'  -- Filter for rows where Revision_status is not 'Done'
  ORDER BY rti.uploaded_at DESC
";

$result1 = $conn->query($query1);

// Fetch colleges
$collegeQuery1 = "SELECT id, college_name_and_color FROM colleges ORDER BY college_name_and_color";
$collegeResult1 = $conn->query($collegeQuery1);

// Fetch unique categories
$categoryQuery = "SELECT DISTINCT research_category FROM researcher_title_informations WHERE research_category IS NOT NULL ORDER BY research_category";
$categoryResult = $conn->query($categoryQuery);

// Fetch unique colleges
$collegeQuery = "SELECT DISTINCT college FROM researcher_title_informations WHERE college IS NOT NULL ORDER BY college";
$collegeResult = $conn->query($collegeQuery);

// Fetch unique payment statuses
$paymentQuery = "SELECT DISTINCT payment FROM researcher_title_informations WHERE payment IS NOT NULL ORDER BY payment";
$paymentResult = $conn->query($paymentQuery);

// Fetch unique types of review
$typeQuery = "SELECT DISTINCT type_of_review FROM researcher_title_informations WHERE type_of_review IS NOT NULL ORDER BY type_of_review";
$typeResult = $conn->query($typeQuery);

// Below is for the insert

// Fetch unique categories
$categoryQuery2 = "SELECT DISTINCT research_category FROM researchertitleinfo_nouser WHERE research_category IS NOT NULL ORDER BY research_category";
$categoryResult2 = $conn->query($categoryQuery2);

// Fetch unique colleges
$collegeQuery2 = "SELECT DISTINCT college FROM researchertitleinfo_nouser WHERE college IS NOT NULL ORDER BY college";
$collegeResult2 = $conn->query($collegeQuery2);

// Fetch unique payment statuses
$paymentQuery2 = "SELECT DISTINCT payment FROM researchertitleinfo_nouser WHERE payment IS NOT NULL ORDER BY payment";
$paymentResult2 = $conn->query($paymentQuery2);

// Fetch unique types of review
$typeQuery2 = "SELECT DISTINCT type_of_review FROM researchertitleinfo_nouser WHERE type_of_review IS NOT NULL ORDER BY type_of_review";
$typeResult2 = $conn->query($typeQuery2);

// Fetch all reviewers from the database
$reviewerQuery = "SELECT rp.user_id, CONCAT(rp.first_name, ' ', COALESCE(rp.middle_initial, ''), ' ', rp.last_name) AS full_name FROM reviewer_profiles AS rp";
$reviewers = $conn->query($reviewerQuery);
// Fetch unique months from new_date_column
$dateQuery = "SELECT `uploaded_at` FROM `researcher_title_informations`";
$dates = $conn->query($dateQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin-Application Forms</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="/css/styles.css">
	<link rel="stylesheet" type="text/css" href="/css/piechart.css">
	<link rel="stylesheet" type="text/css" href="/css/admin-form.css" />
	<link href='https://unpkg.com/boxicons@2.1.1/css/boxicons.min.css' rel='stylesheet'>

    
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all Edit buttons
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Get the 'id' from the data-id attribute of the clicked button
            const id = this.getAttribute('data-id');
            
            // Redirect to the edit page with the id as a query parameter
            window.location.href = 'editResearch.php?id=' + id;
        });
    });
});

</script>
    <style>
      
        
       
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
        .table-container {
            margin-top: 20px;
        }
 
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            font-size: 12px;
           
        }
        th {
            background-color:  #aa3636;
            color: white;
            text-align: center;
        }
     

    .insert{
        background-color:  #aa3636;
            color: white;
            padding: 10px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 95px;
            margin-top:20px; 
            transition: background-color 0.3s;
    }


    .insert:hover{
        background-color: #802c2c;
    }
    



.edit-btn{
    background-color:  #aa3636;
    color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */
}


.edit-btn:hover{
    background-color: #802c2c;
}




        td.name {
	font-weight: bold;
}
td.email {
	color: #666;
	text-decoration: underline;
}

/* form styles */
input, select {
	font: inherit;
	margin: 0px 20px 0px 20px;
	padding: 4px;
	border: 1px solid #bbb;
}
.input-text {
	min-width: 150px;
	background-color: #fff;
}
select {
	min-width: 150px;
}
label {
	margin-right: 4px;
}



/* Filtable styles */
tr.hidden {
	display: none;
}
tr:nth-child(odd) > td {
	background-color: #ffffff;
}
tr:nth-child(even) > td {
	background-color: #f4f4f2;
}
tr.odd > td {
	background-color: #ffffff;
}
tr.even > td {
	background-color: #f4f4f2;
}


/* Large table example */

.console {
	font-family: ui-monospace, monospace;
}


.table-filters{
    position: relative;
   margin-left: 255px;
    unicode-bidi: isolate;

}

.table-filters label{
   margin-left: 35px;

}


.table-filters1{

    position: relative;
margin-bottom: 30px;
    unicode-bidi: isolate;

}

.table-filters1 label{
   margin-left: 20px;

}

/* Modal checkbox trick */



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

 
    left: 100px;
  display: flex; /* Display modal when 'show' class is added */
}
/* Modal Content */


dialog::backdrop {
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(4px); /* Blurs the background */
  -webkit-backdrop-filter: blur(4px); /* Safari compatibility */
  z-index: 1;
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





input[type="checkbox"] {
    display: none;
}

input[type="checkbox"]:checked ~ .modal {
    display: flex;
}





button{
    cursor: pointer;
}



.tablee{
    position: relative;
    top: 50px;
}


.restitle {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
            transition: background-color 0.3s ease;
    
          }
          
          .restitle:hover {
            overflow: visible;
            white-space: normal;
            max-width: none;
            background-color: #fff;
            z-index: 1;
          }

 /* ARIS CODE AGAIN EDIT MOLANG */
 .count-box {
            background-color:rgb(139, 56, 56); /* Tomato background */
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 20px;
            position: relative;
            left: -800px;
            border-radius: 10px;
            width: 250px;
            margin: 20px auto;
            z-index: 1;
        }


        .vision2 {
  background-color: #F8F7F4;
  position: relative;
margin-top: -50px;
  padding-bottom: 50px;
  text-align: center; 
}

.assign-btn{
    background-color: #007bff; /* Blue color */
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */

}


.assign-btn:hover {
            background-color: #0056b3; /* Darker blue on hover */
          }


        .view-btn{
            background-color: #22a554; /* Blue color */
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */
            margin-left: 18px;
        }
        .view-btn:hover {
            background-color:  #176d38; /* Darker blue on hover */
          }
  
.reviewedbtn{
    background-color: rgb(139, 56, 56);  /* Blue color */
    position: relative;
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */
            margin-left:60px;
            top:170px;
            z-index: 1;
        }
        .reviewedbtn:hover {
            background-color:  rgb(109, 56, 56); /* Darker blue on hover */
          }
  






          .view-files-btn{
            width: 100px;
            background-color: #22a554; /* Blue color */
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */
          
        }
        .view-files-btn:hover {
            background-color:  #176d38; /* Darker blue on hover */
          }
  

          .generate-btn{
    background-color: #007bff; /* Blue color */
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */

}
/* Backdrop Blur Fallback */
.backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(4px); /* Blur effect */
  -webkit-backdrop-filter: blur(4px); /* Safari compatibility */
  z-index: 999; /* Ensure it sits behind the modal */
  display: none; /* Initially hidden */
}

/* Display the backdrop when the modal is active */
.backdrop.show {
  display: block;
}

.generate-btn:hover {
            background-color: #0056b3; /* Darker blue on hover */
          }

    </style>

</head>

<body>
       
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



<div class="count-box">
    Total Count of Applications: <span id="rowCount" style="font-weight: bold;">0</span>
</div>

<h1 class="vision2">Application Forms With Assigned Reviewer</h1>
    <!-- Main Content -->
    <div class="main-content">
      
        <div class="table-filters1">
            <!-- Input fields for search -->
            <input class="input-text" type="text" id="searchTitle" placeholder="Search by Study Protocol Title" data-filter-col="0,1">
            <input class="input-text" type="text" id="searchEmail" placeholder="Search by Email"  data-filter-col="2">

            
    <select id="filterCategory">
        <option value="">All Categories</option>
        <?php while ($row = $categoryResult->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['research_category']) . '">' . htmlspecialchars($row['research_category']) . '</option>';
        } ?>
    </select>
    <select id="filterCollege">
        <option value="">All Colleges</option>
        <?php while ($row = $collegeResult->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['college']) . '">' . htmlspecialchars($row['college']) . '</option>';
        } ?>
    </select>
    <select id="filterPayment">
        <option value="">All Payments</option>
        <?php while ($row = $paymentResult->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['payment']) . '">' . htmlspecialchars($row['payment']) . '</option>';
        } ?>
    </select>
    <select id="filterTypeOfResearch">
        <option value="">All Types</option>
        <?php while ($row = $typeResult->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['type_of_review']) . '">' . htmlspecialchars($row['type_of_review']) . '</option>';
        } ?>
    </select>
    <br>
    <br>
    <label for="filterReviewer" >Filter by Reviewer:</label>
    <select id="filterReviewer" onchange="filterByReviewer()">
        <option value="">All Reviewers</option>
        <?php
        if ($reviewers->num_rows > 0) {
            while ($reviewer = $reviewers->fetch_assoc()) {
                echo '<option value="' . htmlspecialchars($reviewer['user_id']) . '">' . htmlspecialchars($reviewer['full_name']) . '</option>';
            }
        }
        ?>
    </select>

    <label for="filterDate">Filter by Month:</label>
<select id="filterDate">
    <option value="">All Months</option>
    <?php
    // Query to fetch unique months from 'new_date_column' (Year-Month format)
    $dateQuery = "SELECT DISTINCT DATE_FORMAT(new_date_column, '%Y-%m') AS month_year 
                  FROM researcher_title_informations 
                  WHERE new_date_column IS NOT NULL 
                  ORDER BY new_date_column DESC";
    $dateResult = $conn->query($dateQuery);

    // Generate options dynamically for the filter
    while ($row = $dateResult->fetch_assoc()) {
        // Get the month-year (e.g., 2024-12)
        $monthYear = $row['month_year'];
        
        // Create a DateTime object from the month-year format
        $date = DateTime::createFromFormat('Y-m', $monthYear);
        
        // Format the month as a textual representation (e.g., "December")
        $formattedMonth = $date->format('F Y'); // F = full month name, Y = full year

        // Display the option with the formatted month
        echo '<option value="' . htmlspecialchars($monthYear) . '">' . htmlspecialchars($formattedMonth) . '</option>';
    }
    ?>
</select>




</div>


<div style="width: 100%; overflow-x: auto; position: relative; ">
            <table  style="border-collapse: collapse; width: 100% !important; ; " >
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Study Protocol Title</th>
                        <th>Research Category</th>
                        <th>College/ Institution</th>
                        <th>Name of the Adviser</th>
                        <th>Date Approved</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Payment</th>
                        <th>Type Of Research</th>
                        <th>Researchers Involved</th>
                        <th>Submitted Files</th>
                        <th>Certification</th>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                <?php
                $query = "
                SELECT rti.id, 
                       rti.user_id, 
                       rti.Revision_status, 
                       rti.study_protocol_title, 
                       rti.new_date_column, 
                       rti.research_category, 
                       rti.college,
                       rti.adviser_name, 
                       rti.payment,                
                       rti.type_of_review,         
                       a.appointment_date,
                       rp.mobile_number,
                       u.email,
                       ar.researcher_info_id       -- Include researcher_info_id for filtering
                FROM researcher_title_informations AS rti
                LEFT JOIN appointments AS a ON rti.id = a.researcher_title_id
                LEFT JOIN researcher_profiles AS rp ON rti.user_id = rp.user_id
                LEFT JOIN users AS u ON rti.user_id = u.id
                LEFT JOIN assign_reviewer AS ar ON rti.id = ar.researcher_info_id -- Join with assign_reviewer table
                WHERE rti.Revision_status = 'Done'  -- Filter rows where Revision_status is not 'Done'
                  AND ar.researcher_info_id IS NOT NULL -- Show only rows with assigned reviewers
                ORDER BY rti.uploaded_at DESC
                ";
                
              // Execute the query and store the result
              $result = $conn->query($query);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Revision_status']) . "</td>";
        echo "<td class='restitle'>" . htmlspecialchars($row['study_protocol_title']) . "</td>"; // Display study_protocol_title
        echo "<td>" . htmlspecialchars($row['research_category']) . "</td>";
        echo "<td>" . htmlspecialchars($row['college']) . "</td>";
        echo "<td>" . htmlspecialchars($row['adviser_name']) . "</td>";

        echo "<td>" . ($row['appointment_date'] ? htmlspecialchars($row['new_date_column']) : "No Schedule") . "</td>"; // Display appointment date or "No Schedule"
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['mobile_number']) . "</td>";

        // Payment dropdown
        echo "<td>
                <select class='payment-dropdown' data-id='" . $row['id'] . "'>
                    <option value='None' " . ($row['payment'] === 'None' ? 'selected' : '') . ">None</option>
                    <option value='Issued Payment Slip' " . ($row['payment'] === 'Issued Payment Slip' ? 'selected' : '') . ">Issued Payment Slip</option>
                    <option value='Paid' " . ($row['payment'] === 'Paid' ? 'selected' : '') . ">Paid</option>
                </select>
              </td>";

        // Type of Review dropdown
        echo "<td>
                <select class='type-review-dropdown' data-id='" . $row['id'] . "'>
                    <option value='For Initial Review' " . ($row['type_of_review'] === 'For Initial Review' ? 'selected' : '') . ">For Initial Review</option>
                    <option value='Initial Review' " . ($row['type_of_review'] === 'Initial Review' ? 'selected' : '') . ">Initial Review</option>
                    <option value='Full Review' " . ($row['type_of_review'] === 'Full Review' ? 'selected' : '') . ">Full Review</option>
                    <option value='Expedited' " . ($row['type_of_review'] === 'Expedited' ? 'selected' : '') . ">Expedited</option>
                    <option value='Exempt' " . ($row['type_of_review'] === 'Exempt' ? 'selected' : '') . ">Exempt</option>
                </select>
              </td>";
            
              echo "<td><button class='view-btn' data-id='" . $row['id'] . "'>View</button></td>"; // Use id field for data-id
              echo "<td><button class='view-files-btn' data-id='" . $row['id'] . "'>View Files</button></td>"; // Now using rti.id
              
         // Generate button
         echo '<td><button class="generate-btn" data-id="' . $row['id'] . '" data-review-type="' . htmlspecialchars($row['type_of_review']) . '">Generate</button></td>';

         // Add the Edit button in each row
         echo "<td><button class='edit-btn' data-id='" . $row['id'] . "'>Edit</button></td>";


        echo "</tr>";
    }
} else {
    // Add a placeholder row with a class for "No data available"
    echo "<tr class='placeholder'><td colspan='15'>No data available.</td></tr>";
}
?>
 </tbody>
            </table>
</div>

<dialog id="modal" style="position:relative; top:-150px;"  >
    <button id="modal-close" style="position: absolute; top: 10px; right: 10px; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
    <div id="modal-content"></div>
</dialog>

    </div>
    
   
    <!-- Footer Section -->
   

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
  
  

    <script>
        






function addResearcher() {
    const container = document.getElementById('researchersContainer');
    const newEntry = container.children[1].cloneNode(true); // Clone the first researcher entry
    newEntry.querySelectorAll('input').forEach(input => input.value = ''); // Clear input values
    const addButton = newEntry.querySelector('button');
    addButton.textContent = 'Remove Researcher';
    addButton.onclick = function() { this.parentElement.remove(); }; // Change to remove function
    container.appendChild(newEntry);
}
function toggleCollegeInput() {
    var select = document.getElementById('collegeSelect');
    var textInput = document.getElementById('collegeText');
    // Check if the 'Other' option is selected
    if (select.value === 'other') {
        textInput.style.display = 'block'; // Show text input
    } else {
        textInput.style.display = 'none'; // Hide text input
        textInput.value = ''; // Clear any entered text
    }
}



    document.querySelectorAll('.view-btn').forEach(button => {
        button.addEventListener('click', function() {
            var researcherTitleId = this.getAttribute('data-id'); // Get the researcher_title_id from the button data-id
            
            // Send an AJAX request to fetch the involved researchers for the selected title
            fetch('fetch_researchers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ researcher_title_id: researcherTitleId }) // Send the researcher_title_id to the server
            })
            .then(response => response.json()) // Parse the JSON response
            .then(data => {
                if (data.success) {
                    // Use SweetAlert to display the researchers' details
                    let researcherDetails = '';
        data.researchers.forEach(researcher => {
            // Check if the middle initial is empty or not and adjust accordingly
            let middleInitial = researcher.middle_initial ? researcher.middle_initial + '.' : '';
            let suffix = researcher.suffix ? ' ' + researcher.suffix : ''; // Only add suffix if it's present

            // Construct the researcher details string
            researcherDetails += `<p><strong>Name:</strong> ${researcher.first_name} ${middleInitial} ${researcher.last_name}${suffix}</p>`;
        });

                    Swal.fire({
                        title: 'Researchers Involved',
                        html: researcherDetails,
                        icon: 'info'
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'No researchers found for this title.',
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while fetching the data.',
                    icon: 'error'
                });
            });
        });
    });

    // New View Files Button functionality
document.querySelectorAll('.view-files-btn').forEach(button => {
    button.addEventListener('click', function() {
        var researcherTitleId = this.getAttribute('data-id'); // Get the researcher_title_id from the button data-id

        fetch('fetch_files.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ researcher_title_id: researcherTitleId }) // Send researcher_title_id instead of user_id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let fileDetails = '';
                data.files.forEach(file => {
                    fileDetails += `<p><strong>Type:</strong> ${file.file_type} <br> <strong>Filename:</strong> <a href="${file.file_path}" target="_blank">${file.filename}</a></p>`;
                });

                Swal.fire({
                    title: 'Uploaded Files',
                    html: fileDetails,
                    icon: 'info'
                });
            } else {
                Swal.fire({ title: 'Error', text: 'No files found for this researcher title.', icon: 'error' });
            }
        })
        .catch(error => {
            Swal.fire({ title: 'Error', text: 'An error occurred while fetching the files.', icon: 'error' });
        });
    });
});
$(document).ready(function() {
    // Change event for Payment dropdown
    $('.payment-dropdown').change(function() {
        var id = $(this).data('id');
        var newValue = $(this).val();

        // AJAX request to update the payment value
        $.ajax({
            url: 'update_payment.php', // Replace with your PHP script to handle the update
            type: 'POST',
            data: { id: id, payment: newValue },
            success: function(response) {
                if (response.success) {
                    // Show success alert using SweetAlert
                    Swal.fire({
                        title: 'Success',
                        text: response.message,
                        icon: 'success'
                    });
                } else {
                    // Show error alert using SweetAlert
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function() {
                // Handle errors if AJAX request fails
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while updating the payment.',
                    icon: 'error'
                });
            }
        });
    });

    // Change event for Type of Review dropdown
    $('.type-review-dropdown').change(function() {
        var id = $(this).data('id');
        var newValue = $(this).val();

        // AJAX request to update the type of review value
        $.ajax({
            url: 'update_type_review.php', // Replace with your PHP script to handle the update
            type: 'POST',
            data: { id: id, type_of_review: newValue },
            success: function(response) {
                if (response.success) {
                    // Show success alert using SweetAlert
                    Swal.fire({
                        title: 'Success',
                        text: response.message,
                        icon: 'success'
                    });
                } else {
                    // Show error alert using SweetAlert
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error'
                    });
                }
            },
            error: function() {
                // Handle errors if AJAX request fails
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while updating the type of review.',
                    icon: 'error'
                });
            }
        });
    });
});


document.querySelectorAll('.generate-btn').forEach(button => {
    button.addEventListener('click', function () {
        var userId = this.getAttribute('data-id'); // Get user ID from the button
        var reviewType = this.getAttribute('data-review-type'); // Get review type
        var rtiId = userId; // Assuming `userId` is the `rti_id`
        var certEndpoint = '';
        var coverLetterEndpoint = '';

        // Check if certificate already exists
         // Check if certificates already exist
         fetch(`check_certificate_status.php?rti_id=${rtiId}`, {
            method: 'GET',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not OK');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // If certificates exist, show them in a list with download links
                    let certificateList = data.certificates
                        .map(cert => `<li><a href="${cert.file_url}" target="_blank">${cert.file_name}</a> (Generated at: ${cert.generated_at}, Type: ${cert.file_type})</li>`)
                        .join('');

                    Swal.fire({
                        title: 'Certificates Found',
                        html: `<p>The following certificates have been generated for this title:</p><ul>${certificateList}</ul>`,
                        icon: 'info',
                    });
                }  else {
                    // Proceed with certificate generation if not already generated
                    if (reviewType === 'Exempt') {
                        certEndpoint = `generate_cert_exempt.php?user_id=${userId}`;
                        coverLetterEndpoint = `generate_cover_letter_exempt.php?user_id=${userId}`;
                    } else if (reviewType === 'Full Review' || reviewType === 'Expedited') {
                        certEndpoint = `generate_REC_FLorEXP.php?user_id=${userId}`;
                        coverLetterEndpoint = `generate_cover_letter_researchEthics.php?user_id=${userId}`;
                    }

                    if (certEndpoint) {
                        fetch(certEndpoint, {
                            method: 'GET',
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response for certificate was not OK');
                                }
                                return response.json();
                            })
                            .then(certData => {
                                if (certData.success) {
                                    Swal.fire({
                                        title: 'Success',
                                        text: 'Certificate generated successfully.',
                                        icon: 'success',
                                    });

                                    if (coverLetterEndpoint) {
                                        fetch(coverLetterEndpoint, {
                                            method: 'GET',
                                        })
                                            .then(response => {
                                                if (!response.ok) {
                                                    throw new Error('Network response for cover letter was not OK');
                                                }
                                                return response.json();
                                            })
                                            .then(coverData => {
                                                if (coverData.success) {
                                                    Swal.fire({
                                                        title: 'Success',
                                                        text: 'Cover letter and Certificate generated successfully.',
                                                        icon: 'success',
                                                    });
                                                } else {
                                                    Swal.fire({
                                                        title: 'Error',
                                                        text: coverData.message || 'Error generating cover letter.',
                                                        icon: 'error',
                                                    });
                                                }
                                            })
                                            .catch(error => {
                                                Swal.fire({
                                                    title: 'Error',
                                                    text: 'Error generating cover letter: ' + error.message,
                                                    icon: 'error',
                                                });
                                            });
                                    }
                                } else {
                                    Swal.fire({
                                        title: 'Error',
                                        text: certData.message || 'Error generating certificate.',
                                        icon: 'error',
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Error generating certificate: ' + error.message,
                                    icon: 'error',
                                });
                            });
                    } else {
                        Swal.fire({
                            title: 'Not Eligible',
                            text: 'This review type is not eligible for certificate generation.',
                            icon: 'info',
                        });
                    }
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: 'Network or processing error: ' + error.message,
                    icon: 'error',
                });
            });
    });
});



document.addEventListener("DOMContentLoaded", function () {
    const assignButtons = document.querySelectorAll(".assign-btn");

    assignButtons.forEach(button => {
        button.addEventListener("click", function () {
            const researchId = this.getAttribute("data-id");

            // Make an AJAX request to fetch reviewers and the currently assigned reviewer
            fetch("fetch_reviewers.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ researchId: researchId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let modalContent = "";

                        if (data.assignedReviewer) {
                            const assignedFullName = data.assignedReviewer.middle_initial ? 
                                `${data.assignedReviewer.first_name} ${data.assignedReviewer.middle_initial}. ${data.assignedReviewer.last_name}` :
                                `${data.assignedReviewer.first_name} ${data.assignedReviewer.last_name}`;

                            if (data.assignedReviewer.status === 'Complete') {
                                modalContent += 
                                    `<p>This research title has been reviewed and completed.</p>
                                     <p><strong>Reviewed by:</strong> ${assignedFullName}</p>`;
                                const modalContentElement = document.getElementById("modal-content");
                                modalContentElement.innerHTML = modalContent;
                                document.getElementById("modal").style.display = "flex";
                                return;
                            } else {
                                modalContent += 
                                    `<p>This reviewer is currently assigned to this research title.</p>
                                     <p><strong>Assigned Reviewer:</strong> ${assignedFullName}</p>`;
                            }
                        } else {
                            modalContent += "<p>No reviewer has been assigned yet.</p>";
                        }

                        if (data.reviewers.length > 0) {
                            modalContent += "<table border='1'><tr><th>Name</th><th>Max Number Of Review</th><th>Ongoing Reviews</th><th>Action</th></tr>";
                            data.reviewers.forEach(reviewer => {
                                const fullName = reviewer.middle_initial ? 
                                    `${reviewer.first_name} ${reviewer.middle_initial}. ${reviewer.last_name}` :
                                    `${reviewer.first_name} ${reviewer.last_name}`;

                                modalContent += 
                                    `<tr>
                                        <td>${fullName}</td>
                                        <td>${reviewer.max_reviews}</td>
                                        <td>${reviewer.ongoing_reviews}</td>
                                        <td>
                                            <button class="assign-reviewer" data-id="${reviewer.user_id}" data-research="${researchId}">
                                                Assign
                                            </button>
                                        </td>
                                    </tr>`;
                            });
                            modalContent += "</table>";
                        } else {
                            modalContent += "<p>No available reviewers at the moment.</p>";
                        }

                        const modalContentElement = document.getElementById("modal-content");
                        modalContentElement.innerHTML = modalContent;
                        document.getElementById("modal").style.display = "flex";

                        // Add event listener to assign buttons inside the modal
                        document.querySelectorAll(".assign-reviewer").forEach(assignBtn => {
                            assignBtn.addEventListener("click", function () {
                                const reviewerId = this.getAttribute("data-id");
                                const researchId = this.getAttribute("data-research");

                                // SweetAlert confirmation before assigning reviewer
                                Swal.fire({
                                    title: 'Are you sure?',
                                    text: 'Do you want to assign this reviewer to the research?',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonText: 'Yes, assign it!',
                                    cancelButtonText: 'No, cancel!',
                                    reverseButtons: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        assignReviewer(reviewerId, researchId);
                                    }
                                });
                            });
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "No Reviewers Available",
                            text: data.message || "There are currently no reviewers available.",
                            timer: 3000,
                            showConfirmButton: true
                        });
                    }
                })
                .catch(error => {
                    console.error("Error fetching reviewers:", error);
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "An unexpected error occurred. Please try again.",
                        timer: 3000,
                        showConfirmButton: true
                    });
                });
        });
    });

    // Close modal event
    const modalClose = document.getElementById("modal-close");
    if (modalClose) {
        modalClose.addEventListener("click", function () {
            document.getElementById("modal").style.display = "none";
        });
    }
});


function assignReviewer(reviewerId, researchId) {
    fetch("assign_reviewer.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ reviewerId: reviewerId, researchId: researchId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Reviewer Assigned",
                    text: data.message,
                    timer: 3000,
                    showConfirmButton: true
                }).then(() => {
                    // Refresh the page to show updated data
                    window.location.href = "admin_applicationforms.php";
                });
            } else {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: data.message,
                    timer: 3000,
                    showConfirmButton: true
                });
            }
        })
        .catch(error => {
            console.error("Error assigning reviewer:", error);
            Swal.fire({
                icon: "error",
                title: "Error",
                text: "An unexpected error occurred. Please try again.",
                timer: 3000,
                showConfirmButton: true
            });
        });
}
function updateRowCount() {
    
    // Get all rows in the table body
    const rows = document.querySelectorAll('#tableBody tr');
    let visibleRowCount = 0;

    rows.forEach(row => {
        // Check if the row is visible and not a placeholder for "No data available"
        if (row.style.display !== 'none' && !row.classList.contains('placeholder')) {
            visibleRowCount++;
        }
    });

    // Update the count in the count-box
    document.getElementById('rowCount').textContent = visibleRowCount;
    
   
}
document.addEventListener('DOMContentLoaded', function () {
   // Get all filter inputs
   const searchTitle = document.getElementById('searchTitle');
   const searchEmail = document.getElementById('searchEmail');
   const filterCategory = document.getElementById('filterCategory');
   const filterCollege = document.getElementById('filterCollege');
   const filterPayment = document.getElementById('filterPayment');
   const filterTypeOfResearch = document.getElementById('filterTypeOfResearch');
   const filterReviewer = document.getElementById('filterReviewer');
   const filterDate = document.getElementById('filterDate'); // Added month filter

   // Table body
   const tableBody = document.getElementById('tableBody');

   // Function to filter rows based on the filters
   function filterTable() {
       const rows = tableBody.getElementsByTagName('tr');

       for (let row of rows) {
           const titleCell = row.cells[1]; // Study Protocol Title
           const emailCell = row.cells[6]; // Email
           const categoryCell = row.cells[2]; // Research Category
           const collegeCell = row.cells[3]; // College
           const paymentCell = row.cells[8]; // Payment
           const typeCell = row.cells[9]; // Type of Review
           const dateCell = row.cells[5]; // Assuming the date is in the first column, adjust if necessary

           // Get the values to filter by
           const titleValue = titleCell ? titleCell.textContent.toLowerCase() : '';
           const emailValue = emailCell ? emailCell.textContent.toLowerCase() : '';
           const categoryValue = categoryCell ? categoryCell.textContent.toLowerCase() : '';
           const collegeValue = collegeCell ? collegeCell.textContent.toLowerCase() : '';
           const paymentValue = paymentCell ? paymentCell.textContent.toLowerCase() : '';
           const typeValue = typeCell ? typeCell.textContent.toLowerCase() : '';
           const dateValue = dateCell ? dateCell.textContent.toLowerCase() : '';

           // Extract the month-year for comparison
           const selectedMonth = filterDate.value; // Get selected value from month filter (YYYY-MM)
           const rowMonthYear = dateCell ? dateValue.split('-').slice(0, 2).join('-') : ''; // Assuming date is in "YYYY-MM-DD" format

           // Matches
           const matchesSearchTitle = titleValue.includes(searchTitle.value.toLowerCase());
           const matchesSearchEmail = emailValue.includes(searchEmail.value.toLowerCase());
           const matchesCategory = filterCategory.value === '' || categoryValue.includes(filterCategory.value.toLowerCase());
           const matchesCollege = filterCollege.value === '' || collegeValue.includes(filterCollege.value.toLowerCase());
           const matchesPayment = filterPayment.value === '' || paymentValue.includes(filterPayment.value.toLowerCase());
           const matchesType = filterTypeOfResearch.value === '' || typeValue.includes(filterTypeOfResearch.value.toLowerCase());
           const matchesDate = selectedMonth === '' || rowMonthYear === selectedMonth; // Check if the row matches the selected month
           

           // Show/hide rows based on filters
           if (matchesSearchTitle && matchesSearchEmail && matchesCategory && matchesCollege && matchesPayment && matchesType && matchesDate) {
               row.style.display = '';
               updateRowCount()
               
               
               
           } else {
               row.style.display = 'none';
               updateRowCount()
           }
       }
   }

   // Function to handle reviewer filter
   function filterByReviewer() {
       const reviewerId = filterReviewer.value;

       // Send an AJAX request to fetch filtered data
       const xhr = new XMLHttpRequest();
       xhr.open('POST', 'fetch_rti_by_reviewer_done.php', true);
       xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
       xhr.onload = function () {
           if (xhr.status === 200) {
               // Update the table body with the filtered data
               tableBody.innerHTML = xhr.responseText;

               // Reapply event listeners after updating the table
               reapplyFilters();

               // Reapply the row count after the table updates
               updateRowCount();

               // Trigger the filter function to ensure consistency
               filterTable();
           } else {
               alert('Failed to fetch data. Please try again.');
           }
       };
       xhr.send('reviewer_id=' + reviewerId);
   }

   // Function to reapply event listeners
   function reapplyFilters() {
       searchTitle.addEventListener('input', filterTable);
       searchEmail.addEventListener('input', filterTable);
       filterCategory.addEventListener('change', filterTable);
       filterCollege.addEventListener('change', filterTable);
       filterPayment.addEventListener('change', filterTable);
       filterTypeOfResearch.addEventListener('change', filterTable);
       filterDate.addEventListener('change', filterTable); // Reapply the filter for the month dropdown
   }

   // Initialize event listeners
   searchTitle.addEventListener('input', filterTable);
   searchEmail.addEventListener('input', filterTable);
   filterCategory.addEventListener('change', filterTable);
   filterCollege.addEventListener('change', filterTable);
   filterPayment.addEventListener('change', filterTable);
   filterTypeOfResearch.addEventListener('change', filterTable);
   filterReviewer.addEventListener('change', filterByReviewer);
   filterDate.addEventListener('change', filterTable); // Add event listener for the month filter

   // Initialize the table with the current filters
   filterTable();
});



// Initial row count update after page load
document.addEventListener('DOMContentLoaded', updateRowCount); 

const modal = document.getElementById('modal');
const modalClose = document.getElementById('modal-close');


function openModal() {
  const modal = document.getElementById('modal');
  modal.showModal(); // Open the dialog

}

function closeModal() {
  const modal = document.getElementById('modal');
  modal.close(); // Close the dialog

}

</script>

</body>
</html>