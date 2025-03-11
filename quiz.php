<?php
require_once 'includes/functions.php';
secureSessionStart();

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if all required parameters are set
if (!isset($_GET['difficulty_id']) || !isset($_SESSION['field_id'])) {
    redirect('select-domain.php');
}

$difficultyId = (int)$_GET['difficulty_id'];
$fieldId = $_SESSION['field_id'];

// Get questions for the quiz
$questions = getQuizQuestions($fieldId, $difficultyId);

// If no questions found, redirect with error
if (empty($questions)) {
    $_SESSION['error'] = "No questions available for the selected field and difficulty level.";
    redirect('select-difficulty.php?field_id=' . $fieldId);
}

// Create a new quiz attempt
$attemptId = saveQuizAttempt($_SESSION['user_id'], $fieldId, $difficultyId, 0, count($questions));

// Store attempt_id and questions in session
$_SESSION['attempt_id'] = $attemptId;
$_SESSION['questions'] = $questions;
$_SESSION['current_question'] = 0;
$_SESSION['answers'] = [];
$_SESSION['difficulty_id'] = $difficultyId;

// Get field and difficulty names
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT f.name as field_name, d.name as difficulty_name 
                       FROM fields f, difficulty_levels d 
                       WHERE f.id = ? AND d.id = ?");
$stmt->bind_param("ii", $fieldId, $difficultyId);
$stmt->execute();
$result = $stmt->get_result();
$info = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - <?php echo $info['field_name']; ?> (<?php echo $info['difficulty_name']; ?>)</title>
    <!-- <link rel="stylesheet" href="assets/css/style.css"> -->
    <link rel="stylesheet" href="assets/css/quiz.css">
    <style>
        /* Updated sidebar styles */
        .quiz-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        
        .sidebar {
            width: 280px;
            background-color: #f5f5f5;
            border-right: 1px solid #ddd;
            padding: 1rem;
            height: 100vh;
            overflow-y: auto;
            position: fixed;
            transition: all 0.3s ease;
            z-index: 100;
        }
        
        .main-content {
            flex: 1;
            padding: 1.5rem;
            margin-left: 280px;
            transition: all 0.3s ease;
        }
        
        .toggle-sidebar {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 101;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 0.5rem;
            cursor: pointer;
            display: none;
        }
        
        /* Timer styles */
        .question-timer {
            font-size: 1.5rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 1rem;
            padding: 0.5rem;
            border-radius: 5px;
            background-color: #f0f0f0;
        }
        
        .timer-warning {
            color: #ff9800;
            animation: pulse 1s infinite;
        }
        
        .timer-danger {
            color: #f44336;
            animation: pulse 0.5s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Disabled question styles */
        .question-container.disabled {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .question-expired {
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
            color: #c62828;
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                width: 85%;
                max-width: 320px;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggle-sidebar {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="quiz-layout">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>Question Navigator</h2>
            </div>

            <div class="question-nav" id="question-nav">
                <?php for($i = 0; $i < count($questions); $i++): ?>
                <button type="button" class="question-nav-btn <?php echo ($i === 0) ? 'current' : 'not-visited'; ?>" data-index="<?php echo $i; ?>"><?php echo $i+1; ?></button>
                <?php endfor; ?>
            </div>
            
            <div class="legend">
                <h3>Legend</h3>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--primary-color);"></div>
                    <span>Current Question</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--success-color);"></div>
                    <span>Answered</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--warning-color);"></div>
                    <span>Marked for Review</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--danger-color);"></div>
                    <span>Skipped/Expired</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color" style="background-color: var(--light-gray);"></div>
                    <span>Not Yet Visited</span>
                </div>
            </div>
            
            <div class="summary" id="quiz-summary">
                <h3>Summary</h3>
                <div class="summary-item">
                    <span>Total Questions:</span>
                    <span id="total-count"><?php echo count($questions); ?></span>
                </div>
                <div class="summary-item">
                    <span>Answered:</span>
                    <span id="answered-count">0</span>
                </div>
                <div class="summary-item">
                    <span>Marked for Review:</span>
                    <span id="marked-count">0</span>
                </div>
                <div class="summary-item">
                    <span>Skipped/Expired:</span>
                    <span id="skipped-count">0</span>
                </div>
                <div class="summary-item">
                    <span>Not Visited:</span>
                    <span id="not-visited-count"><?php echo count($questions) - 1; ?></span>
                </div>
            </div>
            
        </aside>
        
        <!-- Mobile toggle button -->
        <button class="toggle-sidebar" id="toggle-sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="quiz-header">
                <h1>Quiz: <?php echo $info['field_name']; ?></h1>
                <h2>Difficulty: <?php echo $info['difficulty_name']; ?></h2>
                <div class="quiz-info">
                    <p>Question <span id="current-question">1</span> of <span id="total-questions"><?php echo count($questions); ?></span></p>
                    <p>Total Time Remaining: <span id="total-timer" class="timer-display">00:00:00</span></p>
                </div>
            </div>
            
            <div id="quiz-container">
                <div class="question-timer" id="question-timer">
                    Time for this question: <span id="question-time">30</span> seconds
                </div>
                
                <div id="question-expired-notice" class="question-expired" style="display: none;">
                    <h3>Time expired for this question!</h3>
                    <p>You can no longer answer this question. Please move to the next question.</p>
                </div>
                
                <form id="quiz-form">
                    <input type="hidden" id="question-id" name="question_id" value="<?php echo $questions[0]['id']; ?>">
                    <input type="hidden" id="attempt-id" name="attempt_id" value="<?php echo $attemptId; ?>">
                    <input type="hidden" id="current-index" name="current_index" value="0">
                    
                    <div class="question-container" id="question-container">
                        <h3 id="question-text"><?php echo $questions[0]['question_text']; ?></h3>
                        
                        <div class="options">
                            <div class="option">
                                <input type="radio" id="option-a" name="answer" value="A">
                                <label for="option-a" id="label-a"><?php echo $questions[0]['option_a']; ?></label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" id="option-b" name="answer" value="B">
                                <label for="option-b" id="label-b"><?php echo $questions[0]['option_b']; ?></label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" id="option-c" name="answer" value="C">
                                <label for="option-c" id="label-c"><?php echo $questions[0]['option_c']; ?></label>
                            </div>
                            
                            <div class="option">
                                <input type="radio" id="option-d" name="answer" value="D">
                                <label for="option-d" id="label-d"><?php echo $questions[0]['option_d']; ?></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="quiz-actions">
                        <div class="quiz-buttons">
                            <button type="button" id="btn-previous" class="btn btn-dark btn-disabled" disabled>
                                <span class="btn-icon">←</span> Previous
                            </button>
                            <button type="button" id="btn-mark" class="btn btn-warning">
                                Mark for Review
                            </button>
                        </div>
                        <div class="quiz-buttons">
                        <button type="button" id="btn-skip" class="btn btn-danger">
                            Skip Question
                        </button>
                        <button type="button" id="btn-next" class="btn btn-primary">
                            Next <span class="btn-icon">→</span>
                        </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Variables
    const questions = <?php echo json_encode($questions); ?>;
    const totalQuestions = questions.length;
    const timePerQuestion = 30; // seconds per question
    const totalTimeInSeconds = totalQuestions * timePerQuestion;
    
    let currentIndex = 0;
    let answers = new Array(totalQuestions).fill(null);
    let visitedQuestions = new Array(totalQuestions).fill(false);
    visitedQuestions[0] = true; // First question is visited
    let markedQuestions = new Array(totalQuestions).fill(false);
    let skippedQuestions = new Array(totalQuestions).fill(false);
    let expiredQuestions = new Array(totalQuestions).fill(false);
    let questionTimers = new Array(totalQuestions).fill(timePerQuestion); // Time remaining for each question
    let remainingTime = totalTimeInSeconds;
    let timerInterval;
    let questionTimerInterval;
    let isQuizEnded = false;
    let currentQuestionTime = timePerQuestion;
    const attemptId = document.getElementById('attempt-id').value;
    
    // Elements
    const sidebar = document.getElementById('sidebar');
    const toggleSidebar = document.getElementById('toggle-sidebar');
    const currentQuestionEl = document.getElementById('current-question');
    const totalQuestionsEl = document.getElementById('total-questions');
    const totalTimerEl = document.getElementById('total-timer');
    const questionTimerEl = document.getElementById('question-timer');
    const questionTimeEl = document.getElementById('question-time');
    const questionExpiredNotice = document.getElementById('question-expired-notice');
    const questionContainer = document.getElementById('question-container');
    const questionNavEl = document.getElementById('question-nav');
    const questionIdEl = document.getElementById('question-id');
    const currentIndexEl = document.getElementById('current-index');
    const questionTextEl = document.getElementById('question-text');
    const optionAEl = document.getElementById('option-a');
    const optionBEl = document.getElementById('option-b');
    const optionCEl = document.getElementById('option-c');
    const optionDEl = document.getElementById('option-d');
    const labelAEl = document.getElementById('label-a');
    const labelBEl = document.getElementById('label-b');
    const labelCEl = document.getElementById('label-c');
    const labelDEl = document.getElementById('label-d');
    const btnPrevious = document.getElementById('btn-previous');
    const btnNext = document.getElementById('btn-next');
    const btnSkip = document.getElementById('btn-skip');
    const btnMark = document.getElementById('btn-mark');
    const quizForm = document.getElementById('quiz-form');
    
    // Summary elements
    const answeredCountEl = document.getElementById('answered-count');
    const markedCountEl = document.getElementById('marked-count');
    const skippedCountEl = document.getElementById('skipped-count');
    const notVisitedCountEl = document.getElementById('not-visited-count');
    
    // Initialize
    startTotalTimer();
    startQuestionTimer();
    updateNavButtons();
    updateSummary();
    
    // Event Listeners
    btnPrevious.addEventListener('click', goToPrevious);
    btnNext.addEventListener('click', goToNext);
    btnSkip.addEventListener('click', skipQuestion);
    btnMark.addEventListener('click', toggleMarkQuestion);
    toggleSidebar.addEventListener('click', function() {
        sidebar.classList.toggle('active');
    });

    // Add event listeners for tab switching detection
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('blur', handleVisibilityChange);
    
    // Navigation button click events
    setupNavButtonListeners();
    
    function setupNavButtonListeners() {
        const navButtons = document.querySelectorAll('.question-nav-btn');
        navButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetIndex = parseInt(this.dataset.index);
                
                // Check if the target question has expired
                // if (expiredQuestions[targetIndex]) {
                //     alert('This question has expired and can no longer be answered.');
                //     return;
                // }
                
                saveCurrentAnswer();
                currentIndex = targetIndex;
                loadQuestion(currentIndex);
                updateNavButtons();
                updateSummary();
                
                // For mobile, close sidebar after selecting
                if (window.innerWidth < 993) {
                    sidebar.classList.remove('active');
                }
            });
        });
    }

    // Function to handle visibility change (tab switching)
    function handleVisibilityChange() {
        if (document.visibilityState === 'hidden' || document.hasFocus() === false) {
            if (!isQuizEnded) {
                // Comment out the endQuizDueToTabSwitch call if you want to disable this feature
                // endQuizDueToTabSwitch();
            }
        }
    }

    function endQuizDueToTabSwitch() {
        clearInterval(timerInterval);
        clearInterval(questionTimerInterval);
        isQuizEnded = true;
        
        // Send AJAX request to invalidate the quiz attempt
        fetch('invalidate-quiz.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'attempt_id=' + attemptId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Quiz has been invalidated due to tab switching. You will be redirected to the results page.');
                window.location.href = 'results.php?attempt_id=' + attemptId;
            } else {
                alert('An error occurred. Quiz invalidated. You will be redirected.');
                window.location.href = 'dashboard.php';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Quiz invalidated. You will be redirected.');
            window.location.href = 'dashboard.php';
        });
    }
    
    // Functions
    function startTotalTimer() {
        updateTotalTimerDisplay();
        timerInterval = setInterval(function() {
            remainingTime--;
            updateTotalTimerDisplay();
            
            if (remainingTime <= 60) {
                totalTimerEl.classList.add('timer-warning');
            }
            
            if (remainingTime <= 0) {
                clearInterval(timerInterval);
                clearInterval(questionTimerInterval);
                submitQuiz();
            }
        }, 1000);
    }
    
    function startQuestionTimer() {
        // Reset timer if it was already running
        if (questionTimerInterval) {
            clearInterval(questionTimerInterval);
        }
        
        // Use the stored time for this question if it exists
        currentQuestionTime = questionTimers[currentIndex];
        updateQuestionTimerDisplay();
        
        questionTimerInterval = setInterval(function() {
            if (currentQuestionTime > 0) {
                currentQuestionTime--;
                questionTimers[currentIndex] = currentQuestionTime;
                updateQuestionTimerDisplay();
                
                if (currentQuestionTime <= 10) {
                    questionTimeEl.classList.add('timer-warning');
                }
                
                if (currentQuestionTime <= 5) {
                    questionTimeEl.classList.add('timer-danger');
                }
            } else {
                // Time expired for this question
                clearInterval(questionTimerInterval);
                expireCurrentQuestion();
            }
        }, 1000);
    }
    
    function updateTotalTimerDisplay() {
        const hours = Math.floor(remainingTime / 3600);
        const minutes = Math.floor((remainingTime % 3600) / 60);
        const seconds = remainingTime % 60;
        
        totalTimerEl.textContent = 
            (hours < 10 ? '0' + hours : hours) + ':' +
            (minutes < 10 ? '0' + minutes : minutes) + ':' +
            (seconds < 10 ? '0' + seconds : seconds);
    }
    
    function updateQuestionTimerDisplay() {
        questionTimeEl.textContent = currentQuestionTime;
    }
    
    function expireCurrentQuestion() {
        // Mark question as expired
        expiredQuestions[currentIndex] = true;
        skippedQuestions[currentIndex] = true; // Also count as skipped for stats
        
        // Update UI to show expired state
        questionContainer.classList.add('disabled');
        questionExpiredNotice.style.display = 'block';
        
        // Disable all radio buttons
        document.querySelectorAll('input[name="answer"]').forEach(radio => {
            radio.disabled = true;
        });
        
        // Update navigation
        updateQuestionNavStatus(currentIndex);
        updateSummary();
        
        // Auto-advance to next question after 2 seconds
        setTimeout(function() {
            if (currentIndex < totalQuestions - 1) {
                goToNext();
            }
        }, 2000);
    }
    
    function saveCurrentAnswer() {
        const selectedOption = document.querySelector('input[name="answer"]:checked');
        if (selectedOption && !expiredQuestions[currentIndex]) {
            answers[currentIndex] = selectedOption.value;
            skippedQuestions[currentIndex] = false; // If answered, it's no longer skipped
            updateQuestionNavStatus(currentIndex);
            updateSummary();
        }
    }
    
    function loadQuestion(index) {
        // First save current question timer
        if (currentIndex !== index) {
            questionTimers[currentIndex] = currentQuestionTime;
        }
        
        // Check if this question has already expired
        if (expiredQuestions[index]) {
            questionContainer.classList.add('disabled');
            questionExpiredNotice.style.display = 'block';
            document.querySelectorAll('input[name="answer"]').forEach(radio => {
                radio.disabled = true;
            });
        } else {
            // Reset UI for non-expired questions
            questionContainer.classList.remove('disabled');
            questionExpiredNotice.style.display = 'none';
            document.querySelectorAll('input[name="answer"]').forEach(radio => {
                radio.disabled = false;
            });
            
            // Reset timer classes
            questionTimeEl.classList.remove('timer-warning', 'timer-danger');
            
            // Start a new timer for this question
            startQuestionTimer();
        }
        
        // Mark as visited
        visitedQuestions[index] = true;
        
        // Update current question display
        currentQuestionEl.textContent = index + 1;
        currentIndexEl.value = index;
        
        // Update question data
        const question = questions[index];
        questionIdEl.value = question.id;
        questionTextEl.textContent = question.question_text;
        labelAEl.textContent = question.option_a;
        labelBEl.textContent = question.option_b;
        labelCEl.textContent = question.option_c;
        labelDEl.textContent = question.option_d;
        
        // Clear all radio selections
        optionAEl.checked = false;
        optionBEl.checked = false;
        optionCEl.checked = false;
        optionDEl.checked = false;
        
        // Set saved answer if exists
        if (answers[index]) {
            document.getElementById(`option-${answers[index].toLowerCase()}`).checked = true;
        }
        
        // Update Mark button text based on state
        btnMark.textContent = markedQuestions[index] ? 'Unmark' : 'Mark for Review';
        btnMark.classList.toggle('btn-warning', !markedQuestions[index]);
        btnMark.classList.toggle('btn-secondary', markedQuestions[index]);
        
        // Update active navigation button
        document.querySelectorAll('.question-nav-btn').forEach((btn, idx) => {
            btn.classList.toggle('current', idx === index);
        });
        
        updateSummary();
    }
    
    function updateNavButtons() {
        // Update Previous button state
        btnPrevious.disabled = currentIndex === 0;
        btnPrevious.classList.toggle('btn-disabled', currentIndex === 0);
        
        // Update Next button text and state for last question
        if (currentIndex === totalQuestions - 1) {
            btnNext.textContent = 'Finish Quiz';
            btnNext.innerHTML = 'Finish Quiz <span class="btn-icon">✓</span>';
        } else {
            btnNext.innerHTML = 'Next <span class="btn-icon">→</span>';
        }
    }
    
    function updateQuestionNavStatus(index) {
        const navBtn = document.querySelector(`.question-nav-btn[data-index="${index}"]`);
        
        // Remove all status classes but keep the data-index attribute
        navBtn.classList.remove('current', 'answered', 'marked', 'skipped', 'not-visited');
        
        // Apply appropriate status class
        if (currentIndex === index) {
            navBtn.classList.add('current');
        } else if (answers[index]) {
            navBtn.classList.add('answered');
        } else if (markedQuestions[index]) {
            navBtn.classList.add('marked');
        } else if (skippedQuestions[index] || expiredQuestions[index]) {
            navBtn.classList.add('skipped');
        } else if (!visitedQuestions[index]) {
            navBtn.classList.add('not-visited');
        } else {
            // Visited but no action taken
            navBtn.classList.add('not-visited');
        }
    }
    
    function updateSummary() {
        // Count answered questions
        const answeredCount = answers.filter(answer => answer !== null).length;
        answeredCountEl.textContent = answeredCount;
        
        // Count marked questions
        const markedCount = markedQuestions.filter(marked => marked).length;
        markedCountEl.textContent = markedCount;
        
        // Count skipped questions (including expired)
        const skippedCount = skippedQuestions.filter((skipped, index) => 
            skipped || expiredQuestions[index]
        ).length;
        skippedCountEl.textContent = skippedCount;
        
        // Count not visited questions
        const notVisitedCount = visitedQuestions.filter(visited => !visited).length;
        notVisitedCountEl.textContent = notVisitedCount;
        
        // Update all question navigation buttons
        document.querySelectorAll('.question-nav-btn').forEach((btn, idx) => {
            updateQuestionNavStatus(idx);
        });
    }
    
    function goToPrevious() {
        if (currentIndex > 0) {
            saveCurrentAnswer();
            currentIndex--;
            loadQuestion(currentIndex);
            updateNavButtons();
        }
    }
    
    function goToNext() {
        saveCurrentAnswer();
        
        if (currentIndex < totalQuestions - 1) {
            currentIndex++;
            loadQuestion(currentIndex);
            updateNavButtons();
        } else {
            // On last question, submit the quiz
            // if (confirm('Are you sure you want to finish the quiz?')) {
            //     submitQuiz();
            // }
            submitQuiz();
        }
    }
    
    function skipQuestion() {
        skippedQuestions[currentIndex] = true;
        updateQuestionNavStatus(currentIndex);
        
        if (currentIndex < totalQuestions - 1) {
            currentIndex++;
            loadQuestion(currentIndex);
            updateNavButtons();
        } else {
            // Find first unanswered and non-expired question
            const firstUnanswered = [...Array(totalQuestions).keys()].find(i => 
                answers[i] === null && !expiredQuestions[i]
            );
            
            if (firstUnanswered !== undefined && firstUnanswered !== currentIndex) {
                currentIndex = firstUnanswered;
                loadQuestion(currentIndex);
                updateNavButtons();
            }
        }
    }
    
    function toggleMarkQuestion() {
        if (!expiredQuestions[currentIndex]) {
            markedQuestions[currentIndex] = !markedQuestions[currentIndex];
            updateQuestionNavStatus(currentIndex);
            
            // Update button text
            btnMark.textContent = markedQuestions[currentIndex] ? 'Unmark' : 'Mark for Review';
            btnMark.classList.toggle('btn-warning', !markedQuestions[currentIndex]);
            btnMark.classList.toggle('btn-secondary', markedQuestions[currentIndex]);
            
            updateSummary();
        }
    }
    
    function submitQuiz() {
        // Save last answer if not expired
        if (!expiredQuestions[currentIndex]) {
            saveCurrentAnswer();
        }
        
        // Clear timers
        clearInterval(timerInterval);
        clearInterval(questionTimerInterval);
        
        // Prepare data for submission
        const formData = new FormData();
        formData.append('attempt_id', document.getElementById('attempt-id').value);
        
        // Add all answers
        answers.forEach((answer, index) => {
            if (answer) {
                formData.append(`answers[${questions[index].id}]`, answer);
            }
        });
        
        // Send to server
        fetch('process-answer.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'results.php?attempt_id=' + document.getElementById('attempt-id').value;
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error submitting quiz:', error);
            alert('An error occurred while submitting your quiz. Please try again.');
        });
    }
    
    // Handle form submission
    quizForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitQuiz();
    });
});
    </script>
</body>
</html>