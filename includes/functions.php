<?php
require_once 'db.php';

// Start session with security measures
function secureSessionStart() {
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > SESSION_TIMEOUT) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

// Redirect to a page
function redirect($page) {
    header("Location: " . BASE_URL . $page);
    exit;
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Get domains from database
function getDomains() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM domains");
    
    $domains = [];
    while ($row = $result->fetch_assoc()) {
        $domains[] = $row;
    }
    
    $conn->close();
    return $domains;
}

// Get fields by domain
function getFieldsByDomain($domainId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM fields WHERE domain_id = ?");
    $stmt->bind_param("i", $domainId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $fields = [];
    
    while ($row = $result->fetch_assoc()) {
        $fields[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $fields;
}

// Get difficulty levels
function getDifficultyLevels() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM difficulty_levels");
    
    $levels = [];
    while ($row = $result->fetch_assoc()) {
        $levels[] = $row;
    }
    
    $conn->close();
    return $levels;
}

// Get questions for a quiz
function getQuizQuestions($fieldId, $difficultyId, $limit = 10) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM questions WHERE field_id = ? AND difficulty_id = ? ORDER BY RAND() LIMIT ?");
    $stmt->bind_param("iii", $fieldId, $difficultyId, $limit);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $questions = [];
    
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    return $questions;
}

// Save quiz attempt
function saveQuizAttempt($userId, $fieldId, $difficultyId, $score, $totalQuestions, $isValid = 1) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO quiz_attempts (user_id, field_id, difficulty_id, score, total_questions, is_valid) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiii", $userId, $fieldId, $difficultyId, $score, $totalQuestions, $isValid);
    $stmt->execute();
    
    $attemptId = $stmt->insert_id;
    
    $stmt->close();
    $conn->close();
    
    return $attemptId;
}

// Save user answer
function saveUserAnswer($attemptId, $questionId, $userAnswer, $isCorrect) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO user_answers (attempt_id, question_id, user_answer, is_correct) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $attemptId, $questionId, $userAnswer, $isCorrect);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
}

// Mark quiz attempt as invalid
function invalidateQuizAttempt($attemptId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE quiz_attempts SET is_valid = 0 WHERE id = ?");
    $stmt->bind_param("i", $attemptId);
    $stmt->execute();
    
    $stmt->close();
    $conn->close();
}

// Get quiz attempt details
function getQuizAttempt($attemptId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT qa.*, f.name as field_name, d.name as difficulty_name, u.name 
                           FROM quiz_attempts qa 
                           JOIN fields f ON qa.field_id = f.id 
                           JOIN difficulty_levels d ON qa.difficulty_id = d.id 
                           JOIN students u ON qa.user_id = u.id 
                           WHERE qa.id = ?");
    $stmt->bind_param("i", $attemptId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $attempt = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $attempt;
}

// Get user answers for a quiz attempt
function getUserAnswers($attemptId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT ua.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer 
                           FROM user_answers ua 
                           JOIN questions q ON ua.question_id = q.id 
                           WHERE ua.attempt_id = ?");
    $stmt->bind_param("i", $attemptId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $answers = [];
    
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $answers;
}

// Add this function to get user details
function getUserDetails($userId) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $user;
}
?>

