<?php
include "config/db.php";
if (!isset($_SESSION['user_id'])) header("Location: index.php");

/* Add Recipe */
if (isset($_POST['add_recipe'])) {
    $stmt = $conn->prepare("
        INSERT INTO recipes (recipe_name, description, selling_price)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("ssd", $_POST['name'], $_POST['desc'], $_POST['price']);
    $stmt->execute();
}

/* Add BOM Item */
if (isset($_POST['add_bom'])) {
    $stmt = $conn->prepare("
        INSERT INTO recipe_items (recipe_id, item_id, quantity)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iid", $_POST['recipe_id'], $_POST['item_id'], $_POST['qty']);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recipes - Inventory System</title>
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

        /* Add Recipe Form */
        .form-container {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.06));
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 32px;
            margin-bottom: 40px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
        }

        .form-title {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 24px;
            color: var(--text);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            color: rgba(244,244,246,0.82);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
            background: rgba(0,0,0,0.22);
            color: var(--text);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: rgba(242,192,137,0.55);
            box-shadow: var(--ring);
            background: rgba(0,0,0,0.28);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--muted2);
            opacity: 0.5;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent3), var(--accent1), var(--accent2));
            color: #1a1a1a;
            border: none;
            padding: 12px 28px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 900;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 18px 35px rgba(197,122,58,0.20);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 48px rgba(197,122,58,0.28);
            filter: brightness(1.03);
        }

        .btn-secondary {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent1);
        }

        /* Recipe Cards */
        .recipe-cards {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .recipe-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 24px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
            overflow: hidden;
        }

        .recipe-header {
            padding: 24px 32px;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recipe-title {
            font-size: 20px;
            font-weight: 900;
            color: var(--text);
        }

        .recipe-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--accent3);
            background: rgba(0,0,0,0.3);
            padding: 8px 16px;
            border-radius: 30px;
            border: 1px solid var(--border);
        }

        .recipe-description {
            padding: 20px 32px 0;
            color: var(--muted);
            font-size: 14px;
            font-style: italic;
            margin-bottom: 20px;
        }

        /* BOM Table */
        .bom-section {
            padding: 0 32px 32px;
        }

        .bom-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bom-title small {
            color: var(--muted2);
            font-weight: normal;
            font-size: 13px;
        }

        .table-container {
            background: rgba(0,0,0,0.15);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: rgba(0,0,0,0.25);
            border-bottom: 1px solid var(--border);
        }

        th {
            padding: 16px 24px;
            text-align: left;
            font-size: 12px;
            font-weight: 800;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 16px 24px;
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

        /* Add Ingredient Form */
        .add-ingredient-form {
            background: rgba(0,0,0,0.15);
            border-radius: 16px;
            padding: 24px;
            border: 1px dashed var(--border);
        }

        .add-ingredient-form .form-grid {
            margin-bottom: 16px;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--muted2);
        }

        .empty-state p {
            margin-top: 12px;
            font-size: 14px;
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
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .recipe-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            
            .bom-title {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            
            table {
                display: block;
                overflow-x: auto;
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
            <h1>Recipes & Bill of Materials</h1>
            <p>Manage recipes and their ingredient requirements</p>
        </div>

        <!-- ADD RECIPE FORM -->
        <div class="form-container">
            <h3 class="form-title">Add New Recipe</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Recipe Name *</label>
                        <input type="text" id="name" name="name" placeholder="e.g., Latte" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Selling Price *</label>
                        <input type="number" id="price" name="price" step="1.00" min="0" placeholder="0.00" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="desc">Description</label>
                        <textarea id="desc" name="desc" placeholder="Recipe description or notes"></textarea>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="add_recipe" class="btn-primary">Add Recipe</button>
                </div>
            </form>
        </div>

        <!-- RECIPE LIST -->
        <div class="recipe-cards">
            <?php
            $recipes = $conn->query("SELECT * FROM recipes WHERE is_active=1");
            
            if ($recipes->num_rows > 0):
                while ($r = $recipes->fetch_assoc()):
            ?>
                <div class="recipe-card">
                    <!-- Recipe Header -->
                    <div class="recipe-header">
                        <div class="recipe-title"><?= htmlspecialchars($r['recipe_name']) ?></div>
                        <div class="recipe-price">₱<?= number_format($r['selling_price'], 2) ?></div>
                    </div>
                    
                    <?php if (!empty($r['description'])): ?>
                        <div class="recipe-description">
                            <?= htmlspecialchars($r['description']) ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- BOM Section -->
                    <div class="bom-section">
                        <div class="bom-title">
                            <span>Bill of Materials</span>
                            <small>Ingredients per serving</small>
                        </div>
                        
                        <?php
                        $bom = $conn->query("
                            SELECT ri.quantity, i.item_name, i.unit_of_measure
                            FROM recipe_items ri
                            JOIN items i ON ri.item_id = i.id
                            WHERE ri.recipe_id = {$r['id']}
                        ");
                        
                        if ($bom->num_rows > 0):
                        ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Ingredient</th>
                                            <th>Quantity</th>
                                            <th>Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($b = $bom->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($b['item_name']) ?></td>
                                            <td><?= htmlspecialchars($b['quantity']) ?></td>
                                            <td><?= htmlspecialchars($b['unit_of_measure']) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <p>No ingredients added yet. Add ingredients using the form below.</p>
                            </div>
                        <?php endif; ?>
                        
                        <!-- ADD INGREDIENT FORM -->
                        <div class="add-ingredient-form">
                            <form method="POST">
                                <input type="hidden" name="recipe_id" value="<?= $r['id'] ?>">
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="item_id_<?= $r['id'] ?>">Ingredient</label>
                                        <select id="item_id_<?= $r['id'] ?>" name="item_id" required>
                                            <option value="">Select an ingredient...</option>
                                            <?php
                                            $items = $conn->query("SELECT * FROM items WHERE is_active=1");
                                            while ($i = $items->fetch_assoc()):
                                            ?>
                                            <option value="<?= $i['id'] ?>">
                                                <?= htmlspecialchars($i['item_name']) ?> (<?= htmlspecialchars($i['unit_of_measure']) ?>)
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="qty_<?= $r['id'] ?>">Quantity per serving</label>
                                        <input type="number" id="qty_<?= $r['id'] ?>" name="qty" 
                                               step="0.01" min="0.01" placeholder="0.00" required>
                                    </div>
                                </div>
                                <div class="form-actions">
                                    <button type="submit" name="add_bom" class="btn-secondary">Add Ingredient</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="empty-state">
                    <p>No recipes found. Add your first recipe using the form above.</p>
                </div>
            <?php endif; ?>
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

        // Format price inputs on blur
        document.querySelectorAll('input[type="number"][step="0.01"]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        });

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
    </script>
</body>
</html>