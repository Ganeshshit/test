<?php
// Create a new file called results-data.php with this content
require_once '../includes/functions.php';
secureSessionStart();

// Redirect if not logged in or not admin
if (!isLoggedIn() || !isAdmin()) {
    echo "Unauthorized access";
    exit;
}

// Check if attempt_id is provided
if (!isset($_GET['attempt_id'])) {
    echo "No attempt ID provided";
    exit;
}

$attemptId = (int)$_GET['attempt_id'];

// Get quiz attempt details
$attempt = getQuizAttempt($attemptId);

// Get user answers
$userAnswers = getUserAnswers($attemptId);

// Check if attempt exists
if (!$attempt) {
    echo "Invalid quiz attempt.";
    exit;
}
?>

<div class="modal-header">
    <h2>Quiz Results</h2>
    <span class="close-button" onclick="closeModal()">&times;</span>
</div>

<div class="results-summary">
    <h3>Summary</h3>
    <p>Student: <strong><?php echo htmlspecialchars($attempt['name'] ?? 'Unknown'); ?></strong></p>
    <p>Field: <strong><?php echo htmlspecialchars($attempt['field_name']); ?></strong></p>
    <p>Difficulty: <strong><?php echo htmlspecialchars($attempt['difficulty_name']); ?></strong></p>
    <p>Date: <strong><?php echo date('F j, Y, g:i a', strtotime($attempt['created_at'])); ?></strong></p>
    <p>Status: <strong><?php echo $attempt['is_valid'] ? 'Valid' : 'Invalid (Tab Switching Detected)'; ?></strong></p>
    
    <?php if ($attempt['is_valid']): ?>
        <p>Questions Attempted: <strong><?php echo count($userAnswers); ?></strong></p>
        <p>Correct Answers: <strong><?php echo $attempt['score']; ?></strong></p>
        <p>Incorrect Answers: <strong><?php echo count($userAnswers) - $attempt['score']; ?></strong></p>
        <p>Score: <strong><?php echo number_format(($attempt['score'] / count($userAnswers)) * 100, 1); ?>%</strong></p>
    <?php else: ?>
        <p class="invalid-message">This quiz attempt was invalidated due to tab switching.</p>
    <?php endif; ?>
</div>

<?php if ($attempt['is_valid']): ?>
    <div class="results-details">
        <h3>Question Details</h3>
        
        <?php foreach ($userAnswers as $index => $answer): ?>
            <div class="question-result <?php echo $answer['is_correct'] ? 'correct' : 'incorrect'; ?>">
                <h4>Question <?php echo $index + 1; ?></h4>
                <p class="question-text"><?php echo htmlspecialchars($answer['question_text']); ?></p>
                
                <div class="options">
                    <div class="option <?php echo $answer['correct_answer'] === 'A' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'A' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                        <span class="option-label">A:</span> <?php echo htmlspecialchars($answer['option_a']); ?>
                        <?php if ($answer['user_answer'] === 'A'): ?>
                            <span class="user-choice">(Student's Answer)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="option <?php echo $answer['correct_answer'] === 'B' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'B' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                        <span class="option-label">B:</span> <?php echo htmlspecialchars($answer['option_b']); ?>
                        <?php if ($answer['user_answer'] === 'B'): ?>
                            <span class="user-choice">(Student's Answer)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="option <?php echo $answer['correct_answer'] === 'C' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'C' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                        <span class="option-label">C:</span> <?php echo htmlspecialchars($answer['option_c']); ?>
                        <?php if ($answer['user_answer'] === 'C'): ?>
                            <span class="user-choice">(Student's Answer)</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="option <?php echo $answer['correct_answer'] === 'D' ? 'correct-answer' : ''; ?> <?php echo $answer['user_answer'] === 'D' && !$answer['is_correct'] ? 'wrong-answer' : ''; ?>">
                        <span class="option-label">D:</span> <?php echo htmlspecialchars($answer['option_d']); ?>
                        <?php if ($answer['user_answer'] === 'D'): ?>
                            <span class="user-choice">(Student's Answer)</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>