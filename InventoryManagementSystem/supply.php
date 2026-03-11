<?php
include "config/db.php";
if (!isset($_SESSION['user_id'])) header("Location: index.php");

if ($_POST) {
    $stmt = $conn->prepare("
        INSERT INTO suppliers 
        (supplier_code, supplier_name, contact_person, email, phone, address)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssss",
        $_POST['code'],
        $_POST['name'],
        $_POST['contact'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['address']
    );
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers Management - Inventory System</title>
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

        /* Header */
        .page-header {
            margin-bottom: 40px;
        }

        .page-header h2 {
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

        /* Form */
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

        .form-group input {
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
            background: rgba(0,0,0,0.22);
            color: var(--text);
        }

        .form-group input:focus {
            outline: none;
            border-color: rgba(242,192,137,0.55);
            box-shadow: var(--ring);
            background: rgba(0,0,0,0.28);
        }

        .form-group input::placeholder {
            color: var(--muted2);
            opacity: 0.5;
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
            padding: 14px 28px;
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

        /* Table */
        .table-container {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.06));
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            backdrop-filter: blur(14px);
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

        td a {
            color: var(--accent3);
            text-decoration: none;
        }

        td a:hover {
            text-decoration: underline;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
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
            
            <div class="nav-item active">
                <a href="supply.php">Suppliers</a>
            </div>
            
            <div class="nav-item nav-dropdown">
                <a href="#" class="nav-dropdown-toggle">Recipes</a>
                <div class="dropdown-menu">
                    <div class="dropdown-menu">
                    <a href="recipes.php">Recipes and Consumption</a>
                    <a href="sales.php">Orders</a>
                </div>
                </div>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="page-header">
            <h2>Suppliers</h2>
            <p>Manage your suppliers and their contact information</p>
        </div>

        <!-- ADD SUPPLIER FORM -->
        <div class="form-container">
            <h3 class="form-title">Add New Supplier</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="code">Supplier Code *</label>
                        <input type="text" id="code" name="code" placeholder="Enter supplier code" required>
                    </div>
                    <div class="form-group">
                        <label for="name">Supplier Name *</label>
                        <input type="text" id="name" name="name" placeholder="Enter supplier name" required>
                    </div>
                    <div class="form-group">
                        <label for="contact">Contact Person</label>
                        <input type="text" id="contact" name="contact" placeholder="Enter contact person name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter email address">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Enter phone number">
                    </div>
                    <div class="form-group">
                        <label for="address">Physical Address</label>
                        <input type="text" id="address" name="address" placeholder="Enter full address">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>

        <!-- SUPPLIERS TABLE -->
        <div class="table-container">
            <?php
            $res = $conn->query("SELECT * FROM suppliers WHERE is_active=1");
            $rowCount = $res->num_rows;
            
            if ($rowCount > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Supplier</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Phone</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($s = $res->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text);"><?= htmlspecialchars($s['supplier_name']) ?></strong>
                                <div style="font-size: 12px; color: var(--muted2); margin-top: 4px;">
                                    Code: <?= htmlspecialchars($s['supplier_code']) ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($s['contact_person']) ?: '—' ?></td>
                            <td>
                                <?php if ($s['email']): ?>
                                    <a href="mailto:<?= htmlspecialchars($s['email']) ?>" style="color: var(--accent3);">
                                        <?= htmlspecialchars($s['email']) ?>
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['phone']) ?: '—' ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No suppliers found. Add your first supplier using the form above.</p>
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