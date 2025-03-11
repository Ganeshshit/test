<?php
require_once 'includes/functions.php';
secureSessionStart();

//! Redirect if not logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

//* Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$attemptId = isset($_POST['attempt_id']) ? (int)$_POST['attempt_id'] : 0;

// Validate data
if ($attemptId === 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Invalidate quiz attempt
invalidateQuizAttempt($attemptId);

// Send JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
exit;
?>