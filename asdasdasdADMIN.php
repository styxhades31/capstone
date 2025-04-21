<?php
session_start();
// Include database connection
require 'dbConnCode.php'; // Ensure you have a db_connection.php file for database connection
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to generate a random 6-digit verification code
function generateVerificationCode() {
    return sprintf('%06d', mt_rand(0, 999999));
}

// Initialize variables
$email = '';
$password = '';
$error = '';
$verificationCodeSent = false;
$verificationCodeInput = '';
$isCodeCorrect = false;

// Generate a CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize EmailInputisDone if it doesn't exist
if (empty($_SESSION['EmailInputisDone'])) {
    $_SESSION['EmailInputisDone'] = 0; // Default value
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid CSRF token.';
    } else {
        // Get the email and password from the POST request
        if (isset($_POST['email'])) {
            $email = trim($_POST['email']);
        }
        if (isset($_POST['password'])) {
            $password = trim($_POST['password']);
        }

        // Verify verification code if it's set
        if (isset($_POST['verification_code'])) {
            $verificationCodeInput = trim($_POST['verification_code']);

            // Check if verification code matches what is stored in the session
            if (isset($_SESSION['verification_code']) && $verificationCodeInput === $_SESSION['verification_code']) {
                $isCodeCorrect = true; // Verification code is correct
            } else {
                $error = "Incorrect verification code.";
            }
        }

        // If verification code hasn't been sent yet, handle sending it
        if (!$verificationCodeSent && !empty($email)) {
            // Validate email
            if (empty($email)) {
                $error = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format.";
            } else {
                // Generate a new verification code
                $verificationCode = generateVerificationCode(); 

                // Prepare SQL statement to store the verification code and temporary email holder
                $stmt = $conn->prepare("INSERT INTO users (isActive, verification_code, temporaryemailholder) VALUES (?, ?, ?)"); 
                $isActive = 0; // Set as inactive by default until verified

                // Execute statement to store verification code and temporary email holder
                if ($stmt->execute([$isActive, $verificationCode, $email])) {
                    // Send verification email
                    $mail = new PHPMailer(true);
                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'westkiria@gmail.com'; // Replace with your email
                        $mail->Password   = 'qpktvouqahvubayd'; // Replace with your email password
                        $mail->SMTPSecure = 'tls';
                        $mail->Port       = 587;

                        // Recipients
                        $mail->setFrom($mail->Username, 'West Kiria'); // Replace with your name
                        $mail->addAddress($email);

                        // Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Verification Code';
                        $mail->Body    = "Your verification code is: $verificationCode";
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );

                        $mail->send();

                        // Set success alert
                        $verificationCodeSent = true;
                        $_SESSION['verification_code'] = $verificationCode; // Store the verification code in session

                        // Store email in session
                        $_SESSION['email'] = $email; // <--- Place it here

                        // Set EmailInputisDone to 1
                        $_SESSION['EmailInputisDone'] = 1;

                    } catch (Exception $e) {
                        $error = "Message could not be sent. Mailer Error: " . htmlspecialchars($mail->ErrorInfo);
                    }
                } else {
                    $error = "Error creating verification entry: " . htmlspecialchars($stmt->errorInfo()[2]);
                }
            }
        }

        // If the code is correct, proceed to create the account
        if ($isCodeCorrect) {
            if (empty($_SESSION['email'])) {
                $error = "Email is required.";
            } else {
                $email = $_SESSION['email']; // Retrieve email from session
                if (empty($password)) {
                    $error = "Password is required.";
                } else {
                    // Hash the password before storing it
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Prepare SQL statement to update the user's email and password
                    $stmt = $conn->prepare("UPDATE users SET email = ?, password = ?, verification_code = NULL WHERE verification_code = ? AND temporaryemailholder = ?");
                    
                    // Execute statement with the additional condition
                    if ($stmt->execute([$email, $hashedPassword, $_SESSION['verification_code'], $email])) {
                        // Check if any row was affected
                        if ($stmt->rowCount() > 0) { // Use rowCount() for PDO
                            // Success handling (e.g., success message or redirect)
                            header("Location: success.php");
                            exit;
                        } else {
                            $error = "No changes made. Email might already be set or verification code is incorrect.";
                        }
                    } else {
                        $error = "Error updating account: " . htmlspecialchars($stmt->errorInfo()[2]);
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f5f5f5;
        }
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
        input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Create Admin Account</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <?php if (!$verificationCodeSent && $_SESSION['EmailInputisDone'] === 0): ?>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">

            <button type="submit">Send Verification Code</button>
        <?php elseif ($verificationCodeSent): ?>
            <label for="verification_code">Verification Code:</label>
            <input type="text" id="verification_code" name="verification_code" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Verify Code and Create Account</button>
        <?php else: ?>
            <div class="error">Please send the verification code first.</div>
        <?php endif; ?>
    </form>
</div>

</body>
</html>
