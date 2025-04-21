<?php
session_start();

require 'dbConnCode.php'; // Database connection file

$error = ''; // To store error messages
$success = ''; // To store success messages

// Check if the role is provided in the session
if (!isset($_SESSION['role'])) {
    $role = 'Researcher'; // Retrieve role from session
} else {
    $role = $_SESSION['role']; // Retrieve role from session
    
}

// Check if the mobile number is provided in the session
if (!isset($_SESSION['mobile'])) {
    $error = 'No mobile number found in session. Please restart the process.';
} else {
    $mobileNumber = $_SESSION['mobile'];
}


// Check if the email is provided in the session
if (!isset($_SESSION['email'])) {
    $error = 'No email found in session. Please restart the process.';
} else {
    $email = $_SESSION['email'];

    // Initialize failed attempts if not set
    if (!isset($_SESSION['failed_attempts'])) {
        $_SESSION['failed_attempts'] = 0; // Start attempts at 0
    }

    // Check if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Retrieve and sanitize inputs
        $verificationCode = trim($_POST['verificationCode']);

       
        // Check if the user has exceeded 5 failed attempts
        if ($_SESSION['failed_attempts'] >= 5) {
            $_SESSION['is_max_attempts_reached'] = true;
            // Display message before redirecting
            // Delete the user row from the database
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE temporaryemailholder = ?");
            $deleteStmt->bind_param("s", $email);
            $deleteStmt->execute();

            // Destroy the session before redirecting
            session_destroy();
        } else {
            $_SESSION['is_max_attempts_reached'] = false;
            // Check if the verification code matches the one in the database
            $stmt = $conn->prepare("SELECT verification_code FROM users WHERE temporaryemailholder = ?");
            $stmt->bind_param("s", $email); // Bind the email parameter
            $stmt->execute();
            $result = $stmt->get_result(); // Get the result set

            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc(); // Fetch the row
                if ($row['verification_code'] === $verificationCode) {
                    // Reset failed attempts on success
                    $_SESSION['failed_attempts'] = 0;

                    // Update the user record and set the email, verification_code to NULL
                    $updateStmt = $conn->prepare("UPDATE users SET email = ?, verification_code = NULL, temporaryemailholder = NULL WHERE temporaryemailholder = ?");
                    $updateStmt->bind_param("ss", $email, $email); // Bind the email and temporary email
                    if ($updateStmt->execute()) {
                        // Get the user ID using the email
                        $userIdStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                        $userIdStmt->bind_param("s", $email);
                        $userIdStmt->execute();
                        $userIdResult = $userIdStmt->get_result();

                        if ($userIdResult && $userIdResult->num_rows > 0) {
                            $userRow = $userIdResult->fetch_assoc();
                            $userId = $userRow['id']; // Get the user ID

                            // Determine role_id based on role name
                            $roleIdStmt = $conn->prepare("SELECT id FROM roles WHERE name = ?");
                            $roleIdStmt->bind_param("s", $role);
                            $roleIdStmt->execute();
                            $roleIdResult = $roleIdStmt->get_result();

                            if ($roleIdResult && $roleIdResult->num_rows > 0) {
                                $roleRow = $roleIdResult->fetch_assoc();
                                $roleId = $roleRow['id']; // Get the role ID

                                // Insert into user_roles table
                                $insertRoleStmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                                $insertRoleStmt->bind_param("ii", $userId, $roleId); // Bind user ID and role ID
                                if ($insertRoleStmt->execute()) {
                                    // Insert mobile number into Researcher_profiles
                                    $insertMobileStmt = $conn->prepare("INSERT INTO researcher_profiles (user_id, mobile_number) VALUES (?, ?)");
                                    $insertMobileStmt->bind_param("is", $userId, $mobileNumber); // Bind user ID and mobile number
                                    if ($insertMobileStmt->execute()) {
                                        $success = 'Account created successfully! Wait for the account activation.';
                                        // Unset session variables
                                        unset($_SESSION['email'], $_SESSION['password'], $_SESSION['role']);
                                    } else {
                                        $error = 'Error saving mobile number. Please try again.';
                                    }
                                } else {
                                    $error = 'Error assigning role. Please try again.';
                                }
                            } else {
                                $error = 'Invalid role specified.';
                            }
                        } else {
                            $error = 'Error retrieving user ID. Please try again.';
                        }
                    } else {
                        $error = 'Error creating account. Please try again.';
                    }
                } else {
                    // Increment failed attempts on wrong verification code
                    $_SESSION['failed_attempts'] += 1;
                    $error = 'Invalid verification code. Attempt ' . $_SESSION['failed_attempts'] . ' of 5.';
                }
            } else {
                $error = 'Invalid email or verification code.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Login Form</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/login1.css">
	<link rel="stylesheet" type="text/css" href="./css/login2.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>




.container-login100 {
    width: 100%;  
    min-height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    justify-content: center;
    align-items: center;
    border: none;
    position: relative; 
    overflow: hidden;
}

.container-login100::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
   
    background-image: 
        linear-gradient(rgba(8, 8, 8, 0.8), rgba(88, 33, 33, 0.8)), 
        url('./img/reocpic.jpg'); 
    background-size: cover; 
    background-position: center; 
    filter: blur(2px); 
  
    
  }
  .txt1 {
    font-family: Poppins-Regular;
      position: relative;
      left: 270px;
      font-size: 14px;
      line-height: 1.7;
      color: #666666;
      margin: 0px;
      transition: all 0.4s;
      -webkit-transition: all 0.4s;
    -o-transition: all 0.4s;
    -moz-transition: all 0.4s;
  }
  


  .txt2 {
    font-family: Poppins-Regular;
      position: relative;
      top: 40px;
      left: 90px;
      font-size: 14px;
      line-height: 1.7;
      color: #666666;
      margin: 0px;
      transition: all 0.4s;
      -webkit-transition: all 0.4s;
    -o-transition: all 0.4s;
    -moz-transition: all 0.4s;
  }
  
  
 


  
  .txt1:focus {
      outline: none !important;
  }
  
  .txt1:hover {
      text-decoration: none;
    color: #802c2c;
  }


  .txt2:focus {
      outline: none !important;
  }
  
  .txt2:hover {
      text-decoration: none;
    color: #802c2c;
  }
  
        
  
        
    </style>
</head>
<body>

<div class="container">
 
    <!-- Display error or success messages -->
    <script>
    // Check if there's an error or success message to display
    const errorMessage = <?php echo json_encode($error); ?>;
    const successMessage = <?php echo json_encode($success); ?>;
    const isMaxAttemptsReached = <?php echo json_encode($_SESSION['is_max_attempts_reached'] ?? false); ?>; // Check the session variable

    if (successMessage) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage,
        }).then(() => {
            // Redirect to login or another page after 3 seconds
           
                window.location.href = 'login.php'; // Change to your desired redirect URL
           
        });
    }

    if (errorMessage) {
        if (isMaxAttemptsReached) {
            // Display message for too many attempts without an OK button
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Too many attempts. Please try again later.',
                showCancelButton: false, // Prevent showing buttons
            }).then(() => {
                // Redirect immediately
                window.location.href = 'superUsersignup.php'; // Redirect to the signup page
            });
        } else {
            // Display regular error message for invalid verification code
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMessage,
            });
        }
    }

    // Optionally, if too many attempts were reached when the page loads
    if (isMaxAttemptsReached) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Too many attempts. Please try again later.',
            showCancelButton: false, // Prevent showing buttons
        }).then(() => {
            // Redirect immediately
            window.location.href = 'superUsersignup.php'; // Redirect to the signup page
        });
    }
</script>







<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title" style="background-image: url(./img/wmsu5.jpg);">
					<span class="login100-form-title-1">
						reoc-wmsu portal
					</span>
					<h4 class="sign">LOG IN </h4>
				</div>


    <!-- Form for account creation -->
    <form method="POST" action="" class="login100-form validate-form" >
    <div class="wrap-input100 validate-input m-b-26" data-validate="Username is required">
        <label class="label-input100" for="verificationCode">Verification Code:</label>
        <input class="input100" type="text" id="verificationCode" name="verificationCode" required placeholder="Enter the code sent to your email">
		<span class="focus-input100"></span>
	</div>




    <div class="container-login100-form-btn2" style="margin-top:20px;">
        <button  class="login100-form-btn2"  type="submit">Create Account</button>
   </div>




    </form>














</div>

</body>
</html>
