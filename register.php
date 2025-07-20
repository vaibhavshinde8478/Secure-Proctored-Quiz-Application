<?php
// File: C:\xampp\htdocs\quiz_platform\register.php

// This script handles user registration.
require_once 'includes/db_connect.php';

$username = $email = $password = $role = "";
$errors = [];
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Input Validation and Sanitization ---
    if (empty(trim($_POST["username"]))) {
        $errors[] = "Username is required.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["email"]))) {
        $errors[] = "Email is required.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        $email = trim($_POST["email"]);
    }

    if (empty(trim($_POST["password"]))) {
        $errors[] = "Password is required.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $errors[] = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($_POST["role"])) {
        $errors[] = "Please select a role (Student or Instructor).";
    } else {
        $role = $_POST["role"];
    }

    // --- Database Checks ---
    if (empty($errors)) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $param_username, $param_email);
            $param_username = $username;
            $param_email = $email;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors[] = "This username or email is already taken.";
                }
            } else {
                $errors[] = "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }

    // --- Insert New User ---
    if (empty($errors)) {
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $param_username, $param_email, $param_password, $param_role);
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT);
            $param_role = $role;

            if ($stmt->execute()) {
                $success_message = "Registration successful! You can now log in.";
                $username = $email = $password = $role = "";
            } else {
                $errors[] = "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Create Account</h2>
    <p>Please fill this form to create an account.</p>

    <?php
    if (!empty($errors)) {
        echo '<div class="message error">';
        foreach ($errors as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
    }
    if (!empty($success_message)) {
        echo '<div class="message success"><p>' . htmlspecialchars($success_message) . '</p></div>';
    }
    ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="form-group">
            <label>I am a:</label>
            <select name="role" class="form-control">
                <option value="">--Select Role--</option>
                <option value="student" <?php if ($role == "student") echo "selected"; ?>>Student</option>
                <option value="instructor" <?php if ($role == "instructor") echo "selected"; ?>>Instructor</option>
            </select>
        </div>
        <div class="form-group">
            <input type="submit" class="btn full-width" value="Register">
        </div>
        <p>Already have an account? <a href="login.php">Login here</a>.</p>
    </form>
</div>

<?php
include 'includes/footer.php';
?>