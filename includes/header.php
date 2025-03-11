<html>
    <head>
        <link rel="stylesheet" href="assets/css/header.css"/>
    </head>
    <body>
        <nav class="navbar" id="navbar">
            <a href="index.php" class="navbar-brand">
                <!-- Logo SVG -->
                <svg class="logo" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <defs>
                        <linearGradient id="logo-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#4361ee" />
                            <stop offset="100%" stop-color="#7209b7" />
                        </linearGradient>
                    </defs>
                    <path fill="url(#logo-gradient)" d="M256 32C132.3 32 32 132.3 32 256s100.3 224 224 224 224-100.3 224-224S379.7 32 256 32zm128 224c0 8.8-7.2 16-16 16h-94.8l44.8 44.8c6.2 6.2 6.2 16.4 0 22.6-6.2 6.2-16.4 6.2-22.6 0L224 267.2l-71.2 71.2c-6.2 6.2-16.4 6.2-22.6 0-6.2-6.2-6.2-16.4 0-22.6l44.8-44.8H80c-8.8 0-16-7.2-16-16s7.2-16 16-16h94.8l-44.8-44.8c-6.2-6.2-6.2-16.4 0-22.6 6.2-6.2 16.4-6.2 22.6 0l71.2 71.2 71.2-71.2c6.2-6.2 16.4-6.2 22.6 0 6.2 6.2 6.2 16.4 0 22.6L272.8 240H368c8.8 0 16 7.2 16 16z"/>
                </svg>
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

    </body>
</html>