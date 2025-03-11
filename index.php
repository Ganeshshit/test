<?php
    session_start(); 

    //! Redirect user to main page if login otherwise to login page
    
    if (isset($_SESSION['email'])) {
        header("Location: select-domain.php");
        exit;
    } else {
        header("Location: login.php");
        exit;
    }
?>
