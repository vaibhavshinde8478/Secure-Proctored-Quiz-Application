<?php
// File: C:\xampp\htdocs\quiz_platform\quiz_result.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
    header("location: login.php");
    exit;
}
if (!isset($_GET['submission_id']) || empty($_GET['submission_id'])) {
    header("location: dashboard.php");
    exit;
}

require_once 'includes/db_connect.php';

$submission_id = $_GET['submission_id'];
$student_id = $_SESSION['user_id'];
$result = null;

// Fetch submission details, ensuring it belongs to the current student
$sql = "SELECT s.score, q.title, (SELECT COUNT(*) FROM questions WHERE quiz_id = s.quiz_id) AS total_questions
        FROM submissions s
        JOIN quizzes q ON s.quiz_id = q.id
        WHERE s.id = ? AND s.student_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ii", $submission_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
$conn->close();

if (!$result) {
    // Submission not found or doesn't belong to this student
    header("location: dashboard.php");
    exit;
}

include 'includes/header.php';
?>

<div class="result-container">
    <h2>Quiz Completed!</h2>
    <h3><?php echo htmlspecialchars($result['title']); ?></h3>
    <p>Your final score is:</p>
    <div class="score">
        <?php echo htmlspecialchars($result['score']); ?> / <?php echo htmlspecialchars($result['total_questions']); ?>
    </div>
    <p>Thank you for participating!</p>
    <br>
    <div>
        <a href="available_quizzes.php" class="btn">Take Another Quiz</a>
        <a href="my_results.php" class="btn btn-success">View All My Results</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
