
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

    <style>

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

.section1 {
    display: flex;
    background-image: url("img/REOCBG2.jpg");
    justify-content: center;
    background-size: cover; /* Ensures the image covers the entire section */
    background-position: center; /* Centers the image */
    background-repeat: no-repeat; /* Prevents the image from repeating */
    height: 100vh; /* Optional: Full screen height */
    align-items: center; 
    height: 100vh; 
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
    
      
        <!-- Logout Button -->
        
  <button type="submit" name="signup" class="logout-button">
    <a href="Signup.php" >Sign Up</a>
</button>

      </div>
    </div>
  </div>
</header>
  
</div>

<section class="home">
      <div class="gradient"></div>
        <img decoding="async" class="img-slide active" src="./img/reocpic.jpg" ></img>
        <img decoding="async" class="img-slide" src="./img/wmsu2.jpg" ></img>
        <img decoding="async" class="img-slide" src="./img/wmsu1.jpg" ></img>
        <img decoding="async" class="img-slide" src="./img/wmsu5.jpg" ></img>
        <img decoding="async" class="img-slide" src="./img/wmsu1.jpg" ></img>


        <div class="content active">
            <h1>Best in Service<br></h1>
            <p>The Research Ethics Oversight Committee (REOC) offers the highest standard of service in Mindanao, ensuring that all research activities adhere to ethical principles and guidelines. With a commitment to safeguarding the rights and welfare of research participants, the REOC provides comprehensive review processes, expert guidance, and timely support. Their dedication to upholding ethical integrity in research has established them as a trusted authority, making them the go-to committee for researchers seeking ethical approval in Mindanao.</p>
          <a href="login.php" style="    transition: 0.3s ease;">Create Account</a>
        </div>
        <div class="content">
          <h1>Ethical Excellence Guaranteed<br></h1>
          <p>The Research Ethics Oversight Committee (REOC) at Western Mindanao State University (WMSU) provides the best ethical review services in Mindanao. As a leading institution, WMSU ensures that all research projects meet the highest ethical standards, safeguarding participants’ rights and promoting responsible research. Through rigorous evaluation and expert guidance, REOC at WMSU supports researchers by providing swift, transparent, and thorough reviews, positioning the university as a pillar of ethical integrity in the region.</p>
          <a href="superUsersignup.php" style="    transition: 0.3s ease;">Create Admin Account</a>
        </div>
        <div class="content">
          <h1>High Standards for Research Ethics<br></h1>
          <p>WMSU REOC has been granted Level 2 Accreditation by the Philippine Health Research Ethics Board (PHREB). This Level 2 accreditation is a testament to the committee's dedication and commitment to upholding the highest standards of research ethics. It empowers WMSU REOC to conduct thorough research reviews across all research categories, except clinical trials.</p>
        
        </div>
       
        <div class="slider-navigation">
            <div class="nav-btn active"></div>
            <div class="nav-btn"></div>
            <div class="nav-btn"></div>
        
    </section>


<!-- partial -->
<script  src="./script.js"></script>


<script src="./js/main.js"></script>
<script src="./js/swiper.js"></script>
<script src="./js/footer.js"></script>
<script src="./js/faq.js"></script>

</div>


