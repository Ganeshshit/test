<?php
require_once 'includes/functions.php';
secureSessionStart();

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if attempt_id is provided
if (!isset($_GET['attempt_id'])) {
    redirect('select-domain.php');
}

$attemptId = (int)$_GET['attempt_id'];

// Get quiz attempt details
$attempt = getQuizAttempt($attemptId);

// Get user answers
$userAnswers = getUserAnswers($attemptId);

// Check if attempt exists and belongs to the current user
if (!$attempt || $attempt['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = "Invalid quiz attempt.";
    redirect('select-domain.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - Quiz App</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include "includes/navbar.php" ?>
    <div class="container">
        <h1>Quiz Results</h1>
        
        <div class="results-summary">
            <h2>Summary</h2>
            <p>Field: <strong><?php echo $attempt['field_name']; ?></strong></p>
            <p>Difficulty: <strong><?php echo $attempt['difficulty_name']; ?></strong></p>
            <?php date_default_timezone_set('Asia/Kolkata'); ?>
            <p>Date: <strong><?php echo date('F j, Y, g:i a', strtotime($attempt['created_at'])); ?></strong></p>
            <p>Status: <strong><?php echo $attempt['is_valid'] ? 'Valid' : 'Invalid (Tab Switching Detected)'; ?></strong></p>
            
            <?php if ($attempt['is_valid']): ?>
                <p>Questions Attempted: <strong><?php echo count($userAnswers); ?></strong></p>
                <!-- <p>Correct Answers: <strong><?php echo $attempt['score']; ?></strong></p> -->
                <!-- <p>Incorrect Answers: <strong><?php echo count($userAnswers) - $attempt['score']; ?></strong></p> -->
            <?php else: ?>
                <p class="invalid-message">This quiz attempt was invalidated due to tab switching.</p>
            <?php endif; ?>
        </div>
        
        <!-- <?php if ($attempt['is_valid']): ?>
            <div class="results-details">
                <h2>Question Details</h2>
                
                <?php foreach ($userAnswers as $index => $answer): ?>
                    <div class="question-result <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                        <h3>Question <?php echo $index + 1; ?></h3>
                        <p class="question-text"><?php echo $answer['question_text']; ?></p>
                        
                        <div class="options">
                            <div class="option <?php echo $answer['correct_answer'] === 'A' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'A' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                                <span class="option-label">A:</span> <?php echo $answer['option_a']; ?>
                                <?php if ($answer['user_answer'] === 'A'): ?>
                                    <span class="user-choice">(Your Answer)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="option <?php echo $answer['correct_answer'] === 'B' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'B' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                                <span class="option-label">B:</span> <?php echo $answer['option_b']; ?>
                                <?php if ($answer['user_answer'] === 'B'): ?>
                                    <span class="user-choice">(Your Answer)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="option <?php echo $answer['correct_answer'] === 'C' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'C' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                                <span class="option-label">C:</span> <?php echo $answer['option_c']; ?>
                                <?php if ($answer['user_answer'] === 'C'): ?>
                                    <span class="user-choice">(Your Answer)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="option <?php echo $answer['correct_answer'] === 'D' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'D' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                                <span class="option-label">D:</span> <?php echo $answer['option_d']; ?>
                                <?php if ($answer['user_answer'] === 'D'): ?>
                                    <span class="user-choice">(Your Answer)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
         -->
        <div class="actions">
            <a href="select-domain.php" class="btn btn-primary">Take Another Quiz</a>
            <a href="profile.php" class="btn btn-primary">My Profile</a>
            <a href="logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
</body>
</html>

