<?php
include "../config/db.php";

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    header("Location: ../index.php?error=empty&username=" . urlencode($username));
    exit();
}

$query = $conn->prepare("
    SELECT id, username, password, role
    FROM users
    WHERE username = ? AND is_active = 1
    LIMIT 1
");
$query->bind_param("s", $username);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 1) {

    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $username;

        // Redirect based on role
        if ($user['role'] === 'owner') {
            header("Location: ../owner_dashboard.php");
        } else {
            header("Location: ../dashboard.php");
        }
        exit();
    }
}

header("Location: ../index.php?error=invalid&username=" . urlencode($username));
exit();