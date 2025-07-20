<?php
// File: C:\xampp\htdocs\quiz_platform\dashboard.php

// This is the main page for a logged-in user.
// It will show different content based on the user's role (student or instructor).

// Initialize the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in, if not then redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include the header
include 'includes/header.php';
?>

<div class="page-header">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
    <p style="margin:0;">Your role is: <strong><?php echo htmlspecialchars(ucfirst($_SESSION["role"])); ?></strong></p>
</div>

<div class="dashboard-content">
    <?php if ($_SESSION["role"] == 'instructor'): ?>
        
        <h3>Instructor Dashboard</h3>
        <p>From here you can manage your quizzes and student groups.</p>
        <a href="my_quizzes.php" class="btn">Manage My Quizzes</a>
        <a href="manage_groups.php" class="btn btn-success">Manage Student Groups</a>

    <?php else: // Role is 'student' ?>

        <h3>Student Dashboard</h3>
        <p>From here you can find and take available quizzes, and review your past results.</p>
        <a href="available_quizzes.php" class="btn">Browse Available Quizzes</a>
        <a href="my_results.php" class="btn btn-success">View My Results</a>

    <?php endif; ?>
</div>

<?php
// Include the footer
include 'includes/footer.php';
?>
