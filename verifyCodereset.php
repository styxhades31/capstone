<?php
session_start();
$error = ''; // To store error messages
$success = ''; // To store success messages

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $enteredCode = trim($_POST['code']);
    $email = $_SESSION['email']; // Retrieve the email stored in session

    // Validation: Check if the code field is empty
    if (empty($enteredCode)) {
        $error = 'Please enter the verification code.';
    } else {
        require 'dbConnCode.php'; // Include database connection

        // Retrieve the stored code and expiry time from the database
        $stmt = $conn->prepare("SELECT reset_code, reset_code_expiry FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $storedCode = $row['reset_code'];
            $expiry = $row['reset_code_expiry'];

            // Check if the code has expired
            if (time() > $expiry) {
                $error = 'The code has expired. Please request a new one.';
            } elseif ($enteredCode !== $storedCode) {
                // Check if the entered code matches the stored code
                $error = 'The verification code is incorrect. Please try again.';
            } else {
                // Code is correct, redirect to the password reset page
                header("Location: resetPassword.php");
                exit();
            }
        } else {
            $error = 'No verification code found for this email.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<title>Verify Code</title>
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
            window.location.href = 'login.php'; // Redirect to login after success
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

    <!-- Code Verification Form -->
    <div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title" style="background-image: url(./img/wmsu5.jpg);">
					<span class="login100-form-title-1">
						reoc-wmsu portal
					</span>
					<h4 class="sign">Code Verification </h4>
				</div>





    <form method="POST" action="" class="login100-form validate-form">
    <div class="wrap-input100 validate-input m-b-26" data-validate="Email Address is required">
        <label for="code"  class="label-input100">Verification code:</label>
        <input  class="input100" type="text" id="code" name="code" required placeholder="Enter Verification Code" />
        <span class="focus-input100"></span>
        </div>

        <div class="flex-sb-m w-full p-b-30">
						<div class="contact100-form-checkbox">
						</div>
					</div>
		


  <div class="container-login100-form-btn2">
        <button class="login100-form-btn2" type="submit">Verify Code</button>
        </div>
    </form>
    




</div>
</div>
</div>
</div>

</body>








</html>
