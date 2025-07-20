<?php
// File: C:\xampp\htdocs\quiz_platform\manage_groups.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: ensure user is logged in and is an instructor.
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'instructor') {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

$instructor_id = $_SESSION['user_id'];
$group_name = "";
$errors = [];
$success_message = "";

// Handle form submission for creating a new group
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_group'])) {
    $group_name = trim($_POST['group_name']);
    if (empty($group_name)) {
        $errors[] = "Group name cannot be empty.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO `groups` (group_name, instructor_id) VALUES (?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("si", $group_name, $instructor_id);
            if ($stmt->execute()) {
                $success_message = "Group '" . htmlspecialchars($group_name) . "' created successfully!";
                $group_name = ""; // Clear the form
            } else {
                $errors[] = "Failed to create group. Please try again.";
            }
            $stmt->close();
        }
    }
}

// Fetch all groups created by this instructor
$groups = [];
$sql_fetch = "SELECT id, group_name, created_at, (SELECT COUNT(*) FROM group_members WHERE group_id = groups.id) as member_count FROM `groups` WHERE instructor_id = ? ORDER BY created_at DESC";
if ($stmt_fetch = $conn->prepare($sql_fetch)) {
    $stmt_fetch->bind_param("i", $instructor_id);
    $stmt_fetch->execute();
    $result = $stmt_fetch->get_result();
    $groups = $result->fetch_all(MYSQLI_ASSOC);
    $stmt_fetch->close();
}
$conn->close();

include 'includes/header.php';
?>

<div class="page-header">
    <h2>Manage Student Groups</h2>
</div>

<div class="group-management-container">
    <!-- Create Group Form -->
    <div class="form-container">
        <h3>Create a New Group</h3>
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
        <form action="manage_groups.php" method="post">
            <div class="form-group">
                <label for="group_name">Group Name (e.g., "11th Grade - Section A")</label>
                <input type="text" name="group_name" id="group_name" class="form-control" value="<?php echo htmlspecialchars($group_name); ?>" required>
            </div>
            <div class="form-group">
                <input type="submit" name="create_group" class="btn full-width" value="Create Group">
            </div>
        </form>
    </div>

    <!-- List of Existing Groups -->
    <div class="existing-groups-container">
        <h3>My Groups</h3>
        <?php if (empty($groups)): ?>
            <p>You have not created any groups yet.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Group Name</th>
                        <th>Members</th>
                        <th>Created On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($group['group_name']); ?></td>
                            <td><?php echo $group['member_count']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($group['created_at'])); ?></td>
                            <td class="actions">
                                <a href="view_group.php?group_id=<?php echo $group['id']; ?>">View / Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
