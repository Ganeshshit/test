<?php
require_once '../../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if template ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid template ID']);
    exit;
}

$templateId = (int)$_GET['id'];
$conn = getDbConnection();

$stmt = $conn->prepare("SELECT * FROM email_templates WHERE id = ?");
$stmt->bind_param("i", $templateId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $template = $result->fetch_assoc();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'template' => $template]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Template not found']);
}

$stmt->close();
$conn->close();
?>