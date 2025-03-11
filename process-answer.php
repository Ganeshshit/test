<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/functions.php';
secureSessionStart();

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if request is POST
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
    echo json_encode(['success' => false, 'message' => 'Missing attempt ID']);
    exit;
}

// Check if answers array exists
if (!isset($_POST['answers']) || !is_array($_POST['answers'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No answers submitted']);
    exit;
}

try {
    // Verify that the attempt belongs to the current user
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM quiz_attempts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $attemptId, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid attempt']);
        $stmt->close();
        $conn->close();
        exit;
    }

    $attempt = $result->fetch_assoc();
    $stmt->close();

    $correctCount = 0;
    $totalProcessed = 0;

    // Process each answer
    foreach ($_POST['answers'] as $questionId => $userAnswer) {
        $questionId = (int)$questionId;
        
        // Get correct answer from database
        $stmt = $conn->prepare("SELECT correct_answer FROM questions WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $question = $result->fetch_assoc();
            $isCorrect = ($userAnswer === $question['correct_answer']) ? 1 : 0;
            
            if ($isCorrect) {
                $correctCount++;
            }
            
            // Insert or update answer in database
            $stmt2 = $conn->prepare("INSERT INTO user_answers (attempt_id, question_id, user_answer, is_correct) 
                                   VALUES (?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE user_answer = ?, is_correct = ?");
            $stmt2->bind_param("iisisi", $attemptId, $questionId, $userAnswer, $isCorrect, $userAnswer, $isCorrect);
            $stmt2->execute();
            $stmt2->close();
            
            $totalProcessed++;
        }
        $stmt->close();
    }

    // Update score and mark as completed
    $score = ($totalProcessed > 0) ? $correctCount : 0;
    $stmt = $conn->prepare("UPDATE quiz_attempts SET score = ? WHERE id = ?");
    $stmt->bind_param("di", $score, $attemptId);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Quiz completed successfully',
        'attempt_id' => $attemptId,
        'score' => $score,
        'correct' => $correctCount,
        'total' => $totalProcessed
    ];

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    // Log error and return friendly message
    error_log('Quiz submission error: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your quiz: ' . $e->getMessage()
    ]);
}
exit;
?>