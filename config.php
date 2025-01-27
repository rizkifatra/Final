<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'final');

// SMTP configuration
define('SMTP_HOST', 'smtp.gmail.com'); // or your SMTP server
define('SMTP_USER', 'rizkifatra31@gmail.com');
define('SMTP_PASS', 'csgtjktolxvqnxyd');
define('SMTP_PORT', 587);
define('SMTP_FROM_EMAIL', 'your_email@gmail.com');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
