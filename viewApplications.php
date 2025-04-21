<?php
session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true); // Regenerate session id on first visit
}

// Check if the user is logged in and if their role is 'Researcher'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Researcher') {
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
require_once 'dbConnCode.php';
// SQL query to fetch all Vision, Mission, and Goal
$sql_vm = "SELECT * FROM vision_mission ORDER BY FIELD(statement_type, 'Vision', 'Mission', 'Goals'), last_updated DESC";
$result_vm = $conn->query($sql_vm);

// Initialize variables
$vision = '';
$mission = '';
$goals = ''; // Single goal variable

// Check if data is available
if ($result_vm->num_rows > 0) {
    // Loop through the result set
    while ($row = $result_vm->fetch_assoc()) {
        // Categorize the content based on the statement type
        if ($row['statement_type'] == 'Vision') {
            $vision = $row['content'];
        } elseif ($row['statement_type'] == 'Mission') {
            $mission = $row['content'];
        } elseif ($row['statement_type'] == 'Goals') {
            $goals = $row['content']; // Store only the first goal found
        }
    }
} else {
    // Default messages if no data is found
    $vision = 'No Vision statement found.';
    $mission = 'No Mission statement found.';
    $goals = 'No Goal has been defined yet.';
}

// Fetch Researcher titles and appointments
function getResearchTitlesAndAppointments($userId) {
  global $conn;

  $sql = "SELECT rt.id, rt.study_protocol_title, a.appointment_date, rt.Revision_document, rt.Revision_Upload_button, 
  rt.type_of_review, rt.Revision_status
FROM researcher_title_informations rt
JOIN appointments a ON rt.id = a.researcher_title_id
WHERE rt.user_id = ?";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $result = $stmt->get_result();

  $titlesAndAppointments = [];
  while ($row = $result->fetch_assoc()) {
      $titlesAndAppointments[] = $row;
  }
  $stmt->close();

  return $titlesAndAppointments;
}
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REOC PORTAL</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">
    <script src="//cdn.jsdelivr.net/gh/freeps2/a7rarpress@main/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/swiper.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Include FullCalendar CSS -->
<link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/core/main.min.css' rel='stylesheet' />
<link href='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/daygrid/main.min.css' rel='stylesheet' />

<!-- Include FullCalendar JS -->
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/core/main.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/daygrid/main.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/interaction/main.min.js'></script>
<!--===============================================================================================-->
<body>
	
<style>

body {
  background: none; /* Remove the direct background image */
  position: relative; /* Ensure the pseudo-element is positioned correctly */
}

body::before {
  content: '';
  position: fixed; /* Cover the entire viewport */
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #F8F7F4;
  background: url('img/REOCBG3.jpg') no-repeat center center;
  background-size: contain; /* Ensures the image covers the entire viewport */
  opacity: 0.5; /* Lower the opacity of the background image */
  z-index: -1; /* Place it behind other elements */
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

.footer{
  background-color: #F8F7F4;
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
          position: relative;
         margin-left: 550px;
            flex: 1;
            padding: 20px;


          
  justify-content: center;   /* Centers horizontally */
  align-items: center;       /* Centers vertically */
    
        }
       
         /* Calendar Styling */
         #calendar {
          position: relative;
        
            max-width: 600px; /* Adjust the width of the calendar */
          
            padding: 10px;
            margin-top: 30px;
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
          width: fit-content;
       
    background-color:rgba(255, 255, 255, 0);;
    border-radius: 8px;
  
    padding: 20px ;
    margin-top: 20px;
    position: relative;
    left: -170px;
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
  position: relative;
 
  background-color: rgb(143, 142, 142);
  border-style: solid;
  border-color: rgb(143, 142, 142);
  border-radius: 10px;
  width: 200px;
  padding: 10px;
    margin-top: -40px;
    font-weight: bold;
    font-size: 19px;
    
    color: white;
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


.vision {
    background-color:rgba(248, 247, 244, 0);
    position: relative;
    padding-top: 100px;
    left: -270px;
    text-align: center;
}

.schedbtn {
position: relative;
margin-left: -230px;
  background-color: #a14242; /* Green background */
  color: white;             /* White text */
  font-size: 16px;          /* Adjust font size */
  padding: 10px 10px;       /* Padding for size */
  margin-top: 50px;
  border: none;             /* Remove default border */
  border-radius: 5px;       /* Rounded corners */
  cursor: pointer;          /* Pointer cursor on hover */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
  transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transitions */
}

.schedbtn:hover {
  background-color: #812b2c;

  transform: scale(1.05);    /* Slightly larger on hover */
}

.subbtn{
  position: relative;

  background-color: #a14242; /* Green background */
  color: white;             /* White text */
  font-size: 12px;          /* Adjust font size */
  padding: 5px 10px;       /* Padding for size */

  border: none;             /* Remove default border */
  border-radius: 5px;       /* Rounded corners */
  cursor: pointer;          /* Pointer cursor on hover */
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow */
  transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transitions */
}

.subbtn:hover {
  background-color: #812b2c;

  transform: scale(1.05);    /* Slightly larger on hover */
}

.title{
  width: 820px; /* Set a specific width */
 
  display: block;
    white-space: nowrap; /* Prevent text from wrapping */
    overflow: hidden; /* Hide the overflow */
    text-overflow: ellipsis; /* Add ellipsis when the text overflows */
}

.revision-link {
  position: relative;
       background-color: #a14242; /* Green background */
       color: white; /* White text */
 top:10px;
       text-decoration: none; /* Remove underline */
       padding: 5px 10px; /* Smaller padding for a more compact button */
    
       border-radius: 5px;  
       display: inline-block; /* Make it behave like a button */
       font-size: 12px; /* Smaller font size */
       position: relative;
   
       left: -15px;
       transition: background-color 0.3s ease, transform 0.3s ease; /* Add transition for hover effects */
   }

   .revision-link:hover {
       background-color:#812b2c; /* Darker green when hovered */
       color: #fff; /* White text when hovered */
       transform: scale(1.05); /* Slightly enlarge the link on hover */
   }

   .revision-link:active {
       background-color:#812b2c; /* Even darker green on click */
       transform: scale(1); /* Reset the scale */
   }





.research-container {
  width: 1080px;
  height: 130px;
  margin-left: -80px;
    margin-bottom: 100px;
    padding: 15px;
    border: 1px solid rgba(56, 142, 60, 0);
    border-radius: 10px;
    background-color:rgb(224, 224, 224);
}
.research-container h4 {
    margin-bottom: 10px;
}

.status {
  position: relative;
  left: 1080px;
  width: 165px;
  height: 130px;
  margin-top: -103px;
    background-color: #E0E0E0;
    color: black;
    padding: 15px;
    border-radius: 10px;
    position: relative;
    font-size: 20px;
   
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
        <a href="researcherHome.php">Home</a>
        <div class="dropdown1">
          <a href="#">Applications</a>
          <div class="dropdown-content1">
            <div class="file-item1">
              <a href="SubmitFiles.php">Submit Application</a>
            </div>
            <div class="file-item1">
              <a href="viewApplications.php">View Applications</a>
            </div>
          </div>
        </div>

        <div class="dropdown">
          <a href="#">Downloadables</a>
          <div class="dropdown-content">
            <div class="file-item">
              <span><strong>Application Form (WMSU-REOC-FR-001)</strong></span>
              <a href="public_html/files/2-FR.002-Application-Form.doc" download>Download</a>
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
        <form method="POST" action="researcherHome.php" style="display: inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
      </div>
    </div>
  </div>
</header>
  
</div>


  
  

<!-- Main Content -->
<div class="main-content">
<h1 class="vision"> Appointment Schedule</h1>
   
    <button class="schedbtn" id="rescheduleButton" data-user-id="<?php echo htmlspecialchars($user_id); ?>">Reschedule Appointment</button>
    
<div id='calendar'></div>

  <!-- Display research titles and appointments -->
<?php
$titlesAndAppointments = getResearchTitlesAndAppointments($_SESSION['user_id']);
if (!empty($titlesAndAppointments)) {
    echo "<div class='titles-appointments'>";
    echo "<h3 style=' position:relative; left:-80px; bottom:30px;'>Your Research Titles and Appointments:</h3>";
    foreach ($titlesAndAppointments as $item) {
        echo "<div class='research-container'>"; // New container for each research item

        // Display research title and appointment
        echo "<h4 class='title' style=' position: relative; margin-left:220px; top:30px;'>" . htmlspecialchars($item['study_protocol_title']) . "</h4>";
        echo "<p class='appointment'>Appointment on: " . date("F d, Y", strtotime($item['appointment_date'])) . "</p>";

        // Display status (type_of_review and Revision_status)
        echo "<p class='status'>Status:<br> <strong> " . htmlspecialchars ($item ['type_of_review' ]) ;
        if ($item['Revision_status'] !== 'None') {
            echo " | " . htmlspecialchars($item['Revision_status']);
        }
        echo " </strong> </p>";

        // Provide the downloadable link for the revision document
        if ($item['Revision_Upload_button'] == 'Yes' && !empty($item['Revision_document'])) {
            echo "<p><a href='" . htmlspecialchars($item['Revision_document']) . "' target='_blank' class='revision-link'>View Recommendations</a></p>";
        }

        // Display upload button
        if (!empty($item['Revision_document']) && $item['Revision_Upload_button'] == 'Yes') {
            echo "<form style='margin-top:-20px; left:-100px' method='POST' action='upload_revision.php' enctype='multipart/form-data'>
                    <input type='file' name='revision_file' accept='.pdf' required style=' position:relative; left:330px; top:5px;'>
                    <input type='hidden' name='researcher_title_id' value='" . $item['id'] . "'>
                    <input class='subbtn' type='submit' value='Upload Revision' style=' position:relative; left:-100px; top:3px;'>
                  </form>";
        }

        echo "</div>"; // Close research container
    }
    echo "</div>";
} else {
    echo "<p>No ongoing research titles assigned to you at the moment.</p>";
}
?>
</div>
  </d>




<script>
document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    calendarEl.style.display = 'none'; // Initially hide the calendar

    let unavailableDates = []; // Unavailable dates
    let pendingDates = []; // Pending appointment dates

    const calendar = new FullCalendar.Calendar(calendarEl, {
        plugins: ['interaction', 'dayGrid'],
        defaultView: 'dayGridMonth',
        validRange: {
            start: new Date() // Prevent selecting past dates
        },
        businessHours: {
            daysOfWeek: [1, 2, 3, 4, 5] // Monday to Friday
        },
        dateClick: function (info) {
            const clickedDate = new Date(info.dateStr);
            const dayOfWeek = clickedDate.getDay(); // 0 = Sunday, 6 = Saturday

            if (dayOfWeek === 0 || dayOfWeek === 6) {
                Swal.fire('Unavailable!', 'Weekends are not available for scheduling.', 'error');
                return;
            }

            if (unavailableDates.includes(info.dateStr)) {
                Swal.fire('Unavailable!', 'You cannot select this date as it is unavailable.', 'error');
            } else {
                rescheduleAppointment(info.dateStr);
            }
        }
    });

    document.getElementById('rescheduleButton').addEventListener('click', function () {
        const isDisplayed = calendarEl.style.display;
        calendarEl.style.display = isDisplayed === 'block' ? 'none' : 'block';

        if (calendarEl.style.display === 'block') {
            fetch('getUnavailableDates.php')
                .then(response => response.json())
                .then(data => {
                    unavailableDates = Array.isArray(data.unavailableDates) ? data.unavailableDates : [];
                    
                    calendar.removeAllEvents();

                    // Mark unavailable dates as background events
                    unavailableDates.forEach(date => {
                        calendar.addEvent({
                            start: date,
                            allDay: true,
                            rendering: 'background',
                            color: '#ff9f89' // Highlight unavailable dates
                        });
                    });

                    // Fetch pending appointments
                    return fetch('getPendingAppointments.php');
                })
                .then(response => response.json())
                .then(data => {
                    pendingDates = Array.isArray(data.pendingDates) ? data.pendingDates : [];

                    // Mark pending appointment dates in green with a professional message
                    pendingDates.forEach(date => {
                        calendar.addEvent({
                            start: date,
                            allDay: true,
                            rendering: 'background',
                            color: '#90EE90', // Highlight pending dates in green
                            title: 'Your appointment is scheduled on this day.' // Add a professional tooltip
                        });
                    });

                    calendar.render();
                })
                .catch(error => {
                    console.error('Error fetching dates:', error);
                });
        }
    });

    calendar.render();

    function rescheduleAppointment(newDate) {
        Swal.fire({
            title: 'Confirm Rescheduling',
            text: `Reschedule your appointment to ${newDate}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, reschedule it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const userId = document.getElementById('rescheduleButton').getAttribute('data-user-id');

                fetch('rescheduleAppointment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `newDate=${encodeURIComponent(newDate)}&userId=${encodeURIComponent(userId)}&csrf_token=${encodeURIComponent('<?php echo $_SESSION['csrf_token']; ?>')}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Rescheduled!', 'Your appointment has been rescheduled.', 'success').then(() => {
                                // Refresh calendar events
                                window.location.href = 'researcherHome.php';
                                calendar.refetchEvents();
                            });
                        } else {
                            Swal.fire('Error!', data.message || 'Could not reschedule. Please try again.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error!', 'A network or server error occurred.', 'error');
                    });
            }
        });
    }
});

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
	
	  <script src="./js/footer.js"></script>
	  
	

	<script src="./js/fonts.js"></script>
  
  

</body>
</html>