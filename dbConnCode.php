<?php
// Database configuration
$servername = "localhost";
$username = "u937652960_admin";
$password = "December312002!";
$database = "u937652960_ReocWebDB";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
