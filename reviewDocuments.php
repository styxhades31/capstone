<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true); // Regenerate session id on first visit
}

// Check if the user is logged in and if their role is 'Reviewer'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Reviewer') {
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
        session_destroy(); // Destroy the session to log the user out
        header("Location: login.php");
        exit();
    } else {
        // Handle CSRF token validation failure (optional)
        echo "<script>alert('Invalid CSRF token.');</script>";
    }
}
require_once('vendor/autoload.php'); // Include FPDI and FPDF libraries
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'dbConnCode.php';
// Fetch the research titles assigned to the reviewer with 'Ongoing' status only
$userId = $_SESSION['user_id'];
$sql = "SELECT rti.id, rti.study_protocol_title, rti.college, rti.research_category, rti.adviser_name, rti.type_of_review, rti.Revision_document, rti.Revised_document, rti.Revision_status, ar.status 
        FROM assign_reviewer ar
        JOIN researcher_title_informations rti ON ar.researcher_info_id = rti.id
        WHERE ar.user_id = ? AND ar.status = 'Ongoing'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Get the 'researcher_info_id' (from the row that the user wants to toggle) through GET or POST
if (isset($_GET['researcher_info_id'])) {
    $researcherInfoId = $_GET['researcher_info_id']; // Ensure you get the correct researcher info ID from the URL or form

    // Prepare the query to update the status to 'Complete'
    $sql = "UPDATE assign_reviewer 
            SET status = 'Complete' 
            WHERE user_id = ? AND researcher_info_id = ?";

    // Prepare and bind parameters
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $researcherInfoId); // Bind the user ID and researcher_info_id
    $stmt->execute();
    $stmt->close();

     // Update the Revision_status to 'Done' in Researcher_title_informations table
     $updateSql = "UPDATE researcher_title_informations 
              SET Revision_status = 'Done', 
                  new_date_column = CURDATE() 
              WHERE id = ?";

$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("i", $researcherInfoId); // Bind researcher_info_id
$updateStmt->execute();
$updateStmt->close();
// Fetch the current Revision_document and user email
$sql = "SELECT rti.user_id, u.email 
        FROM researcher_title_informations rti 
        INNER JOIN users u ON rti.user_id = u.id 
        WHERE rti.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $researcherInfoId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($userId, $userEmail);
$stmt->fetch();
$stmt->close();
// Now, send the email based on the Revision_status
$mail = new PHPMailer(true);
try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Replace with your email host
    $mail->SMTPAuth = true;
    $mail->Username = 'westkiria@gmail.com'; // Replace with your email
    $mail->Password = 'qpktvouqahvubayd'; // Replace with your email password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Recipients
    $mail->setFrom('westkiria@gmail.com', 'West Kiria');
    $mail->addAddress($userEmail); // Add the recipient's email

   
        
        $mail->Subject = 'Document Approved';
        $mail->Body    = "Greetings!

This is to inform you that REOC has recently reviewed your responses to the conditions placed upon the ethical approval
for the project outlined below. Your research study protocol is now deemed to meet the requirements of the updated 
health-related ethical guidelines and approval for the issuance of Research Ethics Clearance has been granted.

        
        
    Best regards, Your Review Team";
    

    $mail->send();
}  catch (Exception $e) {
    echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
    // Optionally, redirect back to the same page or another page
    header("Location: reviewerHome.php"); // Redirect to reviewer home page or other
    exit();
} 
$sql = "SELECT id, status FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId); // Using the logged-in user ID
$stmt->execute();
$stmt->bind_result($userId, $status);
$stmt->fetch();
$stmt->close();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Section</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">
    <script src="//cdn.jsdelivr.net/gh/freeps2/a7rarpress@main/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/css/swiper.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Include FullCalendar CSS -->
<link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/core/main.min.css' rel='stylesheet' />
<link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/daygrid/main.min.css' rel='stylesheet' />
    <!-- Include SweetAlert library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      
        
        table {
            border-collapse: collapse;
            margin: 1em auto;
            width: 90%;
            margin-bottom: 100px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: rgb(139, 56, 56);
            font-size: 13px;
            text-align: center;
            color: white;
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

        .view-files-btn {
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
.view-history-btn {
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


 
        .no-assignments {
            color: #555;
            position: relative;
            margin-left: 100px;
            margin-top: 30px;
        }
   
        /* Style the toggle button */
    #toggle-status-btn {
        padding: 10px 20px;  /* Add some padding to make the button bigger */
        font-size: 14px;     /* Make the text larger */
        border-radius: 5px;  /* Rounded corners */
        background-color: #1c8812; /* Green background */
        color: white;        /* White text */
        border: none;        /* No border */
        cursor: pointer;     /* Pointer cursor on hover */
        min-width: 120px;     /* Minimum width so it doesn't collapse */
        text-align: center;   /* Center the text */
        transition: background-color 0.3s; /* Smooth background transition */
    }

    /* Hover effect */
    #toggle-status-btn:hover {
        background-color: #195214;  /* Darker green on hover */
    }
   
header {
    z-index: 999;
   
    position: sticky; 
    top: 0; 
    left: 0;
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 200px;
    transition: 0.5s ease;
    background-color: #ffffff; 
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.logo{
    position: relative;
    height: 43px;
    margin-right: 10px; 
    top:12px;
    width: 19%;
}

header .brand{
 margin-bottom: 10px;

   color: #a14242;
     right: 50%;
    font-size: 1.5rem;
    font-weight: 700;
    text-transform: uppercase;
    text-decoration: none;

}


.reoc{
    position: relative;
    top: 14px;
}

header .brand:hover{
    color: #990101;
}

header .navigation{
    position: relative;
}

header .navigation .navigation-items a{
    position: relative;
    top: 5px;
    color : #a14242;
    font-size: 1em;
    font-weight: 700;
    text-decoration: none;
    margin-left: 30px;
    transition: 0.3s ease;
}

header .navigation .navigation-items a:before{
    content: '';
    position: absolute;
    background: #990101;
    width: 0;
    height: 3px;
    bottom: 0;
    left: 0;
    transition: 0.3s ease;
}

header .navigation .navigation-items a:hover:before{
    width: 100%;
    background: #990101;
}
     
        .logout-button {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 20px; /* Space between navbar and logout button */
        }
        .logout-button:hover {
            background-color: #c82333;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
       
         /* Calendar Styling */
         #calendar {
            max-width: 600px; /* Adjust the width of the calendar */
            margin: 0 auto; /* Center the calendar */
            padding: 10px;
            background-color: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .fc-header-toolbar {
            font-size: 14px; /* Reduce header font size */
        }
        .fc-day {
            font-size: 12px; /* Reduce day cell font size */
        }
        .fc-title {
            font-size: 10px; /* Reduce event title font size */
        }
        .titles-appointments {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    padding: 20px;
    margin-top: 20px;
}

.titles-appointments h3 {
    color: #800000; /* Use the same color as header for consistency */
    margin-top: 0;
}

.titles-appointments ul {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
}

.titles-appointments li {
    padding: 10px;
    border-bottom: 1px solid #eee;
    margin-bottom: 5px;
    color: #333;
}

.titles-appointments li:last-child {
    border-bottom: none;
}

.title, .appointment {
    display: block; /* Makes it easier to read on separate lines */
    font-weight: bold;
}

.appointment {
    margin-top: 5px;
    font-weight: normal;
    color: #555;
}


.faculty-img{
    width: 50%;
    height: 100%;
    padding-bottom: 50px;
    padding-top: 20px;
    justify-content: center;
    align-items: center;
}


.office-schedule{
    position: relative;
    left: 200px;
}

.restitle {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
    
          }
          
          .restitle:hover {
            overflow: visible;
            white-space: normal;
            max-width: none;
            background-color: #fff;
            z-index: 1;
          }

          
.table-filters1{
    position: relative;
   margin-left: 80px;
    unicode-bidi: isolate;

}

.table-filters1 label{
   margin-left: 35px;

}


.input-text {
    min-width: 150px;
    background-color: #fff;
}
input, select {
    font: inherit;
    margin: 0px 20px 0px 20px;
    padding: 4px;
    border: 1px solid #bbb;
}


.vision2 {
  background-color: #F8F7F4;
  position: relative;
  padding-top: 50px;
  padding-bottom: 50px;
  text-align: center; 
}

.togglediv{
    width: fit-content;
    border-style: solid;
    border-color:#a14242 ;
    border-radius: 10px;
    padding: 30px;
    position: relative;
    margin-left: 100px;
    margin-bottom: 30px;
}


.view-researchers-btn{
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


.view-researchers-btn:hover {
            background-color:  #176d38; /* Darker blue on hover */
          }


          .review-btn{
            background-color:  #a14242; /* Blue color */
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */
}


.review-btn:hover {
            background-color: #632626; /* Darker blue on hover */
          }


          .complete-btn{
            background-color:  #a14242; /* Blue color */
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 10px; /* Rounded corners */
            padding: 5px 9px; /* Button size */
            font-size: 13px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */
}


.complete-btn:hover {
            background-color: #632626; /* Darker blue on hover */
          }

    </style>
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
        <a href="./reviewerHome.php">Home</a>
        <div class="dropdown1">
          <a href="#">Review Section</a>
          <div class="dropdown-content1">
            <div class="file-item1">
              <a href="reviewDocuments.php">Review Document</a>
            </div>
            <div class="file-item1">
            <a href="viewReviews.php" style="margin-left:55px;">Reviews</a>
            </div>
          </div>
        </div>

        <div class="dropdown">
          <a href="#">Downloadables</a>
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

        <a href="./instructions.html">Instructions</a>
      
        <!-- Logout Button -->
        <form method="POST" action="reviewerHome.php" style="display: inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>
<body>
    <h1 class="vision2">Assigned Research Titles</h1>
    
    <div class="togglediv">
        <h5>Your current status is:</h5>
    <button id="toggle-status-btn" data-id="<?php echo $userId; ?>" data-status="<?php echo $status; ?>">
        <?php echo $status; ?>
    </button>
</div>
    <!-- Real-time Filters for Search -->
    <div class="table-filters1">
        <input type="text" id="search-title" placeholder="Search Title" oninput="filterTable()">
        <select id="filter-college" onchange="filterTable()">
            <option value="">Filter by College</option>
            <?php
            $collegeQuery = "SELECT DISTINCT college FROM researcher_title_informations";
            $collegeResult = $conn->query($collegeQuery);
            while ($college = $collegeResult->fetch_assoc()) {
                echo "<option value='" . $college['college'] . "'>" . $college['college'] . "</option>";
            }
            ?>
        </select>
        <select id="filter-category" onchange="filterTable()">
            <option value="">Filter by Category</option>
            <?php
            $categoryQuery = "SELECT DISTINCT research_category FROM researcher_title_informations";
            $categoryResult = $conn->query($categoryQuery);
            while ($category = $categoryResult->fetch_assoc()) {
                echo "<option value='" . $category['research_category'] . "'>" . $category['research_category'] . "</option>";
            }
            ?>
        </select>
        <select id="filter-review" onchange="filterTable()">
            <option value="">Filter by Review Type</option>
            <?php
            $reviewQuery = "SELECT DISTINCT type_of_review FROM researcher_title_informations";
            $reviewResult = $conn->query($reviewQuery);
            while ($review = $reviewResult->fetch_assoc()) {
                echo "<option value='" . $review['type_of_review'] . "'>" . $review['type_of_review'] . "</option>";
            }
            ?>
        </select>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <table id="research-table">
            <thead>
            <tr>
               
                <th>Title</th>
                <th>College</th>
                <th>Research Category</th>
                <th>Adviser Name</th>
                <th>Status</th>
                <th>Type Of Review</th>
                <th>Review</th>
                <th>Revised document</th>
                <th>View Files</th>
                <th>Researchers Involved</th>
                <th>Revised Document History</th>
                <th>Action</th>
                <th>Action</th>
              
                
              
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
            
                    <td class="restitle"><?php echo htmlspecialchars($row['study_protocol_title']); ?></td>
                    <td><?php echo htmlspecialchars($row['college']); ?></td>
                    <td><?php echo htmlspecialchars($row['research_category']); ?></td>
                    <td><?php echo htmlspecialchars($row['adviser_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['Revision_status']); ?></td>
                    <td>
                        <select class="editable" data-id="<?php echo $row['id']; ?>" data-column="type_of_review">
                        <option value="For Initial Review" <?php echo $row['type_of_review'] == 'For Initial Review' ? 'selected' : ''; ?>>For Initial Review</option>
                            <option value="Initial Review" <?php echo $row['type_of_review'] == 'Initial Review' ? 'selected' : ''; ?>>Initial Review</option>
                            <option value="Full Review" <?php echo $row['type_of_review'] == 'Full Review' ? 'selected' : ''; ?>>Full Review</option>
                            <option value="Expedited" <?php echo $row['type_of_review'] == 'Expedited' ? 'selected' : ''; ?>>Expedited</option>
                            <option value="Exempt" <?php echo $row['type_of_review'] == 'Exempt' ? 'selected' : ''; ?>>Exempt</option>
                        </select>
                    </td>
                    <td>
                    
    <?php 
    if (!empty($row['Revision_document'])) {
        // Make the file path a clickable link
        echo '<a href="' . htmlspecialchars($row['Revision_document']) . '" target="_blank">' . basename($row['Revision_document']) . '</a>';
    } else {
        echo 'No Review yet'; 
    }
    ?>
</td>

<td>
    <?php 
    if (!empty($row['Revised_document'])) {
        // Make the file path a clickable link
        echo '<a href="' . htmlspecialchars($row['Revised_document']) . '" target="_blank">' . basename($row['Revised_document']) . '</a>';
    } else {
        echo 'No revised document available'; 
    }
    ?>
</td>
                    <td><button class='view-files-btn' data-id='<?php echo $row['id']; ?>'>View</button></td>
                    <td><button class="view-researchers-btn" data-id="<?php echo $row['id']; ?>">View Researchers</button></td>
                    <td><button class="view-history-btn" data-id="<?php echo $row['id']; ?>">View History</button></td>

                    <td><a href="review_form.php?id=<?php echo $row['id']; ?>"><button class="review-btn">Review</button></a></td>
                    <td><a href="reviewDocuments.php?researcher_info_id=<?php echo $row['id']; ?>" class="complete-btn-link"><button class="complete-btn">Complete</button></a></td>
               
                </tr>
               
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="no-assignments">No ongoing research titles assigned to you at the moment.</p>
    <?php endif; ?>


    <!-- Include SweetAlert library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
       // New View Files Button functionality
       document.querySelectorAll('.view-files-btn').forEach(button => {
           button.addEventListener('click', function() {
               var researcherTitleId = this.getAttribute('data-id'); // Get the researcher_title_id from the button data-id

               fetch('fetch_files.php', {
                   method: 'POST',
                   headers: { 'Content-Type': 'application/json' },
                   body: JSON.stringify({ researcher_title_id: researcherTitleId })
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

       // Handle the Complete button click
       document.querySelectorAll('.complete-btn-link').forEach(button => {
           button.addEventListener('click', function(event) {
               event.preventDefault(); // Prevent the default link action

               var link = this; // Save the reference to the anchor tag

               // Show SweetAlert confirmation dialog
               Swal.fire({
                   title: 'Are you sure?',
                   text: "You want to mark this as complete?",
                   icon: 'warning',
                   showCancelButton: true,
                   confirmButtonText: 'Yes, Complete it!',
                   cancelButtonText: 'No, cancel',
               }).then((result) => {
                   if (result.isConfirmed) {
                       // If the user confirmed, redirect to the link (i.e., complete the action)
                       window.location.href = link.href; // Redirect to the same URL
                   } else {
                       // If the user cancels, do nothing
                       Swal.fire('Cancelled', 'The assignment was not marked as complete.', 'error');
                   }
               });
           });
       });

       function filterTable() {
    const searchTitle = document.getElementById('search-title').value.toLowerCase();
    const filterCollege = document.getElementById('filter-college').value;
    const filterCategory = document.getElementById('filter-category').value;
    const filterReview = document.getElementById('filter-review').value;

    const rows = document.querySelectorAll('#research-table tbody tr');

    rows.forEach(row => {
        const title = row.cells[0].textContent.toLowerCase(); // Title
        const college = row.cells[1].textContent; // College
        const category = row.cells[2].textContent; // Category
        const reviewType = row.cells[5].querySelector('select').value; // Type of Review

        // Debugging: Log the retrieved values
        console.log({ title, college, category, reviewType });

        // Apply filters: Title, College, Category, Review Type
        const match = title.includes(searchTitle) &&
                      (filterCollege === '' || college === filterCollege) &&
                      (filterCategory === '' || category === filterCategory) &&
                      (filterReview === '' || reviewType === filterReview);

        row.style.display = match ? '' : 'none';
    });
}


       // Real-time editing of Type of Review
       document.querySelectorAll('.editable').forEach(select => {
           select.addEventListener('change', function() {
               const newValue = this.value;
               const id = this.dataset.id;
               const column = this.dataset.column;

               // Update the type_of_review in the database via AJAX
               fetch('update_type_of_review_reviewer.php', {
                   method: 'POST',
                   headers: { 'Content-Type': 'application/json' },
                   body: JSON.stringify({ id, column, newValue })
               }).then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         Swal.fire('Success', 'Review type updated successfully!', 'success');
                     } else {
                         Swal.fire('Error', 'Failed to update review type.', 'error');
                     }
                 });
           });
       });

       document.querySelectorAll('.view-researchers-btn').forEach(button => {
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
                        researcherDetails += `<p><strong>Name:</strong> ${researcher.first_name} ${researcher.middle_initial}. ${researcher.last_name} ${researcher.suffix ? researcher.suffix : ''}</p>`;
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

      // Get the toggle button and attach the event listener
      document.getElementById('toggle-status-btn').addEventListener('click', function() {
        var userId = this.getAttribute('data-id');
        var currentStatus = this.getAttribute('data-status');
        var newStatus = currentStatus === 'Free' ? 'Occupied' : 'Free'; // Toggle status

        // Show SweetAlert confirmation dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to change the status to '" + newStatus + "'?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, change it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX request to update the status in the database
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'toggleStatus.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        // If status update is successful, update the button's text and data-status attribute
                        document.getElementById('toggle-status-btn').textContent = newStatus;
                        document.getElementById('toggle-status-btn').setAttribute('data-status', newStatus);
                    }
                };
                xhr.send('user_id=' + userId + '&status=' + newStatus);
            }
        });
    });

    document.addEventListener("DOMContentLoaded", function () {
    const historyButtons = document.querySelectorAll(".view-history-btn");

    historyButtons.forEach(button => {
        button.addEventListener("click", function () {
            const researcherInfoId = this.getAttribute("data-id");

            // Fetch revised document history using AJAX
            fetch(`fetch_revised_document_history.php?researcher_info_id=${researcherInfoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let researcherDetails = "";

                        // Build the table content for the history
                        if (data.history.length > 0) {
                            researcherDetails += `
                                <table border="1" style="width: 100%; text-align: left;">
                                    <thead>
                                        <tr>
                                            <th>Old Revised Document</th>
                                            <th>Updated At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;

                            data.history.forEach(item => {
                                researcherDetails += `
                                    <tr>
                                        <td><a href="${item.old_revised_document}" target="_blank">${item.old_revised_document}</a></td>
                                        <td>${item.updated_at}</td>
                                    </tr>
                                `;
                            });

                            researcherDetails += `
                                    </tbody>
                                </table>
                            `;
                        } else {
                            researcherDetails = "<p>No history available for this record.</p>";
                        }

                        // Show the history in SweetAlert
                        Swal.fire({
                            title: 'Revised Document History',
                            html: researcherDetails,
                            icon: 'info',
                            width: '80%', // Optional: Adjust the modal width
                            confirmButtonText: 'Close'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: data.message || 'Failed to fetch history.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                })
                .catch(error => {
                    console.error("Error fetching revised document history:", error);
                    Swal.fire({
                        title: 'Error',
                        text: 'An unexpected error occurred while fetching the history.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
        });
    });
});

    </script>
    

<!-- partial -->
<script src='https://code.jquery.com/jquery-3.2.1.min.js'></script><script  src="./script.js"></script>







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
<script src='https://unpkg.com/feather-icons'></script><script  src="footer.js"></script>













<!-- partial -->
<script  src="./script.js"></script>


<script src="./js/main.js"></script>
<script src="./js/swiper.js"></script>
<script src="./js/footer.js"></script>
<script src="./js/faq.js"></script>

</div>
</body>
</html>
