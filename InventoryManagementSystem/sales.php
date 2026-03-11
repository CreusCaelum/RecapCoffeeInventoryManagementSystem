<?php
include "config/db.php";
include "config/functions.php";
checkLogin();

$message = '';
$error = '';

/* Encode Sale */
if (isset($_POST['sell'])) {

    $recipe_id = $_POST['recipe_id'];
    $qtySold = $_POST['qty'];

    // Get BOM
    $bom = $conn->query("
        SELECT item_id, quantity, i.item_name, i.current_stock, i.unit_of_measure
        FROM recipe_items ri
        JOIN items i ON ri.item_id = i.id
        WHERE ri.recipe_id = $recipe_id
    ");

    $insufficientStock = false;
    $insufficientItems = [];

    // Check stock availability first
    while ($b = $bom->fetch_assoc()) {
        $consumeQty = $b['quantity'] * $qtySold;
        
        if ($b['current_stock'] < $consumeQty) {
            $insufficientStock = true;
            $insufficientItems[] = [
                'name' => $b['item_name'],
                'needed' => $consumeQty,
                'available' => $b['current_stock'],
                'unit' => $b['unit_of_measure']
            ];
        }
    }

    if ($insufficientStock) {
        $error = "Insufficient stock to complete the sale.";
    } else {
        // Reset pointer and process sale
        $bom->data_seek(0);
        
        while ($b = $bom->fetch_assoc()) {
            $consumeQty = $b['quantity'] * $qtySold;

            // Deduct stock
            $conn->query("
                UPDATE items
                SET current_stock = current_stock - $consumeQty
                WHERE id = {$b['item_id']}
            ");

            // Record transaction
            $conn->query("
                INSERT INTO stock_transactions
                (transaction_type, item_id, quantity, performed_by, status, notes)
                VALUES
                ('usage', {$b['item_id']}, $consumeQty, {$_SESSION['user_id']}, 'approved', 'Recipe consumption')
            ");
        }

        // Get recipe price for sale record
        $recipe = $conn->query("
            SELECT recipe_name, selling_price 
            FROM recipes 
            WHERE id = $recipe_id
        ")->fetch_assoc();

        $message = "Sale recorded successfully! " . $qtySold . " × " . $recipe['recipe_name'] . " sold.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Sale - Inventory System</title>
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
            color: var(--text);
            display: flex;
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

        /* Left Sidebar Navigation */
        .sidebar {
            width: 240px;
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.06));
            border-right: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(14px);
            box-shadow: 1px 0 20px rgba(0,0,0,0.20);
        }

        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-brand {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: .4px;
        }

        .sidebar-brand::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent1), var(--accent3));
            box-shadow: 0 10px 22px rgba(197,122,58,0.35);
            flex-shrink: 0;
        }

        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
            overflow-y: auto;
        }

        .nav-item {
            position: relative;
            padding: 0 16px;
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

        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
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

        .sidebar-footer {
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
            margin-left: 240px;
            max-width: calc(100% - 240px);
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .page-header p {
            color: var(--muted);
            font-size: 14px;
        }

        /* Alerts */
        .alert {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            font-size: 20px;
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

        /* Sale Form */
        .sale-form {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 40px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
            margin-bottom: 40px;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border);
        }

        .form-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--accent1), var(--accent2));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 12px 28px rgba(197,122,58,0.3);
        }

        .form-title {
            font-size: 22px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 4px;
        }

        .form-subtitle {
            color: var(--muted2);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 14px;
            font-size: 14px;
            transition: all 0.2s;
            color: var(--text);
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: var(--ring);
            background: rgba(0,0,0,0.35);
        }

        .form-group select option {
            background: var(--bg2);
            color: var(--text);
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .qty-btn {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            width: 44px;
            height: 44px;
            border-radius: 12px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
        }

        .qty-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
        }

        .qty-input {
            flex: 1;
            text-align: center;
            font-weight: 600;
        }

        /* Recipe Info Card */
        .recipe-info {
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            padding: 24px;
            margin: 24px 0;
            border: 1px solid var(--border);
            display: none;
        }

        .recipe-info.active {
            display: block;
        }

        .recipe-info h4 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .recipe-info h4 span {
            color: var(--accent3);
        }

        .ingredient-list {
            margin-bottom: 16px;
        }

        .ingredient-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }

        .ingredient-item:last-child {
            border-bottom: none;
        }

        .ingredient-name {
            color: var(--text);
            font-weight: 600;
        }

        .ingredient-qty {
            color: var(--muted);
        }

        .stock-status {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 30px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .stock-status.available {
            background: rgba(76,175,80,0.15);
            color: var(--success);
            border: 1px solid rgba(76,175,80,0.3);
        }

        .stock-status.low {
            background: rgba(255,152,0,0.15);
            color: var(--warning);
            border: 1px solid rgba(255,152,0,0.3);
        }

        .stock-status.unavailable {
            background: rgba(244,67,54,0.15);
            color: var(--danger);
            border: 1px solid rgba(244,67,54,0.3);
        }

        /* Price Display */
        .price-display {
            text-align: center;
            padding: 24px;
            background: rgba(0,0,0,0.2);
            border-radius: 20px;
            margin: 24px 0;
            border: 1px solid var(--border);
        }

        .price-label {
            font-size: 13px;
            color: var(--muted2);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .total-price {
            font-size: 42px;
            font-weight: 900;
            color: var(--accent3);
            line-height: 1;
            margin-bottom: 8px;
            text-shadow: 0 10px 20px rgba(197,122,58,0.2);
        }

        .unit-price {
            font-size: 14px;
            color: var(--muted2);
        }

        /* Stock Warning */
        .stock-warning {
            background: rgba(244,67,54,0.1);
            border: 1px solid rgba(244,67,54,0.3);
            border-radius: 16px;
            padding: 20px;
            margin: 24px 0;
        }

        .stock-warning h4 {
            font-size: 14px;
            font-weight: 800;
            color: var(--danger);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stock-warning-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(244,67,54,0.2);
            font-size: 13px;
        }

        .stock-warning-item:last-child {
            border-bottom: none;
        }

        .stock-warning-item span:first-child {
            color: var(--text);
        }

        .stock-warning-item span:last-child {
            color: var(--danger);
            font-weight: 600;
        }

        /* Form Actions */
        .form-actions {
            margin-top: 32px;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            border: none;
            padding: 16px 48px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 10px 25px rgba(197,122,58,0.3);
            min-width: 240px;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(197,122,58,0.4);
            filter: brightness(1.1);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 10px;
            cursor: pointer;
            flex-direction: column;
            gap: 4px;
            backdrop-filter: blur(10px);
        }

        .mobile-menu-btn span {
            width: 20px;
            height: 2px;
            background: var(--text);
            border-radius: 2px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                max-width: 100%;
                padding: 24px;
            }
            
            .mobile-menu-btn {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .sale-form {
                padding: 24px;
            }
            
            .quantity-control {
                flex-direction: column;
            }
            
            .qty-btn {
                width: 100%;
            }
            
            .total-price {
                font-size: 32px;
            }
            
            .form-header {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="noise"></div>
    
    <!-- MOBILE MENU BUTTON -->
    <div class="mobile-menu-btn" id="mobileMenuBtn">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- LEFT SIDEBAR NAVIGATION -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-brand">Inventory System</a>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-item">
                <a href="dashboard.php">Dashboard</a>
            </div>
            
            <div class="nav-item nav-dropdown">
                <a href="#" class="nav-dropdown-toggle">Stock</a>
                <div class="dropdown-menu">
                    <a href="transactions.php">Stock In / Out and Adjustments</a>
                </div>
            </div>
            
            <div class="nav-item nav-dropdown">
                <a href="#" class="nav-dropdown-toggle">Inventory</a>
                <div class="dropdown-menu">
                    <a href="items.php">Items and Categories</a>
                </div>
            </div>
            
            <div class="nav-item nav-dropdown">
                <a href="#" class="nav-dropdown-toggle">Suppliers</a>
                <div class="dropdown-menu">
                    <a href="supply.php">Suppliers and Purchase Orders</a>
                </div>
            </div>
            
            <div class="nav-item active">
                <a href="recipes.php">Recipes</a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="page-header">
            <h1>Record Sale</h1>
            <p>Sell recipes and automatically consume ingredients</p>
        </div>

        <?php if ($message): ?>
            <div class="alert success">
                <div class="alert-icon">✅</div>
                <div class="alert-content">
                    <div class="alert-title">Sale Successful</div>
                    <div class="alert-message"><?= htmlspecialchars($message) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error">
                <div class="alert-icon">⚠️</div>
                <div class="alert-content">
                    <div class="alert-title">Cannot Complete Sale</div>
                    <div class="alert-message"><?= htmlspecialchars($error) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- SALE FORM -->
        <div class="sale-form">
            <div class="form-header">
                <div class="form-icon">💰</div>
                <div>
                    <h3 class="form-title">Record New Sale</h3>
                    <p class="form-subtitle">Select a recipe and quantity to process the sale</p>
                </div>
            </div>
            
            <form method="POST" id="saleForm">
                <!-- Recipe Selection -->
                <div class="form-group">
                    <label for="recipe_id">Select Recipe</label>
                    <select name="recipe_id" id="recipe_id" required onchange="updateRecipeInfo()">
                        <option value="">Choose a recipe...</option>
                        <?php
                        $r = $conn->query("SELECT * FROM recipes WHERE is_active=1 ORDER BY recipe_name");
                        while ($row = $r->fetch_assoc()):
                        ?>
                            <option value="<?= $row['id'] ?>" 
                                    data-price="<?= $row['selling_price'] ?>"
                                    data-name="<?= htmlspecialchars($row['recipe_name']) ?>">
                                <?= htmlspecialchars($row['recipe_name']) ?> 
                                - ₱<?= number_format($row['selling_price'], 2) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Recipe Information (Dynamic) -->
                <div id="recipeInfo" class="recipe-info">
                    <!-- Content populated by JavaScript -->
                </div>

                <!-- Quantity -->
                <div class="form-group">
                    <label for="qty">Quantity</label>
                    <div class="quantity-control">
                        <button type="button" class="qty-btn" onclick="adjustQuantity(-1)">−</button>
                        <input type="number" id="qty" name="qty" value="1" min="1" step="1" 
                               class="qty-input" onchange="updateTotal()">
                        <button type="button" class="qty-btn" onclick="adjustQuantity(1)">+</button>
                    </div>
                </div>

                <!-- Total Price -->
                <div class="price-display">
                    <div class="price-label">Total Amount</div>
                    <div id="totalPrice" class="total-price">₱0.00</div>
                    <div id="unitPrice" class="unit-price">₱0.00 per unit</div>
                </div>

                <?php if (isset($insufficientItems) && !empty($insufficientItems)): ?>
                    <div class="stock-warning">
                        <h4>❌ Insufficient Stock</h4>
                        <?php foreach ($insufficientItems as $item): ?>
                            <div class="stock-warning-item">
                                <span><?= htmlspecialchars($item['name']) ?></span>
                                <span>
                                    Need: <?= $item['needed'] ?> <?= htmlspecialchars($item['unit']) ?> 
                                    (Available: <?= $item['available'] ?>)
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" name="sell" class="btn-primary" id="sellBtn">
                        Complete Sale
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('active');
            });
            
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 1024 && 
                    !sidebar.contains(e.target) && 
                    !mobileMenuBtn.contains(e.target) && 
                    sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        }
        
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024 && sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });

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

        // Quantity controls
        function adjustQuantity(change) {
            const qtyInput = document.getElementById('qty');
            let currentQty = parseInt(qtyInput.value) || 1;
            let newQty = currentQty + change;
            
            if (newQty < 1) newQty = 1;
            
            qtyInput.value = newQty;
            updateTotal();
        }

        // Update total price
        function updateTotal() {
            const recipeSelect = document.getElementById('recipe_id');
            const qtyInput = document.getElementById('qty');
            const totalPrice = document.getElementById('totalPrice');
            const unitPrice = document.getElementById('unitPrice');
            
            if (recipeSelect.value) {
                const selectedOption = recipeSelect.options[recipeSelect.selectedIndex];
                const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const qty = parseInt(qtyInput.value) || 1;
                const total = price * qty;
                
                totalPrice.textContent = '₱' + total.toFixed(2);
                unitPrice.textContent = '₱' + price.toFixed(2) + ' per unit';
            } else {
                totalPrice.textContent = '₱0.00';
                unitPrice.textContent = '₱0.00 per unit';
            }
        }

        // Update recipe info
        function updateRecipeInfo() {
            const recipeSelect = document.getElementById('recipe_id');
            const recipeInfo = document.getElementById('recipeInfo');
            
            if (recipeSelect.value) {
                recipeInfo.innerHTML = '<p style="text-align: center; color: var(--muted2);">Loading recipe details...</p>';
                recipeInfo.classList.add('active');
                
                // In a real implementation, fetch actual ingredients via AJAX
                setTimeout(() => {
                    recipeInfo.innerHTML = `
                        <h4><span>📋</span> Ingredients Required</h4>
                        <div class="ingredient-list">
                            <div class="ingredient-item">
                                <span class="ingredient-name">Milk</span>
                                <span class="ingredient-qty">200 ml</span>
                                <span class="stock-status available">In Stock</span>
                            </div>
                            <div class="ingredient-item">
                                <span class="ingredient-name">Coffee Beans</span>
                                <span class="ingredient-qty">50 g</span>
                                <span class="stock-status available">In Stock</span>
                            </div>
                            <div class="ingredient-item">
                                <span class="ingredient-name">Sugar</span>
                                <span class="ingredient-qty">15 g</span>
                                <span class="stock-status low">Low Stock</span>
                            </div>
                        </div>
                        <p style="font-size: 12px; color: var(--muted2); text-align: center;">
                            Stock levels will be checked before completing sale
                        </p>
                    `;
                }, 500);
            } else {
                recipeInfo.classList.remove('active');
            }
            
            updateTotal();
        }

        // Form validation
        document.getElementById('saleForm').addEventListener('submit', function(e) {
            const recipeSelect = document.getElementById('recipe_id');
            const qtyInput = document.getElementById('qty');
            
            if (!recipeSelect.value) {
                e.preventDefault();
                alert('Please select a recipe');
                recipeSelect.focus();
                return false;
            }
            
            if (!qtyInput.value || parseInt(qtyInput.value) < 1) {
                e.preventDefault();
                alert('Please enter a valid quantity');
                qtyInput.focus();
                return false;
            }
            
            const selectedOption = recipeSelect.options[recipeSelect.selectedIndex];
            const recipeName = selectedOption.getAttribute('data-name');
            const qty = qtyInput.value;
            
            const confirmMessage = `Are you sure you want to record the sale of ${qty} × ${recipeName}?\n\nThis will automatically consume the required ingredients from stock.`;
            
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateTotal();
            
            // Auto-select first recipe if available
            const recipeSelect = document.getElementById('recipe_id');
            if (recipeSelect.options.length === 2) { // 1 option + placeholder
                recipeSelect.selectedIndex = 1;
                updateRecipeInfo();
            }
        });
    </script>
</body>
</html>