<?php
// Include database connection
include('dbConnCode.php');  // Assuming you have a db connection file
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

// Handle activation/deactivation request
if (isset($_GET['toggle_id'])) {
    $user_id = $_GET['toggle_id'];
    // Toggle the isActive status
    $current_status_query = "SELECT isActive FROM users WHERE id = ?";
    $stmt = $conn->prepare($current_status_query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // If currently active, set to inactive (0), else set to active (1)
    $new_status = $row['isActive'] == 1 ? 0 : 1;

    $update_query = "UPDATE users SET isActive = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('ii', $new_status, $user_id);
    $stmt->execute();

    // Redirect back to the same page to reflect changes
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle mobile number update
if (isset($_POST['update_mobile'])) {
    $user_id = $_POST['user_id'];
    $new_mobile_number = $_POST['mobile_number'];

    // Update the mobile number in the database
    $update_mobile_query = "UPDATE researcher_profiles SET mobile_number = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_mobile_query);
    $stmt->bind_param('si', $new_mobile_number, $user_id);
    $stmt->execute();

    // Redirect back to the same page after updating with a query string to trigger the SweetAlert
    header('Location: ' . $_SERVER['PHP_SELF'] . '?mobile_update=success');
    exit;
}

// Fetch all users with their mobile numbers
$sql = "
    SELECT u.id, u.email, u.isActive, rp.mobile_number
    FROM users AS u
    LEFT JOIN researcher_profiles AS rp ON u.id = rp.user_id
    LEFT JOIN user_roles AS ur ON u.id = ur.user_id
    LEFT JOIN roles AS r ON ur.role_id = r.id
    WHERE u.email != '' AND u.email IS NOT NULL
    AND r.name = 'Researcher';
";

$result = mysqli_query($conn, $sql);


$total_users_query = "
    SELECT COUNT(*) AS total_users
    FROM users AS u
    LEFT JOIN user_roles AS ur ON u.id = ur.user_id
    LEFT JOIN roles AS r ON ur.role_id = r.id
    WHERE u.email != '' AND u.email IS NOT NULL
    AND r.name = 'Researcher';
";

$total_result = mysqli_query($conn, $total_users_query);
$total_row = mysqli_fetch_assoc($total_result);
$total_users = $total_row['total_users'];


?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>Manage Users</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">


    <link rel="stylesheet" type="text/css" href="./css/table.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    

    <script defer src="./js/table.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- SweetAlert -->
    <style>
   
        table {
            border-collapse: collapse;
            margin: 1em auto;
            width: 50%;
            margin-bottom: 100px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
          background-color: rgb(139, 56, 56);
        
          padding: 8px;
          border: 1px solid #ccc;
          text-align: left;
      }


      th{
        font-size: 13px;
        text-align: center;
        color: white;
    }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .back-btn {
            position: absolute;
            top: 20px;  
            right: 20px;  
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .back-btn:hover {
            background-color: #0056b3;
        }


        .search-box {
            position: relative;
            left: 180px;
            margin-bottom: 20px;
            padding: 8px;
            font-size: 16px;
            width: 100%;
            max-width: 300px;
            margin-bottom: 20px;
        }

        .print-btn {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }

        .print-btn:hover {
            background-color: #0056b3;
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





		.disablebtn{
			padding: 10px 20px; font-size: 16px; cursor: pointer;    
            background-color: #aa3636; /* Blue color */
          color: white; /* Text color */
          border: none; /* Remove border */
          border-radius: 9px; /* Rounded corners */
          padding: 5px 9px; /* Button size */
          font-size: 14px; /* Font size */
		  transition: background-color 0.3s;

	
	}

	.disablebtn:hover {
		background-color: #802c2c;

	}



    .table-filters{
        display: flex;
        gap: 5px;
    position: relative;

}

.vision2 {
  background-color: #F8F7F4;
  position: relative;
  padding-top: 10px;
  padding-bottom: 50px;
  text-align: center; 
  margin-top:20px;
}


.searchlbl{
    position: relative;
    top:35px;
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



<h1 class="vision2">Manage Accounts</h1>
    
   

    <!-- Search Box -->
    <div class="table-filters" style="display: flex; align-items: center;  gap: 5px;">

        <div style="position:relative; margin-left:200px;">
           <label for="filter-name" class="searchlbl">Search by email:</label>
            <input type="text" id="searchBox" class="search-box" placeholder="Search by email..." onkeyup="searchFunction()">
        </div>

 
              <div style="display: flex; align-items: center; position:relative; margin-left:400px;">
                 <div class="total-users" style="margin-right: 20px; margin-top:20px;">
                   <p><strong>Total Users:</strong> <?php echo $total_users; ?></p>
                 </div>
                  <button class="print-btn" onclick="printTable()">Print</button>
              </div>

    </div>
   

    <table id="userTable">
        <tr>
            <th>Email</th>
            <th>Mobile Number</th> <!-- New column for mobile number -->
            <th>Account Status</th>
            <th>Actions</th>
        </tr>
        
        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <!-- Mobile number input form -->
                <td>
                    <form method="POST" action="">
                        <input type="text" name="mobile_number" value="<?php echo htmlspecialchars($row['mobile_number']); ?>" required>
                        <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="update_mobile">Update</button>
                    </form>
                </td>
                <td>
                    <?php 
                        // Display the status (Active/Inactive)
                        echo $row['isActive'] == 1 ? 'Active' : 'Inactive'; 
                    ?>
                </td>
                <td class="actions">
                    <!-- Toggle activation/deactivation button -->
                    <a href="?toggle_id=<?php echo $row['id']; ?>">
                        <button class="disablebtn">
                            <?php echo $row['isActive'] == 1 ? 'Disable' : 'Activate'; ?>
                        </button>
                    </a>
                </td>
            </tr>
        <?php } ?>
    </table>

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
        // SweetAlert when toggling status
        <?php if (isset($_GET['toggle_id'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Account status updated',
                text: '<?php echo $row['isActive'] == 1 ? "Account has been activated." : "Account has been deactivated."; ?>',
                showConfirmButton: false,
                timer: 2000
            });
        <?php endif; ?>

        // SweetAlert when mobile number is updated
        <?php if (isset($_GET['mobile_update']) && $_GET['mobile_update'] == 'success'): ?>
            Swal.fire({
                icon: 'success',
                title: 'Mobile number updated successfully!',
                showConfirmButton: false,
                timer: 2000
            });
        <?php endif; ?>

        // Real-time search function
        function searchFunction() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById('searchBox');
            filter = input.value.toUpperCase();
            table = document.getElementById('userTable');
            tr = table.getElementsByTagName('tr');
            
            for (i = 1; i < tr.length; i++) {
                td = tr[i].getElementsByTagName('td')[0]; // Search by email column
                if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }       
            }
        }

        // Print table
        function printTable() {
            var printWindow = window.open('', '', 'height=600,width=800');

            // Get the total users count and inject it into the print window
            var totalUsers = "<?php echo $total_users; ?>";

            // Write the HTML to the print window, including styles and the total count of users
            printWindow.document.write('<html><head><title>Print User List</title>');
            printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; }');
            printWindow.document.write('th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }');
            printWindow.document.write('th { background-color: #800000; color: white; }');
            printWindow.document.write('tr:nth-child(even) { background-color: #f2f2f2; }</style></head><body>');
            printWindow.document.write('<h1>User List</h1>');
            printWindow.document.write('<p>Total Users: ' + totalUsers + '</p>');
            printWindow.document.write(document.getElementById('userTable').outerHTML);
            printWindow.document.write('</body></html>');

            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>
