<?php
// File: C:\xampp\htdocs\quiz_platform\profile.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Security check: user must be logged in to access this page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$username = $email = "";
$errors = [];
$success_message = "";
$password_errors = [];
$password_success = "";

// --- Handle Profile Information Update ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    // Validate and sanitize new username
    $new_username = trim($_POST['username']);
    if (empty($new_username)) {
        $errors[] = "Username cannot be empty.";
    }

    // Validate and sanitize new email
    $new_email = trim($_POST['email']);
    if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Check if the new username or email is already taken by ANOTHER user
    if (empty($errors)) {
        $sql_check = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        if ($stmt_check = $conn->prepare($sql_check)) {
            $stmt_check->bind_param("ssi", $new_username, $new_email, $user_id);
            $stmt_check->execute();
            $stmt_check->store_result();
            if ($stmt_check->num_rows > 0) {
                $errors[] = "The new username or email is already taken by another account.";
            }
            $stmt_check->close();
        }
    }

    // If no errors, update the user's profile
    if (empty($errors)) {
        $sql_update = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        if ($stmt_update = $conn->prepare($sql_update)) {
            $stmt_update->bind_param("ssi", $new_username, $new_email, $user_id);
            if ($stmt_update->execute()) {
                // Update session variables as well
                $_SESSION['username'] = $new_username;
                $success_message = "Profile updated successfully!";
            } else {
                $errors[] = "Something went wrong. Please try again.";
            }
            $stmt_update->close();
        }
    }
}

// --- Handle Password Change ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $password_errors[] = "Please fill in all password fields.";
    } elseif ($new_password !== $confirm_password) {
        $password_errors[] = "New password and confirmation do not match.";
    } elseif (strlen($new_password) < 6) {
        $password_errors[] = "New password must be at least 6 characters long.";
    }

    if (empty($password_errors)) {
        // First, retrieve the current hashed password from the database
        $sql_pass = "SELECT password FROM users WHERE id = ?";
        if ($stmt_pass = $conn->prepare($sql_pass)) {
            $stmt_pass->bind_param("i", $user_id);
            $stmt_pass->execute();
            $stmt_pass->bind_result($hashed_password);
            if ($stmt_pass->fetch()) {
                // Verify the current password
                if (password_verify($current_password, $hashed_password)) {
                    // Current password is correct, now hash and update the new password
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_pass->close(); // Close the first statement before preparing a new one

                    $sql_update_pass = "UPDATE users SET password = ? WHERE id = ?";
                    if ($stmt_update_pass = $conn->prepare($sql_update_pass)) {
                        $stmt_update_pass->bind_param("si", $new_hashed_password, $user_id);
                        if ($stmt_update_pass->execute()) {
                            $password_success = "Password changed successfully!";
                        } else {
                            $password_errors[] = "Failed to update password. Please try again.";
                        }
                        $stmt_update_pass->close();
                    }
                } else {
                    $password_errors[] = "Incorrect current password.";
                }
            }
        }
    }
}


// Fetch current user data to display in the form
$sql_user = "SELECT username, email FROM users WHERE id = ?";
if ($stmt_user = $conn->prepare($sql_user)) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $stmt_user->bind_result($username, $email);
    $stmt_user->fetch();
    $stmt_user->close();
}

$conn->close();
include 'includes/header.php';
?>

<div class="page-header">
    <h2>My Profile</h2>
</div>

<div class="profile-container">
    <!-- Update Profile Form -->
    <div class="form-container">
        <h3>Update Profile Information</h3>
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
        <form action="profile.php" method="post">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <input type="submit" name="update_profile" class="btn full-width" value="Update Profile">
            </div>
        </form>
    </div>

    <!-- Change Password Form -->
    <div class="form-container">
        <h3>Change Password</h3>
        <?php
        if (!empty($password_errors)) {
            echo '<div class="message error">';
            foreach ($password_errors as $error) { echo '<p>' . htmlspecialchars($error) . '</p>'; }
            echo '</div>';
        }
        if (!empty($password_success)) {
            echo '<div class="message success"><p>' . htmlspecialchars($password_success) . '</p></div>';
        }
        ?>
        <form action="profile.php" method="post">
            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <div class="form-group">
                <input type="submit" name="change_password" class="btn full-width" value="Change Password">
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
