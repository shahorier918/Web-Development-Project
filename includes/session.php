<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isTeacher() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function requireTeacher() {
    requireLogin();
    if ($_SESSION['role'] !== 'teacher') {
        header("Location: ../login.php");
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if ($_SESSION['role'] !== 'student') {
        header("Location: ../login.php");
        exit();
    }
}

// Optional: Add a function to get current user's role
function getUserRole() {
    return $_SESSION['role'] ?? null;
}
?> 