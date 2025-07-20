<?php
// File: C:\xampp\htdocs\quiz_platform\delete_quiz.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security checks: user must be a logged-in instructor
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor') {
    header("location: login.php");
    exit;
}
if (!isset($_GET['quiz_id']) || empty($_GET['quiz_id'])) {
    header("location: my_quizzes.php");
    exit;
}

require_once 'includes/db_connect.php';

$quiz_id = $_GET['quiz_id'];
$instructor_id = $_SESSION['user_id'];

// Verify the quiz belongs to the logged-in instructor before deleting
$sql_verify = "SELECT id FROM quizzes WHERE id = ? AND instructor_id = ?";
if ($stmt_verify = $conn->prepare($sql_verify)) {
    $stmt_verify->bind_param("ii", $quiz_id, $instructor_id);
    $stmt_verify->execute();
    $stmt_verify->store_result();
    
    if ($stmt_verify->num_rows == 1) {
        // Verification successful, proceed with deletion
        $sql_delete = "DELETE FROM quizzes WHERE id = ?";
        if ($stmt_delete = $conn->prepare($sql_delete)) {
            $stmt_delete->bind_param("i", $quiz_id);
            
            // Deleting the quiz will cascade and delete all related data
            // (questions, options, submissions, answers) due to FOREIGN KEY constraints.
            if ($stmt_delete->execute()) {
                // Redirect back to the quizzes list with a success message
                header("location: my_quizzes.php?deleted=true");
                exit;
            }
        }
    }
}

// If something went wrong, redirect with an error
header("location: my_quizzes.php?error=delete_failed");
exit;

?>
