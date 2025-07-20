<?php
// File: C:\xampp\htdocs\quiz_platform\create_quiz.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor') {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

$instructor_id = $_SESSION['user_id'];
$title = $description = $start_time = $end_time = $duration_minutes = "";
$assigned_groups = [];
$errors = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $duration_minutes = trim($_POST['duration_minutes']);
    $assigned_groups = isset($_POST['assigned_groups']) ? $_POST['assigned_groups'] : [];

    if (empty($title)) {
        $errors[] = "Quiz title is required.";
    }
    if (empty($start_time) || empty($end_time) || empty($duration_minutes)) {
        $errors[] = "Start time, end time, and duration are required.";
    }
    if (empty($assigned_groups)) {
        $errors[] = "You must assign the quiz to at least one group.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();
        try {
            // Step 1: Insert the quiz into the quizzes table
            $sql_quiz = "INSERT INTO quizzes (title, description, instructor_id, start_time, end_time, duration_minutes) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_quiz = $conn->prepare($sql_quiz);
            $stmt_quiz->bind_param("ssissi", $title, $description, $instructor_id, $start_time, $end_time, $duration_minutes);
            $stmt_quiz->execute();
            $quiz_id = $conn->insert_id;
            $stmt_quiz->close();

            // Step 2: Insert assignments into the quiz_assignments table
            $sql_assign = "INSERT INTO quiz_assignments (quiz_id, group_id) VALUES (?, ?)";
            $stmt_assign = $conn->prepare($sql_assign);
            foreach ($assigned_groups as $group_id) {
                $stmt_assign->bind_param("ii", $quiz_id, $group_id);
                $stmt_assign->execute();
            }
            $stmt_assign->close();

            $conn->commit();
            header("location: add_questions.php?quiz_id=" . $quiz_id);
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Something went wrong. Please try again. Error: " . $e->getMessage();
        }
    }
}

// Fetch instructor's groups to populate the select box
$groups = [];
$sql_groups = "SELECT id, group_name FROM `groups` WHERE instructor_id = ?";
if ($stmt_groups = $conn->prepare($sql_groups)) {
    $stmt_groups->bind_param("i", $instructor_id);
    $stmt_groups->execute();
    $result = $stmt_groups->get_result();
    $groups = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_groups->close();
}
$conn->close();

include 'includes/header.php';
?>

<div class="form-container wide">
    <h2>Create a New Quiz</h2>
    <p>Enter the details for your new quiz and assign it to the relevant student groups.</p>
    
    <?php
    if (!empty($errors)) {
        echo '<div class="message error">';
        foreach ($errors as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
    }
    ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label>Quiz Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
        </div>
        <div class="form-group">
            <label>Description (Optional)</label>
            <textarea name="description" class="form-control"><?php echo htmlspecialchars($description); ?></textarea>
        </div>
        <div class="form-group">
            <label>Start Time</label>
            <input type="datetime-local" name="start_time" class="form-control" value="<?php echo htmlspecialchars($start_time); ?>" required>
        </div>
        <div class="form-group">
            <label>End Time</label>
            <input type="datetime-local" name="end_time" class="form-control" value="<?php echo htmlspecialchars($end_time); ?>" required>
        </div>
        <div class="form-group">
            <label>Duration (in minutes)</label>
            <input type="number" name="duration_minutes" class="form-control" value="<?php echo htmlspecialchars($duration_minutes); ?>" min="1" required>
        </div>
        <div class="form-group">
            <label>Assign to Groups (Hold Ctrl or Cmd to select multiple)</label>
            <select name="assigned_groups[]" class="form-control" multiple required size="5">
                <?php foreach ($groups as $group): ?>
                    <option value="<?php echo $group['id']; ?>" <?php if (in_array($group['id'], $assigned_groups)) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($group['group_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <input type="submit" class="btn full-width" value="Create Quiz and Add Questions">
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
