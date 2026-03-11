<?php
include "config/db.php";
include "config/functions.php";
checkLogin();

if (!isset($_GET['id'])) {
    header("Location: purchase_orders.php");
    exit();
}

$id = (int)$_GET['id'];

// Get PO details
$po = $conn->query("
    SELECT po.*, s.supplier_name, s.contact_person, s.email, s.phone
    FROM purchase_orders po
    LEFT JOIN suppliers s ON po.supplier_id = s.id
    WHERE po.id = $id
")->fetch_assoc();

if (!$po) {
    die("Purchase order not found.");
}

/* Add item */
if (isset($_POST['add_item'])) {
    $total = $_POST['qty'] * $_POST['cost'];

    $conn->query("
        INSERT INTO purchase_order_items
        (po_id, item_id, quantity, unit_cost, total_cost)
        VALUES ($id, {$_POST['item_id']}, {$_POST['qty']}, {$_POST['cost']}, $total)
    ");

    $conn->query("
        UPDATE purchase_orders
        SET total_amount = total_amount + $total
        WHERE id = $id
    ");
    
    // Refresh PO data
    $po = $conn->query("
        SELECT po.*, s.supplier_name, s.contact_person, s.email, s.phone
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        WHERE po.id = $id
    ")->fetch_assoc();
}

/* Mark delivered → AUTO STOCK IN */
if (isset($_POST['deliver'])) {
    $items = $conn->query("
        SELECT * FROM purchase_order_items WHERE po_id = $id
    ");

    while ($i = $items->fetch_assoc()) {
        $conn->query("
            UPDATE items
            SET current_stock = current_stock + {$i['quantity']},
                unit_cost = {$i['unit_cost']}
            WHERE id = {$i['item_id']}
        ");

        $conn->query("
            INSERT INTO stock_transactions
            (transaction_type, item_id, quantity, unit_cost, reference_id, performed_by, status)
            VALUES
            ('purchase', {$i['item_id']}, {$i['quantity']}, {$i['unit_cost']}, $id, {$_SESSION['user_id']}, 'approved')
        ");
    }

    $conn->query("
        UPDATE purchase_orders
        SET status = 'delivered'
        WHERE id = $id
    ");
    
    // Refresh PO data
    $po = $conn->query("
        SELECT po.*, s.supplier_name, s.contact_person, s.email, s.phone
        FROM purchase_orders po
        LEFT JOIN suppliers s ON po.supplier_id = s.id
        WHERE po.id = $id
    ")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #<?= $po['po_number'] ?> - Inventory System</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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
            color: #666;
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 16px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #000;
        }

        .page-title {
            font-size: 28px;
            font-weight: 600;
            color: #000;
            margin-bottom: 4px;
        }

        .page-subtitle {
            color: #666;
            font-size: 14px;
        }

        .po-number {
            font-weight: 700;
            color: #000;
        }

        /* Status and Actions */
        .header-right {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: flex-end;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
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

        .status-badge.delivered {
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

        /* PO Details Card */
        .po-details-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid #eaeaea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .detail-group h3 {
            font-size: 13px;
            font-weight: 500;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-group p {
            font-size: 16px;
            color: #000;
            font-weight: 500;
        }

        /* Add Item Form */
        .add-item-form {
            background: white;
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 32px;
            border: 1px solid #eaeaea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }

        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
        }

        .form-group select,
        .form-group input {
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
            background: white;
            color: #333;
        }

        .form-group select:focus,
        .form-group input:focus {
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

        .btn-secondary {
            background: transparent;
            color: #666;
            border: 1px solid #ddd;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-secondary:hover {
            background: #f5f5f5;
            border-color: #bbb;
            color: #000;
        }

        /* Items Table */
        .items-table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #eaeaea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 32px;
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

        .total-row {
            background: #f8f9fa;
            font-weight: 600;
        }

        .total-row td {
            border-top: 2px solid #eaeaea;
            font-size: 16px;
        }

        /* Deliver Action */
        .deliver-action {
            background: white;
            border-radius: 12px;
            padding: 32px;
            border: 1px solid #eaeaea;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            text-align: center;
        }

        .deliver-action h3 {
            font-size: 18px;
            font-weight: 600;
            color: #000;
            margin-bottom: 16px;
        }

        .deliver-action p {
            color: #666;
            font-size: 14px;
            margin-bottom: 24px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-success {
            background: #2e7d32;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            min-width: 200px;
        }

        .btn-success:hover {
            background: #1b5e20;
            transform: translateY(-1px);
        }

        .btn-success:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
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
            
            .header-right {
                align-items: flex-start;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
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
            <div class="header-left">
                <a href="purchase_orders.php" class="back-link">
                    ← Back to Purchase Orders
                </a>
                <h1 class="page-title">Purchase Order Details</h1>
                <p class="page-subtitle">
                    Order: <span class="po-number"><?= htmlspecialchars($po['po_number']) ?></span>
                </p>
            </div>
            
            <div class="header-right">
                <span class="status-badge <?= $po['status'] ?>">
                    <?= ucfirst($po['status']) ?>
                </span>
                <div class="total-amount">
                    <strong>Total: ₱<?= number_format($po['total_amount'], 2) ?></strong>
                </div>
            </div>
        </div>

        <!-- PO Details -->
        <div class="po-details-card">
            <div class="details-grid">
                <div class="detail-group">
                    <h3>Supplier</h3>
                    <p><?= htmlspecialchars($po['supplier_name']) ?></p>
                    <?php if ($po['contact_person']): ?>
                        <p style="font-size: 14px; color: #666; margin-top: 4px;">
                            Contact: <?= htmlspecialchars($po['contact_person']) ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="detail-group">
                    <h3>Order Date</h3>
                    <p><?= date('M d, Y', strtotime($po['order_date'])) ?></p>
                </div>
                
                <div class="detail-group">
                    <h3>Prepared By</h3>
                    <p><?= htmlspecialchars($po['prepared_by']) ?></p>
                </div>
                
                <div class="detail-group">
                    <h3>Delivery Status</h3>
                    <p>
                        <span class="status-badge <?= $po['status'] ?>" style="font-size: 11px; padding: 4px 12px;">
                            <?= ucfirst($po['status']) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Add Item Form -->
        <?php if ($po['status'] !== 'delivered' && $po['status'] !== 'cancelled'): ?>
            <div class="add-item-form">
                <h3 class="form-title">Add Item to Purchase Order</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="item_id">Select Item</label>
                            <select name="item_id" id="item_id" required>
                                <option value="">Choose an item...</option>
                                <?php
                                $it = $conn->query("SELECT * FROM items WHERE is_active=1 ORDER BY item_name");
                                while ($i = $it->fetch_assoc()):
                                ?>
                                <option value="<?= $i['id'] ?>">
                                    <?= htmlspecialchars($i['item_name']) ?> 
                                    (<?= htmlspecialchars($i['unit_of_measure']) ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="qty">Quantity</label>
                            <input type="number" id="qty" name="qty" step="0.01" min="0.01" 
                                   placeholder="Enter quantity" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="cost">Unit Cost (₱)</label>
                            <input type="number" id="cost" name="cost" step="0.01" min="0.01" 
                                   placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" name="add_item" class="btn-primary">Add Item to PO</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Items Table -->
        <div class="items-table-container">
            <?php
            $items = $conn->query("
                SELECT poi.*, i.item_name, i.unit_of_measure
                FROM purchase_order_items poi
                LEFT JOIN items i ON poi.item_id = i.id
                WHERE poi.po_id = $id
                ORDER BY poi.created_at
            ");
            
            if ($items->num_rows > 0):
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Unit</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotal = 0;
                        while ($item = $items->fetch_assoc()): 
                            $total = $item['quantity'] * $item['unit_cost'];
                            $grandTotal += $total;
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($item['item_name']) ?></td>
                                <td><?= htmlspecialchars($item['unit_of_measure']) ?></td>
                                <td><?= number_format($item['quantity'], 2) ?></td>
                                <td>₱<?= number_format($item['unit_cost'], 2) ?></td>
                                <td>₱<?= number_format($total, 2) ?></td>
                            </tr>
                        <?php endwhile; ?>
                        
                        <tr class="total-row">
                            <td colspan="4" style="text-align: right;"><strong>Grand Total:</strong></td>
                            <td><strong>₱<?= number_format($grandTotal, 2) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Items Added</h3>
                    <p>Add items to this purchase order using the form above.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Deliver Action -->
        <?php if ($po['status'] !== 'delivered' && $po['status'] !== 'cancelled'): 
            $hasItems = $items->num_rows > 0;
        ?>
            <div class="deliver-action">
                <h3>Complete Purchase Order</h3>
                <p>
                    Marking this order as delivered will automatically update stock levels 
                    and create purchase transactions for all items. This action cannot be undone.
                </p>
                <form method="POST" onsubmit="return confirmDeliver()">
                    <button type="submit" name="deliver" class="btn-success" 
                            <?= !$hasItems ? 'disabled' : '' ?>>
                        <?php if ($hasItems): ?>
                            ✓ Mark as Delivered & Stock In
                        <?php else: ?>
                            Add items first to enable delivery
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        <?php elseif ($po['status'] === 'delivered'): ?>
            <div class="deliver-action" style="background: #f0f9f0; border-color: #d1e7dd;">
                <h3>Order Delivered</h3>
                <p>
                    This purchase order has been marked as delivered. Stock levels have been 
                    updated and purchase transactions have been recorded.
                </p>
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

        // Confirm delivery
        function confirmDeliver() {
            const itemCount = <?= $items->num_rows ?>;
            if (itemCount === 0) {
                alert('Please add items to the purchase order before marking as delivered.');
                return false;
            }
            
            return confirm(`Are you sure you want to mark this order as delivered?\n\nThis will:
• Update stock levels for ${itemCount} item(s)
• Create purchase transactions
• Cannot be undone`);
        }

        // Format cost inputs on blur
        document.querySelectorAll('input[type="number"][step="0.01"]').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        });

        // Auto-calculate total when cost or quantity changes
        const qtyInput = document.getElementById('qty');
        const costInput = document.getElementById('cost');
        
        function calculateTotal() {
            if (qtyInput && costInput && qtyInput.value && costInput.value) {
                const total = parseFloat(qtyInput.value) * parseFloat(costInput.value);
                // You could show this in a preview element if desired
            }
        }
        
        if (qtyInput) qtyInput.addEventListener('input', calculateTotal);
        if (costInput) costInput.addEventListener('input', calculateTotal);

        // Form validation for add item
        const addItemForm = document.querySelector('form[method="POST"]');
        if (addItemForm) {
            addItemForm.addEventListener('submit', function(e) {
                const itemSelect = document.getElementById('item_id');
                const qty = document.getElementById('qty');
                const cost = document.getElementById('cost');
                
                if (!itemSelect || !itemSelect.value) {
                    e.preventDefault();
                    alert('Please select an item');
                    itemSelect.focus();
                    return false;
                }
                
                if (!qty.value || parseFloat(qty.value) <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid quantity');
                    qty.focus();
                    return false;
                }
                
                if (!cost.value || parseFloat(cost.value) <= 0) {
                    e.preventDefault();
                    alert('Please enter a valid unit cost');
                    cost.focus();
                    return false;
                }
                
                return true;
            });
        }
    </script>
</body>
</html>