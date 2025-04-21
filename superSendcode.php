<?php
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
    $role = 'Role not specified'; // Fallback if no role is provided
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
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'CSRF token validation failed.';
        
    } else {
        // Validate the email input
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email format.';
            } else {
              
                // Check if email exists in the database
                $stmt = $conn->prepare("SELECT COUNT(*) AS email_count FROM users WHERE email = ?");
                $stmt->bind_param('s', $email); // Bind the email parameter
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $emailExists = $row['email_count']; // Fetch the count from the result

                if ($emailExists > 0) {
                    $error = 'This email is already registered.';
                } else {
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

                       // Insert email and verification code into the database
$stmt = $conn->prepare("INSERT INTO users (verification_code, temporaryemailholder, password, isActive) VALUES (?, ?, ?, ?)");
$isActive = 0; // Default value for isActive (inactive)

// Bind the parameters to the statement
$stmt->bind_param('sssi', $verificationCode, $email, $hashedPassword, $isActive);

// Execute the statement
if ($stmt->execute()) {
                            // Store email in session
                            $_SESSION['email'] = $email;

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
                                header("Location: adminAccountcreation.php?role=" . urlencode($role));
                                exit;
                            }
                        } else {
                            $error = "Error inserting verification code into the database: " ;
                            error_log($error); // Log the error for debugging
                        }
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
	<title>Reviewer Account Setup</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">
	<link rel="stylesheet" type="text/css" href="./css/login1.css">
	<link rel="stylesheet" type="text/css" href="./css/login2.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <style>
      



.limiter {
    width: 100%;
    margin: 0 auto;
  }
  
  .container-login100 {
    width: 100%;  
    min-height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    padding: 15px;
    background: #ebeeef;
  }


  .container-login1001 {
    width: 100%;  
    min-height: 100vh;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    padding: 15px;
    background: #ebeeef;
  }
  
  
  
  
  .wrap-login100 {
    width: 670px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }



  .wrap-login1001 {
    width: 670px;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    position: relative;
  }
  
  
  /*==================================================================
  [ Title form ]*/
  .login100-form-title {
    width: 100%;
    position: relative;
    z-index: 1;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    flex-direction: column;
    align-items: center;
  
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
  
    padding: 70px 15px 74px 15px;
  }
  
  .login100-form-title-1 {
    font-family: Poppins-Bold;
    font-size: 30px;
    color: #fff;
    text-transform: uppercase;
    line-height: 1.2;
    text-align: center;
  }
  
  .login100-form-title::before {
    content: "";
    display: block;
    position: absolute;
    z-index: -1;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(190, 41, 41, 0.562), rgba(51, 39, 39, 0.712));
  }




    /*==================================================================
  [ Title form1 ]*/
  .login100-form1-title {
    width: 100%;
    position: relative;
    z-index: 1;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    flex-direction: column;
    align-items: center;
  
    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;
  
    padding: 70px 15px 74px 15px;
  }
  
  .login100-form1-title-1 {
    font-family: Poppins-Bold;
    font-size: 30px;
    color: #fff;
    text-transform: uppercase;
    line-height: 1.2;
    text-align: center;
  }
  
  .login100-form1-title::before {
    content: "";
    display: block;
    position: absolute;
    z-index: -1;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: linear-gradient(to bottom, rgba(190, 41, 41, 0.562), rgba(51, 39, 39, 0.712));
  }
  
  
  
  /*==================================================================
  [ Form ]*/
  
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

  .login100-form1 {
    width: 100%;
    position: relative;

    margin-top: 50px;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 43px 88px 93px 150px;
  }


  
  
  
  /*------------------------------------------------------------------
  [ Input ]*/
  
  .wrap-input100 {
    top: 20px;
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }

  .wrap-input200 {
    top: 20px;
    width: 60%;
    position: relative;
    border-bottom: 1px solid #0a8b5a00;
  }



  .wrap-input1001 {
    top: 20px;
    width: 100%;

    position: relative;
    border-radius: 5px;
  }


  .wrap-input100SN {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }



  .wrap-input100FN {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }


.opt{
  color: #660707;
}

  .wrap-input100MI {
    width: 100%;
    position: relative;
    border-bottom: 1px solid #b2b2b2;
  }
  
  .label-input100 {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #000000;
    line-height: 1.2;
    text-align: left;
  
    position: absolute;
    top: 14px;
    left: -105px;
    width: 80px;
  
  }
  



  .label-input200 {
    font-family: Poppins-Regular;
    font-size: 13px;
    color: #000000;
    line-height: 1.2;
    text-align: left;
  
    position: absolute;
  
    left: -105px;
    width: 270px;
  
  }





  /*---------------------------------------------*/
  .input100 {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }


  .input200 {
    position: relative;
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    left: 180px;
    background: transparent;
    padding: 0 5px;
  }



.input1001 {
  position: relative;
  top: -7px;
  padding: 10px;
  border-radius: 5px;
  border: 1px solid #ccc;
  width: 100%;

  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  font-size: 16px;
  color: #333;

}


select.input1001 option:hover {
  background-color: #ff0000; 
  color: red; 
}


select.input1001 option:checked {
  background-color: #a83939; 
  color: rgb(255, 255, 255); 
}

.input1001 {
  background: url('data:image/svg+xml;charset=US-ASCII,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="gray"><path d="M7 10l5 5 5-5H7z"/></svg>') no-repeat right;
  background-size: 16px;
  height: 50px;
}




.login100-form-btn:hover {
  background-color: #a30707;
}


.login100-form-btn2:hover {
  background-color: #a30707;
}

.login100-form-btn1:hover {
  background-color: #a30707;
}


.login100-form1-btn:hover {
  background-color: #a30707;
}


.login100-form1-btn2:hover {
  background-color: #a30707;
}

.login100-form1-btn1:hover {
  background-color: #a30707;
}






  .inputsign {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }



  .inputsignSN {
    font-family: Poppins-Regular;
    font-size: 15px;
    color: #555555;
    line-height: 1.2;
    display: block;
    width: 100%;
    background: transparent;
    padding: 0 5px;
  }




  .name-fields {
    display: flex;
    justify-content: space-between;
}

.name-fields .wrap-input100 {
    width: 100%; 
}


.name-fields .wrap-input200 {
  width: 100%; 
}






.name-fields .wrap-input100SN {
    width: 35%; 
}

.name-fields .wrap-input100FN {
    width: 45%; 
}



.name-fields .wrap-input100MI {
    width: 8%; 
}

  
  .focus-input100 {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }





  .focus-input200 {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }







  .focus-input100FN {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  
  .focus-input100SN {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  
  .focus-input100MI {
    position: absolute;
    display: block;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    pointer-events: none;
  }
  





  .focus-input100::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  
  

  .focus-input200::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  
  

  .focus-input100FN::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  

  .focus-input100SN::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  

  .focus-input100MI::before {
    content: "";
    display: block;
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 0;
    height: 1px;
  
    -webkit-transition: all 0.6s;
    -o-transition: all 0.6s;
    -moz-transition: all 0.6s;
    transition: all 0.6s;
  
    background-color: #751111;
  }
  


  /*---------------------------------------------*/
  input.input100 {
    height: 45px;
  }



  input.input200 {
    height: 45px;
  }




  input.inputsign {
    height: 45px;
  }
  
  
  input.inputsignSN {
    height: 45px;
  }
  


  input.inputsignFN {
    height: 45px;
  }
  
  input.inputsignMI {
    height: 45px;
  }
  

  
  .input100:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.input100 + .focus-input100::before {
    width: 100%;
  }
  

  .input100FN:focus + .focus-input100FN::before {
    width: 100%;
  }
  
  .has-val.input100FN + .focus-input100FN::before {
    width: 100%;
  }
  



  .input200:focus + .focus-input200::before {
    width: 100%;
  }
  
  .has-val.input200 + .focus-input200::before {
    width: 100%;
  }
  
















  .input100SN:focus + .focus-input100SN::before {
    width: 100%;
  }
  
  .has-val.input100SN + .focus-input100SN::before {
    width: 100%;
  }
  

  .input100MI:focus + .focus-input100MI::before {
    width: 100%;
  }
  
  .has-val.input100MI + .focus-input100MI::before {
    width: 100%;
  }
  








  .inputsign:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.inputsign + .focus-input100::before {
    width: 100%;
  }




  .inputsign:focus + .focus-input200::before {
    width: 100%;
  }
  
  .has-val.inputsign + .focus-input200::before {
    width: 100%;
  }












  .inputsignSN:focus + .focus-input100::before {
    width: 100%;
  }
  
  .has-val.inputsignSN + .focus-input100::before {
    width: 100%;
  }











  /*==================================================================
  [ Restyle Checkbox ]*/
  
  .input-checkbox100 {
    display: none;
  }
  
  .label-checkbox100 {
    font-family: Poppins-Regular;
    font-size: 13px;
    color: #999999;
    line-height: 1.4;
  
    display: block;
    position: relative;
    padding-left: 26px;
    cursor: pointer;
  }
  
  .label-checkbox100::before {
    content: "\f00c";
    font-family: FontAwesome;
    font-size: 13px;
    color: transparent;
  
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    justify-content: center;
    align-items: center;
    position: absolute;
    width: 18px;
    height: 18px;
    border-radius: 2px;
    background: #fff;
    border: 1px solid #e6e6e6;
    left: 0;
    top: 50%;
    -webkit-transform: translateY(-50%);
    -moz-transform: translateY(-50%);
    -ms-transform: translateY(-50%);
    -o-transform: translateY(-50%);
    transform: translateY(-50%);
  }
  
  .input-checkbox100:checked + .label-checkbox100::before {
    color: #57b846;
  }
  
  /*------------------------------------------------------------------
  [ Button ]*/
  .container-login100-form-btn {
    
    position: relative;
    margin-left: 350px;
    margin-top: 100px;
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }




  .container-login100-form-btn2 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }

  .container-login1001-form-btn {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }




  .container-login1001-form-btn2 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }





  
  .login100-form-btn {
     position: relative;
right: 20px;
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



  .login100-form-btn {
    position: relative;
   top:-20px;
   right: 222px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20px 20px;
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
 
  
  .login100-form-btn:hover {
    background-color: #a30707;
  }


  .login100-form1-btn:hover {
    background-color: #a30707;
  }




  
  .login100-form-btn2 {
    position: relative;
left:65px;
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
 
 

 .login100-form1-btn2 {
  position: relative;
left:65px;
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










  .container-login100-form-btn1 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }
  

  .container-login1001-form-btn1 {
    width: 100%;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
  }
  



  .login100-form-btn1 {
    position: relative;
  margin-left: 250px;
  margin-top: 90px;
   display: flex;
   gap: 10px;
   justify-content: center;
   align-items: center;
   padding: 20px;
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

 .login100-form-btn1 {
  position: relative;
margin-left: 250px;
margin-top: 90px;
 display: flex;
 gap: 10px;
 justify-content: center;
 align-items: center;
 padding: 20px;
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



   
 
  
  .login100-form-btn1:hover {
    background-color: #a30707;
  }
  
  .login100-form-btn2:hover {
    background-color: #a30707;
  }


    
  .login100-form1-btn1:hover {
    background-color: #a30707;
  }
  
  .login100-form1-btn2:hover {
    background-color: #a30707;
  }
  
  
.move{
    position: relative;
    left: 100px;
}



.addbtn{
    position: relative;
    left: 320px;
    padding: 6px 6px ;
            font-size: 11px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
         margin-top: 20px;

}



.addbtn:hover{
    background-color: #802c2c;
}




.cobtn{
  position: relative;

    padding: 6px 6px ;
            font-size: 11px;
            border: none;
            border-radius: 10px;
            background-color:  #aa3636;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
         margin-top: 20px;
}


.cobtn:hover{
  background-color: #802c2c;
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
    </style>
    </style>
</head>
<body>

<div class="limiter">
		<div class="container-login1001">
			<div class="wrap-login1001">
				<div class="login100-form1-title" style="background-image: url(./img/wmsu5.jpg);">
					<span class="login100-form1-title-1">
						reoc-wmsu portal
					</span>
					<h4 class="sign">REVIEWER ACCOUNT </h4>
				</div>
	
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action=""  class="login100-form1 validate-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">


         <div class="wrap-input100 validate-input m-b-26" data-validate="Email is required">
              <span class="label-input100">Email</span>
              <input class="input100"  type="email" id="email" name="email" required placeholder="Enter your email">
              <span class="focus-input100"></span>
        </div>



        <div class="wrap-input100 validate-input m-b-18" data-validate = "Password is required">
             <span class="label-input100">Password</span>
             <input class="input100" type="password" id="password" name="password" required placeholder="Enter your password">
             <span class="focus-input100"></span>
        </div>


        <div class="wrap-input100 validate-input m-b-18" data-validate = "Password is required">
            <span class="label-input100">Re-enter Password</span>
            <input  class="input100" type="password" id="re_password" name="re_password" required placeholder="Re-enter your password">
            <span class="focus-input100"></span>
       </div>

        <!-- Password rules message -->
        <div class="password-rules" style="margin-top:20px;">
            Password must be at least 6 characters long, and include:
            <ul>
                <li id="length" class="invalid">At least 6 characters long</li>
                <li id="uppercase" class="invalid">At least one uppercase letter (A-Z)</li>
                <li id="lowercase" class="invalid">At least one lowercase letter (a-z)</li>
                <li id="number" class="invalid">At least one number (0-9)</li>
                <li id="special" class="invalid">At least one special character (!, @, #, etc.)</li>
            </ul>
        </div>


        <div class="container-login100-form-btn2" style="margin-top:20px;">
        <button  class="login100-form-btn2" type="submit">Send Verification Code</button>
        </div>



    </form>
</div>
</div>
</div>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const rePasswordInput = document.getElementById('re_password');
    const lengthRule = document.getElementById('length');
    const uppercaseRule = document.getElementById('uppercase');
    const lowercaseRule = document.getElementById('lowercase');
    const numberRule = document.getElementById('number');
    const specialRule = document.getElementById('special');

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
</script>

</body>
</html>
