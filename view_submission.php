<?php
// File: C:\xampp\htdocs\quiz_platform\view_submission.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
if (!isset($_GET['submission_id']) || empty($_GET['submission_id'])) {
    header("location: dashboard.php");
    exit;
}

require_once 'includes/db_connect.php';

$submission_id = $_GET['submission_id'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// --- Authorization Check ---
// A user can view a submission if:
// 1. They are the student who made the submission.
// 2. They are an instructor who owns the quiz.
$sql_auth = "SELECT s.student_id, q.instructor_id, q.title 
             FROM submissions s 
             JOIN quizzes q ON s.quiz_id = q.id 
             WHERE s.id = ?";

if ($stmt_auth = $conn->prepare($sql_auth)) {
    $stmt_auth->bind_param("i", $submission_id);
    $stmt_auth->execute();
    $stmt_auth->bind_result($student_id, $instructor_id, $quiz_title);
    if (!$stmt_auth->fetch()) {
        header("location: dashboard.php"); // Submission not found
        exit;
    }
    $stmt_auth->close();

    if ($user_role == 'student' && $user_id != $student_id) {
        header("location: dashboard.php"); // Student trying to view someone else's submission
        exit;
    }
    if ($user_role == 'instructor' && $user_id != $instructor_id) {
        header("location: dashboard.php"); // Instructor trying to view submission for a quiz they don't own
        exit;
    }
}

// --- Fetch all necessary data for review ---
// 1. Get all questions for this quiz
$questions = [];
$sql_questions = "SELECT q.id, q.question_text 
                  FROM questions q 
                  JOIN quizzes z ON q.quiz_id = z.id
                  JOIN submissions s ON z.id = s.quiz_id
                  WHERE s.id = ?";
$stmt_q = $conn->prepare($sql_questions);
$stmt_q->bind_param("i", $submission_id);
$stmt_q->execute();
$result_q = $stmt_q->get_result();
while ($row = $result_q->fetch_assoc()) {
    $questions[$row['id']] = ['text' => $row['question_text'], 'options' => [], 'student_answer' => null, 'correct_answer' => null];
}
$stmt_q->close();

// 2. Get all options and student's answers
if (!empty($questions)) {
    $question_ids = array_keys($questions);
    $placeholders = implode(',', array_fill(0, count($question_ids), '?'));

    // Get all options for these questions
    $sql_options = "SELECT id, question_id, option_text, is_correct FROM options WHERE question_id IN ($placeholders)";
    $stmt_o = $conn->prepare($sql_options);
    $stmt_o->bind_param(str_repeat('i', count($question_ids)), ...$question_ids);
    $stmt_o->execute();
    $result_o = $stmt_o->get_result();
    while ($row = $result_o->fetch_assoc()) {
        $questions[$row['question_id']]['options'][$row['id']] = $row['option_text'];
        if ($row['is_correct']) {
            $questions[$row['question_id']]['correct_answer'] = $row['id'];
        }
    }
    $stmt_o->close();

    // Get the student's selected answers
    $sql_answers = "SELECT question_id, selected_option_id FROM answers WHERE submission_id = ?";
    $stmt_a = $conn->prepare($sql_answers);
    $stmt_a->bind_param("i", $submission_id);
    $stmt_a->execute();
    $result_a = $stmt_a->get_result();
    while ($row = $result_a->fetch_assoc()) {
        $questions[$row['question_id']]['student_answer'] = $row['selected_option_id'];
    }
    $stmt_a->close();
}

$conn->close();
include 'includes/header.php';
?>

<div class="page-header">
    <h2>Reviewing Quiz: "<?php echo htmlspecialchars($quiz_title); ?>"</h2>
</div>

<div class="review-container">
    <?php foreach ($questions as $q_id => $question): ?>
        <div class="review-question-block">
            <p class="question-text"><?php echo htmlspecialchars($question['text']); ?></p>
            <ul class="review-options-list">
                <?php foreach ($question['options'] as $opt_id => $opt_text): 
                    $class = '';
                    if ($opt_id == $question['correct_answer']) {
                        $class = 'correct'; // Always highlight the correct answer
                    }
                    if ($opt_id == $question['student_answer'] && $opt_id != $question['correct_answer']) {
                        $class = 'incorrect'; // Highlight student's wrong answer
                    }
                ?>
                    <li class="<?php echo $class; ?>">
                        <?php echo htmlspecialchars($opt_text); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
