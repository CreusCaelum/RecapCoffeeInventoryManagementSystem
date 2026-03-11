<?php
include "config/db.php";

// CHANGE THESE VALUES
$username = "owner";
$password = "owner123"; // plain password
$full_name = "Cafe Owner";
$email = "owner@cafe.com";
$role = "owner";

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("
    INSERT INTO users (username, password, full_name, email, role)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "sssss",
    $username,
    $hashed_password,
    $full_name,
    $email,
    $role
);

if ($stmt->execute()) {
    echo "✅ User created successfully.<br>";
    echo "Username: $username <br>";
    echo "Password: $password";
} else {
    echo "❌ Error: " . $stmt->error;
}
