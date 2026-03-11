<?php
ob_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

include "config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: index.php");
    exit();
}

// Initialize message variable
$message = null;

// Handle approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_suggestion'])) {

    $suggestion_id = (int)($_POST['suggestion_id'] ?? 0);

    if ($suggestion_id <= 0) {
        header("Location: owner_dashboard.php?err=invalid_suggestion");
        exit();
    }

    // Must have an owner logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    $owner_id = (int)$_SESSION['user_id'];

    $conn->begin_transaction();

    try {
        // Lock the suggestion row to prevent double-approve
        $s = $conn->prepare("
            SELECT rs.id, rs.item_id, rs.suggested_qty, rs.status, i.item_name, i.current_stock
            FROM reorder_suggestions rs
            JOIN items i ON rs.item_id = i.id
            WHERE rs.id = ?
            FOR UPDATE
        ");
        $s->bind_param("i", $suggestion_id);
        $s->execute();
        $suggestion = $s->get_result()->fetch_assoc();
        $s->close();

        if (!$suggestion) {
            throw new Exception("Suggestion not found.");
        }

        if ($suggestion['status'] !== 'pending') {
            throw new Exception("Suggestion is not pending (current status: {$suggestion['status']}).");
        }

        $item_id = (int)$suggestion['item_id'];
        $qty = (float)$suggestion['suggested_qty'];
        $current_stock = (float)$suggestion['current_stock'];

        if ($qty <= 0) {
            throw new Exception("Suggested quantity is 0. Nothing to purchase.");
        }

        // Get unit cost (optional)
        $c = $conn->prepare("SELECT unit_cost, unit_of_measure FROM items WHERE id = ?");
        $c->bind_param("i", $item_id);
        $c->execute();
        $itemRow = $c->get_result()->fetch_assoc();
        $c->close();

        $unit_cost = $itemRow ? (float)$itemRow['unit_cost'] : null;
        $unit_of_measure = $itemRow ? $itemRow['unit_of_measure'] : 'units';

        // Mark suggestion approved
        $updSug = $conn->prepare("UPDATE reorder_suggestions SET status = 'approved' WHERE id = ?");
        $updSug->bind_param("i", $suggestion_id);
        $updSug->execute();
        $updSug->close();

        // Insert approved purchase transaction
        $type = 'purchase';
        $status = 'approved';
        $notes = "Auto purchase from reorder suggestion #{$suggestion_id} for {$suggestion['item_name']}";
        $refNumber = "RS-{$suggestion_id}-" . date('Ymd');
        $refId = $suggestion_id;

        $t = $conn->prepare("
            INSERT INTO stock_transactions
            (transaction_type, item_id, quantity, unit_cost, reference_id, reference_number, notes, performed_by, approved_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$t) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // types: s i d d i s s i i s
        $t->bind_param(
            "siddissiis",
            $type,
            $item_id,
            $qty,
            $unit_cost,
            $refId,
            $refNumber,
            $notes,
            $owner_id,
            $owner_id,
            $status
        );

        if (!$t->execute()) {
            throw new Exception("Failed insert stock_transactions: " . $t->error);
        }
        $transaction_id = $t->insert_id;
        $t->close();

        // Update item stock
        $new_stock = $current_stock + $qty;
        $u = $conn->prepare("UPDATE items SET current_stock = current_stock + ? WHERE id = ?");
        $u->bind_param("di", $qty, $item_id);
        $u->execute();
        $u->close();

        $conn->commit();

        // Store success message in session for display after redirect
        $_SESSION['approval_message'] = "Suggestion #{$suggestion_id} for {$suggestion['item_name']} has been approved. {$qty} {$unit_of_measure} added to stock. Transaction ID: #{$transaction_id}";
        
        header("Location: owner_dashboard.php?approved=1");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: owner_dashboard.php?err=1");
        exit();
    }
}

// Handle reject suggestion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_suggestion'])) {

    $suggestion_id = (int)($_POST['suggestion_id'] ?? 0);

    if ($suggestion_id <= 0) {
        header("Location: owner_dashboard.php?err=invalid_suggestion");
        exit();
    }

    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }

    $owner_id = (int)$_SESSION['user_id'];

    try {
        // Get suggestion details for message
        $s = $conn->prepare("
            SELECT rs.id, i.item_name, rs.status 
            FROM reorder_suggestions rs
            JOIN items i ON rs.item_id = i.id
            WHERE rs.id = ?
        ");
        $s->bind_param("i", $suggestion_id);
        $s->execute();
        $suggestion = $s->get_result()->fetch_assoc();
        $s->close();

        if (!$suggestion) {
            throw new Exception("Suggestion not found.");
        }

        if ($suggestion['status'] !== 'pending') {
            throw new Exception("Suggestion is not pending.");
        }

        // Mark suggestion as rejected
        $updSug = $conn->prepare("UPDATE reorder_suggestions SET status = 'rejected' WHERE id = ?");
        $updSug->bind_param("i", $suggestion_id);
        $updSug->execute();
        $updSug->close();

        $_SESSION['approval_message'] = "Suggestion #{$suggestion_id} for {$suggestion['item_name']} has been rejected.";
        
        header("Location: owner_dashboard.php?rejected=1");
        exit();

    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: owner_dashboard.php?err=1");
        exit();
    }
}

// Get statistics for dashboard
$total_items = $conn->query("SELECT COUNT(*) as count FROM items")->fetch_assoc()['count'];
$low_stock_items = $conn->query("SELECT COUNT(*) as count FROM items WHERE current_stock <= reorder_level AND current_stock > 0")->fetch_assoc()['count'];
$out_of_stock = $conn->query("SELECT COUNT(*) as count FROM items WHERE current_stock = 0")->fetch_assoc()['count'];
$pending_suggestions = $conn->query("SELECT COUNT(*) as count FROM reorder_suggestions WHERE status = 'pending'")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

// Get total value of pending purchases
$pending_value = $conn->query("
    SELECT SUM(rs.suggested_qty * i.unit_cost) as total 
    FROM reorder_suggestions rs
    JOIN items i ON rs.item_id = i.id
    WHERE rs.status = 'pending'
")->fetch_assoc()['total'];

// Get success message from session if it exists
$approval_message = '';
if (isset($_SESSION['approval_message'])) {
    $approval_message = $_SESSION['approval_message'];
    unset($_SESSION['approval_message']);
}

// Get error message from session if it exists
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Handle URL parameters
if (isset($_GET['approved'])) {
    $approval_message = $approval_message ?: "Suggestion approved successfully! Purchase transaction created.";
}

if (isset($_GET['rejected'])) {
    $approval_message = $approval_message ?: "Suggestion rejected successfully.";
}

if (isset($_GET['err'])) {
    if ($_GET['err'] == 'invalid_suggestion') {
        $error_message = "Invalid suggestion ID.";
    } elseif ($_GET['err'] == '1') {
        $error_message = $error_message ?: "An error occurred while processing your request.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Inventory System</title>
    <style>
        :root {
            --bg1: #0f0f12;
            --bg2: #1b1b22;
            --card: rgba(255,255,255,0.10);
            --card2: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.18);
            --text: #f4f4f6;
            --muted: rgba(244,244,246,0.75);
            --muted2: rgba(244,244,246,0.60);
            --accent1: #c57a3a;
            --accent2: #7b4a2e;
            --accent3: #f2c089;
            --shadow: 0 24px 70px rgba(0,0,0,0.35);
            --shadow2: 0 12px 30px rgba(0,0,0,0.22);
            --ring: 0 0 0 4px rgba(197,122,58,0.18);
            
            /* Status colors */
            --success: #4caf50;
            --warning: #ff9800;
            --danger: #f44336;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        body {
            display: flex;
            color: var(--text);
            min-height: 100vh;
            background: radial-gradient(1200px 600px at 20% 10%, rgba(197,122,58,0.25), transparent 60%),
                        radial-gradient(900px 500px at 80% 20%, rgba(242,192,137,0.18), transparent 60%),
                        radial-gradient(900px 600px at 50% 100%, rgba(123,74,46,0.25), transparent 60%),
                        linear-gradient(135deg, var(--bg1), var(--bg2));
            position: relative;
        }

        .noise {
            position: fixed;
            inset: 0;
            pointer-events: none;
            z-index: 0;
            opacity: 0.18;
            mix-blend-mode: overlay;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)' opacity='.16'/%3E%3C/svg%3E");
        }

        /* Left Navigation Bar - Coffee Theme */
        .side-nav {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border-right: 1px solid var(--border);
            width: 240px;
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            box-shadow: 1px 0 20px rgba(0,0,0,0.20);
            z-index: 1000;
            backdrop-filter: blur(14px);
            overflow-y: auto;
        }

        .nav-brand {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            text-decoration: none;
            padding: 0 24px 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: .4px;
        }

        .nav-brand::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent1), var(--accent3));
            box-shadow: 0 10px 22px rgba(197,122,58,0.35);
            flex-shrink: 0;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
            padding: 0 16px;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .nav-item > a {
            color: rgba(244,244,246,0.82);
            text-decoration: none;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            border-radius: 12px;
            margin-bottom: 4px;
            background: rgba(0,0,0,0.16);
            border: 1px solid rgba(255,255,255,0.10);
        }

        .nav-item > a:hover {
            background: rgba(255,255,255,0.10);
            color: #fff;
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(0,0,0,0.20);
        }

        .nav-item.active > a {
            background: linear-gradient(135deg, rgba(242,192,137,0.35), rgba(197,122,58,0.30), rgba(123,74,46,0.25));
            color: #fff;
            border-color: rgba(242,192,137,0.25);
            box-shadow: 0 18px 36px rgba(197,122,58,0.18);
        }

        .nav-item.active > a::before {
            content: '';
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            height: 16px;
            width: 3px;
            background: linear-gradient(180deg, var(--accent3), var(--accent1));
            border-radius: 0 2px 2px 0;
        }

        .nav-actions {
            padding: 20px 24px;
            border-top: 1px solid var(--border);
            margin-top: auto;
        }

        .logout-btn {
            background: linear-gradient(135deg, var(--accent3), var(--accent1), var(--accent2));
            border: none;
            color: #1a1a1a;
            padding: 11px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
            width: 100%;
            box-shadow: 0 18px 35px rgba(197,122,58,0.20);
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 48px rgba(197,122,58,0.28);
            filter: brightness(1.03);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 40px;
            max-width: calc(100% - 240px);
            margin-left: 240px;
            position: relative;
            z-index: 1;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
            text-align: center;
            padding-top: 20px;
        }

        .page-header h1 {
            font-size: 36px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 12px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .page-header p {
            color: var(--muted2);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto 20px;
        }

        .owner-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(197,122,58,0.2);
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Alert Messages */
        .alert {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .alert.success {
            border-color: rgba(76,175,80,0.3);
            background: rgba(76,175,80,0.1);
        }

        .alert.error {
            border-color: rgba(244,67,54,0.3);
            background: rgba(244,67,54,0.1);
        }

        .alert-icon {
            font-size: 22px;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-title {
            font-weight: 800;
            color: var(--text);
            margin-bottom: 4px;
        }

        .alert-message {
            color: var(--muted);
            font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 28px 24px;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow2);
            transition: all 0.3s ease;
            text-align: center;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow);
            border-color: rgba(242,192,137,0.3);
        }

        .stat-card h3 {
            font-size: 13px;
            font-weight: 700;
            color: var(--muted2);
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 38px;
            font-weight: 900;
            color: var(--text);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-card .sub-value {
            font-size: 14px;
            color: var(--muted2);
            margin-top: 4px;
        }

        .stat-card .trend {
            font-size: 13px;
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 30px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
        }

        .stat-card .trend.positive {
            color: var(--success);
        }

        .stat-card .trend.negative {
            color: var(--danger);
        }

        .stat-card .trend.warning {
            color: var(--warning);
        }

        /* Dashboard Sections */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 32px;
            margin-bottom: 40px;
        }

        @media (max-width: 1100px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-section {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .section-header h2 {
            font-size: 20px;
            font-weight: 900;
            color: var(--text);
        }

        .section-header p {
            color: var(--muted2);
            font-size: 13px;
            margin-top: 4px;
        }

        .badge {
            background: rgba(0,0,0,0.3);
            color: var(--text);
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid var(--border);
        }

        .badge.warning {
            background: rgba(255,152,0,0.15);
            color: var(--warning);
            border-color: rgba(255,152,0,0.3);
        }

        /* Table Container */
        .table-container {
            background: rgba(0,0,0,0.2);
            border-radius: 16px;
            overflow-x: auto;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }

        thead {
            background: rgba(0,0,0,0.25);
            border-bottom: 1px solid var(--border);
        }

        th {
            padding: 18px 24px;
            text-align: left;
            font-size: 12px;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            color: var(--text);
            font-size: 14px;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: rgba(255,255,255,0.05);
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Stock Status */
        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .stock-status.good {
            background: rgba(76,175,80,0.15);
            color: var(--success);
            border-color: rgba(76,175,80,0.3);
        }

        .stock-status.low {
            background: rgba(255,152,0,0.15);
            color: var(--warning);
            border-color: rgba(255,152,0,0.3);
        }

        .stock-status.critical {
            background: rgba(244,67,54,0.15);
            color: var(--danger);
            border-color: rgba(244,67,54,0.3);
        }

        /* Suggestion List */
        .suggestion-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .suggestion-item {
            background: rgba(0,0,0,0.2);
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .suggestion-item:hover {
            border-color: rgba(242,192,137,0.3);
            background: rgba(255,255,255,0.05);
        }

        .suggestion-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .suggestion-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
        }

        .suggestion-meta {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--muted2);
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .suggestion-qty {
            font-weight: 700;
            color: var(--accent3);
            background: rgba(0,0,0,0.3);
            padding: 4px 10px;
            border-radius: 20px;
            border: 1px solid var(--border);
        }

        .suggestion-date {
            color: var(--muted2);
        }

        .suggestion-actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .suggestion-actions form {
            display: inline;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            box-shadow: 0 8px 15px rgba(197,122,58,0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(197,122,58,0.3);
        }

        .btn-success {
            background: rgba(76,175,80,0.2);
            color: var(--success);
            border-color: rgba(76,175,80,0.3);
        }

        .btn-success:hover {
            background: rgba(76,175,80,0.3);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: rgba(244,67,54,0.2);
            color: var(--danger);
            border-color: rgba(244,67,54,0.3);
        }

        .btn-danger:hover {
            background: rgba(244,67,54,0.3);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(0,0,0,0.3);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
            transform: translateY(-1px);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted2);
        }

        .empty-state h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 40px;
            padding-top: 40px;
            border-top: 1px solid var(--border);
            flex-wrap: wrap;
        }

        /* View All Link */
        .view-all {
            text-align: center;
            margin-top: 16px;
        }

        .view-all a {
            color: var(--accent3);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 30px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .view-all a:hover {
            background: rgba(255,255,255,0.05);
            border-color: var(--accent1);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            body {
                flex-direction: column;
            }
            
            .side-nav {
                width: 100%;
                height: auto;
                position: relative;
                padding: 16px 0;
            }
            
            .nav-brand {
                padding: 0 20px 16px;
                margin-bottom: 16px;
            }
            
            .nav-links {
                flex-direction: row;
                overflow-x: auto;
                padding: 0 20px;
                gap: 8px;
            }
            
            .nav-item > a {
                white-space: nowrap;
            }
            
            .main-content {
                margin-left: 0;
                max-width: 100%;
                padding: 24px;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-section {
                padding: 24px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .suggestion-header {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .suggestion-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .suggestion-actions .btn {
                width: 100%;
                justify-content: center;
            }
            
            .quick-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .quick-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="noise"></div>

    <!-- LEFT NAVIGATION BAR - Coffee Theme -->
    <nav class="side-nav">
        <a href="owner_dashboard.php" class="nav-brand">Inventory System</a>
        
        <div class="nav-links">
            <div class="nav-item active">
                <a href="owner_dashboard.php">Dashboard</a>
            </div>
            
            <div class="nav-item">
                <a href="add_user.php">Add User</a>
            </div>
        </div>
        
        <div class="nav-actions">
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="owner-badge">👑 Owner Access</div>
            <h1>Owner Dashboard</h1>
            <p>Welcome, Owner! Manage inventory and approve reorder suggestions to automatically purchase products.</p>
        </div>

        <?php if (!empty($approval_message)): ?>
            <div class="alert success">
                <div class="alert-icon">✅</div>
                <div class="alert-content">
                    <div class="alert-title">Success</div>
                    <div class="alert-message"><?= htmlspecialchars($approval_message) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert error">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                    <div class="alert-title">Error</div>
                    <div class="alert-message"><?= htmlspecialchars($error_message) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Items</h3>
                <div class="value"><?= $total_items ?></div>
                <div class="trend positive">Active in inventory</div>
            </div>
            
            <div class="stat-card">
                <h3>Low Stock Items</h3>
                <div class="value"><?= $low_stock_items ?></div>
                <div class="sub-value"><?= $out_of_stock ?> out of stock</div>
                <div class="trend <?= $low_stock_items > 0 ? 'warning' : 'positive' ?>">
                    <?= $low_stock_items > 0 ? '⚠️ Needs attention' : '✅ All good' ?>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>Pending Suggestions</h3>
                <div class="value"><?= $pending_suggestions ?></div>
                <?php if ($pending_value > 0): ?>
                <div class="sub-value">₱<?= number_format($pending_value, 2) ?></div>
                <?php endif; ?>
                <div class="trend <?= $pending_suggestions > 0 ? 'warning' : 'positive' ?>">
                    <?= $pending_suggestions > 0 ? '⏳ Awaiting approval' : '✅ No pending' ?>
                </div>
            </div>
            
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?= $total_users ?></div>
                <div class="trend positive">Active accounts</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Current Inventory -->
            <div class="dashboard-section">
                <div class="section-header">
                    <div>
                        <h2>Current Inventory</h2>
                        <p>Top items needing attention</p>
                    </div>
                    <span class="badge">Top Items</span>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Current Stock</th>
                                <th>Reorder Level</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $items = $conn->query("
                                SELECT id, item_name, current_stock, reorder_level, unit_of_measure 
                                FROM items 
                                ORDER BY 
                                    CASE 
                                        WHEN current_stock <= reorder_level THEN 0 
                                        ELSE 1 
                                    END,
                                    current_stock ASC 
                                LIMIT 8
                            ");
                            
                            if ($items->num_rows > 0):
                                while ($item = $items->fetch_assoc()):
                                    $stock_status = '';
                                    if ($item['current_stock'] <= 0) {
                                        $stock_status = 'critical';
                                        $status_text = 'Out of Stock';
                                    } elseif ($item['current_stock'] <= $item['reorder_level']) {
                                        $stock_status = 'low';
                                        $status_text = 'Low Stock';
                                    } else {
                                        $stock_status = 'good';
                                        $status_text = 'In Stock';
                                    }
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= number_format($item['current_stock'], 2) ?> <?= htmlspecialchars($item['unit_of_measure']) ?></td>
                                    <td><?= number_format($item['reorder_level'], 2) ?></td>
                                    <td>
                                        <span class="stock-status <?= $stock_status ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <h3>No Items Found</h3>
                                        <p>Add items to your inventory to get started.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- View All Items link removed per request -->
            </div>

            <!-- Pending Reorder Suggestions -->
            <div class="dashboard-section">
                <div class="section-header">
                    <div>
                        <h2>Pending Reorder Suggestions</h2>
                        <p>Review and approve purchase requests</p>
                    </div>
                    <span class="badge <?= $pending_suggestions > 0 ? 'warning' : '' ?>">
                        <?= $pending_suggestions ?> Pending
                    </span>
                </div>
                
                <?php
                $suggestions = $conn->query("
                    SELECT rs.id, i.item_name, rs.suggested_qty, rs.created_at, i.unit_of_measure, i.unit_cost, i.current_stock, i.reorder_level
                    FROM reorder_suggestions rs
                    JOIN items i ON rs.item_id = i.id
                    WHERE rs.status = 'pending'
                    ORDER BY rs.created_at DESC
                    LIMIT 5
                ");
                
                if ($suggestions->num_rows > 0): ?>
                    <div class="suggestion-list">
                        <?php while ($sug = $suggestions->fetch_assoc()): 
                            $total_cost = $sug['unit_cost'] ? $sug['suggested_qty'] * $sug['unit_cost'] : null;
                        ?>
                        <div class="suggestion-item">
                            <div class="suggestion-header">
                                <div class="suggestion-title"><?= htmlspecialchars($sug['item_name']) ?></div>
                                <?php if ($sug['current_stock'] <= $sug['reorder_level']): ?>
                                <span class="badge warning">Low Stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="suggestion-meta">
                                <span class="suggestion-qty">📦 <?= $sug['suggested_qty'] ?> <?= htmlspecialchars($sug['unit_of_measure']) ?></span>
                                <span class="suggestion-date">📅 <?= date("M d, Y", strtotime($sug['created_at'])) ?></span>
                                <?php if ($total_cost): ?>
                                <span class="suggestion-date">💰 ₱<?= number_format($total_cost, 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="suggestion-actions">
                                <!-- Approve Form -->
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="suggestion_id" value="<?= $sug['id'] ?>">
                                    <button type="submit" name="approve_suggestion" class="btn btn-success">
                                        ✅ Approve & Purchase
                                    </button>
                                </form>
                                
                                <!-- Reject Form -->
                                <form method="POST" style="display:inline;" onsubmit="return confirmReject()">
                                    <input type="hidden" name="suggestion_id" value="<?= $sug['id'] ?>">
                                    <button type="submit" name="reject_suggestion" class="btn btn-danger">
                                        ❌ Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No Pending Suggestions</h3>
                        <p>All reorder suggestions have been processed.</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($suggestions->num_rows > 0): ?>
                <div class="view-all">
                    <a href="reorder_suggestions.php">View All Suggestions →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-section" style="margin-top: 20px;">
            <div class="section-header">
                <div>
                    <h2>Recent Activity</h2>
                    <p>Latest stock movements</p>
                </div>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $activities = $conn->query("
                            SELECT st.created_at, i.item_name, st.transaction_type, st.quantity, st.status, i.unit_of_measure
                            FROM stock_transactions st
                            JOIN items i ON st.item_id = i.id
                            ORDER BY st.created_at DESC
                            LIMIT 5
                        ");
                        
                        if ($activities->num_rows > 0):
                            while ($act = $activities->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= date("M d, H:i", strtotime($act['created_at'])) ?></td>
                                <td><?= htmlspecialchars($act['item_name']) ?></td>
                                <td><?= ucfirst($act['transaction_type']) ?></td>
                                <td><?= number_format($act['quantity'], 2) ?> <?= htmlspecialchars($act['unit_of_measure']) ?></td>
                                <td>
                                    <span class="stock-status <?= $act['status'] == 'approved' ? 'good' : 'low' ?>">
                                        <?= ucfirst($act['status']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php 
                            endwhile;
                        else: ?>
                            <tr>
                                <td colspan="5" class="empty-state">
                                    <p>No recent activity</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
    </main>

    <script>
        // Confirm approval action
        document.querySelectorAll('button[name="approve_suggestion"]').forEach(button => {
            button.addEventListener('click', function(e) {
                const form = this.closest('form');
                const suggestionItem = this.closest('.suggestion-item');
                const itemName = suggestionItem ? suggestionItem.querySelector('.suggestion-title').textContent : 'this item';
                const qtyElement = suggestionItem ? suggestionItem.querySelector('.suggestion-qty') : null;
                const qtyText = qtyElement ? qtyElement.textContent : '';
                
                if (!confirm(`Are you sure you want to approve ${itemName}?\n\n${qtyText}\n\nThis will automatically create a purchase transaction and update inventory.`)) {
                    e.preventDefault();
                }
            });
        });

        // Confirm reject action
        function confirmReject() {
            return confirm('Are you sure you want to reject this suggestion? This action cannot be undone.');
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>