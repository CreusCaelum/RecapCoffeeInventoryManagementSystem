<?php
include "config/db.php";
include "config/functions.php";

checkLogin();

$message = '';
if ($_POST) {
    addStockTransaction(
        $conn,
        $_POST['type'],
        (int)$_POST['item_id'],
        (float)$_POST['qty'],
        $_SESSION['user_id']
    );

    updateStock(
        $conn,
        (int)$_POST['item_id'],
        (float)$_POST['qty'],
        $_POST['type']
    );

    $message = 'Transaction recorded successfully';
}

// Get low stock items for warning
$lowStock = $conn->query("
    SELECT COUNT(*) as count 
    FROM items 
    WHERE current_stock <= reorder_level 
    AND is_active = 1
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Transactions - Inventory System</title>
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

        /* Header with Stats */
        .page-header {
            margin-bottom: 32px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .header-top h1 {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            text-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .header-badge {
            background: rgba(0,0,0,0.20);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: 8px 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent1);
            box-shadow: 0 0 12px var(--accent1);
        }

        /* Quick Stats */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-item {
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .stat-label {
            font-size: 13px;
            color: var(--muted2);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 900;
            color: var(--text);
        }

        .stat-note {
            font-size: 12px;
            color: var(--muted2);
            margin-top: 4px;
        }

        .warning-stat {
            border-left: 3px solid var(--accent1);
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
            border-color: rgba(92,255,180,0.18);
        }

        /* Main Form Card */
        .form-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.06));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
            margin-bottom: 40px;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .form-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent1), var(--accent2));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .form-header h2 {
            font-size: 20px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 4px;
        }

        .form-header p {
            font-size: 14px;
            color: var(--muted2);
        }

        /* Form Grid Layout */
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
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
            display: flex;
            align-items: center;
            gap: 6px;
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

        .form-group select:hover, 
        .form-group input:hover {
            background: rgba(0,0,0,0.35);
            border-color: rgba(242,192,137,0.3);
        }

        .form-group select:focus, 
        .form-group input:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: 0 0 0 3px rgba(197,122,58,0.15);
            background: rgba(0,0,0,0.4);
        }

        .form-group option {
            background: var(--bg2);
            color: var(--text);
        }

        /* Stock Info Badge */
        .stock-badge {
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            font-size: 13px;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stock-badge span {
            color: var(--accent3);
            font-weight: 800;
        }

        /* Radio Group Styling */
        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .radio-option {
            flex: 1;
            position: relative;
        }

        .radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .radio-label {
            display: flex;
            flex-direction: column;
            padding: 16px 20px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
            border-radius: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .radio-option input[type="radio"]:checked + .radio-label {
            background: linear-gradient(135deg, rgba(197,122,58,0.15), rgba(123,74,46,0.1));
            border-color: var(--accent1);
            box-shadow: 0 8px 20px rgba(197,122,58,0.15);
        }

        .type-title {
            font-size: 16px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 4px;
        }

        .type-desc {
            font-size: 12px;
            color: var(--muted2);
        }

        .radio-option input[type="radio"]:checked + .radio-label .type-desc {
            color: var(--muted);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            border: none;
            padding: 14px 36px;
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

        /* Recent Transactions Section */
        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recent-header h3 {
            font-size: 18px;
            font-weight: 900;
            color: var(--text);
        }

        .view-all {
            color: var(--accent3);
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: rgba(0,0,0,0.2);
            border-radius: 30px;
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .view-all:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
        }

        /* Transactions List */
        .transactions-list {
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            transition: all 0.2s;
        }

        .transaction-item:last-child {
            border-bottom: none;
        }

        .transaction-item:hover {
            background: rgba(255,255,255,0.04);
        }

        .transaction-info {
            flex: 1;
        }

        .transaction-info h4 {
            font-size: 16px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 6px;
        }

        .transaction-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 13px;
        }

        .transaction-type {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: rgba(0,0,0,0.3);
            border-radius: 30px;
            border: 1px solid var(--border);
        }

        .type-badge {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .type-badge.purchase {
            background: #4caf50;
            box-shadow: 0 0 10px #4caf50;
        }

        .type-badge.usage {
            background: var(--accent1);
            box-shadow: 0 0 10px var(--accent1);
        }

        .transaction-date {
            color: var(--muted2);
        }

        .transaction-qty {
            font-size: 18px;
            font-weight: 900;
            padding: 6px 16px;
            border-radius: 30px;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
        }

        .transaction-qty.positive {
            color: #4caf50;
        }

        .transaction-qty.negative {
            color: var(--accent1);
        }

        /* Empty State */
        .empty-state {
            padding: 60px 24px;
            text-align: center;
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h4 {
            font-size: 18px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
        }

        .empty-state p {
            color: var(--muted2);
            font-size: 14px;
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
            }
            
            .main-content {
                padding: 24px;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .radio-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="noise"></div>
    
    <!-- LEFT NAVIGATION BAR (unchanged) -->
    <nav class="side-nav">
        <a href="dashboard.php" class="nav-brand">Inventory System</a>
        
        <div class="nav-links">
            <div class="nav-item">
                <a href="dashboard.php">Dashboard</a>
            </div>
            
            <div class="nav-item active">
                <a href="transactions.php">Stock</a>
            </div>
            
            <div class="nav-item nav-dropdown">
                <div class="nav-dropdown-toggle">Inventory</div>
                <div class="dropdown-menu">
                    <a href="items.php">Items and Categories</a>
                </div>
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
                    <div class="dropdown-menu">
                    <a href="recipes.php">Recipes and Consumption</a>
                    <a href="sales.php">Orders</a>
                </div>
                </div>
            </div>
        </div>
        
        <div class="nav-actions">
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Header -->
        <div class="page-header">
            <div class="header-top">
                <h1>Stock Transactions</h1>
                <div class="header-badge">
                    <span class="badge-dot"></span>
                    <span><?= date('l, F j, Y') ?></span>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-label">📦 Total Items</div>
                    <div class="stat-number">
                        <?php 
                        $totalItems = $conn->query("SELECT COUNT(*) as count FROM items WHERE is_active = 1")->fetch_assoc();
                        echo $totalItems['count'];
                        ?>
                    </div>
                    <div class="stat-note">Active inventory items</div>
                </div>
                
                <div class="stat-item <?= $lowStock['count'] > 0 ? 'warning-stat' : '' ?>">
                    <div class="stat-label">⚠️ Low Stock Items</div>
                    <div class="stat-number"><?= $lowStock['count'] ?></div>
                    <div class="stat-note">Need reordering soon</div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-label">🔄 Today's Transactions</div>
                    <div class="stat-number">
                        <?php 
                        $todayTrans = $conn->query("
                            SELECT COUNT(*) as count 
                            FROM stock_transactions 
                            WHERE DATE(created_at) = CURDATE()
                        ")->fetch_assoc();
                        echo $todayTrans['count'];
                        ?>
                    </div>
                    <div class="stat-note">So far today</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert success">
                <div>✅</div>
                <div><?= htmlspecialchars($message) ?></div>
            </div>
        <?php endif; ?>

        <!-- Main Transaction Form Card -->
        <div class="form-card">
            <div class="form-header">
                <div class="form-icon">📝</div>
                <div>
                    <h2>Record New Transaction</h2>
                    <p>Add stock purchases or record usage/wastage</p>
                </div>
            </div>
            
            <form method="POST" id="transactionForm">
                <div class="form-grid">
                    <!-- Item Selection -->
                    <div class="form-group">
                        <label>🔍 Select Item</label>
                        <select name="item_id" id="item_id" required onchange="updateStockInfo(this)">
                            <option value="">Choose an item...</option>
                            <?php
                            $items = $conn->query("SELECT * FROM items WHERE is_active = 1 ORDER BY item_name");
                            while ($i = $items->fetch_assoc()):
                                $isLow = $i['current_stock'] <= $i['reorder_level'];
                            ?>
                                <option value="<?= $i['id'] ?>" 
                                        data-stock="<?= $i['current_stock'] ?>"
                                        data-unit="<?= htmlspecialchars($i['unit_of_measure']) ?>">
                                    <?= htmlspecialchars($i['item_name']) ?> 
                                    (<?= $i['current_stock'] ?> <?= htmlspecialchars($i['unit_of_measure']) ?>)
                                    <?= $isLow ? '⚠️' : '' ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div id="stockInfo" class="stock-badge" style="display: none;">
                            <span>📊 Current Stock:</span>
                            <span id="currentStock"></span>
                        </div>
                    </div>

                    <!-- Transaction Type as Radio -->
                    <div class="form-group" style="grid-column: span 2;">
                        <label>📋 Transaction Type</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="type_usage" name="type" value="usage" required 
                                       onchange="updateType(this)" checked>
                                <label for="type_usage" class="radio-label">
                                    <span class="type-title">📉 Usage / Wastage</span>
                                    <span class="type-desc">Remove items from inventory</span>
                                </label>
                            </div>
                            
                            <div class="radio-option">
                                <input type="radio" id="type_purchase" name="type" value="purchase" required 
                                       onchange="updateType(this)">
                                <label for="type_purchase" class="radio-label">
                                    <span class="type-title">📈 Purchase / Restock</span>
                                    <span class="type-desc">Add new items to inventory</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity -->
                    <div class="form-group">
                        <label id="qtyLabel">🔢 Quantity (Usage)</label>
                        <input type="number" id="qty" name="qty" step="0.01" min="0.01" 
                               placeholder="Enter quantity" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <span>💾</span>
                        Record Transaction
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Transactions Section -->
        <?php
        $recent = $conn->query("
            SELECT t.*, i.item_name, i.unit_of_measure
            FROM stock_transactions t
            JOIN items i ON t.item_id = i.id
            ORDER BY t.created_at DESC
            LIMIT 10
        ");
        ?>
        
        <div class="recent-header">
            <h3>🕒 Recent Transactions</h3>
            <a href="transactions_history.php" class="view-all">
                <span>View All</span>
                <span>→</span>
            </a>
        </div>
        
        <?php if ($recent->num_rows > 0): ?>
            <div class="transactions-list">
                <?php while ($t = $recent->fetch_assoc()): 
                    $isUsage = $t['transaction_type'] === 'usage';
                ?>
                    <div class="transaction-item">
                        <div class="transaction-info">
                            <h4><?= htmlspecialchars($t['item_name']) ?></h4>
                            <div class="transaction-meta">
                                <span class="transaction-type">
                                    <span class="type-badge <?= $isUsage ? 'usage' : 'purchase' ?>"></span>
                                    <?= ucfirst($t['transaction_type']) ?>
                                </span>
                                <span class="transaction-date">
                                    <?= date('M d, Y • g:i A', strtotime($t['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="transaction-qty <?= $isUsage ? 'negative' : 'positive' ?>">
                            <?= $isUsage ? '−' : '+' ?><?= number_format($t['quantity'], 2) ?> 
                            <?= htmlspecialchars($t['unit_of_measure']) ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📭</div>
                <h4>No transactions yet</h4>
                <p>Record your first stock transaction using the form above</p>
            </div>
        <?php endif; ?>
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

        // Update transaction type label
        function updateType(radio) {
            const qtyLabel = document.getElementById('qtyLabel');
            qtyLabel.innerHTML = radio.value === 'usage' ? '🔢 Quantity (Usage)' : '🔢 Quantity (Purchase)';
        }

        // Update stock info when item is selected
        function updateStockInfo(select) {
            const stockInfo = document.getElementById('stockInfo');
            const currentStock = document.getElementById('currentStock');
            
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const stock = selectedOption.getAttribute('data-stock');
                const unit = selectedOption.getAttribute('data-unit');
                
                currentStock.textContent = parseFloat(stock).toFixed(2) + ' ' + unit;
                stockInfo.style.display = 'flex';
            } else {
                stockInfo.style.display = 'none';
            }
        }

        // Format quantity on blur
        document.getElementById('qty').addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });

        // Form validation
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            const itemId = document.getElementById('item_id').value;
            const qty = document.getElementById('qty').value;
            const type = document.querySelector('input[name="type"]:checked');
            
            if (!itemId || !qty || !type) {
                e.preventDefault();
                alert('Please fill in all required fields');
                return false;
            }
            
            if (parseFloat(qty) <= 0) {
                e.preventDefault();
                alert('Quantity must be greater than 0');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>