<?php
// Start the session
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

// Include database connection
include('dbConnCode.php');

// Handle form submission for adding a new college
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_college'])) {
    $college_name_and_color = mysqli_real_escape_string($conn, $_POST['college_name_and_color']);
    
    // Insert the new college into the database
    $insert_query = "INSERT INTO colleges (college_name_and_color) VALUES ('$college_name_and_color')";
    if (mysqli_query($conn, $insert_query)) {
        $_SESSION['message'] = 'College added successfully!';
        $_SESSION['message_type'] = 'success'; // Set message type
    } else {
        $_SESSION['message'] = 'Error: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'error';
    }
    // Redirect to avoid resubmitting on page reload
    header("Location: colleges.php");
    exit();
}

// Handle editing of a college
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_college'])) {
    $college_id = intval($_POST['college_id']);
    $college_name_and_color = mysqli_real_escape_string($conn, $_POST['college_name_and_color']);
    
    // Update the college name and color
    $update_query = "UPDATE colleges SET college_name_and_color = '$college_name_and_color' WHERE id = $college_id";
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = 'College updated successfully!';
        $_SESSION['message_type'] = 'success'; // Set message type
    } else {
        $_SESSION['message'] = 'Error: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'error';
    }
    // Redirect to avoid resubmitting on page reload
    header("Location: colleges.php");
    exit();
}

// Handle deleting a college
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Delete the college from the database
    $delete_query = "DELETE FROM colleges WHERE id = $delete_id";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['message'] = 'College deleted successfully!';
        $_SESSION['message_type'] = 'success'; // Set message type
    } else {
        $_SESSION['message'] = 'Error: ' . mysqli_error($conn);
        $_SESSION['message_type'] = 'error';
    }

    // Redirect to avoid resubmitting on page reload
    header("Location: colleges.php");
    exit();
}

// Fetch all colleges from the database
$query = "SELECT * FROM colleges";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Manage Colleges</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">


    <link rel="stylesheet" type="text/css" href="./css/table.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    

    <script defer src="./js/table.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert -->
    <style>
     

   
        table {
            border-collapse: collapse;
            margin: 1em auto;
            width: 70%;
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
          width: 50rem;
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

        .vision2 {
  background-color: #F8F7F4;
  position: relative;
  padding-top: 10px;
  padding-bottom: 50px;
  text-align: center; 
  margin-top: 40px;
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
      <a href="account.php">Account Verifications</a>
     <a href="./admin_dashboard.html">Dashboard</a>
       

        <!-- Logout Button -->
        <form method="POST" action="researcherHome.php" style="display: inline; ">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
      </div>
    </div>
  </div>
  </header>
</header>
  
        

    <h1 class="vision2">Analytics</h1>
    <!-- Form to add a new college -->
    <div class="form-container" >
        <form method="POST">
            <label for="college_name_and_color">College Name and Color:</label>
            <input type="text" name="college_name_and_color" required>
            <button type="submit" name="add_college">Add College</button>
        </form>
    </div>


    <table>
        <tr>
            <th>College Name and Color</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['college_name_and_color']); ?></td>
                <td class="actions">
                    <!-- Edit form -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="college_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                        <input type="text" name="college_name_and_color" value="<?php echo htmlspecialchars($row['college_name_and_color']); ?>" required class="college-input">
                        <button type="submit" name="edit_college">Edit</button>
                    </form>
                   <!-- Delete button (with SweetAlert) -->
<a href="javascript:void(0);" onclick="deleteCollege(<?php echo htmlspecialchars($row['id']); ?>)">Delete</a>

                </td>
            </tr>
        <?php } ?>
    </table>

    <!-- SweetAlert message -->
    <script>
        function deleteCollege(collegeId) {
        // Show SweetAlert for confirmation before deleting
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
        }).then((result) => {
            if (result.isConfirmed) {
                // If confirmed, perform the delete action
                window.location.href = "?delete_id=" + collegeId;
            }
        });
    }
        <?php if (isset($_SESSION['message'])): ?>
            Swal.fire({
                icon: '<?php echo $_SESSION['message_type']; ?>',
                title: '<?php echo $_SESSION['message']; ?>',
                showConfirmButton: false,
                timer: 3000
            }).then(() => {
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                window.location.reload(); // Refresh the page to reflect changes
            });
        <?php endif; ?>
    </script>

</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
