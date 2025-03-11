<?php
require_once 'includes/functions.php';
secureSessionStart();

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    // Clear the error from the session after retrieving it
    unset($_SESSION['error']);
} else {
    $errorMessage = null;
}

// Check if field_id is provided
if (!isset($_GET['field_id'])) {
    redirect('select-domain.php');
}

$fieldId = (int)$_GET['field_id'];

// Get difficulty levels
$difficultyLevels = getDifficultyLevels();

// Get field name
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT name FROM fields WHERE id = ?");
$stmt->bind_param("i", $fieldId);
$stmt->execute();
$result = $stmt->get_result();
$field = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Store field_id in session
$_SESSION['field_id'] = $fieldId;

// Set active page for navbar
$activePage = 'quiz';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Difficulty - Quiz App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/select-difficulty.css" />
    <style>
        .error-message {
    background-color: #ffebee;
    border: 1px solid #ffcdd2;
    color: #c62828;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 5px;
    text-align: center;
}
    </style>
</head>
<body>
   <?php include "includes/header.php" ?>

   <?php include "includes/progress-bar.php" ?>

   <?php if ($errorMessage): ?>
        <div class="error-message">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>
    
    <!-- Overlay -->
    <div class="overlay" id="overlay"></div>
    
    <!-- Main Content -->
    <div class="content-container">
        <div class="container">
            <h1>Select Difficulty Level</h1>
            <h2>Field: <?php echo $field['name']; ?></h2>
            
            <div class="difficulty-selection">
                <?php foreach ($difficultyLevels as $level): ?>
                    <a href="quiz.php?difficulty_id=<?php echo $level['id']; ?>" class="difficulty-card">
                        <h3><?php echo $level['name']; ?></h3>
                        <p>Select this difficulty to start the quiz</p>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="actions">
                <a href="select-field.php?domain_id=<?php echo $_SESSION['domain_id']; ?>" class="btn-action btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Fields
                </a>
                <a href="profile.php" class="btn-action btn-primary">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle mobile menu
        const navbarToggle = document.getElementById('navbar-toggle');
        const navbarNav = document.getElementById('navbar-nav');
        const overlay = document.getElementById('overlay');
        
        navbarToggle.addEventListener('click', function() {
            navbarToggle.classList.toggle('active');
            navbarNav.classList.toggle('active');
            overlay.classList.toggle('active');
            
            // Prevent scrolling when menu is open
            if (navbarNav.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = 'visible';
            }
        });
        
        // Close mobile menu when clicking on overlay
        overlay.addEventListener('click', function() {
            navbarToggle.classList.remove('active');
            navbarNav.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'visible';
        });
        
        // Close mobile menu when clicking on a nav link
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                navbarToggle.classList.remove('active');
                navbarNav.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = 'visible';
            });
        });
        
        // Add shadow and change style on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 10) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
    // Set initial progress
    updateProgressBar(3); 
    
    // Function to update progress bar
    function updateProgressBar(currentStep) {
        const totalSteps = 4;
        const progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
        
        // Update the progress line fill
        const progressLine = document.getElementById('progressLineFill');
        progressLine.style.width = `${progressPercentage}%`;
        
        // Update step statuses
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach(step => {
            const stepNumber = parseInt(step.dataset.step);
            
            // Reset all classes first
            step.classList.remove('active', 'completed');
            
            // Apply appropriate class
            if (stepNumber < currentStep) {
                step.classList.add('completed');
            } else if (stepNumber === currentStep) {
                step.classList.add('active');
            }
        });
    }
    
    // Expose the function globally so it can be called from other pages
    window.updateProgressBar = updateProgressBar;
});
    </script>
</body>
</html>