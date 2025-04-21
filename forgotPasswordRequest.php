<?php
session_start();
$error = ''; // To store error messages
$success = ''; // To store success messages
require 'vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    
    // Validation: Check if the email field is empty
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        require 'dbConnCode.php'; // Include database connection

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format. Please enter a valid email address.';
        } else {
            // Check if the email exists in the database
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Email exists, generate a verification code
                $verificationCode = rand(100000, 999999); // Generate a 6-digit random verification code
                $expires = time() + 600; // Code expiry time (10 minutes)

                // Store the code and expiry time in the database
                $updateStmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expiry = ? WHERE email = ?");
                $updateStmt->bind_param("sis", $verificationCode, $expires, $email);
                if ($updateStmt->execute()) {
                    // Send the password reset verification code to the user's email using PHPMailer
                    require 'vendor/autoload.php'; // Include the PHPMailer autoloader

                    $mail = new PHPMailer(true);
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'westkiria@gmail.com'; // Replace with your email
                        $mail->Password = 'qpktvouqahvubayd'; // Replace with your email password
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        // Recipients
                        $mail->setFrom('westkiria@gmail.com', 'West Kiria');
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Your Verification Code';
                        $mail->Body    = "Your verification code is: $verificationCode";

                        // Send the email
                        $mail->send();
                        $_SESSION['email'] = $email; // Store email in session to use in the next step
                        $success = 'A verification code has been sent to your email address.';
                        header("Location: verifyCodereset.php"); // Redirect to verify code page
                        exit();
                    } catch (Exception $e) {
                        $error = "Message could not be sent. Mailer Error: " . $mail->ErrorInfo;
                        error_log($error); // Log the error for debugging
                    }
                } else {
                    $error = 'Failed to update reset code. Please try again.';
                }
            } else {
                $error = 'No user found with that email address.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Forgot Password</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/login1.css">
	<link rel="stylesheet" type="text/css" href="./css/login2.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<style>
      
      .container {
          background-color: white;
          padding: 20px;
          border-radius: 8px;
          box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
          text-align: center;
          width: 300px;
      }
      button {
          padding: 10px 20px;
          margin-top: 10px;
          border: none;
          border-radius: 5px;
          background-color: #007bff;
          color: white;
          cursor: pointer;
      }
      button:hover {
          background-color: #0056b3;
      }

      
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
  
  </style>

<div>
 
    
    <!-- Display error or success messages -->
    <script>
    const errorMessage = <?php echo json_encode($error); ?>;
    const successMessage = <?php echo json_encode($success); ?>;

    if (successMessage) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage,
        }).then(() => {
            window.location.href = 'login.php'; // Redirect to login page after success
        });
    }

    if (errorMessage) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
        });
    }
    </script>

    <!-- Forgot Password Form -->


    <div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title" style="background-image: url(./img/wmsu5.jpg);">
					<span class="login100-form-title-1">
						reoc-wmsu portal
					</span>
					<h4 class="sign">Forgot password Code Verification </h4>
				</div>

                
    <form method="POST" action="" class="login100-form validate-form">

    <div class="wrap-input100 validate-input m-b-26" data-validate="Email Address is required">
        <label for="email" class="label-input100">Email address:</label>
        <input class="input100" type="email" id="email" name="email" required placeholder="Enter your email address" />
        <span class="focus-input100"></span>
    </div>

    <div class="flex-sb-m w-full p-b-30">
						<div class="contact100-form-checkbox">
						</div>
					</div>
		

         <div class="container-login100-form-btn2">
            <button  class="login100-form-btn2" type="submit">Send Reset Code</button>
        </div>





    </form>







    </div>




</div>
</div>
</div>


</body>
</html>
