<?php
include "config/db.php";
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];

    // Get current_stock from items
    $item = $conn->prepare("SELECT current_stock FROM items WHERE id = ?");
    $item->bind_param("i", $item_id);
    $item->execute();
    $result = $item->get_result();
    if ($result->num_rows === 0) {
        die("Invalid item ID.");
    }
    $current_stock = $result->fetch_assoc()['current_stock'];

    // Calculate suggested_qty using the function
    $suggested_qty = getReorderSuggestion($conn, $item_id, $current_stock);

    // Hardcode reorder_level (e.g., 10); adjust if you have it per item
    $reorder_level = 10;

    // Insert into reorder_suggestions
    $insert = $conn->prepare("INSERT INTO reorder_suggestions (item_id, current_stock, reorder_level, suggested_qty, status) VALUES (?, ?, ?, ?, 'pending')");
    $insert->bind_param("iiis", $item_id, $current_stock, $reorder_level, $suggested_qty);
    $insert->execute();

    // Redirect back to dashboard
    $role = $_SESSION['role'];
    if ($role === 'staff') {
        header("Location: staff_dashboard.php");
    } elseif ($role === 'owner') {
        header("Location: owner_dashboard.php");
    }
    exit();
}

// Define the function here too (or include from dashboard.php)
function getReorderSuggestion($conn, $item_id, $current_stock) {
    $usage = $conn->query("
        SELECT IFNULL(SUM(quantity),0) / 30 AS avg_daily
        FROM stock_transactions
        WHERE item_id = $item_id
        AND transaction_type = 'usage'
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ")->fetch_assoc();

    $avgDailyUsage = $usage['avg_daily'];
    $leadTimeDays = 7;
    $suggestedQty = ($avgDailyUsage * $leadTimeDays) - $current_stock;
    return max(0, ceil($suggestedQty));
}