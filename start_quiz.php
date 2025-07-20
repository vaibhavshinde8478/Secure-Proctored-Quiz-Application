<?php
// File: C:\xampp\htdocs\quiz_platform\start_quiz.php

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
$quiz_title = '';

// Fetch quiz title
$sql_quiz = "SELECT title FROM quizzes WHERE id = ?";
if ($stmt_quiz = $conn->prepare($sql_quiz)) {
    $stmt_quiz->bind_param("i", $quiz_id);
    $stmt_quiz->execute();
    $stmt_quiz->bind_result($title);
    $stmt_quiz->fetch();
    $quiz_title = $title;
    $stmt_quiz->close();
}
$conn->close();

include 'includes/header.php';
?>

<div class="page-header">
    <h2>Pre-Quiz System Check for "<?php echo htmlspecialchars($quiz_title); ?>"</h2>
</div>

<div class="proctoring-container">
    <h3>Camera & Microphone Access Required</h3>
    <p>This is a proctored quiz. You must grant access to your camera and microphone to proceed. Please ensure you are in a quiet, well-lit room.</p>
    
    <div class="video-container">
        <video id="video-preview" autoplay muted playsinline></video>
    </div>

    <div id="permission-status">
        <p class="status-text">Please click "Allow" when your browser asks for permission.</p>
    </div>
    
    <a href="take_quiz.php?quiz_id=<?php echo $quiz_id; ?>" id="start-quiz-btn" class="btn" style="display: none;">Proceed to Quiz</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const videoElement = document.getElementById('video-preview');
    const startQuizBtn = document.getElementById('start-quiz-btn');
    const permissionStatus = document.getElementById('permission-status');

    async function startCamera() {
        try {
            // Request access to both video and audio
            const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            
            // If successful, display the stream in the video element
            videoElement.srcObject = stream;
            
            // Show the "Proceed to Quiz" button and hide the status message
            startQuizBtn.style.display = 'inline-block';
            permissionStatus.innerHTML = '<p class="message success">Camera and microphone access granted. You may now proceed.</p>';

        } catch (error) {
            // If the user denies permission or an error occurs
            console.error("Error accessing media devices.", error);
            let errorMessage = 'An error occurred while accessing your camera/microphone.';
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                errorMessage = 'You have denied access to the camera and microphone. You cannot proceed with the quiz without granting permission.';
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                 errorMessage = 'No camera or microphone was found on your device. Please connect them and try again.';
            }
            
            permissionStatus.innerHTML = `<div class="message error"><p>${errorMessage}</p><p>Please check your browser settings to allow access.</p></div>`;
            startQuizBtn.style.display = 'none'; // Ensure button is hidden
        }
    }

    // Start the process as soon as the page loads
    startCamera();
});
</script>

<?php include 'includes/footer.php'; ?>
