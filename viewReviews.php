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

require_once 'dbConnCode.php'; // Include your database connection file


// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true); // Regenerate session ID on first visit
}

// Ensure the user is logged in and `user_id` is set
if (!isset($_SESSION['user_id'])) {
    die("Access denied. User not logged in.");
}

$specificId = $_SESSION['user_id']; // Use the session's user ID

// Query to get the count of distinct users with "Complete" status for the session user ID
$sqlCount = "SELECT COUNT(DISTINCT ar.user_id) AS complete_user_count 
             FROM assign_reviewer ar 
             WHERE ar.status = 'Complete' AND ar.user_id = ?";

// Query to get distinct user data and associated researcher data for the session user ID
$sql = "SELECT DISTINCT ar.user_id, 
               rti.id AS researcher_info_id,
               rti.study_protocol_title, 
               rti.college, 
               rti.research_category, 
               rti.adviser_name, 
               rti.uploaded_at
        FROM assign_reviewer ar
        JOIN researcher_title_informations rti ON ar.researcher_info_id = rti.id
        WHERE ar.status = 'Complete' AND ar.user_id = ?";

try {
    // Prepare and execute the count query
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->bind_param("i", $specificId); // Bind the session user ID
    $stmtCount->execute();
    $resultCount = $stmtCount->get_result();

    $completeUserCount = 0; // Default count is 0
    if ($resultCount->num_rows > 0) {
        $rowCount = $resultCount->fetch_assoc();
        $completeUserCount = $rowCount['complete_user_count'];
    }

    // Prepare and execute the main query for research data
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $specificId); // Bind the session user ID
    $stmt->execute();
    $result = $stmt->get_result();

    $researchData = []; // Array to hold researcher data

    // Fetch all the research data
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $researchData[] = $row;
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Now you can use $completeUserCount and $researchData as needed


$stmt->close();

// Query to get unique values for dynamic dropdowns
$categoryQuery = "SELECT DISTINCT research_category FROM researcher_title_informations";
$categoryResult = $conn->query($categoryQuery);

$collegeQuery = "SELECT DISTINCT college FROM researcher_title_informations";
$collegeResult = $conn->query($collegeQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewed Studies</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">
    <script src="//cdn.jsdelivr.net/gh/freeps2/a7rarpress@main/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/swiper.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">

    <!-- Include SweetAlert library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    
   
        .containerrr {
    position: relative;
    width: 100%;

    border-style: 2px ridge #333;
    padding: 20px;
    background-color: #F8F7F4;
    text-align: center;
}
        .count-box {
            background-color: rgb(139, 56, 56);
            color: white;
            position: relative;
            padding: 20px;
            font-size: 15px;
            text-align: center;
            border-radius: 8px;
            top: -30px;
            width: fit-content;
            margin-left: 100px;
        }
        .count-box span {
            font-weight: bold;
            font-size: 15px;
        }
        .research-table {
            border-collapse: collapse;
            margin: 1em auto;
            width: 90%;
            margin-bottom: 100px;
        }
  
    
        .footer {
            text-align: center;
            margin-top: 50px;
            font-size: 1rem;
            color: #888;
        }
        .filter-bar {
            margin: 20px 0;
        }
        .filter-bar input, .filter-bar select {
            padding: 8px;
            margin-right: 10px;
            font-size: 1rem;
        }
        .filter-bar input[type="text"] {
            width: 250px;
        }

        
        
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


.view-files-btn:hover {
            background-color:  #176d38; /* Darker blue on hover */
          }


 
        .no-assignments {
            color: #555;
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
   margin-left: -700px;
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
  padding-top: 70px;
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

    <div class="containerrr">
        <h1 class="vision2">Completed Users and Their Research Titles</h1>
        
        <div class="count-box">
            <p>Total Reviewed Documents: <span><?php echo $completeUserCount; ?></span></p>
        </div>

        <!-- Real-time Search and Dropdown Filters -->
        <div class="table-filters1">
            <input type="text" id="search-title" placeholder="Search by Title" oninput="filterTable()">
            <select id="filter-college" onchange="filterTable()">
                <option value="">Filter by College</option>
                <?php while ($college = $collegeResult->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($college['college']); ?>"><?php echo htmlspecialchars($college['college']); ?></option>
                <?php endwhile; ?>
            </select>
            <select id="filter-category" onchange="filterTable()">
                <option value="">Filter by Research Category</option>
                <?php while ($category = $categoryResult->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($category['research_category']); ?>"><?php echo htmlspecialchars($category['research_category']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <?php if (count($researchData) > 0): ?>
            <table class="research-table" id="research-table">
                <thead>
                    <tr>
                        <th>Researcher Title</th>
                        <th>College</th>
                        <th>Research Category</th>
                        <th>Adviser Name</th>
                        <th>Upload Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($researchData as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['study_protocol_title']); ?></td>
                            <td><?php echo htmlspecialchars($item['college']); ?></td>
                            <td><?php echo htmlspecialchars($item['research_category']); ?></td>
                            <td><?php echo htmlspecialchars($item['adviser_name']); ?></td>
                            <td><?php echo date("F d, Y", strtotime($item['uploaded_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No completed research titles found.</p>
        <?php endif; ?>
    </div>

    <div class="footer">
        
    </div>

    <script>
        // Real-time Search and Filtering functionality
        function filterTable() {
            const searchTitle = document.getElementById('search-title').value.toLowerCase();
            const filterCollege = document.getElementById('filter-college').value;
            const filterCategory = document.getElementById('filter-category').value;

            const rows = document.querySelectorAll('#research-table tbody tr');

            rows.forEach(row => {
                const title = row.cells[0].textContent.toLowerCase();
                const college = row.cells[1].textContent;
                const category = row.cells[2].textContent;

                // Apply filters: Title, Category, College
                const match = title.includes(searchTitle) &&
                              (filterCollege === '' || college === filterCollege) &&
                              (filterCategory === '' || category === filterCategory);

                row.style.display = match ? '' : 'none';
            });
        }
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

</body>
</html>
