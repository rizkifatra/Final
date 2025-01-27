<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Change to 0 to prevent PHP errors from corrupting JSON
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

// Replace composer autoload with direct includes
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';
require_once 'PHPMailer/src/Exception.php';
require_once 'database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['email'])) {
        throw new Exception('Email is required');
    }

    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    // Database operations
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Email not found');
    }

    // Generate token
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Debug log for token generation
    error_log("Generating new token for email: " . $email);
    error_log("Generated token: " . $token);
    
    // First clear any existing tokens for this email
    $stmt = $db->prepare("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE email = ?");
    $stmt->execute([$email]);
    
    // Then set the new token
    $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
    if (!$stmt->execute([$token, $expiry, $email])) {
        error_log("Failed to update token. Error: " . implode(", ", $stmt->errorInfo()));
        throw new Exception('Failed to generate reset token');
    }
    
    // Verify token was saved
    $stmt = $db->prepare("SELECT reset_token FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $result = $stmt->fetch();
    error_log("Saved token in database: " . ($result['reset_token'] ?? 'null'));

    // Email configuration
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'rizkifatra31@gmail.com';
    $mail->Password = 'csgtjktolxvqnxyd';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('rizkifatra31@gmail.com', 'Quiz App');
    $mail->addAddress($email);
    $mail->isHTML(true);
    
    // Fix the reset link URL construction
    $domain = $_SERVER['HTTP_HOST'];
    $resetLink = "http://" . $domain . "/final/reset-password.html?token=" . urlencode($token);
    
    $mail->Subject = 'Reset Your Password';
    $mail->Body = "
        <h2>Password Reset Request</h2>
        <p>Click the button below to reset your password:</p>
        <p><a href='{$resetLink}' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
        <p>If the button doesn't work, copy and paste this link:</p>
        <p style='word-break: break-all;'>{$resetLink}</p>
        <p>This link will expire in 1 hour.</p>
    ";

    $mail->send();
    
    // Clean response with no extra characters
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Reset instructions sent to your email'
    ], JSON_UNESCAPED_SLASHES);
    exit;

} catch (Exception $e) {
    error_log("Reset password error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_SLASHES);
    exit;
}
?>