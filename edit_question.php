<?php
// File: C:\xampp\htdocs\quiz_platform\edit_question.php

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
$question = null;
$options = [];
$quiz_id = null;
$errors = [];
$success_message = '';

// Verify question belongs to the logged-in instructor
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

// Handle form submission for updating the question
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question_text = trim($_POST['question_text']);
    if (empty($question_text)) {
        $errors[] = "Question text cannot be empty.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Update question text
            $sql_update_q = "UPDATE questions SET question_text = ? WHERE id = ?";
            $stmt_update_q = $conn->prepare($sql_update_q);
            $stmt_update_q->bind_param("si", $question_text, $question_id);
            $stmt_update_q->execute();
            $stmt_update_q->close();

            // Update options
            $posted_options = $_POST['options'];
            $correct_option_id = $_POST['is_correct'];

            $sql_update_o = "UPDATE options SET option_text = ?, is_correct = ? WHERE id = ?";
            $stmt_update_o = $conn->prepare($sql_update_o);
            foreach($posted_options as $option_id => $option_text){
                $is_correct = ($option_id == $correct_option_id) ? 1 : 0;
                $stmt_update_o->bind_param("sii", trim($option_text), $is_correct, $option_id);
                $stmt_update_o->execute();
            }
            $stmt_update_o->close();

            $conn->commit();
            $success_message = "Question updated successfully!";

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Failed to update question. Error: " . $e->getMessage();
        }
    }
}

// Fetch current question and options data for the form
$sql_q = "SELECT question_text, question_type FROM questions WHERE id = ?";
if($stmt_q = $conn->prepare($sql_q)){
    $stmt_q->bind_param("i", $question_id);
    $stmt_q->execute();
    $question = $stmt_q->get_result()->fetch_assoc();
    $stmt_q->close();
}

$sql_o = "SELECT id, option_text, is_correct FROM options WHERE question_id = ?";
if($stmt_o = $conn->prepare($sql_o)){
    $stmt_o->bind_param("i", $question_id);
    $stmt_o->execute();
    $options = $stmt_o->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_o->close();
}

include 'includes/header.php';
?>

<div class="page-header">
    <h2>Edit Question</h2>
    <a href="add_questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn">Back to Manage Questions</a>
</div>

<div class="form-container wide">
    <?php
    if (!empty($errors)) {
        echo '<div class="message error">';
        foreach ($errors as $error) { echo '<p>' . htmlspecialchars($error) . '</p>'; }
        echo '</div>';
    }
    if (!empty($success_message)) {
        echo '<div class="message success"><p>' . htmlspecialchars($success_message) . '</p></div>';
    }
    ?>

    <form action="edit_question.php?question_id=<?php echo $question_id; ?>" method="post">
        <div class="form-group">
            <label>Question Text</label>
            <textarea name="question_text" class="form-control" rows="3" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Options (select the correct answer)</label>
            <div class="options-container">
                <?php foreach($options as $option): ?>
                <div class="option-group">
                    <input type="radio" name="is_correct" value="<?php echo $option['id']; ?>" <?php if($option['is_correct']) echo 'checked'; ?> required>
                    <?php if($question['question_type'] == 'true_false'): ?>
                        <label style="margin-left: 10px; font-weight: normal;"><?php echo htmlspecialchars($option['option_text']); ?></label>
                        <input type="hidden" name="options[<?php echo $option['id']; ?>]" value="<?php echo htmlspecialchars($option['option_text']); ?>">
                    <?php else: ?>
                        <input type="text" name="options[<?php echo $option['id']; ?>]" class="form-control" value="<?php echo htmlspecialchars($option['option_text']); ?>" required>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group">
            <input type="submit" class="btn btn-success" value="Update Question">
        </div>
    </form>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
