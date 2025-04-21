<?php
session_start();

// Check if the user is logged in and if their role is 'Admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Start CSRF token generation if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'dbConnCode.php';

// Initialize a message variable for SweetAlert
$message = null;

// Handle combined form submission for updating both tables
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Toggle application status
    if (isset($_POST['toggle_status'])) {
        $status = $_POST['current_status'] === 'open' ? 'closed' : 'open';

        $stmt = $conn->prepare("UPDATE application_status SET status = ? WHERE id = 1");
        $stmt->bind_param("s", $status);
        $stmt->execute();

        $message = $status === 'open' ? "Application is now open." : "Application is now closed.";
    }

    // Update research codes
    if (isset($_POST['research_codes'])) {
        foreach ($_POST['research_codes'] as $id => $data) {
            $codeAcronym = htmlspecialchars($data['code_acronym']);
            $codeNumber = intval($data['code_number']);

            $stmt = $conn->prepare("UPDATE research_codes SET code_acronym = ?, code_number = ? WHERE id = ?");
            $stmt->bind_param("sii", $codeAcronym, $codeNumber, $id);
            $stmt->execute();
        }
    }

    // Update dynamic data
    if (isset($_POST['dynamic_data'])) {
        foreach ($_POST['dynamic_data'] as $id => $data) {
            $certificateVersion = htmlspecialchars($data['certificate_version']);
            $dateEffective = htmlspecialchars($data['date_effective']);
            $letCode = htmlspecialchars($data['let_code']);
            $signature = htmlspecialchars($data['Signature']);
            $appointmentCapacity = intval($data['appointment_capacity']); // Ensure it's an integer
    

            $stmt = $conn->prepare("
            UPDATE reoc_dynamic_data 
            SET certificate_version = ?, date_effective = ?, let_code = ?, Signature = ?, appointment_capacity = ? 
            WHERE id = ?
        ");
        $stmt->bind_param("sssssi", $certificateVersion, $dateEffective, $letCode, $signature, $appointmentCapacity, $id);
        $stmt->execute();
        }
    }

    $message = "Data updated successfully.";
}
// Check if the application status exists, if not, insert default value
$result = $conn->query("SELECT * FROM application_status WHERE id = 1");

if ($result && $result->num_rows > 0) {
    // If data exists, fetch the status
    $status = $result->fetch_assoc()['status'];
} else {
    // If no data exists, insert a default record with 'open' status
    $insertQuery = "INSERT INTO application_status (id, status) VALUES (1, 'open')";
    if ($conn->query($insertQuery) === TRUE) {
        // After inserting, fetch the newly inserted status
        $status = 'open';
    } else {
        // Handle any error with the insertion
        $status = 'error';  // You can set an error status or handle it differently
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Manage Datas</title>
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
    
    
   
    <style>
         html{
            overflow-x: hidden;
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


        .charts-container {
    display: flex;
    justify-content: space-around; /* Evenly distribute charts */
    gap:40px; /* Space between the charts */
    flex-wrap: wrap; /* Wrap if screen is too small */
    align-items: flex-start; /* Align items at the top */
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
     
        h3 {
            position: relative;
            left: 300px;
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
        #vmForm {
        display: none; /* Initially hidden */
        position: fixed; /* Position it fixed in the viewport */
        top: 50%; /* Center vertically */
        left: 50%; /* Center horizontally */
        transform: translate(-50%, -50%); /* Adjust for centering */
        background-color: #fff; /* White background */
        padding: 20px; /* Padding for the form */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow for depth */
        border-radius: 8px; /* Rounded corners */
        z-index: 1000; /* Ensure it appears above other elements */
        width: 90%; /* Responsive width */
        max-width: 500px; /* Max width for larger screens */
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


        .modal {
		display: none ;
		position: fixed;
		top: 350%;
		left: 500px;
		width: 100%;
		height: 100%;
		
		display: flex;
		justify-content: center;
		align-items: center;
	  }
	  .modal-content {
		background: white;
		padding: 20px;
		border-radius: 8px;
		text-align: center;
		width: 500px;
		height: auto;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
	
	  }
	  .modal-content img {
		object-fit: contain;
		max-width: 100%;
		max-height: 200px;
		margin-bottom: 15px;
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
			padding: 10px 20px; font-size: 16px; cursor: pointer;    background-color: #aa3636; /* Blue color */
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
    top: 320px;
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
  background-color: #F8F7F4;
  position: relative;
  padding-top: 10px;
  padding-bottom: 50px;
  text-align: center; 
  margin-top:20px;
}



        .main-content {
            flex: 1;
            padding: 20px;
        }

        .status-toggle-btn {
            position: relative;
            margin-left: 300px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }

        .status-toggle-btn:hover {
            background-color: #0056b3;
        }



        
        table {
            border-collapse: collapse;
            margin: 1em auto;
            width: 70%;
            margin-bottom: 100px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }

        th,  {
            background-color: rgb(139, 56, 56);
            width: 50rem;
            font-size: 13px;
            text-align: center;
            color: white;
            padding: 8px;
            border: 1px solid #ccc;
            text-align: left;
        }
      
 
td{
    background-color: white;
}
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .form-container {
            position: relative;
            margin-left:300px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .form-container input {
            padding: 10px;
            margin-right: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container button {
            padding: 10px 15px;
            background-color: #800000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-container button:hover {
            background-color: #dc3545;
        }

        .actions button {
            padding: 5px 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .actions button:hover {
            background-color: #218838;
        }

        .actions a {
            color: #dc3545;
            text-decoration: none;
            margin-left: 10px;
        }

        .actions a:hover {
            text-decoration: underline;
        }
        .college-input {
            width: 300px; /* Adjust this value to the width you need */
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .vision2 {
  background-color: #F8F7F4;
  position: relative;
  padding-top: 10px;
  padding-bottom: 50px;
  text-align: center; 
  margin-top:20px;
}


.saveall{
			padding: 10px 20px; font-size: 16px; cursor: pointer;    background-color: #aa3636; /* Blue color */
          color: white; /* Text color */
          border: none; /* Remove border */
          border-radius: 10px; /* Rounded corners */
          padding: 5px 9px; /* Button size */
          font-size: 16px; /* Font size */
		  transition: background-color 0.3s;
          position: relative;
          left: 850px;

	
	}

	.saveall:hover {
		background-color: #802c2c;

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


    <div class="main-content">
    <h1 class="vision2">Manage Datas</h1>

        <!-- Status Toggle Section -->
        <?php
        $result = $conn->query("SELECT * FROM application_status WHERE id = 1");
        $status = $result->fetch_assoc()['status'];
        ?>
        <form method="POST" >
            <input type="hidden" name="current_status" value="<?php echo $status; ?>">
            <button type="submit" name="toggle_status" class="status-toggle-btn">
                Application is currently: <?php echo ucfirst($status); ?>
            </button>
        

        <!-- Research Codes Section -->
        <h3>Research Codes</h3>
        <table>
            <thead>
                <tr>
                    <th style="background-color:#aa3636; color:white;">ID</th>
                    <th style="background-color:#aa3636; color:white;">Code Acronym</th>
                    <th style="background-color:#aa3636; color:white;">Code Number</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $researchCodes = $conn->query("SELECT * FROM research_codes");
                while ($row = $researchCodes->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <input type="text" name="research_codes[<?php echo $row['id']; ?>][code_acronym]" 
                               value="<?php echo htmlspecialchars($row['code_acronym']); ?>">
                    </td>
                    <td>
                        <input type="number" name="research_codes[<?php echo $row['id']; ?>][code_number]" 
                               value="<?php echo $row['code_number']; ?>">
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- REOC Dynamic Data Section -->
        <h3>REOC Dynamic Data</h3>
        <table>
            <thead>
                <tr>
                <th style="background-color:#aa3636; color:white;">ID</th>
                <th style="background-color:#aa3636; color:white;">Certificate Version</th>
                <th style="background-color:#aa3636; color:white;">Date Effective</th>
                <th style="background-color:#aa3636; color:white;">Let Code</th>
                <th style="background-color:#aa3636; color:white;">Signature</th>
                <th style="background-color:#aa3636; color:white;">Appointment Capacity</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $dynamicData = $conn->query("SELECT * FROM reoc_dynamic_data");
                while ($row = $dynamicData->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td>
                        <input type="text" name="dynamic_data[<?php echo $row['id']; ?>][certificate_version]" 
                               value="<?php echo htmlspecialchars($row['certificate_version']); ?>">
                    </td>
                    <td>
                        <input type="text" name="dynamic_data[<?php echo $row['id']; ?>][date_effective]" 
                               value="<?php echo htmlspecialchars($row['date_effective']); ?>">
                    </td>
                    <td>
                        <input type="text" name="dynamic_data[<?php echo $row['id']; ?>][let_code]" 
                               value="<?php echo htmlspecialchars($row['let_code']); ?>">
                    </td>
                    <td>
        <input type="text" name="dynamic_data[<?php echo $row['id']; ?>][Signature]" 
               value="<?php echo htmlspecialchars($row['Signature']); ?>">
    </td>
    <td>
        <input type="number" name="dynamic_data[<?php echo $row['id']; ?>][appointment_capacity]" 
               value="<?php echo htmlspecialchars($row['appointment_capacity']); ?>">
    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Single Submit Button -->
        <button type="submit" class="saveall">Save All Data</button>
    </form>
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
    <?php if (!empty($message)): ?>
        Swal.fire({
            title: 'Success',
            text: '<?php echo htmlspecialchars($message); ?>',
            icon: 'success'
        });
    <?php endif; ?>
</script>
</body>
</html>
