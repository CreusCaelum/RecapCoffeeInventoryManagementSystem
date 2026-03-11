<?php
include "config/db.php";
if (!isset($_SESSION['user_id'])) header("Location: index.php");

if (!isset($_GET['item_id'])) {
    die("Item not specified.");
}

$item_id = (int)$_GET['item_id'];

// Get item info
$item = $conn->query("
    SELECT item_name, unit_of_measure, current_stock, reorder_level
    FROM items
    WHERE id = $item_id
")->fetch_assoc();

if (!$item) {
    die("Item not found.");
}

// Get transaction history
$history = $conn->query("
    SELECT 
        st.transaction_type,
        st.quantity,
        st.created_at,
        u.full_name
    FROM stock_transactions st
    LEFT JOIN users u ON st.performed_by = u.id
    WHERE st.item_id = $item_id
    ORDER BY st.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($item['item_name']) ?> History - Inventory System</title>
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

        /* Top Navigation Bar */
        .top-nav {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.06));
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            height: 70px;
        }

        .nav-brand {
            font-size: 18px;
            font-weight: 800;
            color: var(--text);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .nav-brand::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: linear-gradient(135deg, var(--accent1), var(--accent3));
            box-shadow: 0 0 15px var(--accent1);
        }

        .nav-links {
            display: flex;
            gap: 4px;
            height: 100%;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .nav-item > a {
            color: rgba(244,244,246,0.82);
            text-decoration: none;
            padding: 0 24px;
            height: 70px;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.2s;
            border-radius: 0;
            position: relative;
        }

        .nav-item > a:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        .nav-item.active > a {
            color: #fff;
        }

        .nav-item.active > a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 16px;
            right: 16px;
            height: 2px;
            background: linear-gradient(90deg, var(--accent3), var(--accent1));
            border-radius: 2px 2px 0 0;
        }

        .nav-dropdown {
            position: relative;
        }

        .nav-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .nav-dropdown-toggle::after {
            content: '▼';
            font-size: 10px;
            transition: transform 0.2s;
            opacity: 0.7;
        }

        .nav-dropdown.active .nav-dropdown-toggle::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: linear-gradient(180deg, rgba(20,20,25,0.95), rgba(15,15,20,0.98));
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: var(--shadow2);
            min-width: 240px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            z-index: 1001;
            backdrop-filter: blur(20px);
            overflow: hidden;
        }

        .nav-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 14px 20px;
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            border-bottom: 1px solid var(--border);
            transition: all 0.2s;
        }

        .dropdown-menu a:hover {
            background: rgba(255,255,255,0.08);
            color: #fff;
            padding-left: 26px;
        }

        .dropdown-menu a:last-child {
            border-bottom: none;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .logout-btn {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 8px 24px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
            color: #fff;
            transform: translateY(-1px);
        }

        /* Main Content */
        .main-content {
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* Page Header */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left {
            flex: 1;
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

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 20px rgba(197,122,58,0.2);
        }

        .action-btn.secondary {
            background: rgba(0,0,0,0.3);
            color: var(--text);
            border: 1px solid var(--border);
            box-shadow: none;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(197,122,58,0.3);
        }

        .action-btn.secondary:hover {
            background: rgba(255,255,255,0.08);
            border-color: var(--accent1);
            color: #fff;
        }

        /* Summary Stats */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .summary-card {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(10px);
        }

        .summary-card h4 {
            font-size: 13px;
            font-weight: 700;
            color: var(--muted2);
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-card .value {
            font-size: 32px;
            font-weight: 900;
            color: var(--text);
            line-height: 1;
        }

        .summary-card .label {
            font-size: 12px;
            color: var(--muted2);
            margin-top: 4px;
        }

        .summary-card .current-stock {
            color: var(--accent3);
            font-size: 36px;
        }

        /* Filter Bar */
        .filter-bar {
            background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
            display: flex;
            gap: 16px;
            align-items: center;
        }

        .filter-label {
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
        }

        .filter-select {
            padding: 12px 18px;
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            color: var(--text);
            min-width: 180px;
            cursor: pointer;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: var(--ring);
        }

        .filter-select option {
            background: var(--bg2);
            color: var(--text);
        }

        /* History Table */
        .table-container {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow2);
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

        /* Transaction Type Badges */
        .type-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid transparent;
        }

        .type-badge.usage {
            background: rgba(244,67,54,0.15);
            color: var(--danger);
            border-color: rgba(244,67,54,0.3);
        }

        .type-badge.purchase {
            background: rgba(76,175,80,0.15);
            color: var(--success);
            border-color: rgba(76,175,80,0.3);
        }

        /* Quantity Display */
        .quantity-cell {
            font-weight: 700;
        }

        .quantity-cell.positive {
            color: var(--success);
        }

        .quantity-cell.negative {
            color: var(--danger);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--muted2);
        }

        .empty-state h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .nav-links {
                display: none;
            }
            
            .nav-container {
                justify-content: center;
                position: relative;
            }
            
            .nav-brand {
                position: absolute;
                left: 24px;
            }
            
            .nav-actions {
                position: absolute;
                right: 24px;
            }
            
            .main-content {
                padding: 24px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
            }
            
            .header-actions {
                width: 100%;
                flex-direction: column;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .summary-cards {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-select {
                width: 100%;
            }
        }

        /* Print styles */
        @media print {
            .top-nav, .filter-bar, .header-actions, .back-link {
                display: none !important;
            }
            
            .main-content {
                padding: 0;
                margin: 0;
            }
            
            .summary-cards, .table-container {
                background: white;
                border: 1px solid #ddd;
            }
            
            td, th {
                color: #000;
            }
        }
    </style>
</head>
<body>
    <div class="noise"></div>
    
    <!-- TOP NAVIGATION BAR -->
    <nav class="top-nav">
        <div class="nav-container">
            <a href="dashboard.php" class="nav-brand">Inventory System</a>
            
            <div class="nav-links">
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
                
                <div class="nav-item nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">Recipes</a>
                    <div class="dropdown-menu">
                        <a href="recipes.php">Recipes and Consumption</a>
                    </div>
                </div>
            </div>
            
            <div class="nav-actions">
                <a href="auth/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <a href="items.php" class="back-link">
                    ← Back to Items
                </a>
                <h1 class="page-title"><?= htmlspecialchars($item['item_name']) ?></h1>
                <p class="page-subtitle">Transaction History</p>
            </div>
            
            <div class="header-actions">
                <a href="transactions.php?item_id=<?= $item_id ?>" class="action-btn">
                    New Transaction
                </a>
                <button onclick="window.print()" class="action-btn secondary">
                    Print Report
                </button>
            </div>
        </div>

        <!-- Summary Stats -->
        <?php
        // Calculate summary stats
        $totalUsage = $conn->query("
            SELECT SUM(quantity) as total 
            FROM stock_transactions 
            WHERE item_id = $item_id AND transaction_type = 'usage'
        ")->fetch_assoc()['total'] ?? 0;
        
        $totalPurchase = $conn->query("
            SELECT SUM(quantity) as total 
            FROM stock_transactions 
            WHERE item_id = $item_id AND transaction_type = 'purchase'
        ")->fetch_assoc()['total'] ?? 0;
        
        $transactionCount = $history->num_rows;
        ?>
        
        <div class="summary-cards">
            <div class="summary-card">
                <h4>Current Stock</h4>
                <div class="value current-stock"><?= number_format($item['current_stock'], 2) ?></div>
                <div class="label"><?= htmlspecialchars($item['unit_of_measure']) ?></div>
            </div>
            
            <div class="summary-card">
                <h4>Total Transactions</h4>
                <div class="value"><?= $transactionCount ?></div>
                <div class="label">All-time records</div>
            </div>
            
            <div class="summary-card">
                <h4>Total Usage</h4>
                <div class="value"><?= number_format($totalUsage, 2) ?></div>
                <div class="label"><?= htmlspecialchars($item['unit_of_measure']) ?> used</div>
            </div>
            
            <div class="summary-card">
                <h4>Total Purchases</h4>
                <div class="value"><?= number_format($totalPurchase, 2) ?></div>
                <div class="label"><?= htmlspecialchars($item['unit_of_measure']) ?> restocked</div>
            </div>
        </div>

        <!-- Date Filter -->
        <div class="filter-bar">
            <span class="filter-label">Filter by:</span>
            <select class="filter-select" onchange="filterByDate(this.value)">
                <option value="all">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
            </select>
        </div>

        <!-- History Table -->
        <div class="table-container">
            <?php if ($history->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Performed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Reset pointer and loop through history
                        $history->data_seek(0);
                        while ($row = $history->fetch_assoc()): 
                            $isUsage = $row['transaction_type'] === 'usage';
                        ?>
                            <tr>
                                <td><?= date("M d, Y H:i", strtotime($row['created_at'])) ?></td>
                                <td>
                                    <span class="type-badge <?= $row['transaction_type'] ?>">
                                        <?= ucfirst($row['transaction_type']) ?>
                                    </span>
                                </td>
                                <td class="quantity-cell <?= $isUsage ? 'negative' : 'positive' ?>">
                                    <?= $isUsage ? '−' : '+' ?><?= number_format($row['quantity'], 2) ?> 
                                    <?= htmlspecialchars($item['unit_of_measure']) ?>
                                </td>
                                <td><?= htmlspecialchars($row['full_name'] ?? 'System') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Transaction History</h3>
                    <p>No transactions have been recorded for this item yet.</p>
                    <a href="transactions.php?item_id=<?= $item_id ?>" class="action-btn">
                        Record First Transaction
                    </a>
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

        // Date filter function
        function filterByDate(period) {
            // This would typically reload the page with a date filter parameter
            // For demo purposes, we'll just show a message
            console.log('Filtering by:', period);
            // Uncomment for actual implementation:
            // window.location.href = `?item_id=<?= $item_id ?>&period=${period}`;
        }

        // Print styling
        function beforePrint() {
            document.body.classList.add('printing');
        }

        function afterPrint() {
            document.body.classList.remove('printing');
        }

        if (window.matchMedia) {
            const mediaQueryList = window.matchMedia('print');
            mediaQueryList.addListener((mql) => {
                if (mql.matches) {
                    beforePrint();
                } else {
                    afterPrint();
                }
            });
        }

        window.onbeforeprint = beforePrint;
        window.onafterprint = afterPrint;

        // Format numbers in quantity cells
        document.querySelectorAll('.quantity-cell').forEach(cell => {
            const text = cell.textContent;
            if (text.includes('−') || text.includes('+')) {
                // Already formatted
            }
        });
    </script>
</body>
</html>