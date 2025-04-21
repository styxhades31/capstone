<?php
// fetch_picture.php

// Assuming you have already connected to the database
$faculty_id = 1; // Since you said it’s always 1
$query = "SELECT `picture` FROM `faculty_members` WHERE `id` = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($current_picture);
$stmt->fetch();

$response = [
    'picture' => $current_picture // Send the picture name or null if not found
];

echo json_encode($response);
?>
