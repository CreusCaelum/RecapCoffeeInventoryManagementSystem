<?php
include "config/db.php";
include "config/functions.php";

checkLogin();

$items = getAllItems($conn);

// Handle inline updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $field = $_POST['field'];
    $value = $_POST['value'];
    
    // Validate and sanitize
    $allowed_fields = ['item_name', 'item_code', 'unit_of_measure', 'reorder_level'];
    if (in_array($field, $allowed_fields)) {
        $stmt = $conn->prepare("UPDATE items SET $field = ? WHERE id = ?");
        if ($field === 'reorder_level') {
            $stmt->bind_param("di", $value, $item_id);
        } else {
            $stmt->bind_param("si", $value, $item_id);
        }
        $stmt->execute();
        
        // Return JSON response for AJAX
        echo json_encode(['success' => true]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Inventory System</title>
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

        /* Left Navigation Bar */
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

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .header-left h1 {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .header-left p {
            color: var(--muted2);
            font-size: 15px;
        }

        .add-product-btn {
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .add-product-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(197,122,58,0.4);
        }

        /* Stats Row */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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

        /* Search and Filter Bar */
        .search-bar {
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 32px;
            backdrop-filter: blur(10px);
        }

        .search-container {
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 14px 18px;
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 14px;
            font-size: 14px;
            transition: all 0.2s;
            color: var(--text);
        }

        .search-input::placeholder {
            color: var(--muted2);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: var(--ring);
            background: rgba(0,0,0,0.35);
        }

        .filter-select {
            padding: 14px 18px;
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 14px;
            font-size: 14px;
            color: var(--text);
            min-width: 180px;
            cursor: pointer;
        }

        .filter-select option {
            background: var(--bg2);
            color: var(--text);
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        /* Product Card */
        .product-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow2);
            border-color: rgba(242,192,137,0.3);
        }

        .product-card.low-stock {
            border-left: 4px solid var(--warning);
        }

        .product-card.out-of-stock {
            border-left: 4px solid var(--danger);
        }

        .product-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            background: rgba(0,0,0,0.15);
            position: relative;
        }

        .product-code {
            font-size: 12px;
            font-weight: 600;
            color: var(--accent3);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .product-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
            padding: 6px 10px;
            border-radius: 8px;
            border: 1px solid transparent;
            margin-left: -10px;
        }

        .product-name:hover {
            background: rgba(255,255,255,0.05);
            border-color: var(--border);
        }

        .product-name.editing {
            background: rgba(0,0,0,0.3);
            border-color: var(--accent1);
        }

        .low-stock-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, var(--warning), #ff5722);
            color: #1a1a1a;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(255,152,0,0.3);
        }

        .product-card-body {
            padding: 24px;
        }

        .product-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            padding: 8px 12px;
            border-radius: 10px;
            transition: background 0.2s;
        }

        .product-detail.editable {
            cursor: pointer;
        }

        .product-detail.editable:hover {
            background: rgba(255,255,255,0.05);
        }

        .product-detail.editing {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--accent1);
            margin: -1px;
        }

        .detail-label {
            font-size: 13px;
            color: var(--muted2);
            font-weight: 600;
        }

        .detail-value {
            font-size: 14px;
            color: var(--text);
            font-weight: 700;
            min-width: 120px;
            text-align: right;
        }

        .detail-input {
            width: 100%;
            padding: 8px 12px;
            background: rgba(0,0,0,0.4);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            color: var(--text);
            text-align: right;
        }

        .detail-input:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: 0 0 0 2px rgba(197,122,58,0.2);
        }

        .stock-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0,0,0,0.2);
            padding: 16px 20px;
            border-radius: 14px;
            margin-top: 12px;
            border: 1px solid var(--border);
        }

        .stock-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--text);
        }

        .stock-label {
            font-size: 12px;
            color: var(--muted2);
        }

        .stock-warning {
            color: var(--warning);
            font-size: 12px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Edit Controls */
        .edit-controls {
            display: none;
            gap: 8px;
            margin-top: 16px;
            justify-content: flex-end;
        }

        .edit-controls.visible {
            display: flex;
        }

        .edit-btn {
            padding: 8px 16px;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s;
        }

        .edit-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
        }

        .edit-btn.save-btn {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            border: none;
        }

        .edit-btn.save-btn:hover {
            filter: brightness(1.1);
        }

        .product-card-footer {
            padding: 0 24px 24px;
            display: flex;
            gap: 12px;
        }

        .card-btn {
            flex: 1;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
            border: none;
        }

        .card-btn.primary {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            box-shadow: 0 8px 20px rgba(197,122,58,0.2);
        }

        .card-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(197,122,58,0.3);
        }

        .card-btn.secondary {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            color: var(--text);
        }

        .card-btn.secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 30px;
            right: 30px;
            background: linear-gradient(180deg, rgba(255,255,255,0.15), rgba(255,255,255,0.08));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 24px;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow2);
            display: flex;
            align-items: center;
            gap: 12px;
            transform: translateX(120%);
            transition: transform 0.3s ease;
            z-index: 10000;
        }

        .notification.visible {
            transform: translateX(0);
        }

        .notification.success {
            border-color: rgba(76,175,80,0.3);
            background: rgba(76,175,80,0.15);
        }

        .notification.error {
            border-color: rgba(244,67,54,0.3);
            background: rgba(244,67,54,0.15);
        }

        /* Empty State */
        .empty-state {
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 80px 40px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .empty-state h3 {
            font-size: 24px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 12px;
        }

        .empty-state p {
            color: var(--muted2);
            font-size: 15px;
            margin-bottom: 24px;
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
            
            .nav-actions {
                display: none;
            }
            
            .main-content {
                padding: 24px;
            }
            
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .search-container {
                flex-direction: column;
            }
            
            .search-input,
            .filter-select {
                width: 100%;
            }
            
            .page-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }
            
            .notification {
                left: 20px;
                right: 20px;
                top: 20px;
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
            <div class="nav-item">
                <a href="dashboard.php">Dashboard</a>
            </div>
            
            <div class="nav-item nav-dropdown">
                <div class="nav-dropdown-toggle">Stock</div>
                <div class="dropdown-menu">
                    <a href="transactions.php">Stock In / Out and Adjustments</a>
                </div>
            </div>
            
            <div class="nav-item active">
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

    <!-- Notification -->
    <div id="notification" class="notification">
        <span id="notificationIcon" class="notification-icon">✅</span>
        <span id="notificationMessage">Update successful</span>
    </div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>Products</h1>
                <p>Manage your inventory items and stock levels</p>
            </div>
            <a href="add_item.php" class="add-product-btn">
                <span>+</span>
                Add New Product
            </a>
        </div>

        <!-- Quick Stats -->
        <?php
        $totalProducts = $conn->query("SELECT COUNT(*) as count FROM items WHERE is_active = 1")->fetch_assoc()['count'];
        $totalCategories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
        $avgStock = $conn->query("SELECT AVG(current_stock) as avg FROM items WHERE is_active = 1")->fetch_assoc()['avg'];
        ?>
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-label">📦 Total Products</div>
                <div class="stat-number"><?= $totalProducts ?></div>
                <div class="stat-note">Active inventory items</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">📁 Categories</div>
                <div class="stat-number"><?= $totalCategories ?></div>
                <div class="stat-note">Product categories</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">📊 Avg. Stock Level</div>
                <div class="stat-number"><?= number_format($avgStock ?? 0, 1) ?></div>
                <div class="stat-note">Per item average</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">⚠️ Low Stock Items</div>
                <div class="stat-number"><?= $lowStock ?? 0 ?></div>
                <div class="stat-note">Need attention</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="search-bar">
            <div class="search-container">
                <input type="text" 
                       placeholder="🔍 Search products by name or code..." 
                       class="search-input"
                       id="searchInput">
                
                <select class="filter-select" id="stockFilter">
                    <option value="">All Stock Status</option>
                    <option value="low">Low Stock Only</option>
                    <option value="out">Out of Stock</option>
                    <option value="normal">Normal Stock</option>
                </select>
                
                <select class="filter-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php
                    $categories = $conn->query("SELECT * FROM categories ORDER BY category_name");
                    while($cat = $categories->fetch_assoc()):
                    ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if ($items->num_rows > 0): ?>
            <div class="products-grid" id="productsGrid">
                <?php while ($row = $items->fetch_assoc()):
                    $isLowStock = $row['current_stock'] <= $row['reorder_level'] && $row['current_stock'] > 0;
                    $isOutStock = $row['current_stock'] == 0;
                ?>
                <div class="product-card <?= $isLowStock ? 'low-stock' : '' ?> <?= $isOutStock ? 'out-of-stock' : '' ?>" 
                     data-item-id="<?= $row['id'] ?>"
                     data-stock="<?= $row['current_stock'] ?>" 
                     data-reorder="<?= $row['reorder_level'] ?>"
                     data-category="<?= $row['category_id'] ?>">
                    
                    <div class="product-card-header">
                        <div class="product-code">
                            <?= htmlspecialchars($row['item_code']) ?>
                        </div>
                        <div class="product-name editable" 
                             data-field="item_name"
                             data-value="<?= htmlspecialchars($row['item_name']) ?>">
                            <?= htmlspecialchars($row['item_name']) ?>
                        </div>
                        
                        <?php if ($isLowStock): ?>
                            <div class="low-stock-badge">Low Stock</div>
                        <?php elseif ($isOutStock): ?>
                            <div class="low-stock-badge" style="background: linear-gradient(135deg, var(--danger), #d32f2f);">Out of Stock</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-card-body">
                        <div class="product-detail">
                            <span class="detail-label">Category</span>
                            <span class="detail-value"><?= htmlspecialchars($row['category_name'] ?? 'Uncategorized') ?></span>
                        </div>
                        
                        <div class="product-detail editable" 
                             data-field="unit_of_measure"
                             data-value="<?= htmlspecialchars($row['unit_of_measure']) ?>">
                            <span class="detail-label">Unit of Measure</span>
                            <span class="detail-value"><?= htmlspecialchars($row['unit_of_measure']) ?></span>
                        </div>
                        
                        <div class="stock-info">
                            <div>
                                <div class="stock-value"><?= number_format($row['current_stock'], 2) ?></div>
                                <div class="stock-label">Current Stock</div>
                                <?php if ($isLowStock): ?>
                                    <div class="stock-warning">
                                        <span>⚠️</span> Below reorder level
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <div class="stock-value" style="font-size: 20px; color: var(--accent3);">
                                    <span class="editable" 
                                          data-field="reorder_level"
                                          data-value="<?= $row['reorder_level'] ?>">
                                        <?= $row['reorder_level'] ?>
                                    </span>
                                </div>
                                <div class="stock-label">Reorder Level</div>
                            </div>
                        </div>

                        <!-- Edit Controls -->
                        <div class="edit-controls" id="editControls_<?= $row['id'] ?>">
                            <button class="edit-btn cancel-btn">Cancel</button>
                            <button class="edit-btn save-btn" data-item-id="<?= $row['id'] ?>">Save Changes</button>
                        </div>
                    </div>
                    
                    <div class="product-card-footer">
                        <a href="item_history.php?item_id=<?= $row['id'] ?>" 
                           class="card-btn primary">
                            📊 View History
                        </a>
                        <button class="card-btn secondary edit-trigger-btn" 
                                data-item-id="<?= $row['id'] ?>">
                            ✏️ Edit
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div style="font-size: 64px; margin-bottom: 20px;">📦</div>
                <h3>No Products Found</h3>
                <p>Get started by adding your first product to the inventory.</p>
                <a href="add_item.php" class="add-product-btn" style="display: inline-flex;">
                    + Add Your First Product
                </a>
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

        // Search and Filter functionality
        const searchInput = document.getElementById('searchInput');
        const stockFilter = document.getElementById('stockFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        
        function filterProducts() {
            const searchTerm = searchInput.value.toLowerCase();
            const stockValue = stockFilter.value;
            const categoryValue = categoryFilter.value;
            const productCards = document.querySelectorAll('.product-card');
            
            productCards.forEach(card => {
                let showCard = true;
                
                // Search filter
                if (searchTerm) {
                    const productName = card.querySelector('.product-name').textContent.toLowerCase();
                    const productCode = card.querySelector('.product-code').textContent.toLowerCase();
                    if (!productName.includes(searchTerm) && !productCode.includes(searchTerm)) {
                        showCard = false;
                    }
                }
                
                // Stock filter
                if (stockValue && showCard) {
                    const isLowStock = card.classList.contains('low-stock');
                    const isOutStock = card.classList.contains('out-of-stock');
                    
                    if (stockValue === 'low' && !isLowStock) showCard = false;
                    if (stockValue === 'out' && !isOutStock) showCard = false;
                    if (stockValue === 'normal' && (isLowStock || isOutStock)) showCard = false;
                }
                
                // Category filter
                if (categoryValue && showCard) {
                    const cardCategory = card.dataset.category;
                    if (cardCategory !== categoryValue) showCard = false;
                }
                
                card.style.display = showCard ? 'block' : 'none';
            });
        }
        
        searchInput.addEventListener('input', filterProducts);
        stockFilter.addEventListener('change', filterProducts);
        categoryFilter.addEventListener('change', filterProducts);

        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.getElementById('notification');
            const notificationIcon = document.getElementById('notificationIcon');
            const notificationMessage = document.getElementById('notificationMessage');
            
            notification.className = `notification ${type} visible`;
            notificationMessage.textContent = message;
            notificationIcon.textContent = type === 'success' ? '✅' : '⚠️';
            
            setTimeout(() => {
                notification.classList.remove('visible');
            }, 3000);
        }

        // Inline editing functionality
        let currentEditField = null;
        let originalValue = null;

        function toggleEditMode(element, isEditing) {
            if (isEditing) {
                element.classList.add('editing');
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'detail-input';
                input.value = element.dataset.value || element.textContent.trim();
                element.textContent = '';
                element.appendChild(input);
                input.focus();
                input.select();
            } else {
                element.classList.remove('editing');
                const input = element.querySelector('input');
                if (input) {
                    element.textContent = input.value || element.dataset.value;
                }
            }
        }

        // Handle clicks on editable elements
        document.addEventListener('click', function(e) {
            const editableElement = e.target.closest('.editable');
            const editTriggerBtn = e.target.closest('.edit-trigger-btn');
            const saveBtn = e.target.closest('.save-btn');
            const cancelBtn = e.target.closest('.cancel-btn');
            
            if (editableElement && !editableElement.classList.contains('editing')) {
                if (currentEditField && currentEditField !== editableElement) {
                    toggleEditMode(currentEditField, false);
                }
                
                currentEditField = editableElement;
                originalValue = editableElement.textContent;
                toggleEditMode(editableElement, true);
                
                const itemId = editableElement.closest('.product-card').dataset.itemId;
                const editControls = document.getElementById(`editControls_${itemId}`);
                if (editControls) {
                    editControls.classList.add('visible');
                }
            }
            
            if (editTriggerBtn) {
                const itemId = editTriggerBtn.dataset.itemId;
                const card = editTriggerBtn.closest('.product-card');
                const editableField = card.querySelector('.editable');
                
                if (editableField) {
                    currentEditField = editableField;
                    originalValue = editableField.textContent;
                    toggleEditMode(editableField, true);
                    
                    const editControls = document.getElementById(`editControls_${itemId}`);
                    if (editControls) {
                        editControls.classList.add('visible');
                    }
                }
            }
            
            if (saveBtn) {
                const itemId = saveBtn.dataset.itemId;
                const card = document.querySelector(`[data-item-id="${itemId}"]`);
                const editingField = card.querySelector('.editing');
                
                if (editingField) {
                    const input = editingField.querySelector('input');
                    const newValue = input ? input.value.trim() : '';
                    const field = editingField.dataset.field;
                    
                    if (newValue && newValue !== originalValue) {
                        updateItem(itemId, field, newValue, editingField);
                    } else {
                        toggleEditMode(editingField, false);
                    }
                    
                    const editControls = document.getElementById(`editControls_${itemId}`);
                    if (editControls) {
                        editControls.classList.remove('visible');
                    }
                }
            }
            
            if (cancelBtn) {
                const editControls = cancelBtn.closest('.edit-controls');
                const itemId = editControls.id.split('_')[1];
                const card = document.querySelector(`[data-item-id="${itemId}"]`);
                const editingField = card.querySelector('.editing');
                
                if (editingField) {
                    toggleEditMode(editingField, false);
                }
                
                editControls.classList.remove('visible');
                currentEditField = null;
            }
        });

        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (currentEditField && currentEditField.classList.contains('editing')) {
                const input = currentEditField.querySelector('input');
                if (!input) return;
                
                if (e.key === 'Enter') {
                    const card = currentEditField.closest('.product-card');
                    const itemId = card.dataset.itemId;
                    const newValue = input.value.trim();
                    const field = currentEditField.dataset.field;
                    
                    if (newValue && newValue !== originalValue) {
                        updateItem(itemId, field, newValue, currentEditField);
                    } else {
                        toggleEditMode(currentEditField, false);
                    }
                    
                    const editControls = document.getElementById(`editControls_${itemId}`);
                    if (editControls) {
                        editControls.classList.remove('visible');
                    }
                } else if (e.key === 'Escape') {
                    toggleEditMode(currentEditField, false);
                    
                    const card = currentEditField.closest('.product-card');
                    if (card) {
                        const itemId = card.dataset.itemId;
                        const editControls = document.getElementById(`editControls_${itemId}`);
                        if (editControls) {
                            editControls.classList.remove('visible');
                        }
                    }
                    currentEditField = null;
                }
            }
        });

        // Click outside to cancel
        document.addEventListener('click', function(e) {
            if (currentEditField && 
                !e.target.closest('.editable') && 
                !e.target.closest('.edit-controls') &&
                !e.target.closest('.edit-trigger-btn')) {
                
                const card = currentEditField.closest('.product-card');
                if (card) {
                    const itemId = card.dataset.itemId;
                    
                    toggleEditMode(currentEditField, false);
                    currentEditField = null;
                    
                    const editControls = document.getElementById(`editControls_${itemId}`);
                    if (editControls) {
                        editControls.classList.remove('visible');
                    }
                }
            }
        });

        // Update item via AJAX
        async function updateItem(itemId, field, value, element) {
            try {
                const formData = new FormData();
                formData.append('update_item', true);
                formData.append('item_id', itemId);
                formData.append('field', field);
                formData.append('value', value);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    element.textContent = value;
                    element.dataset.value = value;
                    
                    // Update low stock status if needed
                    if (field === 'reorder_level') {
                        const card = element.closest('.product-card');
                        const currentStock = parseFloat(card.dataset.stock);
                        const reorderLevel = parseFloat(value);
                        
                        if (currentStock <= reorderLevel && currentStock > 0) {
                            card.classList.add('low-stock');
                            card.classList.remove('out-of-stock');
                            
                            if (!card.querySelector('.low-stock-badge')) {
                                const header = card.querySelector('.product-card-header');
                                const badge = document.createElement('div');
                                badge.className = 'low-stock-badge';
                                badge.textContent = 'Low Stock';
                                header.appendChild(badge);
                            }
                        } else {
                            card.classList.remove('low-stock');
                            const badge = card.querySelector('.low-stock-badge');
                            if (badge) {
                                badge.remove();
                            }
                        }
                    }
                    
                    showNotification('Item updated successfully');
                    toggleEditMode(element, false);
                    currentEditField = null;
                } else {
                    throw new Error('Update failed');
                }
            } catch (error) {
                console.error('Error updating item:', error);
                showNotification('Failed to update item', 'error');
                toggleEditMode(element, false);
            }
        }
    </script>
</body>
</html>