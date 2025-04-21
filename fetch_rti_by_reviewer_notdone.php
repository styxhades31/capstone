<?php
include 'dbConnCode.php'; // Your DB connection file


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reviewerId = $_POST['reviewer_id'];

    // Build the query
    $query = "
    SELECT rti.id, 
           rti.Revision_status, 
           rti.study_protocol_title, 
           rti.research_category, 
           rti.new_date_column,
           rti.college,
           rti.adviser_name, 
           rti.payment,                
           rti.type_of_review,         
           a.appointment_date,
           rp.mobile_number,
           u.email,
           ar.researcher_info_id       
    FROM researcher_title_informations AS rti
    LEFT JOIN appointments AS a ON rti.id = a.researcher_title_id
    LEFT JOIN researcher_profiles AS rp ON rti.user_id = rp.user_id
    LEFT JOIN users AS u ON rti.user_id = u.id
    LEFT JOIN assign_reviewer AS ar ON rti.id = ar.researcher_info_id
    WHERE rti.Revision_status != 'Done'AND ar.researcher_info_id IS NOT NULL" ;

    // Add reviewer filter if reviewerId is selected
    if (!empty($reviewerId)) {
        $query .= " AND ar.user_id = ?";
    }

    $query .= " ORDER BY rti.uploaded_at DESC";

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!empty($reviewerId)) {
        $stmt->bind_param("i", $reviewerId);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Generate the table rows
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td class='restitle'>" . htmlspecialchars($row['study_protocol_title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['research_category']) . "</td>";
            echo "<td>" . htmlspecialchars($row['college']) . "</td>";
            echo "<td>" . htmlspecialchars($row['adviser_name']) . "</td>";
            echo "<td>" . ($row['appointment_date'] ? htmlspecialchars($row['new_date_column']) : "No Schedule") . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mobile_number']) . "</td>";
            echo "<td>
            <select class='payment-dropdown' data-id='" . $row['id'] . "'>
                <option value='None' " . ($row['payment'] === 'None' ? 'selected' : '') . ">None</option>
                <option value='Issued Payment Slip' " . ($row['payment'] === 'Issued Payment Slip' ? 'selected' : '') . ">Issued Payment Slip</option>
                <option value='Paid' " . ($row['payment'] === 'Paid' ? 'selected' : '') . ">Paid</option>
            </select>
          </td>";

    // Type of Review dropdown
    echo "<td>
            <select class='type-review-dropdown' data-id='" . $row['id'] . "'>
                <option value='For Initial Review' " . ($row['type_of_review'] === 'For Initial Review' ? 'selected' : '') . ">For Initial Review</option>
                <option value='Initial Review' " . ($row['type_of_review'] === 'Initial Review' ? 'selected' : '') . ">Initial Review</option>
                <option value='Full Review' " . ($row['type_of_review'] === 'Full Review' ? 'selected' : '') . ">Full Review</option>
                <option value='Expedited' " . ($row['type_of_review'] === 'Expedited' ? 'selected' : '') . ">Expedited</option>
                <option value='Exempt' " . ($row['type_of_review'] === 'Exempt' ? 'selected' : '') . ">Exempt</option>
            </select>
          </td>";
          
            echo "<td><button class='assign-btn' data-id='" . $row['id'] . "'>Assign</button></td>";
            echo "<td><button class='view-btn' data-id='" . $row['id'] . "'>View</button></td>";
            echo "<td><button class='view-files-btn' data-id='" . $row['id'] . "'>View Files</button></td>";
            echo "<td><button class='generate-btn' data-id='" . $row['id'] . "'>Generate</button></td>";
            echo "<td><button class='edit-btn' data-id='" . $row['id'] . "'>Edit</button></td>";
            echo "</tr>";
        }
    } else {
        // Add a placeholder row with a class for "No data available"
        echo "<tr class='placeholder'><td colspan='15'>No data available.</td></tr>";
    }
}

?>

