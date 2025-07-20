<?php
// File: C:\xampp\htdocs\quiz_platform\my_results.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: ensure user is logged in and is a student.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

$results = [];
$student_id = $_SESSION['user_id'];

// Fetch all of the student's past submissions, including the submission ID
$sql = "SELECT s.id as submission_id, q.title, s.score, s.submitted_at, 
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) AS total_questions
        FROM submissions s
        JOIN quizzes q ON s.quiz_id = q.id
        WHERE s.student_id = ?
        ORDER BY s.submitted_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $results = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$conn->close();

include 'includes/header.php';
?>

<div class="page-header">
    <h2>My Quiz Results</h2>
</div>

<?php if (isset($_GET['error']) && $_GET['error'] == 'already_taken'): ?>
    <div class="message error">
        <p>You have already completed that quiz.</p>
    </div>
<?php endif; ?>

<?php if (empty($results)): ?>
    <p>You have not taken any quizzes yet.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Quiz Title</th>
                <th>Score</th>
                <th>Date Taken</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['score']); ?> / <?php echo htmlspecialchars($row['total_questions']); ?></td>
                    <td><?php echo date('M d, Y, h:i A', strtotime($row['submitted_at'])); ?></td>
                    <td class="actions">
                        <a href="view_submission.php?submission_id=<?php echo $row['submission_id']; ?>">Review</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
