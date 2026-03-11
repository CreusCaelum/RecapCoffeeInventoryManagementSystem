<?php
include "config/db.php";
if (!isset($_SESSION['user_id'])) header("Location: index.php");

/* =========================
   CREATE PURCHASE ORDER
========================= */
if (isset($_POST['create_po'])) {

    $po_number = "PO-" . date("Ymd-His");

    $stmt = $conn->prepare("
        INSERT INTO purchase_orders
        (po_number, supplier_id, order_date, status, prepared_by)
        VALUES (?, ?, CURDATE(), 'ordered', ?)
    ");

    $stmt->bind_param(
        "sii",
        $po_number,
        $_POST['supplier_id'],
        $_SESSION['user_id']
    );

    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Orders - Inventory System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        body {
            background-color: #f8f9fa;
            color: #333;
        }

        /* Top Navigation Bar */
        .top-nav {
            background: #fff;
            border-bottom: 1px solid #eaeaea;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1400px;
            margin: 0 auto;
            height: 64px;
        }

        .nav-brand {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            text-decoration: none;
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
            color: #555;
            text-decoration: none;
            padding: 0 20px;
            height: 64px;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-item > a:hover {
            background: #f5f5f5;
            color: #000;
        }

        .nav-item.active > a {
            color: #000;
        }

        .nav-item.active > a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: #000;
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
        }

        .nav-dropdown.active .nav-dropdown-toggle::after {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            z-index: 1001;
        }

        .nav-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: block;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s;
        }

        .dropdown-menu a:hover {
            background: #f5f5f5;
            color: #000;
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
            background: transparent;
            border: 1px solid #ddd;
            color: #666;
            padding: 8px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #f5f5f5;
            color: #000;
            border-color: #bbb;
        }

        /* Main Content */
        .main-content {
            padding: 40px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title h1 {
            font-size: 28px;
            font-weight: 600;
            color: #000;
            margin-bottom: 4px;
        }

        .page-subtitle {
            color: #666;
            font-size: 14px;
        }

        /* Stats Overview */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #eaeaea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .stat-card h3 {
            font-size: 13px;
            font-weight: 500;
            color: #666;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #000;
            line-height: 1;
        }

        .stat-card .label {
            font-size: 12px;
            color: #888;
            margin-top: 4px;
        }

        /* Create PO Form */
        .create-po-form {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 40px;
            border: 1px solid #eaeaea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            max-width: 400px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }

        .form-group select {
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
            color: #333;
            margin-bottom: 16px;
        }

        .form-group select:focus {
            outline: none;
            border-color: #888;
            box-shadow: 0 0 0 3px rgba(0,0,0,0.05);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-primary {
            background: #000;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: #333;
            transform: translateY(-1px);
        }

        /* Purchase Orders Table */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eaeaea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
            border-bottom: 1px solid #eaeaea;
        }

        th {
            padding: 18px 24px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 18px 24px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 14px;
        }

        tbody tr {
            transition: background 0.2s;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.ordered {
            background: #e3f2fd;
            color: #1976d2;
        }

        .status-badge.received {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-badge.partial {
            background: #fff3e0;
            color: #f57c00;
        }

        .status-badge.cancelled {
            background: #ffebee;
            color: #d32f2f;
        }

        /* Action Links */
        .action-link {
            color: #000;
            text-decoration: none;
            font-weight: 500;
            font-size: 13px;
            padding: 6px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            transition: all 0.2s;
            display: inline-block;
        }

        .action-link:hover {
            background: #f5f5f5;
            border-color: #bbb;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .empty-state h3 {
            font-size: 18px;
            font-weight: 600;
            color: #666;
            margin-bottom: 12px;
        }

        .empty-state p {
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* Section Divider */
        .section-divider {
            display: flex;
            align-items: center;
            margin: 40px 0 24px;
            gap: 16px;
        }

        .section-divider h2 {
            font-size: 20px;
            font-weight: 600;
            color: #000;
            white-space: nowrap;
        }

        .divider-line {
            flex: 1;
            height: 1px;
            background: #eaeaea;
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
                align-items: flex-start;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .form-group {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
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
                
                <div class="nav-item active">
                    <a href="supply.php">Suppliers</a>
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
            <div class="page-title">
                <h1>Purchase Orders</h1>
                <p class="page-subtitle">Manage and track supplier purchase orders</p>
            </div>
        </div>

        <!-- Stats Overview -->
        <?php
        // Calculate stats
        $totalPOs = $conn->query("SELECT COUNT(*) as count FROM purchase_orders")->fetch_assoc()['count'];
        $pendingPOs = $conn->query("SELECT COUNT(*) as count FROM purchase_orders WHERE status = 'ordered'")->fetch_assoc()['count'];
        $totalAmount = $conn->query("SELECT SUM(total_amount) as total FROM purchase_orders")->fetch_assoc()['total'] ?? 0;
        ?>
        
        <div class="stats-overview">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="value"><?= $totalPOs ?></div>
                <div class="label">All purchase orders</div>
            </div>
            
            <div class="stat-card">
                <h3>Pending</h3>
                <div class="value"><?= $pendingPOs ?></div>
                <div class="label">Awaiting delivery</div>
            </div>
            
            <div class="stat-card">
                <h3>Total Value</h3>
                <div class="value">₱<?= number_format($totalAmount, 2) ?></div>
                <div class="label">Overall order value</div>
            </div>
        </div>

        <!-- Create PO Form -->
        <div class="create-po-form">
            <h3 class="form-title">Create New Purchase Order</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="supplier_id">Select Supplier</label>
                    <select name="supplier_id" id="supplier_id" required>
                        <option value="">-- Choose a supplier --</option>
                        <?php
                        $suppliers = $conn->query("
                            SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name
                        ");
                        while ($s = $suppliers->fetch_assoc()):
                        ?>
                            <option value="<?= $s['id'] ?>">
                                <?= htmlspecialchars($s['supplier_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_po" class="btn-primary">Create Purchase Order</button>
                </div>
            </form>
        </div>

        <!-- Purchase Orders List -->
        <div class="section-divider">
            <h2>Purchase Order List</h2>
            <div class="divider-line"></div>
        </div>

        <div class="table-container">
            <?php
            $orders = $conn->query("
                SELECT po.*, s.supplier_name
                FROM purchase_orders po
                LEFT JOIN suppliers s ON po.supplier_id = s.id
                ORDER BY po.created_at DESC
            ");
            
            if ($orders->num_rows > 0):
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>PO Number</th>
                            <th>Supplier</th>
                            <th>Order Date</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($po = $orders->fetch_assoc()): 
                            $statusClass = '';
                            switch($po['status']) {
                                case 'ordered': $statusClass = 'ordered'; break;
                                case 'received': $statusClass = 'received'; break;
                                case 'partial': $statusClass = 'partial'; break;
                                case 'cancelled': $statusClass = 'cancelled'; break;
                            }
                        ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($po['po_number']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($po['supplier_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($po['order_date'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= ucfirst($po['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>₱<?= number_format($po['total_amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <a href="purchase_view.php?id=<?= $po['id'] ?>" class="action-link">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Purchase Orders</h3>
                    <p>Create your first purchase order using the form above.</p>
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
                
                // Close all other dropdowns
                document.querySelectorAll('.nav-dropdown').forEach(other => {
                    if (other !== dropdown) {
                        other.classList.remove('active');
                    }
                });
                
                // Toggle current dropdown
                dropdown.classList.toggle('active');
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-dropdown')) {
                document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });

        // Close dropdowns on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                    dropdown.classList.remove('active');
                });
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const supplierSelect = document.getElementById('supplier_id');
            if (!supplierSelect.value) {
                e.preventDefault();
                alert('Please select a supplier');
                supplierSelect.focus();
                return false;
            }
            return true;
        });

        // Auto-focus supplier select on page load
        document.addEventListener('DOMContentLoaded', function() {
            const supplierSelect = document.getElementById('supplier_id');
            if (supplierSelect) {
                supplierSelect.focus();
            }
        });
    </script>
</body>
</html>