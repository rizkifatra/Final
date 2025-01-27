<?php
require_once 'database.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'debug.log');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['token']) || !isset($data['password'])) {
        throw new Exception('Missing required fields');
    }

    $token = urldecode(trim($data['token'])); // Clean and decode the token
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Debug logs
    error_log("Received token: " . $token);
    
    // First verify the token exists
    $stmt = $db->prepare("SELECT * FROM users WHERE reset_token = ? LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("Database query result: " . print_r($user, true));
    
    if (!$user) {
        error_log("No user found with token: " . $token);
        throw new Exception('Invalid reset token');
    }
    
    // Check if token is expired
    if (strtotime($user['reset_expires']) < time()) {
        error_log("Token expired. Expiry: " . $user['reset_expires']);
        throw new Exception('Reset token has expired');
    }

    // Update password
    $stmt = $db->prepare("UPDATE users SET 
        password = ?, 
        reset_token = NULL, 
        reset_expires = NULL 
        WHERE reset_token = ?");
    
    $success = $stmt->execute([$password, $token]);
    
    if (!$success) {
        error_log("Password update failed. PDO Error: " . print_r($stmt->errorInfo(), true));
        throw new Exception('Failed to update password');
    }
    
    error_log("Password updated successfully for user ID: " . $user['id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Password updated successfully'
    ]);

} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>