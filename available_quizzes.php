<?php
// File: C:\xampp\htdocs\quiz_platform\available_quizzes.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: ensure user is logged in and is a student.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

$quizzes = [];
$student_id = $_SESSION['user_id'];

// Get a list of quiz IDs the student has already taken
$taken_quizzes_ids = [];
$sql_taken = "SELECT DISTINCT quiz_id FROM submissions WHERE student_id = ?";
if($stmt_taken = $conn->prepare($sql_taken)){
    $stmt_taken->bind_param("i", $student_id);
    $stmt_taken->execute();
    $result_taken = $stmt_taken->get_result();
    while($row = $result_taken->fetch_assoc()){
        $taken_quizzes_ids[] = $row['quiz_id'];
    }
    $stmt_taken->close();
}


// Fetch quizzes assigned to the student's groups that are currently active
$sql = "SELECT DISTINCT q.id, q.title, q.description, u.username AS instructor_name 
        FROM quizzes q
        JOIN users u ON q.instructor_id = u.id
        JOIN quiz_assignments qa ON q.id = qa.quiz_id
        JOIN group_members gm ON qa.group_id = gm.group_id
        WHERE gm.student_id = ? AND NOW() BETWEEN q.start_time AND q.end_time
        ORDER BY q.created_at DESC";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quizzes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
$conn->close();

include 'includes/header.php';
?>

<div class="page-header">
    <h2>Available Quizzes</h2>
</div>

<?php if (empty($quizzes)): ?>
    <p>There are no available quizzes assigned to you at the moment. Please check back later.</p>
<?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Created by</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quizzes as $quiz): ?>
                <tr>
                    <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                    <td><?php echo htmlspecialchars($quiz['description']); ?></td>
                    <td><?php echo htmlspecialchars($quiz['instructor_name']); ?></td>
                    <td class="actions">
                        <?php if (in_array($quiz['id'], $taken_quizzes_ids)): ?>
                            <span class="btn" style="background: #aaa; cursor: not-allowed;">Completed</span>
                        <?php else: ?>
                            <a href="start_quiz.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn">Take Quiz</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
