<?php
session_start();
require 'dbConnCode.php'; // Database connection

$error = ''; // To store error messages
$login_success = false; // Flag to trigger SweetAlert in HTML

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate email
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    // Validate the email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        // Trim password input
        $password = trim($_POST['password']);

        // Fetch the user by email
        $stmt = $conn->prepare("SELECT id, password, isActive FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check if the account is active
            if ($user['isActive'] == 1) {
                // Verify the password
                if (password_verify($password, $user['password'])) {
                    // Fetch the user's role
                    $userId = $user['id'];
                    $roleStmt = $conn->prepare("SELECT roles.name FROM user_roles 
                                                JOIN roles ON user_roles.role_id = roles.id 
                                                WHERE user_roles.user_id = ?");
                    $roleStmt->bind_param("i", $userId);
                    $roleStmt->execute();
                    $roleResult = $roleStmt->get_result();

                    if ($roleResult && $roleResult->num_rows > 0) {
                        $role = $roleResult->fetch_assoc()['name'];

                        // Set session variables
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['role'] = $role;

                         // Mark login as successful and set redirect page based on role
                         $login_success = true;
                         if ($role == 'Admin') {
                             $redirect_page = 'adminHome.php';
                         } elseif ($role == 'Reviewer') {
                             $redirect_page = 'reviewerHome.php';
                         } else {
                             $redirect_page = 'researcherHome.php';
                         }
                    } else {
                        $error = 'No role assigned to this user.';
                    }
                } else {
                    $error = 'Incorrect password.';
                }
            } else {
                // Account is inactive
                $error = 'Your account is not yet activated. Please contact the REOC admin for account activation.';
            }
        } else {
            $error = 'No user found with this email.';
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
</head>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
	
	<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title" style="background-image: url(./img/wmsu5.jpg);">
					<span class="login100-form-title-1">
						reoc-wmsu portal
					</span>
					<h4 class="sign">LOG IN </h4>
				</div>



                
    <?php if ($error): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?php echo $error; ?>'
            });
        </script>
    <?php endif; ?>







    
 
   
    <form class="login100-form validate-form" method="POST" action="login.php">
					<div class="wrap-input100 validate-input m-b-26" data-validate="Username is required">
						<span class="label-input100">Email Address</span>
						<input class="input100" type="email" id="email" name="email"  placeholder="Enter Email Address">
						<span class="focus-input100"></span>
					</div>

					<div class="wrap-input100 validate-input m-b-18" data-validate = "Password is required">
						<span class="label-input100">Password</span>
						<input class="input100" type="password" id="password" name="password"  placeholder="Enter password">
						<span class="focus-input100"></span>
					</div>

					<div class="flex-sb-m w-full p-b-30">
					

						<div>



                        <div>
							<a href="forgotPasswordRequest.php" class="txt1">
								Forgot Password
							</a>
						</div>




						</div>
					</div>

					<div class="container-login100-form-btn2">
						<button class="login100-form-btn2" type="submit">
							Log In
						</button>
					</div>
                           <div>
							<a href="Signup.php" class="txt2">
								Create Account
							</a>
						</div>


				</form>
			</div>
		</div>
	</div>
	

	

<script src="vendor/jquery/jquery-3.2.1.min.js"></script>
	<script src="vendor/animsition/js/animsition.min.js"></script>
	<script src="vendor/select2/select2.min.js"></script>
	<script src="vendor/daterangepicker/moment.min.js"></script>
	<script src="vendor/daterangepicker/daterangepicker.js"></script>
	<script src="vendor/countdowntime/countdowntime.js"></script>
	<script src="./js/fonts.js"></script>

<!-- Trigger SweetAlert for Successful Login -->
<?php if ($login_success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Login Successful',
            text: 'Welcome, <?php echo $_SESSION['role']; ?>!',
            timer: 2000, // Display for 2 seconds
            showConfirmButton: false
        }).then(() => {
            window.location.href = '<?php echo $redirect_page; ?>';
        });
    </script>
<?php endif; ?>

</body>
</html>
