<?php
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

// Get the researcher ID from the URL
if (!isset($_GET['id'])) {
    die("No researcher ID provided.");
}

$researcherTitleId = $_GET['id'];

// Connect to the database
require_once('dbConnCode.php');  // Assuming this file handles your database connection

// Fetch the research title from the database based on the researcher ID
$sql = "SELECT study_protocol_title FROM researcher_title_informations WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $researcherTitleId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($studyProtocolTitle);

if ($stmt->fetch()) {
    $title = $studyProtocolTitle;
} else {
    $title = "Title not found.";
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviewer Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css">
    <script src="//cdn.jsdelivr.net/gh/freeps2/a7rarpress@main/swiper-bundle.min.js"></script>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="./css/swiper.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    background: url('img/REOCBG33.jpg') no-repeat center center;
    background-size: contain; /* Ensures the image covers the entire viewport */
    opacity: 0.5; /* Lower the opacity of the background image */
    z-index: -1; /* Place it behind other elements */
  }

        .container1 {
    width: 50%;
    margin: 50px auto;
    padding: 60px 70px;
    background-color:rgba(189, 189, 189, 0.58);
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
        h1 {
            color: #333;
        }

      
        h3 {
            margin-bottom: 10px;
            color: #444;
        }

        label {
            font-weight: bold;
        }

        textarea {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            height: 150px;  /* Increased height to display more characters */
        }

        .textarea-container {
            margin-bottom: 15px;
        }

        .textarea-container span {
            font-size: 12px;
            color: #999;
        }

        .radio-buttons {
            margin-bottom: 20px;
        }

        input[type="radio"] {
            margin-right: 10px;
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

        .form-footer {
            text-align: center;
            margin-top: 20px;
        }

        .research-title {
            font-size: 18px;
        
            color: #333;
            position: relative;
            margin-left:480px;
        }
        .vision2 {
  background-color:rgba(248, 247, 244, 0);
  position: relative;
  padding-top: 50px;
  padding-bottom: 50px;
  text-align: center; 
}

.submitbtn{
    background-color:  #a14242; /* Blue color */
            color: white; /* Text color */
            border: none; /* Remove border */
            border-radius: 16px; /* Rounded corners */
            padding: 7px 11px; /* Button size */
            font-size: 20px; /* Font size */
            cursor: pointer; /* Pointer cursor on hover */
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Optional shadow */
            transition: background-color 0.3s ease; /* Smooth hover effect */
}


.submitbtn:hover {
            background-color: #632626; /* Darker blue on hover */
          }

footer{
  background-color: #F8F7F4;
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
        <a href="/reviewerHome.php">Home</a>
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

    <h1 class="vision2">Reviewer Form</h1>

    <!-- Display the Research Title -->
    <div class="research-title">
        <strong>Research Title:</strong> <?php echo htmlspecialchars($title); ?>
    </div>

    <form action="generate_review_pdf.php" method="POST" class="container1">
        <input type="hidden" name="researcher_title_id" value="<?php echo htmlspecialchars($researcherTitleId); ?>">

        <!-- Recommended Action Section -->
        <div class="radio-buttons">
            <h3>Recommended Action:</h3>
            <label>
                <input type="radio" name="recommended_action" value="Qualified for Certification" required>
                Qualified for Certification
            </label><br>
            <label>
                <input type="radio" name="recommended_action" value="Not Qualified for Certification" required>
                Not Qualified for Certification
            </label><br><br>
        </div>

        <!-- Summary of Recommendations Section -->
        <h3>Summary of Recommendations:</h3>
        <ol>
            <?php 
            // Create five recommendations with character limit
            for ($i = 1; $i <= 5; $i++) {
              // Set the label for each chapter dynamically (Chapter 1 to Chapter 5)
              $chapter_label = "Chapter " . $i;
      
              // Generate the textarea for each chapter with a max length of 2000 characters per chapter
              echo '<li class="textarea-container">
                      <label>' . $chapter_label . ':</label>
                      <span id="recommendation_' . $i . '_char_count">0/2500 </span>
                      <textarea name="recommendation_' . $i . '" rows="5" maxlength="2500" placeholder="Enter ' . $chapter_label . ' (max 2500 characters)"></textarea>
                    </li>';
          }
          ?>
           
        </ol>

        <br>
        <div class="form-footer">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button  class="submitbtn" type="submit">Submit Review</button>
        </div>
    </form>

    <script>
        // Update character count for each textarea
        document.querySelectorAll('textarea').forEach(function(textarea) {
            textarea.addEventListener('input', function() {
                var charCount = textarea.value.length;
                var maxLength = textarea.getAttribute('maxlength');
                var charCountDisplay = document.getElementById(textarea.name + '_char_count');
                charCountDisplay.textContent = charCount + '/' + maxLength;
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

</body>
</html>
