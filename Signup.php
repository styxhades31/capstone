<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'dbConnCode.php'; // Include your database connection file
require 'vendor/autoload.php'; // Load PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$error = ''; // Initialize error variable
$verificationCodeSent = false; // Track if code was sent

// Check if the role is provided in the URL
if (isset($_GET['role'])) {
    $role = htmlspecialchars($_GET['role']); // Sanitize the role parameter
} else {
    $role = 'Researcher '; // Fallback if no role is provided
}

// Generate CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Function to generate a 6-digit verification code
function generateVerificationCode() {
    return sprintf('%06d', mt_rand(100000, 999999));
}

// Check if the form was submitted
// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'CSRF token validation failed.';
        
    } else {
        // Validate the email input
        if (isset($_POST['email']) && isset($_POST['mobile'])) {
            $email = trim($_POST['email']);
            $mobile = trim($_POST['mobile']); // Capture mobile number

            $stmt = $conn->prepare("SELECT COUNT(*) AS email_count FROM users WHERE email = ?");
            $stmt->bind_param('s', $email); // Bind the email parameter
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $emailExists = $row['email_count']; // Fetch the count from the result

            if ($emailExists > 0) {
                $error = 'This email is already registered.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } elseif (!preg_match('/^(\+639\d{9}|0\d{10})$/', $mobile)) {
                $error = 'Invalid mobile number format.';
            } else {

                // Convert +63 to 0 if applicable
                if (strpos($mobile, '+63') === 0) {
                    $mobile = '0' . substr($mobile, 3); // Replace +63 with 0
                }
                
                // Validate passwords
                $password = trim($_POST['password']);
                $rePassword = trim($_POST['re_password']);

                if (empty($password) || empty($rePassword)) {
                    $error = 'Please fill in both password fields.';
                } elseif ($password !== $rePassword) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } else {
                    // Hash the password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Generate verification code
                    $verificationCode = generateVerificationCode();

                    $stmt = $conn->prepare("INSERT INTO users (verification_code, temporaryemailholder, password) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $verificationCode, $email, $hashedPassword); // 'sss' means all three parameters are strings
if ($stmt->execute()) {
                        // Store email in session
                        $_SESSION['email'] = $email;
                        $_SESSION['mobile'] = $mobile; // Store mobile number in session

                        // Send verification email
                        $mail = new PHPMailer(true);
                        try {
                            // Server settings
                            $mail->isSMTP();
                            $mail->Host = 'smtp.gmail.com';
                            $mail->SMTPAuth = true;
                            $mail->Username = ''; // Replace with your email
                            $mail->Password = ''; // Replace with your email password
                            $mail->SMTPSecure = 'tls';
                            $mail->Port = 587;

                            // Recipients
                            $mail->setFrom('westkiria@gmail.com', 'West Kiria');
                            $mail->addAddress($email);

                            // Content
                            $mail->isHTML(true);
                            $mail->Subject = 'Your Verification Code';
                            $mail->Body    = "Your verification code is: $verificationCode";

                            $mail->send();
                            $verificationCodeSent = true; // Mark that the code was sent
                        } catch (Exception $e) {
                            $error = "Message could not be sent. Mailer Error: " . $mail->ErrorInfo;
                            error_log($error); // Log the error for debugging
                        }

                        // Redirect or notify that the email was sent
                        if ($verificationCodeSent) {
                            $_SESSION['role'] = $role; // Store the role in the session
                            header("Location: researcherAccountcreation.php?role=" . urlencode($role));
                            exit;
                        }
                    } else {
                        $error = "Error inserting verification code into the database: " . $stmt->error;
                        error_log($error); // Log the error for debugging
                    }
                }
            }
        } else {
            $error = 'Please enter an email.';
        }
    }
}


?>



<!DOCTYPE html>
<html lang="en">
<head>
	<title>Sign In Form</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/login1.css">
	<link rel="stylesheet" type="text/css" href="./css/login2.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <style>
     
     .login100-form {
    width: 100%;

    top: 20px;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 43px 88px 93px 190px;
  }
   
.container-login100 {
    width: 100%;  
    min-height: 80vh;
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
       
        .error {
            color: red;
        }
        .password-rules {
            font-size: 0.9em;
            color: #666;
        }
        .invalid {
            color: red;
        }
        .valid {
            color: green;
        }
        .mobile-rules {
            font-size: 0.9em;
            color: #666;
        }



  
        .login100-form-btn2 {
    position: relative;
left:40px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20 20px;
   min-width: 160px;
   height: 50px;
   background-color: #751111;
   border-radius: 25px;
 
   font-family: Poppins-Regular;
   font-size: 16px;
   color: #fff;
   line-height: 1.2;
 
   -webkit-transition: all 0.4s;
   -o-transition: all 0.4s;
   -moz-transition: all 0.4s;
   transition: all 0.4s;
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
					<h4 class="sign">SIGN UP </h4>
				</div>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>






    <form method="POST" action="" class="login100-form validate-form">
    <div class="wrap-input100 validate-input m-b-26" data-validate="Email Address is required">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <span class="label-input100">Email Address</span>
        <input class="input100" type="email" id="email" name="email" required placeholder="Enter your email">
        <span class="focus-input100"></span>
     </div>



     <div class="wrap-input100 validate-input m-b-26" data-validate="Contact Number is required">
        <span class="label-input100">Contact Number</span>
        <input  class="input100" type="tel" id="mobile" name="mobile" required placeholder="Enter your mobile number" pattern="(\+639\d{9}|0\d{10})" title="Format: 09773106532 or +639773106532">
        <span class="focus-input100"></span>
	</div>





<!-- Mobile number validation rules -->
<div class="mobile-rules">
    Mobile number must:
    <ul>
        <li id="mobile-length" class="invalid">Be exactly 11 digits long (without country code) or 13 characters long (with +63)</li>
        <li id="mobile-format" class="invalid">Start with 0 or +639</li>
    </ul>
</div>

        <div class="wrap-input100 validate-input m-b-26" data-validate="Contact Number is required">
        <span class="label-input100">Password</span>
        <input  class="input100" type="password" id="password" name="password" required placeholder="Enter your password">
        <span class="focus-input100"></span>
        </div>



        <div class="wrap-input100 validate-input m-b-26" data-validate="Contact Number is required">
        <span class="label-input100">Re-enter Password</span>
        <input class="input100" type="password" id="re_password" name="re_password" required placeholder="Re-enter your password">
        <span class="focus-input100"></span>
        </div>

        <!-- Password rules message -->
        <div class="password-rules">
            Password must be at least 6 characters long, and include:
            <ul>
                <li id="length" class="invalid">At least 6 characters long</li>
                <li id="uppercase" class="invalid">At least one uppercase letter (A-Z)</li>
                <li id="lowercase" class="invalid">At least one lowercase letter (a-z)</li>
                <li id="number" class="invalid">At least one number (0-9)</li>
                <li id="special" class="invalid">At least one special character (!, @, #, etc.)</li>
            </ul>
        </div>

        <div class="flex-sb-m w-full p-b-30">
						<div class="contact100-form-checkbox">
						</div>
					</div>


                    <div class="container-login100-form-btn2">
        <button  class="login100-form-btn2" type="submit">Send Verification Code</button>
        </div>



    </form>

<script>
    const passwordInput = document.getElementById('password');
    const rePasswordInput = document.getElementById('re_password');
    const lengthRule = document.getElementById('length');
    const uppercaseRule = document.getElementById('uppercase');
    const lowercaseRule = document.getElementById('lowercase');
    const numberRule = document.getElementById('number');
    const specialRule = document.getElementById('special');

    const mobileInput = document.getElementById('mobile');
const mobileLengthRule = document.getElementById('mobile-length');
const mobileFormatRule = document.getElementById('mobile-format');

    passwordInput.addEventListener('input', function() {
        const password = passwordInput.value;

        // Validate password length
        if (password.length >= 6) {
            lengthRule.classList.remove('invalid');
            lengthRule.classList.add('valid');
            lengthRule.textContent = '✓ At least 6 characters long';
        } else {
            lengthRule.classList.remove('valid');
            lengthRule.classList.add('invalid');
            lengthRule.textContent = 'X At least 6 characters long';
        }

        // Validate uppercase letter
        if (/[A-Z]/.test(password)) {
            uppercaseRule.classList.remove('invalid');
            uppercaseRule.classList.add('valid');
            uppercaseRule.textContent = '✓ At least one uppercase letter (A-Z)';
        } else {
            uppercaseRule.classList.remove('valid');
            uppercaseRule.classList.add('invalid');
            uppercaseRule.textContent = 'X At least one uppercase letter (A-Z)';
        }

        // Validate lowercase letter
        if (/[a-z]/.test(password)) {
            lowercaseRule.classList.remove('invalid');
            lowercaseRule.classList.add('valid');
            lowercaseRule.textContent = '✓ At least one lowercase letter (a-z)';
        } else {
            lowercaseRule.classList.remove('valid');
            lowercaseRule.classList.add('invalid');
            lowercaseRule.textContent = 'X At least one lowercase letter (a-z)';
        }

        // Validate number
        if (/\d/.test(password)) {
            numberRule.classList.remove('invalid');
            numberRule.classList.add('valid');
            numberRule.textContent = '✓ At least one number (0-9)';
        } else {
            numberRule.classList.remove('valid');
            numberRule.classList.add('invalid');
            numberRule.textContent = 'X At least one number (0-9)';
        }

        // Validate special character
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            specialRule.classList.remove('invalid');
            specialRule.classList.add('valid');
            specialRule.textContent = '✓ At least one special character (!, @, #, etc.)';
        } else {
            specialRule.classList.remove('valid');
            specialRule.classList.add('invalid');
            specialRule.textContent = 'X At least one special character (!, @, #, etc.)';
        }
    });
    mobileInput.addEventListener('input', function() {
    const mobile = mobileInput.value;

    // Validate mobile number length
    if (mobile.length === 11 || (mobile.startsWith('+639') && mobile.length === 13)) {
        mobileLengthRule.classList.remove('invalid');
        mobileLengthRule.classList.add('valid');
        mobileLengthRule.textContent = '✓ Be exactly 11 digits long (without country code) or 13 characters long (with +63)';
    } else {
        mobileLengthRule.classList.remove('valid');
        mobileLengthRule.classList.add('invalid');
        mobileLengthRule.textContent = 'X Be exactly 11 digits long (without country code) or 13 characters long (with +63)';
    }

    // Validate mobile number format
    const mobileRegex = /^(0\d{10}|\+639\d{9})$/;
    if (mobileRegex.test(mobile)) {
        mobileFormatRule.classList.remove('invalid');
        mobileFormatRule.classList.add('valid');
        mobileFormatRule.textContent = '✓ Start with 0 or +639';
    } else {
        mobileFormatRule.classList.remove('valid');
        mobileFormatRule.classList.add('invalid');
        mobileFormatRule.textContent = 'X Start with 0 or +639';
    }
});
</script>

</body>
</html>
