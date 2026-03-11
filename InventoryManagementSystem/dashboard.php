<?php
include "config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: index.php");
    exit();
}

/* ===============================
   REORDER SUGGESTION FUNCTION
   =============================== */
function getReorderSuggestion($conn, $item_id, $current_stock) {
    // Average daily usage (last 30 days)
    $usage = $conn->query("
        SELECT IFNULL(SUM(quantity),0) / 30 AS avg_daily
        FROM stock_transactions
        WHERE item_id = $item_id
        AND transaction_type = 'usage'
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ")->fetch_assoc();

    $avgDailyUsage = $usage['avg_daily'];
    $leadTimeDays = 7; // supplier lead time
    $suggestedQty = ($avgDailyUsage * $leadTimeDays) - $current_stock;

    return max(0, ceil($suggestedQty));
}

// Handle form submission for reorder suggestion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_suggestion'])) {

    $item_id = (int)($_POST['item_id'] ?? 0);
    $suggested_qty_input = (float)($_POST['suggested_qty'] ?? 0);

    if ($item_id <= 0) {
        header("Location: dashboard.php?err=invalid_item");
        exit();
    }

    if ($suggested_qty_input <= 0) {
        header("Location: dashboard.php?err=invalid_quantity");
        exit();
    }

    // Fetch item current stock + reorder level
    $stmt = $conn->prepare("SELECT current_stock, reorder_level FROM items WHERE id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$item) {
        header("Location: dashboard.php?err=item_not_found");
        exit();
    }

    $current_stock = (float)$item['current_stock'];
    $reorder_level = (float)$item['reorder_level'];

    // Use the submitted quantity instead of calculated one
    $suggested_qty = $suggested_qty_input;

    // Avoid duplicates: if there's already a pending request for this item, UPDATE it
    $check = $conn->prepare("SELECT id FROM reorder_suggestions WHERE item_id = ? AND status = 'pending' LIMIT 1");
    $check->bind_param("i", $item_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existing) {
        $rid = (int)$existing['id'];

        $upd = $conn->prepare("
            UPDATE reorder_suggestions
            SET current_stock = ?, reorder_level = ?, suggested_qty = ?, created_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $upd->bind_param("dddi", $current_stock, $reorder_level, $suggested_qty, $rid);
        $upd->execute();
        $upd->close();
        
        $success_message = "Reorder suggestion updated successfully for this item.";
    } else {
        $ins = $conn->prepare("
            INSERT INTO reorder_suggestions (item_id, current_stock, reorder_level, suggested_qty, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $ins->bind_param("iddd", $item_id, $current_stock, $reorder_level, $suggested_qty);
        $ins->execute();
        $ins->close();
        
        $success_message = "Reorder suggestion submitted successfully for approval.";
    }
}

/* ===============================
   DASHBOARD STATS
   =============================== */
$totalItems = $conn->query("SELECT COUNT(*) c FROM items WHERE is_active = 1")->fetch_assoc()['c'];
$lowStock = $conn->query("
    SELECT COUNT(*) c 
    FROM items 
    WHERE current_stock <= reorder_level AND current_stock > 0 AND is_active = 1
")->fetch_assoc()['c'];

$outStock = $conn->query("
    SELECT COUNT(*) c 
    FROM items 
    WHERE current_stock = 0 AND is_active = 1
")->fetch_assoc()['c'];

// Get total value of inventory
$inventoryValue = $conn->query("
    SELECT SUM(current_stock * unit_cost) as total 
    FROM items 
    WHERE is_active = 1
")->fetch_assoc()['total'];

// Check for error/success messages
$error_message = '';
$success_message = $success_message ?? '';

if (isset($_GET['err'])) {
    switch($_GET['err']) {
        case 'invalid_item':
            $error_message = 'Invalid item selected.';
            break;
        case 'invalid_quantity':
            $error_message = 'Please enter a valid quantity.';
            break;
        case 'item_not_found':
            $error_message = 'Item not found in database.';
            break;
    }
}

if (isset($_GET['msg'])) {
    switch($_GET['msg']) {
        case 'no_reorder_needed':
            $error_message = 'This item does not need reordering at this time.';
            break;
    }
}

if (isset($_GET['reorder_submitted'])) {
    $success_message = 'Reorder suggestion submitted successfully for approval.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inventory System</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --info: #2196f3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        body {
            color: var(--text);
            display: flex;
            min-height: 100vh;
            background: radial-gradient(1200px 600px at 20% 10%, rgba(197,122,58,0.25), transparent 60%),
                        radial-gradient(900px 500px at 80% 20%, rgba(242,192,137,0.18), transparent 60%),
                        radial-gradient(900px 600px at 50% 100%, rgba(123,74,46,0.25), transparent 60%),
                        linear-gradient(135deg, var(--bg1), var(--bg2));
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

        /* Left Navigation Bar (unchanged) */
        .side-nav {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.06));
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
            border-bottom: 1px solid rgba(255,255,255,0.12);
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
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            height: 16px;
            width: 3px;
            background: linear-gradient(180deg, var(--accent3), var(--accent1));
            border-radius: 0 2px 2px 0;
        }

        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            padding: 12px 16px;
            color: rgba(244,244,246,0.82);
            font-size: 14px;
            font-weight: 700;
            border-radius: 12px;
            transition: all 0.2s;
            background: rgba(0,0,0,0.16);
            border: 1px solid rgba(255,255,255,0.10);
        }

        .nav-dropdown-toggle:hover {
            background: rgba(255,255,255,0.10);
            color: #fff;
            transform: translateY(-1px);
        }

        .nav-dropdown-toggle::after {
            content: '▼';
            font-size: 10px;
            transition: transform 0.2s;
            opacity: .85;
        }

        .nav-dropdown.active .nav-dropdown-toggle::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            padding-left: 16px;
            margin: 6px 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .nav-dropdown.active .dropdown-menu {
            max-height: 220px;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 14px;
            color: rgba(244,244,246,0.75);
            text-decoration: none;
            font-size: 13px;
            border-radius: 12px;
            transition: all 0.2s;
            margin: 6px 0 0;
            background: rgba(0,0,0,0.12);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .dropdown-menu a:hover {
            background: rgba(255,255,255,0.10);
            color: #fff;
            transform: translateY(-1px);
        }

        .nav-actions {
            padding: 20px 24px;
            border-top: 1px solid rgba(255,255,255,0.12);
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
            display: flex;
            align-items: center;
            justify-content: center;
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
            max-width: 1400px;
            overflow-y: auto;
            position: relative;
            z-index: 1;
        }

        /* Welcome Header */
        .welcome-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .welcome-text h1 {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .welcome-text p {
            color: var(--muted2);
            font-size: 15px;
        }

        .date-badge {
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 10px 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(10px);
        }

        .date-badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent1);
            box-shadow: 0 0 12px var(--accent1);
        }

        /* Alert Messages */
        .alert {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 16px 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
        }

        .alert.success {
            border-color: rgba(76,175,80,0.3);
            background: rgba(76,175,80,0.1);
        }

        .alert.error {
            border-color: rgba(244,67,54,0.3);
            background: rgba(244,67,54,0.1);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent1), var(--accent3));
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            background: rgba(197,122,58,0.15);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .stat-label {
            font-size: 13px;
            color: var(--muted2);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 4px;
        }

        .stat-desc {
            font-size: 13px;
            color: var(--muted2);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-desc.warning {
            color: var(--warning);
        }

        .stat-desc.danger {
            color: var(--danger);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .section-header h2 {
            font-size: 20px;
            font-weight: 900;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header h2 span {
            color: var(--accent1);
        }

        /* Form Card */
        .form-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 28px;
            backdrop-filter: blur(10px);
            margin-bottom: 40px;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .form-header-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--accent1), var(--accent2));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .form-header h3 {
            font-size: 18px;
            font-weight: 900;
            color: var(--text);
        }

        .form-header p {
            font-size: 13px;
            color: var(--muted2);
            margin-top: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--muted);
        }

        .form-group select,
        .form-group input {
            padding: 14px 16px;
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 14px;
            color: var(--text);
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: 0 0 0 3px rgba(197,122,58,0.15);
            background: rgba(0,0,0,0.35);
        }

        .form-group small {
            color: var(--muted2);
            margin-top: 8px;
            font-size: 12px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            border: none;
            padding: 14px 28px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 10px 25px rgba(197,122,58,0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(197,122,58,0.4);
        }

        .btn-secondary {
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
        }

        /* Dashboard Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 24px;
            margin: 32px 0;
        }

        /* Chart Card */
        .chart-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 24px;
            backdrop-filter: blur(10px);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .chart-header h3 {
            font-size: 18px;
            font-weight: 900;
            color: var(--text);
        }

        .chart-legend {
            display: flex;
            gap: 16px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--muted2);
        }

        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }

        .chart-container {
            position: relative;
            height: 280px;
            width: 280px;
            margin: 0 auto;
        }

        .chart-center-text {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            pointer-events: none;
        }

        .chart-center-text .value {
            font-size: 36px;
            font-weight: 1000;
            color: var(--text);
            line-height: 1;
        }

        .chart-center-text .label {
            font-size: 13px;
            color: var(--muted2);
            margin-top: 4px;
        }

        /* Low Stock List */
        .low-stock-list {
            margin-top: 16px;
        }

        .low-stock-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .low-stock-item:last-child {
            border-bottom: none;
        }

        .item-info h4 {
            font-size: 15px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 4px;
        }

        .stock-level {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
        }

        .stock-bar {
            width: 100px;
            height: 6px;
            background: rgba(0,0,0,0.3);
            border-radius: 3px;
            overflow: hidden;
        }

        .stock-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--danger), var(--warning));
            border-radius: 3px;
        }

        .stock-numbers {
            color: var(--muted2);
        }

        .stock-numbers strong {
            color: var(--danger);
            font-weight: 800;
        }

        /* Activity Grid */
        .activity-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-top: 32px;
        }

        .activity-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 24px;
            backdrop-filter: blur(10px);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .activity-header h3 {
            font-size: 18px;
            font-weight: 900;
            color: var(--text);
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(0,0,0,0.15);
            border-radius: 14px;
            border: 1px solid var(--border);
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            background: rgba(197,122,58,0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .activity-details {
            flex: 1;
        }

        .activity-details strong {
            font-size: 14px;
            color: var(--text);
        }

        .activity-meta {
            display: flex;
            gap: 12px;
            margin-top: 4px;
            font-size: 12px;
            color: var(--muted2);
        }

        .activity-status {
            padding: 2px 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
        }

        .activity-status.pending {
            background: rgba(255,152,0,0.2);
            color: var(--warning);
        }

        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .quick-action-btn {
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 16px;
            text-align: center;
            text-decoration: none;
            color: var(--text);
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }

        .quick-action-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
            transform: translateY(-2px);
        }

        .quick-action-btn .emoji {
            font-size: 24px;
        }

        .quick-action-btn span {
            font-size: 13px;
            font-weight: 800;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-grid,
            .activity-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1024px) {
            body {
                flex-direction: column;
            }
            
            .side-nav {
                width: 100%;
                height: auto;
                position: relative;
            }
            
            .main-content {
                padding: 24px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="noise"></div>
    
    <!-- LEFT NAVIGATION BAR -->
    <nav class="side-nav">
        <a href="dashboard.php" class="nav-brand">Inventory System</a>
        
        <div class="nav-links">
            <div class="nav-item active">
                <a href="dashboard.php">Dashboard</a>
            </div>
            
            <div class="nav-item nav-dropdown">
                <div class="nav-dropdown-toggle">Stock</div>
                <div class="dropdown-menu">
                    <a href="transactions.php">Stock In / Out and Adjustments</a>
                </div>
            </div>
            
            <div class="nav-item">
                <a href="items.php">Inventory</a>
            </div>
            
            <div class="nav-item nav-dropdown">
                <div class="nav-dropdown-toggle">Suppliers</div>
                <div class="dropdown-menu">
                    <a href="supply.php">Suppliers and Purchase Orders</a>
                </div>
            </div>
            
            <div class="nav-item nav-dropdown">
                <div class="nav-dropdown-toggle">Recipes</div>
                <div class="dropdown-menu">
                    <a href="recipes.php">Recipes and Consumption</a>
                    <a href="sales.php">Orders</a>
                </div>
            </div>
        </div>
        
        <div class="nav-actions">
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <div class="welcome-text">
                <h1>Dashboard</h1>
                <p>Welcome back! Here's what's happening with your inventory today.</p>
            </div>
            <div class="date-badge">
                <span class="date-badge-dot"></span>
                <span><?= date('l, F j, Y') ?></span>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert success">
                <span style="font-size: 20px;">✅</span>
                <span><?= htmlspecialchars($success_message) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert error">
                <span style="font-size: 20px;">⚠️</span>
                <span><?= htmlspecialchars($error_message) ?></span>
            </div>
        <?php endif; ?>

        <!-- Key Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-label">Total Items</div>
                <div class="stat-value"><?= $totalItems ?></div>
                <div class="stat-desc">Active inventory items</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-label">Inventory Value</div>
                <div class="stat-value">₱<?= number_format($inventoryValue ?? 0, 2) ?></div>
                <div class="stat-desc">Total stock value</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-label">Low Stock</div>
                <div class="stat-value"><?= $lowStock ?></div>
                <div class="stat-desc warning">Need reordering soon</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">❌</div>
                <div class="stat-label">Out of Stock</div>
                <div class="stat-value"><?= $outStock ?></div>
                <div class="stat-desc danger">Currently unavailable</div>
            </div>
        </div>

        <!-- Reorder Suggestion Form -->
        <div class="form-card">
            <div class="form-header">
                <div class="form-header-icon">📋</div>
                <div>
                    <h3>Submit Reorder Suggestion</h3>
                    <p>Request approval for purchasing new stock</p>
                </div>
            </div>
            
            <form method="POST" action="" id="reorderForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Select Item</label>
                        <select id="item_id" name="item_id" required>
                            <option value="">Choose an item...</option>
                            <?php
                            $items = $conn->query("SELECT id, item_name, current_stock, unit_of_measure FROM items WHERE is_active = 1 ORDER BY item_name");
                            while ($item = $items->fetch_assoc()):
                                $suggested = getReorderSuggestion($conn, $item['id'], $item['current_stock']);
                                $isLow = $item['current_stock'] <= $item['reorder_level'] ?? 0;
                            ?>
                                <option value="<?= $item['id'] ?>" 
                                        data-current-stock="<?= $item['current_stock'] ?>"
                                        data-suggested="<?= $suggested ?>"
                                        data-unit="<?= htmlspecialchars($item['unit_of_measure']) ?>">
                                    <?= htmlspecialchars($item['item_name']) ?> 
                                    (Current: <?= $item['current_stock'] ?> <?= htmlspecialchars($item['unit_of_measure']) ?>)
                                    <?= $isLow ? '⚠️' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Suggested Quantity</label>
                        <input type="number" id="suggested_qty" name="suggested_qty" min="1" 
                               placeholder="Enter quantity" required>
                        <small>Based on 30-day usage history. Adjust if needed.</small>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; margin-top: 24px;">
                    <button type="submit" name="submit_suggestion" class="btn-primary">
                        <span>📤</span>
                        Submit for Approval
                    </button>
                </div>
            </form>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Low Stock Items -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>⚠️ Low Stock Items</h3>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-color" style="background: var(--warning);"></span>
                            Below reorder level
                        </span>
                    </div>
                </div>
                
                <div class="low-stock-list">
                    <?php
                    $reorder = $conn->query("
                        SELECT id, item_name, current_stock, reorder_level, unit_of_measure
                        FROM items 
                        WHERE current_stock <= reorder_level AND current_stock > 0 AND is_active = 1
                        ORDER BY (reorder_level - current_stock) DESC
                        LIMIT 5
                    ");
                    
                    if ($reorder->num_rows > 0):
                        while($r = $reorder->fetch_assoc()):
                            $percentage = min(100, ($r['current_stock'] / $r['reorder_level']) * 100);
                    ?>
                        <div class="low-stock-item">
                            <div class="item-info">
                                <h4><?= htmlspecialchars($r['item_name']) ?></h4>
                                <div class="stock-level">
                                    <div class="stock-bar">
                                        <div class="stock-bar-fill" style="width: <?= $percentage ?>%;"></div>
                                    </div>
                                    <span class="stock-numbers">
                                        <strong><?= $r['current_stock'] ?></strong> / <?= $r['reorder_level'] ?> <?= htmlspecialchars($r['unit_of_measure']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="item_id" value="<?= $r['id'] ?>">
                                <?php 
                                $quick_suggested = getReorderSuggestion($conn, $r['id'], $r['current_stock']);
                                ?>
                                <input type="hidden" name="suggested_qty" value="<?= $quick_suggested ?>">
                                <button type="submit" name="submit_suggestion" class="btn-secondary" style="padding: 8px 16px;">
                                    Quick Reorder
                                </button>
                            </form>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div style="text-align: center; padding: 40px 20px; color: var(--muted2);">
                            <span style="font-size: 40px; display: block; margin-bottom: 12px;">✅</span>
                            <p>All items are well stocked</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stock Overview Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3>📊 Stock Overview</h3>
                    <div class="chart-legend">
                        <span class="legend-item">
                            <span class="legend-color" style="background: #8a8a8a;"></span>
                            In Stock
                        </span>
                        <span class="legend-item">
                            <span class="legend-color" style="background: var(--warning);"></span>
                            Low
                        </span>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="stockChart"></canvas>
                    <div class="chart-center-text">
                        <div class="value"><?= $totalItems ?></div>
                        <div class="label">Total Items</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Grid -->
        <div class="activity-grid">
            <!-- Recent Activity -->
            <div class="activity-card">
                <div class="activity-header">
                    <h3>🕒 Recent Activity</h3>
                    <a href="#" style="color: var(--accent3); font-size: 13px; text-decoration: none;">View all →</a>
                </div>
                
                <div class="activity-list">
                    <?php
                    $recent = $conn->query("
                        SELECT rs.*, i.item_name 
                        FROM reorder_suggestions rs
                        JOIN items i ON rs.item_id = i.id
                        ORDER BY rs.created_at DESC
                        LIMIT 5
                    ");
                    
                    if ($recent->num_rows > 0):
                        while($activity = $recent->fetch_assoc()):
                    ?>
                        <div class="activity-item">
                            <div class="activity-icon">📦</div>
                            <div class="activity-details">
                                <strong><?= htmlspecialchars($activity['item_name']) ?></strong>
                                <div class="activity-meta">
                                    <span>Qty: <?= $activity['suggested_qty'] ?></span>
                                    <span class="activity-status <?= $activity['status'] ?>">
                                        <?= ucfirst($activity['status']) ?>
                                    </span>
                                </div>
                                <div style="font-size: 11px; color: var(--muted2); margin-top: 2px;">
                                    <?= date('M d, Y • g:i A', strtotime($activity['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div style="text-align: center; padding: 30px; color: var(--muted2);">
                            <p>No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="activity-card">
                <div class="activity-header">
                    <h3>⚡ Quick Actions</h3>
                </div>
                
                <div class="quick-actions-grid">
                    <a href="transactions.php?type=purchase" class="quick-action-btn">
                        <span class="emoji">📥</span>
                        <span>Stock In</span>
                    </a>
                    <a href="transactions.php?type=usage" class="quick-action-btn">
                        <span class="emoji">📤</span>
                        <span>Stock Out</span>
                    </a>
                    <a href="items.php?action=add" class="quick-action-btn">
                        <span class="emoji">➕</span>
                        <span>Add Item</span>
                    </a>
                    <a href="supply.php?action=add" class="quick-action-btn">
                        <span class="emoji">🤝</span>
                        <span>New Supplier</span>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Dropdown functionality
        document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
            const toggle = dropdown.querySelector('.nav-dropdown-toggle');
            
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                document.querySelectorAll('.nav-dropdown').forEach(other => {
                    if (other !== dropdown) {
                        other.classList.remove('active');
                    }
                });
                
                dropdown.classList.toggle('active');
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });

        // Auto-fill suggested quantity
        document.getElementById('item_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const suggestedQty = selectedOption.getAttribute('data-suggested');
            const unit = selectedOption.getAttribute('data-unit');
            
            if (suggestedQty && suggestedQty > 0) {
                document.getElementById('suggested_qty').value = suggestedQty;
            } else {
                document.getElementById('suggested_qty').value = '';
            }
        });

        // Form validation with better UX
        document.getElementById('reorderForm').addEventListener('submit', function(e) {
            const itemSelect = document.getElementById('item_id');
            const suggestedQty = document.getElementById('suggested_qty');
            
            if (!itemSelect.value) {
                e.preventDefault();
                alert('Please select an item.');
                itemSelect.focus();
                return false;
            }
            
            if (!suggestedQty.value || parseInt(suggestedQty.value) < 1) {
                e.preventDefault();
                alert('Please enter a valid quantity (minimum 1).');
                suggestedQty.focus();
                return false;
            }
            
            const selectedOption = itemSelect.options[itemSelect.selectedIndex];
            const itemName = selectedOption.text.split('(')[0].trim();
            
            const confirmMessage = `Submit reorder suggestion for:\n\n${itemName}\nQuantity: ${suggestedQty.value}\n\nThis will be sent for approval.`;
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        // Initialize Chart
        const ctx = document.getElementById('stockChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [
                        <?= max(0, $totalItems - $lowStock - $outStock) ?>,
                        <?= $lowStock ?>,
                        <?= $outStock ?>
                    ],
                    backgroundColor: ['#8a8a8a', 'var(--warning)', 'var(--danger)'],
                    borderWidth: 0,
                    borderRadius: 8,
                    spacing: 4
                }]
            },
            options: {
                cutout: '75%',
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: 'rgba(255,255,255,0.8)',
                        borderColor: 'var(--border)',
                        borderWidth: 1
                    }
                },
                maintainAspectRatio: false
            }
        });

        // Trigger change on page load
        window.addEventListener('load', function() {
            const itemSelect = document.getElementById('item_id');
            if (itemSelect.value) {
                itemSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>