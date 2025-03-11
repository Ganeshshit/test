document.addEventListener('DOMContentLoaded', function() {
    // Get quiz data from PHP
    const totalQuestions = quizData.totalQuestions;
    const attemptId = quizData.attemptId;
    
    // Initialize the first question from the data passed from PHP
    if (quizData.firstQuestion) {
        // Cache the first question to avoid unnecessary AJAX request
        if (!window.questionCache) {
            window.questionCache = {};
        }
        window.questionCache[0] = quizData.firstQuestion;
    }
    
    // Calculate time limit - 30 seconds per question
    let timeLeft = totalQuestions * 30; 
    let timerInterval;
    let isQuizEnded = false;
    
    // Question state tracking
    let currentQuestionIndex = 0;
    const questionStates = new Array(totalQuestions).fill('unanswered');
    questionStates[0] = 'current';
    const userAnswers = new Array(totalQuestions).fill(null);
    const markedForReview = new Array(totalQuestions).fill(false);
    const skippedQuestions = new Array(totalQuestions).fill(false);

    // Debug info to check initialization
    console.log("Quiz initialized with " + totalQuestions + " questions");
    console.log("Attempt ID: " + attemptId);

    // Update question navigation buttons to show current question
    updateQuestionNavigationButtons();
    
    // Start the timer
    startTimer();
    
    // Add event listeners for tab switching detection
    document.addEventListener('visibilitychange', handleVisibilityChange);
    window.addEventListener('blur', handleVisibilityChange);
    
    // Add event listeners for navigation buttons
    const btnPrevious = document.getElementById('btn-previous');
    const btnNext = document.getElementById('btn-next');
    const btnMarkReview = document.getElementById('btn-mark-review');
    const btnSkip = document.getElementById('btn-skip');
    const btnFinish = document.getElementById('btn-finish');
    
    if (btnPrevious) {
        btnPrevious.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Previous button clicked");
            navigateToPreviousQuestion();
        });
    }
    
    if (btnNext) {
        btnNext.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Next button clicked");
            navigateToNextQuestion();
        });
    }
    
    if (btnMarkReview) {
        btnMarkReview.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Mark for review button clicked");
            markForReview();
        });
    }
    
    if (btnSkip) {
        btnSkip.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Skip button clicked");
            skipQuestion();
        });
    }
    
    if (btnFinish) {
        btnFinish.addEventListener('click', function(e) {
            e.preventDefault();
            console.log("Finish button clicked");
            finishQuiz();
        });
    }
    
    // Add event listeners for question navigation buttons
    const questionNavBtns = document.querySelectorAll('.question-nav-btn');
    questionNavBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const questionIndex = parseInt(this.getAttribute('data-index'));
            console.log("Navigating to question: " + (questionIndex + 1));
            navigateToQuestion(questionIndex);
        });
    });
    
    // Add event listener for answer selection
    const answerOptions = document.querySelectorAll('input[name="answer"]');
    answerOptions.forEach(option => {
        option.addEventListener('change', function() {
            console.log("Answer selected: " + this.value);
            userAnswers[currentQuestionIndex] = this.value;
            if (questionStates[currentQuestionIndex] !== 'marked') {
                questionStates[currentQuestionIndex] = 'answered';
            }
            updateQuestionNavigationButtons();
            
            // Save answer via AJAX
            saveAnswer(currentQuestionIndex, this.value);
        });
    });
    
    // Function to save an answer via AJAX
    function saveAnswer(questionIndex, answer) {
        const questionId = document.getElementById('question-id').value;
        
        fetch('process-answer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'attempt_id=' + attemptId + 
                  '&question_id=' + questionId + 
                  '&question_index=' + questionIndex + 
                  '&answer=' + answer + 
                  '&action_type=save'
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error saving answer:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Function to handle visibility change (tab switching)
    // function handleVisibilityChange() {
    //     if (document.visibilityState === 'hidden' || document.hasFocus() === false) {
    //         if (!isQuizEnded) {
    //             endQuizDueToTabSwitch();
    //         }
    //     }
    // }
    
    // Function to end quiz due to tab switching
    function endQuizDueToTabSwitch() {
        clearInterval(timerInterval);
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
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
    
    // Function to start the timer
    function startTimer() {
        updateTimerDisplay();
        
        timerInterval = setInterval(function() {
            timeLeft--;
            updateTimerDisplay();
            
            if (timeLeft <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Your quiz will be submitted.');
                submitAllAnswers();
            }
        }, 1000);
    }
    
    // Function to update timer display
    function updateTimerDisplay() {
        const hours = Math.floor(timeLeft / 3600);
        const minutes = Math.floor((timeLeft % 3600) / 60);
        const seconds = timeLeft % 60;
        
        const timerElement = document.getElementById('timer');
        if (timerElement) {
            timerElement.textContent = 
                (hours < 10 ? '0' + hours : hours) + ':' +
                (minutes < 10 ? '0' + minutes : minutes) + ':' + 
                (seconds < 10 ? '0' + seconds : seconds);
        }
    }
    
    // Function to navigate to the previous question
    function navigateToPreviousQuestion() {
        if (currentQuestionIndex > 0) {
            saveCurrentQuestionState();
            currentQuestionIndex--;
            loadQuestion(currentQuestionIndex);
            updateNavigationButtons();
        }
    }
    
    // Function to navigate to the next question
    function navigateToNextQuestion() {
        if (currentQuestionIndex < totalQuestions - 1) {
            saveCurrentQuestionState();
            currentQuestionIndex++;
            loadQuestion(currentQuestionIndex);
            updateNavigationButtons();
        } else {
            // Show finish button when on the last question
            document.getElementById('btn-next').style.display = 'none';
            document.getElementById('btn-finish').style.display = 'inline-block';
        }
    }
    
    // Function to navigate to a specific question
    function navigateToQuestion(index) {
        if (index >= 0 && index < totalQuestions) {
            saveCurrentQuestionState();
            currentQuestionIndex = index;
            loadQuestion(currentQuestionIndex);
            updateNavigationButtons();
        }
    }
    
    // Function to mark a question for review
    function markForReview() {
        markedForReview[currentQuestionIndex] = !markedForReview[currentQuestionIndex];
        
        if (markedForReview[currentQuestionIndex]) {
            document.getElementById('btn-mark-review').classList.add('active');
            questionStates[currentQuestionIndex] = 'marked';
        } else {
            document.getElementById('btn-mark-review').classList.remove('active');
            questionStates[currentQuestionIndex] = userAnswers[currentQuestionIndex] ? 'answered' : 'current';
        }
        
        updateQuestionNavigationButtons();
        
        // Save marked status via AJAX
        const questionId = document.getElementById('question-id').value;
        
        fetch('mark-question.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'attempt_id=' + attemptId + 
                  '&question_index=' + currentQuestionIndex + 
                  '&marked=' + (markedForReview[currentQuestionIndex] ? 1 : 0)
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    // Function to skip a question
    function skipQuestion() {
        skippedQuestions[currentQuestionIndex] = true;
        questionStates[currentQuestionIndex] = 'skipped';
        updateQuestionNavigationButtons();
        
        // Save skipped status via AJAX
        const questionId = document.getElementById('question-id').value;
        
        fetch('skip-question.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'attempt_id=' + attemptId + 
                  '&question_index=' + currentQuestionIndex + 
                  '&skipped=1'
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Error:', error);
        });
        
        if (currentQuestionIndex < totalQuestions - 1) {
            currentQuestionIndex++;
            loadQuestion(currentQuestionIndex);
            updateNavigationButtons();
        } else {
            // Show finish button when on the last question
            const btnNext = document.getElementById('btn-next');
            const btnFinish = document.getElementById('btn-finish');
            if (btnNext) btnNext.style.display = 'none';
            if (btnFinish) btnFinish.style.display = 'inline-block';
        }
    }
    
    // Function to finish the quiz
    function finishQuiz() {
        if (confirm('Are you sure you want to finish the quiz? You will not be able to change your answers after submission.')) {
            submitAllAnswers();
        }
    }
    
    // Function to save the current question state
    function saveCurrentQuestionState() {
        // Save selected answer
        const selectedOption = document.querySelector('input[name="answer"]:checked');
        if (selectedOption) {
            userAnswers[currentQuestionIndex] = selectedOption.value;
        }
        
        // Update question state
        if (questionStates[currentQuestionIndex] === 'current') {
            questionStates[currentQuestionIndex] = userAnswers[currentQuestionIndex] ? 'answered' : 'unanswered';
        }
    }
    
    // Function to load a question
    function loadQuestion(index) {
        // Set current question display
        const currentQuestionElement = document.getElementById('current-question');
        if (currentQuestionElement) {
            currentQuestionElement.textContent = index + 1;
        }
        
        const questionIndexElement = document.getElementById('question-index');
        if (questionIndexElement) {
            questionIndexElement.value = index;
        }
        
        // If we already have the question data in memory, use it directly
        if (window.questionCache && window.questionCache[index]) {
            displayQuestion(window.questionCache[index]);
            return;
        }
        
        // Otherwise fetch question data
        fetch('get-question.php?attempt_id=' + attemptId + '&question_index=' + index)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Cache the question data
                    if (!window.questionCache) {
                        window.questionCache = {};
                    }
                    window.questionCache[index] = data.question;
                    
                    displayQuestion(data.question);
                } else {
                    console.error('Error loading question:', data.message);
                    alert(data.message || 'Error loading question');
                }
            })
            .catch(error => {
                console.error('Error fetching question:', error);
                alert('An error occurred loading the question. Please try again.');
            });
    }
    
    // Function to display a question
    function displayQuestion(question) {
        // Update question display
        const questionIdElement = document.getElementById('question-id');
        const questionTextElement = document.getElementById('question-text');
        const labelAElement = document.getElementById('label-a');
        const labelBElement = document.getElementById('label-b');
        const labelCElement = document.getElementById('label-c');
        const labelDElement = document.getElementById('label-d');
        
        if (questionIdElement) questionIdElement.value = question.id;
        if (questionTextElement) questionTextElement.textContent = question.question_text;
        if (labelAElement) labelAElement.textContent = question.option_a;
        if (labelBElement) labelBElement.textContent = question.option_b;
        if (labelCElement) labelCElement.textContent = question.option_c;
        if (labelDElement) labelDElement.textContent = question.option_d;
        
        // Update progress bar
        const progressBar = document.querySelector('.progress');
        if (progressBar) {
            progressBar.style.width = `${((currentQuestionIndex + 1) / totalQuestions) * 100}%`;
        }
        
        // Mark the current question
        for (let i = 0; i < totalQuestions; i++) {
            if (i === currentQuestionIndex) {
                questionStates[i] = markedForReview[i] ? 'marked' : 'current';
            } else if (questionStates[i] === 'current') {
                questionStates[i] = userAnswers[i] ? 'answered' : 'unanswered';
            }
        }
        
        // Select the saved answer if any
        const options = document.querySelectorAll('input[name="answer"]');
        options.forEach(option => {
            option.checked = (option.value === userAnswers[currentQuestionIndex]);
        });
        
        // Update mark for review button
        const btnMarkReview = document.getElementById('btn-mark-review');
        if (btnMarkReview) {
            if (markedForReview[currentQuestionIndex]) {
                btnMarkReview.classList.add('active');
            } else {
                btnMarkReview.classList.remove('active');
            }
        }
        
        updateQuestionNavigationButtons();
    }
    
    // Function to update navigation buttons
    function updateNavigationButtons() {
        const btnPrevious = document.getElementById('btn-previous');
        const btnNext = document.getElementById('btn-next');
        const btnFinish = document.getElementById('btn-finish');
        
        // Previous button
        if (btnPrevious) {
            btnPrevious.disabled = (currentQuestionIndex === 0);
        }
        
        // Next button & Finish button
        if (btnNext && btnFinish) {
            if (currentQuestionIndex === totalQuestions - 1) {
                btnNext.style.display = 'none';
                btnFinish.style.display = 'inline-block';
            } else {
                btnNext.style.display = 'inline-block';
                btnFinish.style.display = 'none';
            }
        }
    }
    
    // Function to update question navigation buttons
    function updateQuestionNavigationButtons() {
        const buttons = document.querySelectorAll('.question-nav-btn');
        
        buttons.forEach((btn, index) => {
            // Remove all classes first
            btn.classList.remove('answered', 'current', 'marked', 'skipped', 'unanswered');
            
            // Add appropriate class
            btn.classList.add(questionStates[index]);
        });
    }
    
    // Function to submit all answers
    function submitAllAnswers() {
        clearInterval(timerInterval);
        isQuizEnded = true;
        
        const formData = new FormData();
        formData.append('attempt_id', attemptId);
        formData.append('action_type', 'finish');
        
        // Add all answers to the formData
        userAnswers.forEach((answer, index) => {
            if (answer) {
                formData.append(`answers[${index}]`, answer);
            }
        });
        
        // Submit all answers
        fetch('submit-quiz.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'results.php?attempt_id=' + attemptId;
            } else {
                alert(data.message || 'Error submitting quiz');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
});