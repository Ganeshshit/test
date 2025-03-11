<?php
require_once 'includes/functions.php';
secureSessionStart();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('select-domain.php');
}

$errors = [];
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $institution = sanitizeInput($_POST['institution']);
    $university = sanitizeInput($_POST['university']);
    $usn = sanitizeInput($_POST['usn']);
    $github_url = sanitizeInput($_POST['github']);
    $linkedin_url = sanitizeInput($_POST['linkedin']);
    $password = $_POST['password'];
    
    // Handle resume upload
    $uploadDir = __DIR__ . '/uploads/'; // Set absolute path

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true); // Create with correct permissions if missing
    }
    
    $resumeFileName = '';
    
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['resume']['tmp_name'];
        $originalFileName = $_FILES['resume']['name'];
        $fileExt = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
    
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        if (!in_array($fileExt, $allowedExtensions)) {
            echo json_encode(["status" => "error", "message" => "Invalid file type. Only PDF and DOC files are allowed."]);
            exit;
        }
    
        // Generate a unique filename
        $resumeFileName = uniqid('resume_', true) . '.' . $fileExt;
        $destPath = $uploadDir . $resumeFileName;
    
        if (!move_uploaded_file($fileTmpPath, $destPath)) {
            $uploadError = error_get_last();
            echo json_encode([
                "status" => "error", 
                "message" => "Failed to upload resume. Error: " . ($uploadError ? $uploadError['message'] : 'Unknown error')
            ]);
            exit;
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Resume file is required."]);
        exit;
    }
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }
    
    if (empty($institution)) {
        $errors[] = "Institution is required";
    }
    
    if (empty($university)) {
        $errors[] = "University is required";
    }
    
    if (empty($usn)) {
        $errors[] = "USN is required";
    }
    
    if (!empty($github_url) && !filter_var($github_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid GitHub URL format";
    }
    
    if (!empty($linkedin_url) && !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid LinkedIn URL format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $conn = getDbConnection();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM students WHERE usn = ? OR email = ?");
        $stmt->bind_param("ss", $usn, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "USN or email already exists";
        } else {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO students (name, email, phone, institution, university, usn, github, linkedin, resume_path, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $name, $email, $phone, $institution, $university, $usn, $github_url, $linkedin_url, $resumeFileName, $hashedPassword);
            
            if ($stmt->execute()) {
                $userId = $stmt->insert_id;
                
                // Set session variables
                $_SESSION['user_id'] = $userId;
                $_SESSION['email'] = $email;
                $_SESSION['name'] = $name;
                $_SESSION['is_admin'] = 0;
                $_SESSION['created'] = time();
                
                if ($isAjax) {
                    echo json_encode(["status" => "success", "message" => "Registration successful"]);
                } else {
                    redirect('select-domain.php');
                }
                exit;
            } else {
                $errors[] = "Registration failed: " . $conn->error;
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Quiz App</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth-forms.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include "includes/auth-navbar.php" ?>
    <div class="auth-container">
    <div class="auth-card register-card">
        <div class="auth-header">
            <h1>Join Our Community</h1>
            <p>Create an account to get started</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-container">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form id="registrationForm" method="POST" action="register.php" class="auth-form" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="name">
                        <i class="fas fa-user"></i>
                        <span>Full Name</span>
                    </label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?php echo isset($name) ? $name : ''; ?>" required>
                    <span class="error-message" id="nameError"></span>
                </div>
                
                <div class="form-group half-width">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        <span>Email Address</span>
                    </label>
                    <input type="email" id="email" name="email" placeholder="your.email@example.com" value="<?php echo isset($email) ? $email : ''; ?>" required>
                    <span class="error-message" id="emailError"></span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="phone">
                        <i class="fas fa-phone"></i>
                        <span>Mobile Number</span>
                    </label>
                    <input type="tel" id="phone" name="phone" placeholder="10-digit mobile number" value="<?php echo isset($phone) ? $phone : ''; ?>" required>
                    <span class="error-message" id="phoneError"></span>
                </div>
                
                <div class="form-group half-width">
                    <label for="institution">
                        <i class="fas fa-building"></i>
                        <span>Institution</span>
                    </label>
                    <input type="text" id="institution" name="institution" placeholder="Your college/institution name" value="<?php echo isset($institution) ? $institution : ''; ?>" required>
                    <span class="error-message" id="institutionError"></span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="university">
                        <i class="fas fa-university"></i>
                        <span>University</span>
                    </label>
                    <input type="text" id="university" name="university" placeholder="Your university" value="<?php echo isset($university) ? $university : ''; ?>" required>
                    <span class="error-message" id="universityError"></span>
                </div>
                
                <div class="form-group half-width">
                    <label for="usn">
                        <i class="fas fa-id-card"></i>
                        <span>USN</span>
                    </label>
                    <input type="text" id="usn" name="usn" placeholder="Your University Seat Number" value="<?php echo isset($usn) ? $usn : ''; ?>" required>
                    <span class="error-message" id="usnError"></span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        <span>Password</span>
                    </label>
                    <div class="password-input-container">
                        <input type="password" id="password" name="password" placeholder="Choose a strong password" required>
                        <span class="password-toggle" onclick="togglePasswordVisibility()">
                            <i class="fas fa-eye" id="togglePassword"></i>
                        </span>
                    </div>
                    <span class="error-message" id="passwordError"></span>
                </div>
                
                <div class="form-group half-width">
                    <label for="github">
                        <i class="fab fa-github"></i>
                        <span>GitHub Profile (Optional)</span>
                    </label>
                    <input type="url" id="github" name="github" placeholder="https://github.com/yourusername" value="<?php echo isset($github_url) ? $github_url : ''; ?>">
                    <span class="error-message" id="githubError"></span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group half-width">
                    <label for="linkedin">
                        <i class="fab fa-linkedin"></i>
                        <span>LinkedIn Profile (Optional)</span>
                    </label>
                    <input type="url" id="linkedin" name="linkedin" placeholder="https://linkedin.com/in/yourprofile" value="<?php echo isset($linkedin_url) ? $linkedin_url : ''; ?>">
                    <span class="error-message" id="linkedinError"></span>
                </div>
                
                <div class="form-group half-width">
                    <label for="resume">
                        <i class="fas fa-file-alt"></i>
                        <span>Resume (PDF/DOC, Max 5MB)</span>
                    </label>
                    <div class="file-input-container">
                        <span class="file-input-button">Choose File</span>
                        <span id="file-name">No file chosen</span>
                        <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx" required>
                    </div>
                    <span class="error-message" id="resumeError"></span>
                </div>
            </div>
            
            <div class="server-response" id="serverResponse"></div>
            <button type="submit" id="submitBtn" class="btn-auth">Create Account</button>
        </form>
        
        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
</div>
    
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePassword');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
        
        const form = document.getElementById("registrationForm");
        const submitBtn = document.getElementById("submitBtn");
        const serverResponseDiv = document.getElementById("serverResponse");
        const resumeInput = document.getElementById("resume");
        const fileName = document.getElementById("file-name");

        // Update file name display when file is selected
        resumeInput.addEventListener("change", function() {
            if (this.files.length > 0) {
                fileName.textContent = this.files[0].name;
            } else {
                fileName.textContent = "No file chosen";
            }
        });

        function validateForm() {
            let isValid = true;

            const name = document.getElementById("name").value.trim();
            document.getElementById("nameError").textContent = name ? "" : "Name is required";
            if (!name) isValid = false;

            const email = document.getElementById("email").value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            document.getElementById("emailError").textContent = emailRegex.test(email) ? "" : "Invalid email format";
            if (!emailRegex.test(email)) isValid = false;

            const phone = document.getElementById("phone").value.trim();
            const phoneRegex = /^[0-9]{10}$/;
            document.getElementById("phoneError").textContent = phoneRegex.test(phone) ? "" : "Enter a valid 10-digit number";
            if (!phoneRegex.test(phone)) isValid = false;

            const institution = document.getElementById("institution").value.trim();
            document.getElementById("institutionError").textContent = institution ? "" : "Institution is required";
            if (!institution) isValid = false;

            const university = document.getElementById("university").value.trim();
            document.getElementById("universityError").textContent = university ? "" : "University is required";
            if (!university) isValid = false;

            const usn = document.getElementById("usn").value.trim();
            document.getElementById("usnError").textContent = usn ? "" : "USN is required";
            if (!usn) isValid = false;

            const password = document.getElementById("password").value.trim();
            if (!password) {
                document.getElementById("passwordError").textContent = "Password is required";
                isValid = false;
            } else if (password.length < 6) {
                document.getElementById("passwordError").textContent = "Password must be at least 6 characters";
                isValid = false;
            } else {
                document.getElementById("passwordError").textContent = "";
            }

            const github = document.getElementById("github").value.trim();
            if (github && !github.startsWith("http")) {
                document.getElementById("githubError").textContent = "Invalid URL format";
                isValid = false;
            } else {
                document.getElementById("githubError").textContent = "";
            }

            const linkedin = document.getElementById("linkedin").value.trim();
            if (linkedin && !linkedin.startsWith("http")) {
                document.getElementById("linkedinError").textContent = "Invalid URL format";
                isValid = false;
            } else {
                document.getElementById("linkedinError").textContent = "";
            }

            const resume = document.getElementById("resume").files[0];
            if (resume) {
                const allowedTypes = ["application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document"];
                if (!allowedTypes.includes(resume.type)) {
                    document.getElementById("resumeError").textContent = "Only PDF or DOC files allowed";
                    isValid = false;
                } else if (resume.size > 5 * 1024 * 1024) {
                    document.getElementById("resumeError").textContent = "File must be under 5MB";
                    isValid = false;
                } else {
                    document.getElementById("resumeError").textContent = "";
                }
            } else {
                document.getElementById("resumeError").textContent = "Resume is required";
                isValid = false;
            }

            return isValid;
        }

        // Add form validation on submit
        form.addEventListener("submit", function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return;
            }
            
            submitBtn.innerHTML = 'Creating account...';
            submitBtn.disabled = true;
        });

        // Live validation as user types
        form.addEventListener("input", function() {
            validateForm();
        });
    </script>
</body>
</html>