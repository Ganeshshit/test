<?php
require_once 'includes/functions.php';
secureSessionStart();

// Redirect if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get user details
$user = getUserDetails($_SESSION['user_id']);

// Handle profile update
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $institution = sanitizeInput($_POST['institution']);
    $university = sanitizeInput($_POST['university']);
    $usn = sanitizeInput($_POST['usn']);
    $github_url = sanitizeInput($_POST['github']);
    $linkedin_url = sanitizeInput($_POST['linkedin']);
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
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
    
    // Handle resume upload
    $resume_path = $user['resume_path'];
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['resume']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        // Verify file extension
        if (!in_array(strtolower($filetype), $allowed)) {
            $errors[] = "Resume must be a PDF, DOC, or DOCX file";
        } else {
            // Create uploads directory if it doesn't exist
            if (!file_exists('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Create a unique filename
            $new_filename = uniqid('resume_') . '.' . $filetype;
            $upload_path = 'uploads/' . $new_filename;
            
            // Upload file
            if (move_uploaded_file($_FILES['resume']['tmp_name'], $upload_path)) {
                // Delete old resume if exists
                if (!empty($resume_path) && file_exists($resume_path)) {
                    unlink($resume_path);
                }
                $resume_path = $upload_path;
            } else {
                $errors[] = "Failed to upload resume";
            }
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $conn = getDbConnection();
        
        // Check if email already exists (if changed)
        if ($email != $user['email']) {
            $stmt = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $errors[] = "Email already exists";
                $stmt->close();
                $conn->close();
            } else {
                $stmt->close();
            }
        }
        
        if (empty($errors)) {
            // Update user profile
            $stmt = $conn->prepare("UPDATE students SET name = ?, phone = ?, institution = ?, university = ?, usn = ?, github = ?, linkedin = ?, resume_path = ? WHERE id = ?");
            $stmt->bind_param("ssssssssi", $name, $phone, $institution, $university, $usn, $github_url, $linkedin_url, $resume_path, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = true;
                // Update session variables
                $_SESSION['name'] = $name;
                
                // Refresh user data
                $user = getUserDetails($_SESSION['user_id']);
            } else {
                $errors[] = "Profile update failed: " . $conn->error;
            }
            
            $stmt->close();
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - QuizMaster</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- <link rel="stylesheet" href="assets/css/header.css"/> -->
    <style>
        /* Root variables */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #7209b7;
            --accent-color: #ff9e00;
            --text-color: #2b2d42;
            --bg-color: #ffffff;
            --hover-color: #f8f9fa;
            --light-gray: #f9fafc;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            --card-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
            --transition: 0.25s ease;
        }
        
        /* Base styles and reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-gray);
            color: var(--text-color);
            line-height: 1.6;
            padding-bottom: 50px;
        }

        a {
            text-decoration: none;
            color: inherit;
        }
        
        /* Enhanced Profile Page Styles */
        .profile-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .profile-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .profile-subtitle {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .profile-card {
            background: #fff;
            border-radius: 15px;
            padding: 0;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .profile-card:hover {
            transform: translateY(-5px);
        }
        
        .profile-content {
            padding: 20px 30px 30px;
        }
        
        .form-section {
            margin-top: 20px;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            color: var(--secondary-color);
        }
        
        .form-floating {
            margin-bottom: 20px;
        }
        
        .form-control {
            padding: 1rem 0.75rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.1);
        }
        
        .form-control:disabled {
            background-color: rgba(0, 0, 0, 0.03);
        }
        
        .form-label {
            font-weight: 500;
        }
        
        .social-icon {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.3);
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }
        
        .btn-secondary {
            background-color: transparent;
            color: var(--text-color);
            border: 1px solid rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background-color: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.3);
        }
        
        .resume-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-color);
            font-weight: 500;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .resume-link:hover {
            color: var(--secondary-color);
        }
        
        .resume-link i {
            margin-right: 8px;
        }
        
        .alert {
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .alert-success {
            background-color: #d1e7dd;
            border-color: #badbcc;
            color: #0f5132;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c2c7;
            color: #842029;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .profile-container {
                padding: 0 15px;
            }
            
            .profile-content {
                padding: 20px 20px 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <style>
        /* Navbar Styles */
        .navbar {
            background-color: #ffffff !important;
            box-shadow: var(--shadow) !important;
            padding: 0.75rem 1.5rem !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 1000 !important;
            transition: all var(--transition) !important;
        }

        .navbar-nav {
            display: flex !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
            align-items: center !important;
            flex-direction: row !important; /* Force horizontal layout */
        }
    
        .nav-item {
            margin-left: 1.5rem !important;
        }
    
    
        .navbar-brand {
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.4rem;
            color: var(--primary-color);
        }
    
        .logo {
            height: 36px;
            width: 36px;
            margin-right: 10px;
        }
    
        .company-name {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
    
        .navbar-toggle {
            display: none;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 24px;
            position: relative;
        }
    
        .bar {
            display: block;
            width: 100%;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
            transition: all 0.3s ease;
            position: absolute;
        }
    
        .bar:nth-child(1) {
            top: 0;
        }
    
        .bar:nth-child(2) {
            top: 10px;
        }
    
        .bar:nth-child(3) {
            top: 20px;
        }
    
    
    
        .nav-link {
            color: var(--text-color);
            font-weight: 500;
            font-size: 1rem;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            transition: all var(--transition);
        }
    
        .nav-link:hover {
            color: var(--primary-color);
            background-color: var(--hover-color);
        }
    
        .nav-link.active {
            color: var(--primary-color);
            font-weight: 600;
            position: relative;
        }
    
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0.75rem;
            right: 0.75rem;
            height: 3px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 3px;
        }
    
        .btn-logout {
            color: #fff;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 0.5rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.15);
            transition: all 0.3s ease;
        }
    
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(67, 97, 238, 0.25);
            color: #fff;
        }
    
        /* Responsive design for navbar */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
            }
        
            .navbar-toggle {
                display: block;
                z-index: 1002;
            }
        
            .navbar-nav {
                position: fixed;
                top: 0;
                right: -100%;
                height: 100vh;
                width: 250px;
                background-color: var(--bg-color);
                flex-direction: column;
                align-items: flex-start;
                padding: 80px 20px 30px;
                transition: all 0.4s ease;
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
                z-index: 1001;
            }
        
            .navbar-nav.active {
                right: 0;
            }
        
            .nav-item {
                margin: 0 0 15px 0;
                width: 100%;
            }
        
            .nav-link {
                display: block;
                padding: 0.75rem 1rem;
                width: 100%;
            }
        
            .navbar-toggle.active .bar:nth-child(1) {
                transform: rotate(45deg);
                top: 10px;
            }
        
            .navbar-toggle.active .bar:nth-child(2) {
                opacity: 0;
            }
        
            .navbar-toggle.active .bar:nth-child(3) {
                transform: rotate(-45deg);
                top: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- navbar -->
    <nav class="navbar" id="navbar">
    <a href="index.php" class="navbar-brand">
        <!-- Logo SVG -->
        
        <span class="company-name">Medini</span>
    </a>
    
    <button class="navbar-toggle" id="navbar-toggle" aria-label="Toggle navigation">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </button>
    
    <ul class="navbar-nav" id="navbar-nav">
        <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo ($activePage == 'home') ? 'active' : ''; ?>">Home</a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?php echo ($activePage == 'profile') ? 'active' : ''; ?>">Profile</a>
        </li>
        <li class="nav-item">
            <a href="logout.php" class="nav-link btn btn-logout">Logout</a>
        </li>
    </ul>
</nav>

    <!-- Profile Section -->
    <div class="profile-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <p class="profile-subtitle">Manage your personal information and preferences</p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i> Your profile has been updated successfully!
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> Please correct the following errors:
                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="profile-card">
            <div class="profile-content">
                <form method="POST" action="profile.php" enctype="multipart/form-data">
                    <div class="form-section">
                        <h3 class="section-title">Personal Information</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $user['name']; ?>" placeholder="Full Name" required>
                                    <label for="name"><i class="fas fa-user social-icon"></i>Full Name</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" placeholder="Email" disabled>
                                    <label for="email"><i class="fas fa-envelope social-icon"></i>Email Address</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $user['phone']; ?>" placeholder="Phone Number" required>
                                    <label for="phone"><i class="fas fa-phone social-icon"></i>Phone Number</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Academic Information</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="institution" name="institution" value="<?php echo $user['institution']; ?>" placeholder="Institution" required>
                                    <label for="institution"><i class="fas fa-building social-icon"></i>Institution</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="university" name="university" value="<?php echo $user['university']; ?>" placeholder="University" required>
                                    <label for="university"><i class="fas fa-university social-icon"></i>University</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="usn" name="usn" value="<?php echo $user['usn']; ?>" placeholder="USN" required>
                                    <label for="usn"><i class="fas fa-id-card social-icon"></i>University Seat Number (USN)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Professional Profiles</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="url" class="form-control" id="github_url" name="github" value="<?php echo $user['github']; ?>" placeholder="GitHub URL">
                                    <label for="github_url"><i class="fab fa-github social-icon"></i>GitHub Profile URL</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="url" class="form-control" id="linkedin_url" name="linkedin" value="<?php echo $user['linkedin']; ?>" placeholder="LinkedIn URL">
                                    <label for="linkedin_url"><i class="fab fa-linkedin social-icon"></i>LinkedIn Profile URL</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Resume</h3>
                        <?php if (!empty($user['resume_path'])): ?>
                            <a href="<?php echo $user['resume_path']; ?>" class="resume-link" target="_blank">
                                <i class="fas fa-file-pdf"></i> View Current Resume
                            </a>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="resume" class="form-label"><i class="fas fa-upload social-icon"></i>Upload New Resume (PDF, DOC, DOCX)</label>
                            <input type="file" class="form-control" id="resume" name="resume" accept=".pdf,.doc,.docx">
                            <div class="form-text text-muted">Upload a new file to replace your current resume</div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                        <a href="select-domain.php" class="btn btn-secondary" style="color:black">
                            <i class="fas fa-chevron-left me-2"></i> Back to Quizzes
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>