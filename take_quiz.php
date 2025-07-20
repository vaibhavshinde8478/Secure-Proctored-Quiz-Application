<?php
// File: C:\xampp\htdocs\quiz_platform\take_quiz.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'student') {
    header("location: login.php");
    exit;
}
if (!isset($_GET['quiz_id']) || empty($_GET['quiz_id'])) {
    header("location: available_quizzes.php");
    exit;
}

require_once 'includes/db_connect.php';

$quiz_id = $_GET['quiz_id'];
$student_id = $_SESSION['user_id'];
$quiz = null;
$questions = [];
$errors = [];

// --- Check if student has already taken this quiz ---
$sql_check = "SELECT id FROM submissions WHERE quiz_id = ? AND student_id = ?";
if($stmt_check = $conn->prepare($sql_check)){
    $stmt_check->bind_param("ii", $quiz_id, $student_id);
    $stmt_check->execute();
    $stmt_check->store_result();
    if($stmt_check->num_rows > 0){
        header("location: my_results.php?error=already_taken");
        exit;
    }
    $stmt_check->close();
}


// --- Handle Quiz Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $answers = isset($_POST['answers']) ? $_POST['answers'] : []; // Array of [question_id => selected_option_id]
    $score = 0;
    
    // Get all correct option IDs for this quiz for efficient checking
    $correct_options_sql = "SELECT o.question_id, o.id FROM options o JOIN questions q ON o.question_id = q.id WHERE q.quiz_id = ? AND o.is_correct = 1";
    $stmt_correct = $conn->prepare($correct_options_sql);
    $stmt_correct->bind_param("i", $quiz_id);
    $stmt_correct->execute();
    $result_correct = $stmt_correct->get_result();
    $correct_answers = $result_correct->fetch_all(MYSQLI_ASSOC);
    $stmt_correct->close();

    // Create a map of question_id => correct_option_id
    $correct_map = [];
    foreach($correct_answers as $row){
        $correct_map[$row['question_id']] = $row['id'];
    }

    // Calculate score
    foreach ($answers as $question_id => $selected_option_id) {
        if (isset($correct_map[$question_id]) && $correct_map[$question_id] == $selected_option_id) {
            $score++;
        }
    }

    // Save submission to the database
    $conn->begin_transaction();
    try {
        // Insert into submissions table
        $sql_submission = "INSERT INTO submissions (quiz_id, student_id, score) VALUES (?, ?, ?)";
        $stmt_submission = $conn->prepare($sql_submission);
        $stmt_submission->bind_param("iii", $quiz_id, $student_id, $score);
        $stmt_submission->execute();
        $submission_id = $conn->insert_id;
        $stmt_submission->close();

        // Insert each answer into answers table
        $sql_answer = "INSERT INTO answers (submission_id, question_id, selected_option_id) VALUES (?, ?, ?)";
        $stmt_answer = $conn->prepare($sql_answer);
        foreach ($answers as $question_id => $selected_option_id) {
            $stmt_answer->bind_param("iii", $submission_id, $question_id, $selected_option_id);
            $stmt_answer->execute();
        }
        $stmt_answer->close();

        $conn->commit();
        
        // Redirect to result page
        header("location: quiz_result.php?submission_id=" . $submission_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "Failed to submit quiz. Please try again. Error: " . $e->getMessage();
    }
}

// --- Fetch Quiz Data for Display ---
// Fetch quiz details including duration
$sql_quiz = "SELECT title, duration_minutes FROM quizzes WHERE id = ? AND NOW() BETWEEN start_time AND end_time";
if ($stmt_quiz = $conn->prepare($sql_quiz)) {
    $stmt_quiz->bind_param("i", $quiz_id);
    $stmt_quiz->execute();
    $quiz = $stmt_quiz->get_result()->fetch_assoc();
    $stmt_quiz->close();
}

if (!$quiz) {
    // Quiz not found or not currently active
    header("location: available_quizzes.php?error=not_available");
    exit;
}

// Fetch questions and their options
$sql_questions = "SELECT id, question_text FROM questions WHERE quiz_id = ?";
if ($stmt_q = $conn->prepare($sql_questions)) {
    $stmt_q->bind_param("i", $quiz_id);
    $stmt_q->execute();
    $result_q = $stmt_q->get_result();

    while ($question = $result_q->fetch_assoc()) {
        $sql_options = "SELECT id, option_text FROM options WHERE question_id = ?";
        $stmt_o = $conn->prepare($sql_options);
        $stmt_o->bind_param("i", $question['id']);
        $stmt_o->execute();
        $options = $stmt_o->get_result()->fetch_all(MYSQLI_ASSOC);
        $question['options'] = $options;
        $questions[] = $question;
        $stmt_o->close();
    }
    $stmt_q->close();
}
$conn->close();

include 'includes/header.php';
?>

<!-- Face Detection Library -->
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>

<div class="proctoring-sidebar">
    <div id="quiz-timer" class="quiz-timer"></div>
    <div class="proctoring-feed">
        <video id="proctoring-video" autoplay muted playsinline></video>
        <div id="proctoring-status" class="status-box">Initializing Proctoring...</div>
    </div>
</div>

<div id="warning-overlay" class="warning-overlay">
    <div class="warning-box">
        <h2>Warning!</h2>
        <p>You have switched tabs or windows. Switching again will result in the automatic submission of your quiz.</p>
        <button id="close-warning">I Understand</button>
    </div>
</div>


<div class="page-header">
    <h2>Taking Quiz: <?php echo htmlspecialchars($quiz['title']); ?></h2>
</div>

<?php
if (!empty($errors)) {
    echo '<div class="message error">';
    foreach ($errors as $error) { echo '<p>' . htmlspecialchars($error) . '</p>'; }
    echo '</div>';
}
?>

<form id="quizForm" action="take_quiz.php?quiz_id=<?php echo $quiz_id; ?>" method="post">
    <div class="quiz-container">
        <?php foreach ($questions as $index => $question): ?>
            <div class="question-block">
                <p class="question-text"><?php echo ($index + 1) . ". " . htmlspecialchars($question['question_text']); ?></p>
                <ul class="options-list">
                    <?php foreach ($question['options'] as $option): ?>
                        <li>
                            <label>
                                <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $option['id']; ?>" required>
                                <span><?php echo htmlspecialchars($option['option_text']); ?></span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <div class="form-group">
            <input type="submit" class="btn btn-success full-width" value="Submit Quiz">
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const duration = <?php echo $quiz['duration_minutes']; ?>;
    const timerElement = document.getElementById('quiz-timer');
    const quizForm = document.getElementById('quizForm');
    const warningOverlay = document.getElementById('warning-overlay');
    const closeWarningBtn = document.getElementById('close-warning');
    const video = document.getElementById('proctoring-video');
    const proctoringStatus = document.getElementById('proctoring-status');

    let isQuizActive = true;
    let timerInterval;

    // --- Proctoring & Face Detection Logic ---
    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js@master/weights';
    let noFaceCounter = 0;
    const noFaceThreshold = 30; // Auto-submit after 10 seconds of no face

    async function startProctoring() {
        try {
            proctoringStatus.textContent = 'Loading models...';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL)
            ]);

            proctoringStatus.textContent = 'Starting camera...';
            const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
            video.srcObject = stream;

            video.addEventListener('play', () => {
                proctoringStatus.textContent = 'Proctoring Active';
                startTimer(); // Start the quiz timer only after proctoring is active

                setInterval(async () => {
                    if (!isQuizActive) return;

                    const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions());
                    
                    if (detections.length > 0) {
                        proctoringStatus.textContent = 'Face Detected';
                        proctoringStatus.className = 'status-box success';
                        noFaceCounter = 0; // Reset counter
                    } else {
                        proctoringStatus.textContent = 'No Face Detected!';
                        proctoringStatus.className = 'status-box error';
                        noFaceCounter++;
                    }

                    if (noFaceCounter > noFaceThreshold) {
                        isQuizActive = false;
                        alert('Quiz submitted automatically due to face not being visible.');
                        quizForm.submit();
                    }
                }, 1000);
            });

        } catch (error) {
            proctoringStatus.textContent = 'Proctoring Failed!';
            proctoringStatus.className = 'status-box error';
            alert('Could not start proctoring. Please ensure you have a webcam and have granted permission. The quiz cannot start.');
            window.location.href = 'available_quizzes.php';
        }
    }

    // --- Timer Logic ---
    function startTimer() {
        let timeRemaining = duration * 60;
        timerInterval = setInterval(function() {
            timeRemaining--;
            const minutes = Math.floor(timeRemaining / 60);
            const seconds = timeRemaining % 60;
            timerElement.textContent = `Time Left: ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                alert('Time is up! Your quiz will be submitted automatically.');
                quizForm.submit();
            }
        }, 1000);
    }

    // --- Anti-Cheating Logic ---
    let hasBeenWarned = false;
    
    // 1. Prevent Copying Text
    document.addEventListener('contextmenu', event => event.preventDefault());
    document.body.style.userSelect = 'none';

    // 2. Detect Window/Tab Change
    window.addEventListener('blur', function() {
        if (isQuizActive && warningOverlay.style.display !== 'flex') {
            if (!hasBeenWarned) {
                warningOverlay.style.display = 'flex';
                hasBeenWarned = true;
            } else {
                isQuizActive = false; 
                quizForm.submit();
            }
        }
    });

    closeWarningBtn.addEventListener('click', () => warningOverlay.style.display = 'none');
    quizForm.addEventListener('submit', () => isQuizActive = false);

    // Initialize the proctoring system
    startProctoring();
});
</script>

<?php include 'includes/footer.php'; ?>
