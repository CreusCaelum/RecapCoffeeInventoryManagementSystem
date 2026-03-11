<?php
include "config/db.php";
include "config/functions.php";
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int)$_POST['item_id'];
    $code = $_POST['code'];
    $name = $_POST['name'];
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $unit = $_POST['unit'];
    $reorder_level = (float)$_POST['reorder_level'];
    
    $stmt = $conn->prepare("UPDATE items SET item_code=?, item_name=?, category_id=?, unit_of_measure=?, reorder_level=? WHERE id=?");
    $stmt->bind_param("ssisdi", $code, $name, $category_id, $unit, $reorder_level, $item_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    exit;
}