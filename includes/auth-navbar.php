<?php
require_once 'includes/functions.php';
$isLoggedIn = isLoggedIn();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuizMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
    --primary-color: #4361ee;
    --secondary-color: #7209b7;
    --text-color: #333;
    --bg-color: #fff;
    --hover-color: #f5f7ff;
    --shadow: 0 2px 6px rgba(0,0,0,0.05);
    --transition: 0.2s ease;
}

/* Container */
.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Navbar styles */
.navbar {
    background-color: var(--bg-color);
    box-shadow: var(--shadow);
    position: sticky;
    top: 0;
    z-index: 1000;
    transition: all var(--transition);
    border-bottom: 1px solid rgba(0, 0, 0, 0.03);
}

.navbar .container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
}

/* Logo area */
.navbar-brand {
    display: flex;
    align-items: center;
    text-decoration: none;
}

.logo {
    height: 38px;
    margin-right: 10px;
    transition: transform var(--transition);
}

.navbar-brand:hover .logo {
    transform: scale(1.05);
}

.company-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary-color);
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.5px;
}

/* Navbar collapse */
.navbar-collapse {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-grow: 1;
    margin-left: 2rem;
}

/* Navigation links */
.navbar-nav {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    list-style: none;
    margin: 0;
    padding: 0;
}

.nav-item {
    position: relative;
}

.nav-link {
    color: var(--text-color);
    font-weight: 500;
    font-size: 1rem;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    transition: all var(--transition);
    position: relative;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-link:before {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background-color: var(--primary-color);
    transition: width var(--transition);
}

.nav-link:hover {
    color: var(--primary-color);
}

.nav-link:hover:before {
    width: 100%;
}

.nav-link.active {
    color: var(--primary-color);
    font-weight: 600;
}

.nav-link.active:before {
    width: 100%;
}

/* Navbar actions */
.navbar-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Button styling */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 1.25rem;
    border-radius: 4px;
    transition: all var(--transition);
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
}

.btn-primary {
    background-color: var(--primary-color);
    color: white;
    box-shadow: 0 2px 4px rgba(67, 97, 238, 0.3);
}

.btn-primary:hover {
    background-color: var(--secondary-color);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(114, 9, 183, 0.3);
}

.btn-outline {
    background-color: transparent;
    color: var(--primary-color);
    border: 1px solid var(--primary-color);
}

.btn-outline:hover {
    background-color: rgba(67, 97, 238, 0.05);
}

/* Hamburger menu */
.navbar-toggle {
    display: none;
    cursor: pointer;
    background: none;
    border: none;
    padding: 6px;
    z-index: 1001;
}

.bar {
    display: block;
    width: 22px;
    height: 2px;
    margin: 4px auto;
    background-color: var(--text-color);
    transition: all var(--transition);
}

/* Scrolled navbar style */
.navbar.scrolled {
    padding: 0.6rem 0;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.07);
}

/* Responsive design */
@media (max-width: 768px) {
    .navbar-toggle {
        display: block;
    }
    
    .navbar-collapse {
        position: fixed;
        top: 0;
        right: -100%;
        width: 250px;
        height: 100vh;
        background-color: var(--bg-color);
        flex-direction: column;
        justify-content: center;
        padding: 2rem 1rem;
        transition: all 0.3s ease-in-out;
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
        margin-left: 0;
    }
    
    .navbar-collapse.active {
        right: 0;
    }
    
    .navbar-nav {
        flex-direction: column;
        width: 100%;
        margin-bottom: 2rem;
    }
    
    .nav-item {
        width: 100%;
    }
    
    .nav-link {
        width: 100%;
        text-align: center;
        padding: 0.75rem;
    }
    
    .navbar-actions {
        flex-direction: column;
        width: 100%;
        gap: 1rem;
    }
    
    .btn {
        width: 100%;
        text-align: center;
    }
    
    .navbar-toggle.active .bar:nth-child(1) {
        transform: translateY(6px) rotate(45deg);
    }
    
    .navbar-toggle.active .bar:nth-child(2) {
        opacity: 0;
    }
    
    .navbar-toggle.active .bar:nth-child(3) {
        transform: translateY(-6px) rotate(-45deg);
    }
    
    .nav-link:before {
        display: none;
    }
    
    .nav-link:hover {
        background-color: var(--hover-color);
    }
    
    .nav-link.active {
        background-color: var(--hover-color);
    }
}

/* Overlay for mobile menu */
.overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease-in-out;
}

.overlay.active {
    opacity: 1;
    visibility: visible;
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">
                <!-- <img class="logo" src="" alt="logo" /> -->
                <span class="company-name">Medini</span>
            </a>
            
            <button class="navbar-toggle" id="navbar-toggle" aria-label="Toggle navigation">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </button>
            
            <div class="navbar-collapse" id="navbar-collapse">
                <ul class="navbar-nav">
                    
                    <?php if ($isLoggedIn): ?>
                    <li class="nav-item">
                        <a href="profile.php" class="nav-link <?php echo $currentPage == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <div class="navbar-actions">
                    <?php if ($isLoggedIn): ?>
                        <a href="logout.php" class="btn btn-outline">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-primary">Login</a>
                        <a href="register.php" class="btn btn-outline">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <script src="assets/js/navbar.js"></script>
</body>
</html>