<?php
// File: C:\xampp\htdocs\quiz_platform\logout.php

// This script handles the user logout process.

// Initialize the session
session_start();

// Unset all of the session variables
$_SESSION = [];

// Destroy the session.
session_destroy();

// Redirect to login page
header("location: login.php");
exit;
?>