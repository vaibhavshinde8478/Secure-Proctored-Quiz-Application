<?php
// File: C:\xampp\htdocs\quiz_platform\index.php

// This is the main landing page for the website.
// It will serve as a welcome page and guide users to login or register.

// Include the header file.
include 'includes/header.php';
?>

<div class="page-content" style="text-align: center; padding: 50px 20px;">
    <h1>Welcome to the Online Quiz Platform</h1>
    <p style="font-size: 1.2rem; color: #555;">Test your knowledge or create engaging quizzes for others.</p>
    <br>
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <!-- If user is logged in, show a button to go to the dashboard -->
        <p>You are logged in. Go to your dashboard to get started.</p>
        <a href="dashboard.php" class="btn" style="display: inline-block; width: auto; padding: 10px 30px;">Go to Dashboard</a>
    <?php else: ?>
        <!-- If user is not logged in, show buttons to login or register -->
        <p>Please log in to continue or register for a new account.</p>
        <div>
            <a href="login.php" class="btn" style="display: inline-block; width: auto; padding: 10px 30px; margin-right: 10px;">Login</a>
            <a href="register.php" class="btn" style="display: inline-block; width: auto; padding: 10px 30px; background-color: #28a745;">Register</a>
        </div>
    <?php endif; ?>
</div>


<?php
// Include the footer file.
include 'includes/footer.php';
?>

