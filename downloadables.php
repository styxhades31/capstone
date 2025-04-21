<?php
session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true); // Regenerate session id on first visit
}

// Check if the user is logged in and if their role is 'Researcher'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Researcher') {
    header("Location: login.php");
    exit();
}

// Start CSRF token generation if not already set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Logout logic
if (isset($_POST['logout'])) {
    // Validate CSRF token
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        session_destroy(); // Destroy the session to log the user out
        header("Location: login.php");
        exit();
    } else {
        echo "<script>alert('Invalid CSRF token.');</script>";
    }
}

// Define the base directory path
$baseDirectoryPath = 'C:/xampp/htdocs/REOC/';

// Folders and their respective titles
$folders = [
    'REOC Application Form' => 'Application Form',
    'Exempt Form' => 'Exempt',
    'Expedited and Full Form' => 'Expedited and Full (with Human Participants)',
];

// Array to hold files for each section
$filesByFolder = [];

// Read files from each folder
foreach ($folders as $folder => $title) {
    $directoryPath = $baseDirectoryPath . $folder;
    if (is_dir($directoryPath)) {
        $files = array_diff(scandir($directoryPath), array('..', '.'));
        // Only keep files (exclude directories)
        $filesByFolder[$folder] = array_filter($files, function($file) use ($directoryPath) {
            return is_file($directoryPath . '/' . $file);
        });
    } else {
        echo "<script>alert('Folder $folder does not exist.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Downloadables - Researcher Dashboard</title>
    <link rel="stylesheet" href="styles.css"> <!-- Add your own styles -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .header {
            background-color: #800000;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-content {
            display: flex;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            margin-right: 20px;
        }
        .navbar {
            display: flex;
            gap: 10px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            padding: 10px;
            transition: color 0.3s;
        }
        .navbar a:hover {
            color: #dc3545;
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

        .main-content {
            flex: 1;
            padding: 20px;
        }
        .footer {
            background-color: #800000;
            color: white;
            text-align: center;
            padding: 10px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            margin: 5px 0;
        }
        a.download-link {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        a.download-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<div class="header">
    <div class="header-content">
        <h1>Research Ethics Oversight Committee Portal</h1>
        <div class="navbar">
            <a href="researcherHome.php">Home</a>
            <a href="SubmitFiles.php">Submit Paper</a>
            <a href="downloadables.php">Downloadables</a>
            <a href="Account.php">Account</a>
        </div>
    </div>
    
    <!-- Logout Button -->
    <form method="POST" action="downloadables.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
        <button type="submit" name="logout" class="logout-button">Logout</button>
    </form>
</div>

<!-- Main Content -->
<div class="main-content">
    <h2>Downloadable Research Ethics Review Application Forms</h2>

    <?php foreach ($filesByFolder as $folder => $files): ?>
        <h3><?php echo htmlspecialchars($folders[$folder]); ?></h3>
        <ul>
            <?php foreach ($files as $file): ?>
                <li>
                    <a href="<?php echo rawurlencode($folder) . '/' . rawurlencode($file); ?>" download class="download-link">
                        <?php echo htmlspecialchars($file); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div>

<!-- Footer Section -->
<div class="footer">
    <p>Research Ethics Compliance Portal © 2024</p>
</div>

</body>
</html>
