<?php
// File: C:\xampp\htdocs\quiz_platform\my_quizzes.php

// This page is for instructors to view their created quizzes.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: ensure user is logged in and is an instructor.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor') {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

$quizzes = [];
$instructor_id = $_SESSION['user_id'];

// Fetch quizzes created by the current instructor
$sql = "SELECT id, title, description, start_time, end_time, duration_minutes FROM quizzes WHERE instructor_id = ? ORDER BY created_at DESC";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $instructor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quizzes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$conn->close();

include 'includes/header.php';
?>

<div class="page-header">
    <h2>My Quizzes</h2>
    <a href="create_quiz.php" class="btn btn-success">Create New Quiz</a>
</div>

<?php if (isset($_GET['deleted'])): ?>
    <div class="message success"><p>Quiz successfully deleted.</p></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="message error"><p>Failed to delete quiz. Please try again.</p></div>
<?php endif; ?>

<?php if (empty($quizzes)): ?>
    <p>You have not created any quizzes yet.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Availability</th>
                <th>Duration</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quizzes as $quiz): ?>
                <tr>
                    <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                    <td>
                        <?php echo !empty($quiz['start_time']) ? date('M d, Y h:i A', strtotime($quiz['start_time'])) : 'N/A'; ?>
                        -
                        <?php echo !empty($quiz['end_time']) ? date('M d, Y h:i A', strtotime($quiz['end_time'])) : 'N/A'; ?>
                    </td>
                    <td><?php echo !empty($quiz['duration_minutes']) ? htmlspecialchars($quiz['duration_minutes']) . ' mins' : 'N/A'; ?></td>
                    <td class="actions">
                        <a href="quiz_results_instructor.php?quiz_id=<?php echo $quiz['id']; ?>">View Results</a>
                        <a href="add_questions.php?quiz_id=<?php echo $quiz['id']; ?>">Manage Questions</a>
                        <a href="delete_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this entire quiz? This action cannot be undone.');">Delete Quiz</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
