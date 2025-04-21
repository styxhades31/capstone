<!DOCTYPE html>
<html lang="en">
<head>
	<title>Admin Signup</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/login1.css">
	<link rel="stylesheet" type="text/css" href="./css/login2.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
</head>
    <style>
 

      
        button {
            padding: 10px 20px;
            margin: 10px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        form {
            margin-top: 20px;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
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
</head>
<body>

<div class="limiter">
		<div class="container-login100">
			<div class="wrap-login100">
				<div class="login100-form-title" style="background-image: url(./img/wmsu5.jpg);">
					<span class="login100-form-title-1">
						reoc-wmsu portal
					</span>
					<h4 class="sign">ADMIN ACCOUNT </h4>
				</div>

    
    <!-- Admin and Reviewer selection -->
    <div class="container-login100-form-btn2" style="padding-left:15px; padding-right:50px; padding-top:80px;padding-bottom:80px;">
        <button class="login100-form-btn2" onclick="redirectToForm('Admin')">Create Admin Account</button>
        <button class="login100-form-btn2" onclick="redirectToForm('Reviewer')">Create Reviewer Account</button>
    </div>
     
</div>
</div>
</div>
<script>
    function redirectToForm(role) {
        // Prevent potential XSS by encoding the role
        const safeRole = encodeURIComponent(role);

         // Redirect to the appropriate PHP file based on the role
         if (safeRole === 'Admin') {
            window.location.href = 'superSendcode.php?role=' + safeRole; // For Admin
        } else if (safeRole === 'Reviewer') {
            window.location.href = 'superSendcoderRev.php?role=' + safeRole; // For Reviewer
        } else {
            alert("Invalid role."); // Show an alert if the role is invalid
        }
    }
</script>


</body>
</html>
