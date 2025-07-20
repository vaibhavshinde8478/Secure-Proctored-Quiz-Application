<?php
// File: C:\xampp\htdocs\quiz_platform\view_group.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security checks
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor') {
    header("location: login.php");
    exit;
}
if (!isset($_GET['group_id']) || empty($_GET['group_id'])) {
    header("location: manage_groups.php");
    exit;
}

require_once 'includes/db_connect.php';

$group_id = $_GET['group_id'];
$instructor_id = $_SESSION['user_id'];
$group_name = '';
$errors = [];
$success_message = '';

// Verify group belongs to the logged-in instructor
$sql_verify = "SELECT group_name FROM `groups` WHERE id = ? AND instructor_id = ?";
if ($stmt_verify = $conn->prepare($sql_verify)) {
    $stmt_verify->bind_param("ii", $group_id, $instructor_id);
    $stmt_verify->execute();
    $stmt_verify->bind_result($g_name);
    if (!$stmt_verify->fetch()) {
        header("location: manage_groups.php"); // Not authorized
        exit;
    }
    $group_name = $g_name;
    $stmt_verify->close();
}

// Handle adding a student to the group
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student'])) {
    $student_id = trim($_POST['student_id']);
    if (!empty($student_id)) {
        // Check if student exists and is not already in the group
        $sql_add = "INSERT INTO group_members (group_id, student_id) VALUES (?, ?)";
        if ($stmt_add = $conn->prepare($sql_add)) {
            $stmt_add->bind_param("ii", $group_id, $student_id);
            if ($stmt_add->execute()) {
                $success_message = "Student added to the group.";
            } else {
                // Error could be due to duplicate entry (student already in group) or other issues
                $errors[] = "Could not add student. They may already be in the group.";
            }
            $stmt_add->close();
        }
    }
}

// Handle removing a student from the group
if (isset($_GET['remove_student_id'])) {
    $student_to_remove = $_GET['remove_student_id'];
    $sql_remove = "DELETE FROM group_members WHERE group_id = ? AND student_id = ?";
    if ($stmt_remove = $conn->prepare($sql_remove)) {
        $stmt_remove->bind_param("ii", $group_id, $student_to_remove);
        $stmt_remove->execute();
        $stmt_remove->close();
        $success_message = "Student removed from the group.";
    }
}

// Fetch list of students in this group
$members = [];
$sql_members = "SELECT u.id, u.username, u.email FROM users u JOIN group_members gm ON u.id = gm.student_id WHERE gm.group_id = ?";
if ($stmt_members = $conn->prepare($sql_members)) {
    $stmt_members->bind_param("i", $group_id);
    $stmt_members->execute();
    $members = $stmt_members->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_members->close();
}

// Fetch list of all students NOT in this group to populate the dropdown
$all_students = [];
$sql_all_students = "SELECT id, username, email FROM users WHERE role = 'student' AND id NOT IN (SELECT student_id FROM group_members WHERE group_id = ?)";
if ($stmt_all = $conn->prepare($sql_all_students)) {
    $stmt_all->bind_param("i", $group_id);
    $stmt_all->execute();
    $all_students = $stmt_all->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_all->close();
}


$conn->close();
include 'includes/header.php';
?>

<div class="page-header">
    <h2>Manage Group: "<?php echo htmlspecialchars($group_name); ?>"</h2>
    <a href="manage_groups.php" class="btn">Back to All Groups</a>
</div>

<div class="group-management-container">
    <!-- Add Student Form -->
    <div class="form-container">
        <h3>Add Student to Group</h3>
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
        <form action="view_group.php?group_id=<?php echo $group_id; ?>" method="post">
            <div class="form-group">
                <label for="student_id">Select Student</label>
                <select name="student_id" id="student_id" class="form-control" required>
                    <option value="">-- Choose a student --</option>
                    <?php foreach ($all_students as $student): ?>
                        <option value="<?php echo $student['id']; ?>">
                            <?php echo htmlspecialchars($student['username']) . ' (' . htmlspecialchars($student['email']) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" name="add_student" class="btn full-width" value="Add Student">
            </div>
        </form>
    </div>

    <!-- List of Group Members -->
    <div class="existing-groups-container">
        <h3>Group Members (<?php echo count($members); ?>)</h3>
        <?php if (empty($members)): ?>
            <p>This group has no members yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($member['username']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td class="actions">
                                <a href="view_group.php?group_id=<?php echo $group_id; ?>&remove_student_id=<?php echo $member['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to remove this student from the group?');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
