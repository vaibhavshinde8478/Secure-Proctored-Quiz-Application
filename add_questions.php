<?php
// File: C:\xampp\htdocs\quiz_platform\add_questions.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: ensure user is logged in and is an instructor.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor') {
    header("location: login.php");
    exit;
}

// Check if quiz_id is present in the URL
if (!isset($_GET['quiz_id']) || empty($_GET['quiz_id'])) {
    header("location: my_quizzes.php");
    exit;
}

require_once 'includes/db_connect.php';

$quiz_id = $_GET['quiz_id'];
$quiz_title = '';
$errors = [];
$success_message = '';

// Fetch quiz details to display title and verify ownership
$sql_quiz = "SELECT title FROM quizzes WHERE id = ? AND instructor_id = ?";
if($stmt_quiz = $conn->prepare($sql_quiz)){
    $stmt_quiz->bind_param("ii", $quiz_id, $_SESSION['user_id']);
    $stmt_quiz->execute();
    $stmt_quiz->store_result();
    
    if($stmt_quiz->num_rows == 1){
        $stmt_quiz->bind_result($title);
        $stmt_quiz->fetch();
        $quiz_title = $title;
    } else {
        // Quiz not found or doesn't belong to this instructor
        header("location: my_quizzes.php");
        exit;
    }
    $stmt_quiz->close();
}


// Handle form submission for adding a new question
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['question_text'])) {
    $question_text = trim($_POST['question_text']);
    $question_type = $_POST['question_type'];
    
    if (empty($question_text)) {
        $errors[] = "Question text cannot be empty.";
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Insert the question
            $sql_question = "INSERT INTO questions (quiz_id, question_text, question_type) VALUES (?, ?, ?)";
            $stmt_question = $conn->prepare($sql_question);
            $stmt_question->bind_param("iss", $quiz_id, $question_text, $question_type);
            $stmt_question->execute();
            $question_id = $conn->insert_id;
            $stmt_question->close();

            // Insert the options for multiple choice questions
            if ($question_type == 'multiple_choice' || $question_type == 'true_false') {
                $options = $_POST['options'];
                $correct_option_index = $_POST['is_correct'];

                $sql_option = "INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)";
                $stmt_option = $conn->prepare($sql_option);

                foreach ($options as $index => $option_text) {
                    $trimmed_option_text = trim($option_text);
                    if (!empty($trimmed_option_text)) {
                        $is_correct = ($index == $correct_option_index) ? 1 : 0;
                        $stmt_option->bind_param("isi", $question_id, $trimmed_option_text, $is_correct);
                        $stmt_option->execute();
                    }
                }
                $stmt_option->close();
            }
            
            // Commit transaction
            $conn->commit();
            $success_message = "Question added successfully!";

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Failed to add question. Error: " . $e->getMessage();
        }
    }
}

// Fetch existing questions for this quiz
$questions = [];
$sql_get_q = "SELECT id, question_text FROM questions WHERE quiz_id = ?";
if($stmt_get_q = $conn->prepare($sql_get_q)){
    $stmt_get_q->bind_param("i", $quiz_id);
    $stmt_get_q->execute();
    $result = $stmt_get_q->get_result();
    $questions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_get_q->close();
}


include 'includes/header.php';
?>

<div class="page-header">
    <h2>Manage Questions for "<?php echo htmlspecialchars($quiz_title); ?>"</h2>
    <a href="my_quizzes.php" class="btn">Back to My Quizzes</a>
</div>

<div class="form-container wide">
    <h3>Add a New Question</h3>

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

    <form action="add_questions.php?quiz_id=<?php echo $quiz_id; ?>" method="post" id="addQuestionForm">
        <div class="form-group">
            <label for="question_text">Question Text</label>
            <textarea name="question_text" id="question_text" class="form-control" rows="3" required></textarea>
        </div>
        <div class="form-group">
            <label for="question_type">Question Type</label>
            <select name="question_type" id="question_type" class="form-control">
                <option value="multiple_choice">Multiple Choice</option>
                <option value="true_false">True / False</option>
            </select>
        </div>
        
        <div id="options-container" class="form-group">
            <label>Options (select the correct answer)</label>
            <!-- Options will be dynamically inserted here by JavaScript -->
        </div>

        <div class="form-group">
            <input type="submit" class="btn btn-success" value="Add Question">
        </div>
    </form>
</div>

<div class="existing-questions">
    <h3>Existing Questions</h3>
    <?php if(empty($questions)): ?>
        <p>No questions have been added to this quiz yet.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Question</th>
                    <th style="width: 150px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($questions as $question): ?>
                <tr>
                    <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                    <td class="actions">
                        <a href="edit_question.php?question_id=<?php echo $question['id']; ?>">Edit</a>
                        <a href="delete_question.php?question_id=<?php echo $question['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this question?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const questionTypeSelect = document.getElementById('question_type');
    const optionsContainer = document.getElementById('options-container');

    function renderOptions() {
        const type = questionTypeSelect.value;
        let containerContent = optionsContainer.querySelector('.options-wrapper');
        if (!containerContent) {
            containerContent = document.createElement('div');
            containerContent.className = 'options-wrapper';
            optionsContainer.appendChild(containerContent);
        }
        containerContent.innerHTML = ''; // Clear previous options

        if (type === 'multiple_choice') {
            optionsContainer.style.display = 'block';
            for (let i = 0; i < 4; i++) {
                const optionGroup = document.createElement('div');
                optionGroup.className = 'option-group';
                optionGroup.innerHTML = `
                    <input type="radio" name="is_correct" value="${i}" ${i === 0 ? 'checked' : ''} required>
                    <input type="text" name="options[]" class="form-control" placeholder="Option ${i + 1}" required>
                `;
                containerContent.appendChild(optionGroup);
            }
        } else if (type === 'true_false') {
            optionsContainer.style.display = 'block';
            const trueFalseOptions = ['True', 'False'];
            trueFalseOptions.forEach((text, i) => {
                 const optionGroup = document.createElement('div');
                optionGroup.className = 'option-group';
                optionGroup.innerHTML = `
                    <input type="radio" name="is_correct" value="${i}" ${i === 0 ? 'checked' : ''} required>
                    <input type="hidden" name="options[]" value="${text}">
                    <label style="margin-left: 10px; font-weight: normal;">${text}</label>
                `;
                containerContent.appendChild(optionGroup);
            });
        } else {
            optionsContainer.style.display = 'none';
        }
    }

    // Initial render
    renderOptions();

    // Re-render when type changes
    questionTypeSelect.addEventListener('change', renderOptions);
});
</script>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
