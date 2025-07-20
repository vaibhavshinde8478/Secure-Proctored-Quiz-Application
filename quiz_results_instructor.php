<?php
// File: C:\xampp\htdocs\quiz_platform\quiz_results_instructor.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security checks
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
$quiz_title = '';
$submissions = [];

// Verify quiz belongs to the instructor and get its title
$sql_verify = "SELECT title FROM quizzes WHERE id = ? AND instructor_id = ?";
if ($stmt_verify = $conn->prepare($sql_verify)) {
    $stmt_verify->bind_param("ii", $quiz_id, $instructor_id);
    $stmt_verify->execute();
    $stmt_verify->bind_result($title);
    if (!$stmt_verify->fetch()) {
        header("location: my_quizzes.php"); // Not authorized
        exit;
    }
    $quiz_title = $title;
    $stmt_verify->close();
}

// Fetch all submissions for this quiz
$sql_submissions = "SELECT s.id, u.username, s.score, s.submitted_at, 
                    (SELECT COUNT(*) FROM questions WHERE quiz_id = s.quiz_id) AS total_questions
                    FROM submissions s
                    JOIN users u ON s.student_id = u.id
                    WHERE s.quiz_id = ?
                    ORDER BY s.submitted_at DESC";

if ($stmt = $conn->prepare($sql_submissions)) {
    $stmt->bind_param("i", $quiz_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $submissions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$conn->close();

include 'includes/header.php';
?>

<div class="page-header">
    <h2>Results for "<?php echo htmlspecialchars($quiz_title); ?>"</h2>
    <a href="my_quizzes.php" class="btn">Back to My Quizzes</a>
</div>

<?php if (empty($submissions)): ?>
    <p>No students have completed this quiz yet.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Score</th>
                <th>Date Submitted</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $submission): ?>
                <tr>
                    <td><?php echo htmlspecialchars($submission['username']); ?></td>
                    <td><?php echo $submission['score']; ?> / <?php echo $submission['total_questions']; ?></td>
                    <td><?php echo date('M d, Y, h:i A', strtotime($submission['submitted_at'])); ?></td>
                    <td class="actions">
                        <a href="view_submission.php?submission_id=<?php echo $submission['id']; ?>">View Details</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
