<?php
session_start();
include('updateAppointments.php');
// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true);
}

// Check if the user is logged in and if their role is 'Admin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
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
        session_destroy();
        header("Location: login.php");
        exit();
    } else {
        echo "<script>alert('Invalid CSRF token.');</script>";
    }
}

// Database connection
require_once 'dbConnCode.php'; // Replace with your actual database connection file



$faculty_id = 1; // Replace with dynamic ID based on the schedule being edited

// Query to fetch the current picture for the faculty
$query = "SELECT `picture` FROM `faculty_members` WHERE `id` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($current_picture1);
$stmt->fetch();
$stmt->close();

$schedule_id = 1; // Replace with dynamic ID based on the schedule being edited

// Query to fetch the current picture for the schedule
$query = "SELECT `picture` FROM `Schedule` WHERE `id` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $schedule_id); // Bind the schedule_id dynamically
$stmt->execute();
$stmt->bind_result($current_picture2); // Get the current picture filename
$stmt->fetch();
$stmt->close();

// Fetch available months dynamically based on uploaded_at
$query = "SELECT DISTINCT DATE_FORMAT(uploaded_at, '%Y-%m-01') AS month 
          FROM Researcher_title_informations 
          ORDER BY month DESC";
$result = $conn->query($query);
$availableMonths = [];
while ($row = $result->fetch_assoc()) {
    $availableMonths[] = $row['month'];
}

/// Handle month selection with validation for empty $availableMonths
if (!empty($availableMonths)) {
    // Set selectedMonth from the URL parameter or default to the first available month
    $selectedMonth = $_GET['month'] ?? $availableMonths[0];
} else {
    // Handle the case where no months are available
    $selectedMonth = null; // Or any other fallback logic you prefer
}

// Fetch data for the selected month
$collegeDataQuery = "
    SELECT college, COUNT(*) AS count 
    FROM Researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    GROUP BY college";
$collegeDataResult = $conn->query($collegeDataQuery);

$collegeData = [];
while ($row = $collegeDataResult->fetch_assoc()) {
    $collegeData[] = [
        'college' => $row['college'],
        'count' => $row['count']
    ];
}

// Fetch data for Exempt research categories for the selected month
$exemptDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM Researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Exempt'
    GROUP BY research_category";
$exemptDataResult = $conn->query($exemptDataQuery);

$exemptData = [];
while ($row = $exemptDataResult->fetch_assoc()) {
    $exemptData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
}


// Fetch data for Expedited research categories for the selected month
$expeditedDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM Researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Expedited'
    GROUP BY research_category";
$expeditedDataResult = $conn->query($expeditedDataQuery);

$expeditedData = [];
while ($row = $expeditedDataResult->fetch_assoc()) {
    $expeditedData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
}

// Fetch data for Expedited research categories for the selected month
$expeditedDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM Researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Expedited'
    GROUP BY research_category";
$expeditedDataResult = $conn->query($expeditedDataQuery);

$expeditedData = [];
while ($row = $expeditedDataResult->fetch_assoc()) {
    $expeditedData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
}
// Fetch data for Full Review research categories for the selected month
$fullReviewDataQuery = "
    SELECT research_category, COUNT(*) AS count 
    FROM Researcher_title_informations 
    WHERE DATE_FORMAT(uploaded_at, '%Y-%m') = DATE_FORMAT('$selectedMonth', '%Y-%m') 
    AND type_of_review = 'Full Review'
    GROUP BY research_category";
$fullReviewDataResult = $conn->query($fullReviewDataQuery);

$fullReviewData = [];
while ($row = $fullReviewDataResult->fetch_assoc()) {
    $fullReviewData[] = [
        'research_category' => $row['research_category'],
        'count' => $row['count']
    ];
} 
// Query to get research categories and their counts
$query = "SELECT research_category, COUNT(*) as count FROM Researcher_title_informations GROUP BY research_category";
$result = $conn->query($query);

// Initialize arrays for categories and counts
$researchCategories = [];
$researchCounts = [];

while ($row = $result->fetch_assoc()) {
    $researchCategories[] = $row['research_category'];
    $researchCounts[] = $row['count'];
}

// Convert PHP arrays to JSON for use in JavaScript
$researchCategories = json_encode($researchCategories);
$researchCounts = json_encode($researchCounts);

// Prepare data for the chart (Full Review category)
$fullReviewCategories = json_encode(array_column($fullReviewData, 'research_category'));
$fullReviewCounts = json_encode(array_column($fullReviewData, 'count'));
// Prepare data for the chart (Expedited category)
$expeditedCategories = json_encode(array_column($expeditedData, 'research_category'));
$expeditedCounts = json_encode(array_column($expeditedData, 'count'));
// Prepare data for the chart (Exempt category)
$exemptCategories = json_encode(array_column($exemptData, 'research_category'));
$exemptCounts = json_encode(array_column($exemptData, 'count'));
// Prepare data for the chart
$collegeNames = json_encode(array_column($collegeData, 'college'));
$collegeCounts = json_encode(array_column($collegeData, 'count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Home</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    
    
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
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .logout-button:hover {
            background-color: #c82333;
        }

        .main-content {
            flex: 1;
            padding: 20px;
            
        }

        .filter-container {
            margin-bottom: 20px;
            text-align: center;
        }

        .manage-colleges {
            margin-bottom: 20px;
            text-align: center;
            
            
        }

        .action-button {
            background-color: #800000;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-transform: uppercase;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .action-button:hover {
            background-color: #dc3545;
            color: white;

        }

        .charts-container {
    display: flex;
    justify-content: space-around; /* Evenly distribute charts */
    gap:40px; /* Space between the charts */
    flex-wrap: wrap; /* Wrap if screen is too small */
    align-items: flex-start; /* Align items at the top */
}

.chart-item {
    flex: 1; /* Equal space for each chart */
    max-width: 300px; /* Ensure a consistent width */
    min-width: 250px; /* Minimum width to maintain layout */
    height: 400px; /* Fixed height for all charts */
    display: flex;
    flex-direction: column;
    align-items: center; /* Center content */
    text-align: center; /* Align chart titles centrally */
}

.chart-item h3 {
    margin-bottom: 10px; /* Add some spacing below the title */
}
        .footer {
            background-color: #800000;
            color: white;
            text-align: center;
            padding: 10px;
        }  
        h3 {
        text-align: left;  /* Center-align the text */
        color: #333;         /* Set text color */
        font-size: 24px;     /* Set font size */
        margin-bottom: 20px; /* Add some space below the header */
    }
     /* Print-specific styles */
     @media print {
            body {
                margin: 0;
                padding: 0;
                font-family: Arial, sans-serif;
            }

            .header, .footer {
                display: none; /* Hide header and footer for printing */
            }

            .charts-container {
                display: block;
                width: 100%;
                
            }

            .chart-item {
                width: 100%;
                max-width: 500px;
                margin: 0 auto 20px;
                page-break-inside: avoid; /* Prevent charts from being split across pages */
            }

            .chart-item canvas {
                width: 100% !important; /* Ensure the canvas fits the page */
                height: auto !important;
            }

            .filter-container {
                display: none; /* Hide the filter dropdown for printing */
            }

            .manage-colleges {
                display: none; /* Hide Manage Colleges button for printing */
            }
        }
        #vmForm {
        display: none; /* Initially hidden */
        position: fixed; /* Position it fixed in the viewport */
        top: 50%; /* Center vertically */
        left: 50%; /* Center horizontally */
        transform: translate(-50%, -50%); /* Adjust for centering */
        background-color: #fff; /* White background */
        padding: 20px; /* Padding for the form */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow for depth */
        border-radius: 8px; /* Rounded corners */
        z-index: 1000; /* Ensure it appears above other elements */
        width: 90%; /* Responsive width */
        max-width: 500px; /* Max width for larger screens */
    }
    .cover {
        position: absolute;
        background-color: rgba(0, 255, 0, 0.5); /* Semi-transparent green */
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        pointer-events: none; /* Allow interaction with the input beneath */
        display: none; /* Initially hidden */
    }

    .wrapper {
        position: relative; /* To position the cover relative to the input */
        display: inline-block; /* Match the size of the input field */
    }
    </style>
</head>
<body>
    <!-- Header Section -->
    <div class="header">
        <div class="header-content">
            <h1>Research Ethics Oversight Committee Portal</h1>
            <div class="navbar">
                <a href="adminHome.php">Home</a>
                <a href="admin_applicationforms.php">Application forms</a>
                <a href="admin_useraccounts.php">User accounts</a>
                <a href="admin_analytics.php">Analytics</a>
                <a href="Account.php">Accounts</a>
            </div>
        </div>
        
        <!-- Logout Button -->
        <form method="POST" action="adminHome.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
    </div>
    
<!-- Unavailable Dates -->
 
<form action="admin_mark_unavailable.php" method="POST">
    <label for="dates">Select Unavailable Dates:</label><br>
    <input type="text" name="dates" id="dates" class="datepicker" placeholder="Click to select dates"><br>
    <button type="submit">Submit Unavailable Dates</button>
</form>


    <!-- Main Content -->
    <div class="main-content">
        <h2>Analytics Dashboard</h2>
 <!-- Print Button -->
 <div style="text-align: center; margin-bottom: 20px;">
            <button onclick="window.print()" class="action-button">Print Charts</button>
        </div>

        <!-- Edit Faculty Display Button -->
<button class="action-button" onclick="openFacultyModal()">Edit Faculty Display</button>

<!-- Faculty Modal -->
<div id="facultyModal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeFacultyModal()">&times;</span>
        <h2>Edit Faculty Display</h2>

        <!-- Form for uploading picture -->
        <form id="facultyForm" action="edit_faculty.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="faculty_id" value="1"> <!-- Default ID for the faculty -->
            
            <!-- Current Picture -->
            <div id="current-picture-container">
                <?php if ($current_picture1): ?>
                    <img id="current-picture" src="Faculty Members/<?php echo $current_picture1; ?>" alt="Current Picture" style="width: 200px;">
                    <button type="button" id="remove-picture" onclick="removePicture()">Remove Picture</button>
                <?php else: ?>
                    <p>No picture available</p>
                <?php endif; ?>
            </div>
            
            <!-- New Picture Upload -->
            <label for="faculty_picture">Upload New Picture:</label>
            <input type="file" name="faculty_picture" id="faculty_picture"><br>
            
            <!-- Submit Button -->
            <button type="submit" class="action-button">Save Changes</button>
        </form>
    </div>
</div>

<!-- Edit Schedule Display Button -->
<button class="action-button" onclick="openScheduleModal()">Edit Schedule Display</button>

<!-- Schedule Modal -->
<div id="scheduleModal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeScheduleModal()">&times;</span>
        <h2>Edit Schedule Display</h2>

        <!-- Form for uploading picture -->
        <form id="scheduleForm" action="edit_schedule.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="schedule_id" value="1"> <!-- Default ID for the schedule -->
            
            <!-- Current Picture -->
            <div id="current-picture-container">
                <?php if ($current_picture2): ?>
                    <img id="current-picture" src="Schedules/<?php echo $current_picture2; ?>" alt="Current Picture" style="width: 200px;">
                    <button type="button" id="remove-picture" onclick=" removeSchedulePicture()">Remove Picture</button>
                <?php else: ?>
                    <p>No picture available</p>
                <?php endif; ?>
            </div>
            
            <!-- New Picture Upload -->
            <label for="schedule_picture">Upload New Picture:</label>
            <input type="file" name="schedule_picture" id="schedule_picture"><br>
            
            <!-- Submit Button -->
            <button type="submit" class="action-button">Save Changes</button>
        </form>
    </div>
</div>

<button class="action-button" type="button" onclick="document.getElementById('vmForm').style.display='block';">Edit Vision and Mission</button>

<!-- Form to edit Vision and Mission -->
<div id="vmForm" style="display:none;">
    <h4>Edit Vision and Mission</h4>
    <form action="edit_vm.php" method="post">
        <?php
        // Fetch the vision and mission statements from the database
        require 'dbConnCode.php'; // Include your database connection

        $sql_vm = "SELECT * FROM vision_mission";
        $result_vm = $conn->query($sql_vm);

        // Check if any vision or mission exists
        if ($result_vm && $result_vm->num_rows > 0) {
            while ($row = $result_vm->fetch_assoc()) {
                echo "<label>" . htmlspecialchars($row['statement_type']) . ":</label><br>";
                echo "<textarea name='content[]' rows='4' cols='50'>" . htmlspecialchars($row['content']) . "</textarea><br>";
                echo "<input type='hidden' name='id[]' value='" . $row['id'] . "'><br>";
            }
        } else {
            // No vision or mission exists, allow user to create one
            echo "<label>Vision:</label><br>";
            echo "<textarea name='content[]' rows='4' cols='50' placeholder='Enter your vision here...'></textarea><br>";
            echo "<input type='hidden' name='id[]' value='new_vision'><br>"; // Placeholder for new vision ID
            
            echo "<label>Mission:</label><br>";
            echo "<textarea name='content[]' rows='4' cols='50' placeholder='Enter your mission here...'></textarea><br>";
            echo "<input type='hidden' name='id[]' value='new_mission'><br>"; // Placeholder for new mission ID
        }
        ?>
        <input type="submit" value="Save Changes">
    </form>
</div>

        <!-- Manage Colleges Button -->
        <div class="manage-colleges">
            <a href="colleges.php">
                <button class="action-button">Manage Colleges</button>
            </a>
        </div>

        

        <!-- Filter Dropdown -->
        <div class="filter-container">
            <form method="GET" action="adminHome.php">
                <label for="month">Select Month:</label>
                <select name="month" id="month" onchange="this.form.submit()">
                    <?php foreach ($availableMonths as $month): ?>
                        <option value="<?php echo $month; ?>" <?php echo ($month === $selectedMonth) ? 'selected' : ''; ?>>
                            <?php echo date("F Y", strtotime($month)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
       
        <!-- Chart Container -->
        <div class="charts-container">
<div class="charts-container">
    <div class="chart-item">
    <h3>Colleges/Institutions</h3>
        <canvas id="collegePieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3>Exempt Review</h3>
        <canvas id="exemptPieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3>Expedited Research</h3>
        <canvas id="expeditedPieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3>Full Review</h3>
        <canvas id="fullReviewPieChart"></canvas>
    </div>
    <div class="chart-item">
    <h3>Number of Research per Category</h3>
        <canvas id="researchCategoryPieChart"></canvas>
    </div>
</div>
    </div>
    </div>

    <!-- Footer Section -->
    <div class="footer">
        <p>Research Ethics Compliance Portal © 2024</p>
    </div>

    <script>
    const ctx = document.getElementById('collegePieChart').getContext('2d');

// Modify the college names to exclude the part after the '-'
const modifiedCollegeNames = <?php echo $collegeNames; ?>.map(name => {
    // Split the name by '-' and take the first part (before the dash)
    return name.split(' -')[0];
});

const collegeCounts = <?php echo $collegeCounts; ?>; // Assuming this is an array with counts corresponding to the college names

const collegePieChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: modifiedCollegeNames,  // Modified names without '-'
        datasets: [{
            data: collegeCounts, // Counts corresponding to the college names
            backgroundColor: [
        "cornflowerblue",
        "olivedrab",
        "orange",
        "tomato",
        "crimson",
        "purple",
        "turquoise",
        "forestgreen",
        "navy",
        "magenta",
        "cyan",
        "yellow",
        "red",
        "gold",
        "bronze",
        "violet",
        "limegreen",
        "blue",
        "bronze",
        "silver",
        "teal",
        "azure",
        "salmon",
        "orchid",
        "darkblue",
        "darkred",
        "beige",
        "ruby",
        "gray",
        "sienna",
        "charcoal",
        "pearl",
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Modify the label text to append the count (collegeCounts[index])
                            label.text = `${label.text} (${collegeCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});


    const ctxExempt = document.getElementById('exemptPieChart').getContext('2d');

// Modify the exempt categories names to ensure proper display
const exemptCategories = <?php echo $exemptCategories; ?>; // e.g., ["WMSU - Category A", "WMSU - Category B"]
const exemptCounts = <?php echo $exemptCounts; ?>;         // e.g., [15, 25]

const exemptPieChart = new Chart(ctxExempt, {
    type: 'pie',
    data: {
        labels: exemptCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: exemptCounts,
            backgroundColor: [
        "cornflowerblue",
        "olivedrab",
        "orange",
        "tomato",
        "crimson",
        "purple",
        "turquoise",
        "forestgreen",
        "navy",
        "magenta",
        "cyan",
        "yellow",
        "red",
        "gold",
        "bronze",
        "violet",
        "limegreen",
        "blue",
        "bronze",
        "silver",
        "teal",
        "azure",
        "salmon",
        "orchid",
        "darkblue",
        "darkred",
        "beige",
        "ruby",
        "gray",
        "sienna",
        "charcoal",
        "pearl",
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists

                            // Append the count to the label
                            label.text = `${labelName} (${exemptCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});


const ctxExpedited = document.getElementById('expeditedPieChart').getContext('2d');

// Modify the exempt categories names to ensure proper display
const expeditedCategories = <?php echo $expeditedCategories; ?>; // e.g., ["WMSU - Category A", "WMSU - Category B"]
const expeditedCounts = <?php echo $expeditedCounts; ?>;         // e.g., [15, 25]

const expeditedPieChart = new Chart(ctxExpedited, {
    type: 'pie',
    data: {
        labels: expeditedCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: expeditedCounts,
            backgroundColor: [
        "cornflowerblue",
        "olivedrab",
        "orange",
        "tomato",
        "crimson",
        "purple",
        "turquoise",
        "forestgreen",
        "navy",
        "magenta",
        "cyan",
        "yellow",
        "red",
        "gold",
        "bronze",
        "violet",
        "limegreen",
        "blue",
        "bronze",
        "silver",
        "teal",
        "azure",
        "salmon",
        "orchid",
        "darkblue",
        "darkred",
        "beige",
        "ruby",
        "gray",
        "sienna",
        "charcoal",
        "pearl",
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists

                            // Append the count to the label
                            label.text = `${labelName} (${expeditedCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});

const ctxFullReview = document.getElementById('fullReviewPieChart').getContext('2d');

// Modify the full review categories names to ensure proper display
const fullReviewCategories = <?php echo $fullReviewCategories; ?>; // e.g., ["WMSU - Category A", "WMSU - Category B"]
const fullReviewCounts = <?php echo $fullReviewCounts; ?>;         // e.g., [10, 20]

const fullReviewPieChart = new Chart(ctxFullReview, {
    type: 'pie',
    data: {
        labels: fullReviewCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: fullReviewCounts,
            backgroundColor: [
                "cornflowerblue",
        "olivedrab",
        "orange",
        "tomato",
        "crimson",
        "purple",
        "turquoise",
        "forestgreen",
        "navy",
        "magenta",
        "cyan",
        "yellow",
        "red",
        "gold",
        "bronze",
        "violet",
        "limegreen",
        "blue",
        "bronze",
        "silver",
        "teal",
        "azure",
        "salmon",
        "orchid",
        "darkblue",
        "darkred",
        "beige",
        "ruby",
        "gray",
        "sienna",
        "charcoal",
        "pearl",
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',  // Move the legend below the chart
                align: 'start', // Align labels to the left
                labels: {
                    boxWidth: 20,  // Increase box width for better visibility
                    padding: 10,   // Adjust the space between legend items
                    font: {
                        size: 12  // Adjust font size as needed
                    },
                    usePointStyle: true,  // This makes the boxes circular instead of squares
                    boxHeight: 15, // Adjust box height for the circular style
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists

                            // Append the count to the label
                            label.text = `${labelName} (${fullReviewCounts[index]})`;

                            // Optional: Customize box color (if needed for dataset index)
                            label.fillStyle = label.datasetIndex === 0 ? '#007bff' : label.fillStyle;
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        // Display the category and its count in the tooltip
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});

const ctxResearchCategory = document.getElementById('researchCategoryPieChart').getContext('2d');

// Data for the research category chart
const researchCategories = <?php echo $researchCategories; ?>; // e.g., ["WMSU - Biology", "WMSU - Chemistry"]
const researchCounts = <?php echo $researchCounts; ?>;         // e.g., [10, 20]

const researchCategoryPieChart = new Chart(ctxResearchCategory, {
    type: 'pie',
    data: {
        labels: researchCategories, // Original categories; they will be modified dynamically
        datasets: [{
            data: researchCounts,
            backgroundColor: [
               
        "cornflowerblue",
        "olivedrab",
        "orange",
        "tomato",
        "crimson",
        "purple",
        "turquoise",
        "forestgreen",
        "navy",
        "magenta",
        "cyan",
        "yellow",
        "red",
        "gold",
        "bronze",
        "violet",
        "limegreen",
        "blue",
        "bronze",
        "silver",
        "teal",
        "azure",
        "salmon",
        "orchid",
        "darkblue",
        "darkred",
        "beige",
        "ruby",
        "gray",
        "sienna",
        "charcoal",
        "pearl",
    
            ],
            borderColor: '#fff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                align: 'start',
                labels: {
                    boxWidth: 20,
                    padding: 10,
                    font: {
                        size: 12
                    },
                    usePointStyle: true,
                    boxHeight: 15,
                    generateLabels: function(chart) {
                        const original = Chart.overrides.pie.plugins.legend.labels.generateLabels;
                        const labels = original.call(this, chart);

                        labels.forEach((label, index) => {
                            // Trim label by splitting and removing 'WMSU'
                            let labelName = label.text.split(' -')[0].trim();  // Take the first part before ' -'
                            labelName = labelName.replace('WMSU', '').trim();  // Remove 'WMSU' if exists
                            label.text = `${labelName} (${researchCounts[index]})`; // Add count to the label
                        });

                        return labels;
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return `${context.label}: ${context.raw}`;
                    }
                }
            }
        }
    }
});

function openFacultyModal() {
    

    // Show the modal
    document.getElementById("facultyModal").style.display = "block";
}

// Close Modal
function closeFacultyModal() {
    document.getElementById("facultyModal").style.display = "none";
}


function removePicture() {
    const facultyId = document.querySelector('input[name="faculty_id"]').value;

    // Show SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure you want to remove the current picture?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to remove the picture
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "remove_picture.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove the image from the modal and hide the remove button
                        document.getElementById("current-picture").remove();
                        document.getElementById("remove-picture").style.display = 'none';
                        Swal.fire({
                            title: 'Picture removed successfully.',
                            icon: 'success'
                        }).then(function() {
                            // Redirect to adminHome.php after success
                            window.location.href = "adminHome.php"; // Redirect to the admin home page
                        });
                    } else {
                        Swal.fire({
                            title: 'Error removing the picture.',
                            icon: 'error'
                        });
                    }
                }
            };
            xhr.send("faculty_id=" + facultyId);
        }
    });
}

// Function to open the Schedule Modal
function openScheduleModal() {
    // Show the modal
    document.getElementById("scheduleModal").style.display = "block";
}

// Function to close the Schedule Modal
function closeScheduleModal() {
    document.getElementById("scheduleModal").style.display = "none";
}


// Function to remove the picture for a schedule
function removeSchedulePicture() {
    const scheduleId = document.querySelector('input[name="schedule_id"]').value;

    // Show SweetAlert confirmation dialog
    Swal.fire({
        title: 'Are you sure you want to remove the current picture?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it!',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to remove the picture
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "remove_schedule_picture.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Remove the image from the modal and hide the remove button
                        document.getElementById("current-picture").remove();
                        document.getElementById("remove-picture").style.display = 'none';
                        Swal.fire({
                            title: 'Picture removed successfully.',
                            icon: 'success'
                        }).then(function() {
                            // Refresh the page after success
                            window.location.href = "adminHome.php"; // Redirect to adminHome.php
                        });
                    } else {
                        Swal.fire({
                            title: response.message || 'Error removing the picture.',
                            icon: 'error'
                        });
                    }
                }
            };
            xhr.send("schedule_id=" + scheduleId);
        }
    });
}

flatpickr("#dates", {
    mode: "multiple",           // Allow multiple date selection
    dateFormat: "Y-m-d",        // Date format
    allowInput: false,          // Prevent manual typing
    defaultDate: null,          // Remove default date
    disableMobile: true,        // Disable mobile UI
    minDate: "today",           // Disable past dates
    onChange: function(selectedDates) {
        // Store the selected dates in a hidden input field (for POST submission)
        document.getElementById("dates").value = selectedDates.map(function(date) {
            return date.toISOString().split('T')[0]; // Convert date to YYYY-MM-DD format
        }).join(',');
    }
});

</script>

</body>
</html>
