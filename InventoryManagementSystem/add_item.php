<?php
include "config/db.php";
include "config/functions.php";

checkLogin();

$message = '';

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name");

if ($_POST) {
    // Validate and sanitize inputs
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);
    $unit = trim($_POST['unit']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');
    
    // Add item with default stock = 0
    addItem(
        $conn,
        $code,
        $name,
        0, // default stock
        $unit,
        
  
    );

    $message = 'Item added successfully';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Item - Inventory System</title>
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

        /* Left Navigation Bar - Exact copy from other pages */
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
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 32px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--muted2);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 16px;
            transition: all 0.2s;
            padding: 6px 12px;
            border-radius: 30px;
            background: rgba(0,0,0,0.2);
            border: 1px solid var(--border);
        }

        .back-link:hover {
            color: var(--text);
            background: rgba(255,255,255,0.05);
            border-color: var(--accent1);
            transform: translateX(-2px);
        }

        .page-title {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }

        .page-subtitle {
            color: var(--muted2);
            font-size: 15px;
        }

        /* Alert Message */
        .alert {
            background: linear-gradient(180deg, rgba(76,175,80,0.15), rgba(76,175,80,0.08));
            border: 1px solid rgba(76,175,80,0.3);
            border-radius: 16px;
            padding: 18px 24px;
            margin-bottom: 32px;
            display: flex;
            align-items: center;
            gap: 14px;
            backdrop-filter: blur(10px);
            animation: slideDown 0.3s ease;
        }

        .alert.hidden {
            display: none;
        }

        .alert-icon {
            font-size: 24px;
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

        /* Add Item Form - Centered with table-like alignment */
        .form-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
        }

        .add-item-form {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 40px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
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

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--muted);
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .form-group label.required::after {
            content: " *";
            color: var(--accent1);
            font-size: 16px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 14px;
            font-size: 14px;
            transition: all 0.2s;
            color: var(--text);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: var(--ring);
            background: rgba(0,0,0,0.35);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--muted2);
            opacity: 0.5;
        }

        .form-group select {
            cursor: pointer;
        }

        .form-group select option {
            background: var(--bg2);
            color: var(--text);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .input-hint {
            margin-top: 6px;
            font-size: 12px;
            color: var(--muted2);
        }

        /* Input with icon */
        .input-icon-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted2);
            font-size: 16px;
        }

        .input-icon-wrapper input,
        .input-icon-wrapper select {
            padding-left: 45px;
        }

        /* Section Divider */
        .section-divider {
            margin: 24px 0 16px;
        }

        .section-divider h4 {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-divider h4 span {
            color: var(--accent3);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
            margin-top: 32px;
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
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 160px;
            justify-content: center;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(197,122,58,0.4);
            filter: brightness(1.1);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn-secondary {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 14px 36px;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
            min-width: 160px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent1);
            color: #fff;
            transform: translateY(-1px);
        }

        /* Loading State */
        .loading {
            display: inline-block;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(0,0,0,0.2);
            border-radius: 50%;
            border-top-color: #1a1a1a;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
            body {
                flex-direction: column;
            }
            
            .side-nav {
                width: 100%;
                height: auto;
                position: fixed;
                padding: 16px 0;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 2000;
            }
            
            .side-nav.active {
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
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
                display: block;
            }
        }

        @media (max-width: 768px) {
            .add-item-form {
                padding: 24px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary,
            .btn-secondary {
                width: 100%;
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

    <!-- LEFT NAVIGATION BAR - Exact copy from other pages -->
    <nav class="side-nav" id="sideNav">
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
                    <a href="recipes.php">Recipes and Consumption</a>
                </div>
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
            <a href="items.php" class="back-link">
                ← Back to Items
            </a>
            <h1 class="page-title">Add New Item</h1>
            <p class="page-subtitle">Add a new product to your inventory catalog</p>
        </div>

        <?php if ($message): ?>
            <div class="alert" id="successAlert">
                <div class="alert-icon">✅</div>
                <div class="alert-content">
                    <div class="alert-title">Success!</div>
                    <div class="alert-message"><?= htmlspecialchars($message) ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Container for centering -->
        <div class="form-container">
            <!-- Add Item Form -->
            <div class="add-item-form">
                <div class="form-header">
                    <div class="form-icon">📦</div>
                    <div>
                        <h3 class="form-title">Item Details</h3>
                        <p class="form-subtitle">Enter the basic information for the new item</p>
                    </div>
                </div>
                
                <form method="POST" id="itemForm">
                    <!-- Basic Information -->
                    <div class="section-divider">
                        <h4><span>📋</span> Basic Information</h4>
                    </div>
                    
                    <div class="form-grid">
                        <!-- Item Code -->
                        <div class="form-group">
                            <label for="code" class="required">Item Code</label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">🔑</span>
                                <input type="text" id="code" name="code" 
                                       placeholder="e.g., ABC-001" 
                                       required
                                       maxlength="20"
                                       autocomplete="off">
                            </div>
                            <div class="input-hint">Unique identifier for the item</div>
                        </div>

                        <!-- Item Name -->
                        <div class="form-group">
                            <label for="name" class="required">Item Name</label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">🏷️</span>
                                <input type="text" id="name" name="name" 
                                       placeholder="e.g., Premium Coffee Beans" 
                                       required
                                       maxlength="100"
                                       autocomplete="off">
                            </div>
                            <div class="input-hint">Descriptive name of the item</div>
                        </div>

                        <!-- Category -->
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">📁</span>
                                <select id="category_id" name="category_id">
                                    <option value="">Select a category</option>
                                    <?php while($cat = $categories->fetch_assoc()): ?>
                                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="input-hint">Organize items by category</div>
                        </div>

                        <!-- Unit of Measure -->
                        <div class="form-group">
                            <label for="unit" class="required">Unit of Measure</label>
                            <div class="input-icon-wrapper">
                                <span class="input-icon">⚖️</span>
                                <input type="text" id="unit" name="unit" 
                                       placeholder="e.g., kg, liter, piece" 
                                       required
                                       maxlength="20"
                                       autocomplete="off">
                            </div>
                            <div class="input-hint">Measurement unit (kg, g, L, ml, pcs, etc.)</div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="section-divider">
                        <h4><span>📝</span> Additional Information</h4>
                    </div>

                    <!-- Description -->
                    <div class="form-group full-width">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" 
                                  placeholder="Enter item description, specifications, or notes..."></textarea>
                        <div class="input-hint">Optional details about the item</div>
                    </div>

                    <!-- Note about stock -->
                    <div style="background: rgba(197,122,58,0.1); border: 1px solid rgba(197,122,58,0.2); border-radius: 12px; padding: 16px; margin: 16px 0; display: flex; align-items: center; gap: 12px;">
                        <span style="font-size: 20px;">ℹ️</span>
                        <div style="font-size: 13px; color: var(--muted);">
                            <strong style="color: var(--accent3);">Note:</strong> New items are added with zero stock. Use the <strong>Stock In</strong> transaction to add initial inventory.
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="items.php" class="btn-secondary">Cancel</a>
                        <button type="submit" class="btn-primary" id="submitBtn">
                            <span id="btnText">Add Item</span>
                            <span id="btnLoading" class="loading" style="display: none;"></span>
                        </button>
                    </div>
                </form>
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

        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sideNav = document.getElementById('sideNav');
        
        if (mobileMenuBtn && sideNav) {
            mobileMenuBtn.addEventListener('click', () => {
                sideNav.classList.toggle('active');
            });
            
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 1024 && 
                    !sideNav.contains(e.target) && 
                    !mobileMenuBtn.contains(e.target) && 
                    sideNav.classList.contains('active')) {
                    sideNav.classList.remove('active');
                }
            });
        }
        
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024 && sideNav.classList.contains('active')) {
                sideNav.classList.remove('active');
            }
        });

        // Form submission
        const itemForm = document.getElementById('itemForm');
        const submitBtn = document.getElementById('submitBtn');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        const successAlert = document.getElementById('successAlert');

        itemForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateForm()) {
                return false;
            }
            
            // Show loading state
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';
            submitBtn.disabled = true;
            
            // Hide previous success message
            if (successAlert) {
                successAlert.classList.add('hidden');
            }
            
            // Submit the form
            this.submit();
        });

        function validateForm() {
            const code = document.getElementById('code').value.trim();
            const name = document.getElementById('name').value.trim();
            const unit = document.getElementById('unit').value.trim();
            
            // Required fields validation
            if (!code) {
                showError('Please enter an item code');
                document.getElementById('code').focus();
                return false;
            }
            
            if (!name) {
                showError('Please enter an item name');
                document.getElementById('name').focus();
                return false;
            }
            
            if (!unit) {
                showError('Please enter a unit of measure');
                document.getElementById('unit').focus();
                return false;
            }
            
            // Code format validation
            if (!/^[A-Za-z0-9\-_]+$/.test(code)) {
                showError('Item code can only contain letters, numbers, hyphens, and underscores');
                document.getElementById('code').focus();
                return false;
            }
            
            return true;
        }

        function showError(message) {
            // Create or use existing error alert
            let errorAlert = document.querySelector('.alert.error');
            if (!errorAlert) {
                errorAlert = document.createElement('div');
                errorAlert.className = 'alert error';
                errorAlert.innerHTML = `
                    <div class="alert-icon">⚠️</div>
                    <div class="alert-content">
                        <div class="alert-title">Error</div>
                        <div class="alert-message"></div>
                    </div>
                `;
                document.querySelector('.main-content').insertBefore(errorAlert, document.querySelector('.form-container'));
            }
            
            errorAlert.classList.remove('hidden');
            errorAlert.querySelector('.alert-message').textContent = message;
            
            setTimeout(() => {
                errorAlert.classList.add('hidden');
            }, 5000);
        }

        // Auto-generate item code from name
        document.getElementById('name').addEventListener('blur', function() {
            const nameInput = this.value.trim();
            const codeInput = document.getElementById('code');
            
            if (nameInput && !codeInput.value.trim()) {
                // Create a simple code from name
                const words = nameInput.split(' ');
                let prefix = '';
                
                if (words.length > 1) {
                    // Take first letter of first two words
                    prefix = (words[0][0] + words[1][0]).toUpperCase();
                } else {
                    // Take first 3 letters of single word
                    prefix = nameInput.substring(0, 3).toUpperCase();
                }
                
                const randomNum = Math.floor(Math.random() * 1000);
                codeInput.value = prefix + '-' + randomNum.toString().padStart(3, '0');
            }
        });

        // Auto-capitalize unit of measure
        document.getElementById('unit').addEventListener('blur', function() {
            if (this.value) {
                this.value = this.value.toLowerCase();
            }
        });

        // Focus on first field
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.getElementById('code');
            if (!codeInput.value) {
                codeInput.focus();
            }
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Auto-hide success alert after 5 seconds
        if (successAlert) {
            setTimeout(() => {
                successAlert.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>
</html>