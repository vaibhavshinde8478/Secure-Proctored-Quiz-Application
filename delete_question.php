<?php
// File: C:\xampp\htdocs\quiz_platform\delete_question.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor') {
    header("location: login.php");
    exit;
}
if (!isset($_GET['question_id']) || empty($_GET['question_id'])) {
    header("location: my_quizzes.php");
    exit;
}

require_once 'includes/db_connect.php';

$question_id = $_GET['question_id'];
$quiz_id = null;

// Verify question belongs to the logged-in instructor and get quiz_id for redirect
$sql_verify = "SELECT q.quiz_id FROM questions q JOIN quizzes z ON q.quiz_id = z.id WHERE q.id = ? AND z.instructor_id = ?";
if($stmt_verify = $conn->prepare($sql_verify)){
    $stmt_verify->bind_param("ii", $question_id, $_SESSION['user_id']);
    $stmt_verify->execute();
    $stmt_verify->bind_result($q_id);
    if($stmt_verify->fetch()){
        $quiz_id = $q_id;
    } else {
        // Not authorized or question doesn't exist
        header("location: my_quizzes.php");
        exit;
    }
    $stmt_verify->close();
}

// If verification is successful, proceed with deletion
$sql_delete = "DELETE FROM questions WHERE id = ?";
if ($stmt_delete = $conn->prepare($sql_delete)) {
    $stmt_delete->bind_param("i", $question_id);
    
    // Deleting the question will cascade and delete its options due to FOREIGN KEY constraints
    if ($stmt_delete->execute()) {
        // Redirect back to the manage questions page
        header("location: add_questions.php?quiz_id=" . $quiz_id . "&deleted=true");
        exit;
    } else {
        // Handle error, maybe redirect with an error message
        header("location: add_questions.php?quiz_id=" . $quiz_id . "&error=true");
        exit;
    }
    $stmt_delete->close();
}
$conn->close();
