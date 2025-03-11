<?php
require_once '../../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$conn = getDbConnection();
$result = $conn->query("SELECT * FROM smtp_config LIMIT 1");

if ($result && $result->num_rows > 0) {
    $config = $result->fetch_assoc();
    // Don't send the actual password for security
    $config['password'] = !empty($config['password']) ? '********' : '';
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'config' => $config]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No SMTP configuration found']);
}

$conn->close();
?>