<?php

/* ==========================
   AUTH FUNCTIONS
========================== */

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
}

function isOwner() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

/* ==========================
   INVENTORY FUNCTIONS
========================== */

function getLowStockItems($conn) {
    return $conn->query("
        SELECT * FROM items
        WHERE current_stock <= reorder_level
          AND is_active = 1
    ");
}

function getAllItems($conn) {
    return $conn->query("
        SELECT items.*, categories.category_name
        FROM items
        LEFT JOIN categories ON items.category_id = categories.id
        WHERE items.is_active = 1
    ");
}

function addItem($conn, $code, $name, $stock, $unit) {
    $stmt = $conn->prepare("
        INSERT INTO items (item_code, item_name, current_stock, unit_of_measure)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("ssds", $code, $name, $stock, $unit);
    return $stmt->execute();
}

function updateStock($conn, $item_id, $qty, $type) {
    $sign = ($type === 'purchase') ? "+" : "-";
    return $conn->query("
        UPDATE items
        SET current_stock = current_stock $sign $qty
        WHERE id = $item_id
    ");
}

function addStockTransaction($conn, $type, $item_id, $qty, $user_id) {
    $stmt = $conn->prepare("
        INSERT INTO stock_transactions
        (transaction_type, item_id, quantity, performed_by)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("sidi", $type, $item_id, $qty, $user_id);
    return $stmt->execute();
}