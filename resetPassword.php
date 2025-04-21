<?php
session_start();
$error = ''; // To store error messages
$success = ''; // To store success messages

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $email = $_SESSION['email']; // Retrieve the email stored in session

    // Validation: Check if the passwords match and are not empty
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in both password fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match. Please try again.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!preg_match('/[A-Z]/', $newPassword)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $newPassword)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/\d/', $newPassword)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
        $error = 'Password must contain at least one special character.';
    } else {
        require 'dbConnCode.php'; // Include database connection

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the password in the database
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashedPassword, $email);
        if ($updateStmt->execute()) {
            $success = 'Your password has been successfully reset. You can now log in with your new password.';
            unset($_SESSION['email']); // Clear the email session after successful reset
        } else {
            $error = 'Failed to reset your password. Please try again.';
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
	<title>Reset Password</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/login1.css">
	<link rel="stylesheet" type="text/css" href="./css/login2.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    
    <style>


      
    
       
        .error {
            color: red;
        }
        .password-rules {
            font-size: 0.9em;
            color: #666;
        }
        .valid {
            color: green;
        }
        .invalid {
            color: red;
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
    filter: blur(2px); }
    
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
    
  
      [ login ]*/
  
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
    width: 900px;
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
    margin-left: 135px;
    margin-top: 50px;
    display: -webkit-box;
    display: -webkit-flex;
    display: -moz-box;
    display: -ms-flexbox;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    padding: 20px 88px 93px 55px;
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
					<h4 class="sign">Verify Code </h4>
				</div>


    <!-- Reset Password Form -->
    <form class="login100-form validate-form" method="POST" action="">

    <div class="wrap-input100 validate-input m-b-26" data-validate="Password is required">
        <label  class="label-input100" for="password">New Password:</label>
        <input class="input100" type="password" id="password" name="password" required placeholder="Enter new password" />
        <span class="focus-input100"></span>
    </div>



    <div class="wrap-input100 validate-input m-b-26" data-validate="Password is required">
        <label class="label-input100" for="confirmPassword">Confirm Password:</label>
        <input  class="input100" type="password" id="confirmPassword" name="confirmPassword" required placeholder="Confirm new password" />
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
                         <button class="login100-form-btn2" type="submit">Reset Password</button>
                         </div>
    </form>
    </div>
    </div>
    </div>

 <!-- Display error or success messages -->
 <script>
    const errorMessage = <?php echo json_encode($error); ?>;
    const successMessage = <?php echo json_encode($success); ?>;

    // Show success message
    if (successMessage) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessage,
        }).then(() => {
            window.location.href = 'login.php'; // Redirect to login after success
        });
    }

    // Show error message
    if (errorMessage) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessage,
        });
    }
    </script>

    <script>
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const lengthRule = document.getElementById('length');
    const uppercaseRule = document.getElementById('uppercase');
    const lowercaseRule = document.getElementById('lowercase');
    const numberRule = document.getElementById('number');
    const specialRule = document.getElementById('special');

    // Function to update validation feedback
    function updateValidation() {
        const password = passwordInput.value;

        // Validate password length
        if (password.length >= 6) {
            lengthRule.classList.remove('invalid');
            lengthRule.classList.add('valid');
        } else {
            lengthRule.classList.remove('valid');
            lengthRule.classList.add('invalid');
        }

        // Validate uppercase letter
        if (/[A-Z]/.test(password)) {
            uppercaseRule.classList.remove('invalid');
            uppercaseRule.classList.add('valid');
        } else {
            uppercaseRule.classList.remove('valid');
            uppercaseRule.classList.add('invalid');
        }

        // Validate lowercase letter
        if (/[a-z]/.test(password)) {
            lowercaseRule.classList.remove('invalid');
            lowercaseRule.classList.add('valid');
        } else {
            lowercaseRule.classList.remove('valid');
            lowercaseRule.classList.add('invalid');
        }

        // Validate number
        if (/\d/.test(password)) {
            numberRule.classList.remove('invalid');
            numberRule.classList.add('valid');
        } else {
            numberRule.classList.remove('valid');
            numberRule.classList.add('invalid');
        }

        // Validate special character
        if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
            specialRule.classList.remove('invalid');
            specialRule.classList.add('valid');
        } else {
            specialRule.classList.remove('valid');
            specialRule.classList.add('invalid');
        }

    
    }

    // Add event listeners for password and confirm password fields
    passwordInput.addEventListener('input', updateValidation);
    confirmPasswordInput.addEventListener('input', updateValidation);
    </script>
</body>
</html>
