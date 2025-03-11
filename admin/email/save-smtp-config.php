<?php
require_once '../../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the JSON data from the request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Check if required fields are present
if (!isset($data['host']) || !isset($data['port']) || !isset($data['username']) || 
    !isset($data['from_email']) || !isset($data['from_name'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$conn = getDbConnection();

// Check if configuration already exists
$result = $conn->query("SELECT id FROM smtp_config LIMIT 1");

if ($result && $result->num_rows > 0) {
    // Update existing configuration
    $row = $result->fetch_assoc();
    $id = $row['id'];
    
    // If password is masked (********), get the existing password
    if ($data['password'] === '********') {
        $pwdResult = $conn->query("SELECT password FROM smtp_config WHERE id = $id");
        $pwdRow = $pwdResult->fetch_assoc();
        $password = $pwdRow['password'];
    } else {
        $password = $data['password'];
    }
    
    $stmt = $conn->prepare("UPDATE smtp_config SET host = ?, port = ?, username = ?, password = ?, 
                           from_email = ?, from_name = ?, encryption = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("sisssssi", $data['host'], $data['port'], $data['username'], $password, 
                      $data['from_email'], $data['from_name'], $data['encryption'], $id);
} else {
    // Insert new configuration
    $stmt = $conn->prepare("INSERT INTO smtp_config (host, port, username, password, from_email, from_name, encryption, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sisssss", $data['host'], $data['port'], $data['username'], $data['password'], 
                      $data['from_email'], $data['from_name'], $data['encryption']);
}

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'SMTP configuration saved successfully']);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error saving SMTP configuration: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>