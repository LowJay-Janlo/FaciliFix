<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'facilifix_db';
$port = 3307;

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $username, $password, $dbname, $port);

if ($conn->connect_error) {

    error_log("FaciliFix DB connection failed: " . $conn->connect_error);
    http_response_code(500);
    die("Sorry, the system is unavailable right now. Please try again later.");
}

$conn->set_charset('utf8mb4');
