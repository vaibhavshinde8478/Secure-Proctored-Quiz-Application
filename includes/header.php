<?php
// File: C:\xampp\htdocs\quiz_platform\includes\header.php

// Start the session at the very beginning of the script.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Quiz Platform</title>
    <!-- Link to the external stylesheet -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <header class="navbar">
        <div class="container">
            <a href="index.php" class="nav-logo">Quizzer</a>
            <ul class="nav-menu">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Links to show if the user is logged in -->
                    <li class="nav-item"><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="profile.php" class="nav-link">Profile</a></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link">Logout</a></li>
                <?php else: ?>
                    <!-- Links to show if the user is a guest -->
                    <li class="nav-item"><a href="login.php" class="nav-link">Login</a></li>
                    <li class="nav-item"><a href="register.php" class="nav-link">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>
    <main>
        <div class="container">
        <!-- The main content of each page will go here -->
