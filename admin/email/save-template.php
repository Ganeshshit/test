<?php
require_once '../../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// jgbj tuod jmmk nkcf

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
if (!isset($data['name']) || !isset($data['subject']) || !isset($data['content'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$conn = getDbConnection();

$stmt = $conn->prepare("INSERT INTO email_templates (name, subject, content, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("sss", $data['name'], $data['subject'], $data['content']);

if ($stmt->execute()) {
    $templateId = $conn->insert_id;
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Template saved successfully', 'template_id' => $templateId]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error saving template: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>