<?php
session_start();

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['user_id'])) {
    session_regenerate_id(true); // Regenerate session id on first visit
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
// Include the database connection file (assuming it's named db.php)
include('dbConnCode.php');

// Function to fetch users with their roles and active status
function getUsersWithRoles($search = '', $roleFilter = '', $statusFilter = '') {
    global $conn;
    
    // Base SQL query
    $sql = "SELECT DISTINCT u.id, u.email, u.isActive, u.number_of_reviews, r.name AS role_name
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id";

    
    $conditions = [];
    $params = [];
    $types = '';
    
    // Add search condition
    if (!empty($search)) {
        $conditions[] = "u.email LIKE ?";
        $params[] = "%" . $search . "%";
        $types .= 's';
    }
    
    // Add role filter condition
    if (!empty($roleFilter)) {
        $conditions[] = "r.name = ?";
        $params[] = $roleFilter;
        $types .= 's';
    } else {
        // If "All Roles" is selected, filter to Admin and Reviewer only
        $conditions[] = "r.name IN (?, ?)";
        $params[] = 'Admin';
        $params[] = 'Reviewer';
        $types .= 'ss';
    }

    // Add status filter condition
    if ($statusFilter !== '') { // Allow '0' as a valid filter
        $conditions[] = "u.isActive = ?";
        $params[] = $statusFilter;
        $types .= 'i';
    }
    
    // Append conditions to SQL
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    // Optionally, order by email
    $sql .= " ORDER BY u.email ASC";
    
    $stmt = $conn->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result;
}

// Function to get total user count based on filters
function getUserCount($search = '', $roleFilter = '', $statusFilter = '') {
    global $conn;

    $sql = "SELECT COUNT(DISTINCT u.id) AS user_count
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id";
    
    $conditions = [];
    $params = [];
    $types = '';
    
    // Add search condition
    if (!empty($search)) {
        $conditions[] = "u.email LIKE ?";
        $params[] = "%" . $search . "%";
        $types .= 's';
    }
    
    // Add role filter condition
    if (!empty($roleFilter)) {
        $conditions[] = "r.name = ?";
        $params[] = $roleFilter;
        $types .= 's';
    } else {
        // If "All Roles" is selected, filter to Admin and Reviewer only
        $conditions[] = "r.name IN (?, ?)";
        $params[] = 'Admin';
        $params[] = 'Reviewer';
        $types .= 'ss';
    }

    // Add status filter condition
    if ($statusFilter !== '') { // Allow '0' as a valid filter
        $conditions[] = "u.isActive = ?";
        $params[] = $statusFilter;
        $types .= 'i';
    }
    
    // Append conditions to SQL
    if (count($conditions) > 0) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc();
    
    return $count['user_count'];
}

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // Get search, role, and status parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

    // Fetch users based on the search and filter
    $users = getUsersWithRoles($search, $roleFilter, $statusFilter);

    // Get total user count based on the search and filter
    $userCount = getUserCount($search, $roleFilter, $statusFilter);

    // Prepare users data
    $usersData = [];
    while ($user = $users->fetch_assoc()) {
        $usersData[] = [
            'id' => $user['id'], // Include user ID for actions
            'email' => htmlspecialchars($user['email']),
            'role_name' => htmlspecialchars($user['role_name']),
            'isActive' => $user['isActive'] == 1 ? 'Active' : 'Inactive',
            'number_of_reviews' => $user['number_of_reviews'], // Add this line
            'action' => $user['isActive'] == 1 ? 'deactivate' : 'activate',
            'buttonText' => $user['isActive'] == 1 ? 'Deactivate' : 'Activate',
            'buttonClass' => $user['isActive'] == 1 ? 'deactivate' : ''
        ];
    }

    // Prepare response
    $response = [
        'users' => $usersData,
        'userCount' => $userCount
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Handle the activate/deactivate request
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    // Validate CSRF token if using GET for actions (better to use POST)
    // Consider changing action requests to POST for better security
    $userId = intval($_GET['user_id']);
    $action = $_GET['action'];
    
    // Check the action and update the isActive status accordingly
    if ($action === 'activate') {
        $updateSql = "UPDATE users SET isActive = 1 WHERE id = ?";
    } elseif ($action === 'deactivate') {
        $updateSql = "UPDATE users SET isActive = 0 WHERE id = ?";
    }
    
    if (isset($updateSql)) {
        // Prepare and execute the update query
        $stmt = $conn->prepare($updateSql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    // Redirect back to the same page after the update
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>




<!DOCTYPE html>
<html lang="en">
<head>
	<title>Manage Superuser Accounts</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<link rel="stylesheet" type="text/css" href="fonts/font-awesome-4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" type="text/css" href="./css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



    <link rel="stylesheet" type="text/css" href="./css/table.css">
    <link rel="icon" type="image/x-icon" href="./img/reoclogo1.jpg">
    

    <script defer src="./js/table.js"></script>
    <title>User Roles Management</title>
    <style>
        table {
            border-collapse: collapse;
            margin: 1em auto;
            width: 50%;
            margin-bottom: 100px;
        }

   
        th, td {
          background-color: rgb(139, 56, 56);
        
          padding: 8px;
          border: 1px solid #ccc;
          text-align: left;
      }


      th{
        font-size: 13px;
        text-align: center;
        color: white;
    }

    
    tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #ddd;
        }

        .button {
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 10px;
            cursor: pointer;
        }
        .button.deactivate {
            background-color: #aa3636;
            position: relative;
            left: 45px;
        }


        .button.deactivate :hover {
		background-color: #802c2c;

	}
        .filters {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-bar {
            padding: 5px;
            width: 300px;
        }
        .role-dropdown, .status-dropdown {
            padding: 5px;
            width: 180px;
        }
        .print-button {
            position: relative;
            padding: 5px 10px;
            left: 1450px;
            top: 70px;
            background-color: #2196F3;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
     
        @media print {
            .filters, .print-button, .logout-button {
                display: none;
            }
        }


        
        .search-box {
            position: relative;
            left: 180px;
            margin-bottom: 20px;
            padding: 8px;
            font-size: 16px;
            width: 100%;
            max-width: 300px;
            margin-bottom: 20px;
        }

        .print-btn {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
        }

        .print-btn:hover {
            background-color: #0056b3;
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





		.disablebtn{
			padding: 10px 20px; font-size: 16px; cursor: pointer;    
            background-color: #aa3636; /* Blue color */
          color: white; /* Text color */
          border: none; /* Remove border */
          border-radius: 9px; /* Rounded corners */
          padding: 5px 9px; /* Button size */
          font-size: 14px; /* Font size */
		  transition: background-color 0.3s;

	
	}

	.disablebtn:hover {
		background-color: #802c2c;

	}



    .table-filters{
        display: flex;
        gap: 5px;
    position: relative;
    margin-left: 450px;
    top: 40px;

}

.vision2 {
  background-color: #F8F7F4;
  position: relative;
  padding-top: 10px;
  padding-bottom: 50px;
  text-align: center; 
  margin-top:20px;
}


.searchlbl{
    position: relative;
    
}

.total{
    position: relative;
    margin-left: 1330px;
    bottom: 20px;
}
    </style>
</head>
    
<header>
  <a href="#" class="brand">
    <img src="img/logos.png" class="logo">
    <span class="reoc">Research Ethics Oversite Committee Portal</span>
  </a>

  <div class="menu-btn">
    <div class="navigation">
      <div class="navigation-items">
      <a href="adminHome.php">Home</a>
      <a href="admin_applicationforms.php">Application Forms</a>
      <a href="Account.php">Account Verifications</a>
      <a href="admin_activateSuperuser.php">Super Users</a>
   
   
       

        <!-- Logout Button -->
        <form method="POST" action="researcherHome.php" style="display: inline;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
          <button type="submit" name="logout" class="logout-button">Logout</button>
        </form>
      </div>
    </div>
  </div>
  </header>
<body>
 

<h1 class="vision2">Manage Accounts</h1>

    <!-- Search and Filter Section -->
    <div class="table-filters" style="display: flex; align-items: center;  gap: 5px;">
    <label for="filter-name" class="searchlbl">Search by email:</label>
        <input type="text" name="search" class="search-bar" placeholder="Search by email..." id="searchInput">

        <!-- Role dropdown filter -->
        <select name="role" class="role-dropdown" id="roleDropdown">
            <option value="">All Roles</option>
            <option value="Admin">Admin</option>
            <option value="Reviewer">Reviewer</option>
            <!-- Add more roles here if needed -->
        </select>

        <!-- Status dropdown filter -->
        <select name="status" class="status-dropdown" id="statusDropdown">
            <option value="">All Statuses</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
        </div>
        <!-- Print Button -->
        <button type="button" class="print-button" onclick="window.print()">Print</button>
    </div>

    <!-- Total count display -->
    <p id="totalCount" class="total"><strong>Total Users: </strong> 0</p>

    <!-- User Table -->
    <table id="userTable">
        <thead>
            <tr>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Number of Reviews</th> <!-- New column -->
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- Rows will be populated dynamically -->
        </tbody>
    </table>

	<footer class="footer">
		<div class="owl-carousel">
	  
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu55.jpg" alt="" />
		 
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu11.jpg" alt="" />
		  
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/reoc11.jpg" alt="" />
		   
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu22.jpg" alt="" />
		  
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/reoc22.jpg" alt="" />
		   
		  </a>
		  <a href="#" class="gallery__photo">
			<img src="img/wmsu44.jpg" alt="" />
		   
		  </a>
	  
		</div>
		<div class="footer__redes">
		  <ul class="footer__redes-wrapper">
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				Normal Road, Baliwasan, Z.C.
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				09112464566
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class=""></i>
				wmsureoc@gmail.com
			  </a>
			</li>
			<li>
			  <a href="#" class="footer__link">
				<i class="fab fa-phone-alt"></i>
				
			  </a>
			</li>
		  </ul>
		</div>
		<div class="separador"></div>
		<p class="footer__texto">RESEARCH ETHICS OVERSITE COMMITTEE - WMSU</p>
	  </footer>
	

	  
   
  
  
  
  <!-- partial -->

  
	<script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
	<script src='https://unpkg.com/feather-icons'></script>
	  <script src="./js/main.js"></script>
	  <script src="./js/swiper.js"></script>
	  <script src="./js/footer.js"></script>
	  <script src="./js/faq.js"></script>
	

	<script src="./js/fonts.js"></script>
  
  
    <!-- JavaScript to handle AJAX requests and dynamic updates -->
    <script>
       document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const roleDropdown = document.getElementById('roleDropdown');
    const statusDropdown = document.getElementById('statusDropdown');
    const userTableBody = document.querySelector('#userTable tbody');
    const totalCount = document.getElementById('totalCount');

    let debounceTimeout;

    // Function to fetch and display users
    function fetchUsers() {
        const search = searchInput ? searchInput.value.trim() : '';
        const role = roleDropdown ? roleDropdown.value : '';
        const status = statusDropdown ? statusDropdown.value : '';

        const params = new URLSearchParams({
            ajax: '1',
            search: search,
            role: role,
            status: status
        });

        fetch(`<?php echo $_SERVER['PHP_SELF']; ?>?${params.toString()}`)
            .then((response) => response.json())
            .then((data) => {
                // Update total count
                totalCount.textContent = `Total Users: ${data.userCount}`;

                // Populate user table
                populateUserTable(data.users);
            })
            .catch((error) => console.error('Error fetching users:', error));
    }

    // Function to populate the user table dynamically
    function populateUserTable(users) {
        userTableBody.innerHTML = '';
        if (users.length === 0) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 5; // Adjust colspan to include the new column
            cell.textContent = 'No users found.';
            cell.style.textAlign = 'center';
            row.appendChild(cell);
            userTableBody.appendChild(row);
            return;
        }

        users.forEach((user) => {
            const row = document.createElement('tr');

            const emailCell = document.createElement('td');
            emailCell.textContent = user.email;
            row.appendChild(emailCell);

            const roleCell = document.createElement('td');
            roleCell.textContent = user.role_name;
            row.appendChild(roleCell);

            const statusCell = document.createElement('td');
            statusCell.textContent = user.isActive ? 'Active' : 'Inactive';
            row.appendChild(statusCell);

            const reviewsCell = document.createElement('td'); // Editable column
            const reviewsInput = document.createElement('input');
            reviewsInput.type = 'number';
            reviewsInput.value = user.number_of_reviews || 0; // Display 0 if no value
            reviewsInput.min = 0;

            // Add debounce to the reviewsInput
            reviewsInput.addEventListener(
                'input',
                debounce(() => updateNumberOfReviews(user.id, reviewsInput.value), 500) // Debounce: triggers after 1 second
            );

            reviewsCell.appendChild(reviewsInput);
            row.appendChild(reviewsCell);

            const actionCell = document.createElement('td');
            const actionLink = document.createElement('a');
            actionLink.href = `?action=${user.action}&user_id=${user.id}`;
            actionLink.className = `button ${user.buttonClass}`;
            actionLink.textContent = user.buttonText;
            actionCell.appendChild(actionLink);
            row.appendChild(actionCell);

            userTableBody.appendChild(row);
        });
    }

    // Function to update the number of reviews
    function updateNumberOfReviews(userId, newValue) {
        const params = new URLSearchParams({
            user_id: userId,
            number_of_reviews: newValue
        });

        fetch('update_reviews.php', {
            method: 'POST',
            body: params,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            }
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Number of reviews updated successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to update number of reviews.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch((error) => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the reviews.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                console.error('Error updating number of reviews:', error);
            });
    }

    // Debounce function to delay the execution of the input event
    function debounce(func, delay) {
        let timer;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // Event listeners for search and filter dropdowns
    if (searchInput) searchInput.addEventListener('input', debounce(fetchUsers, 500));
    if (roleDropdown) roleDropdown.addEventListener('change', fetchUsers);
    if (statusDropdown) statusDropdown.addEventListener('change', fetchUsers);

    // Initial fetch
    fetchUsers();
});

    </script>
</body>
</html>
