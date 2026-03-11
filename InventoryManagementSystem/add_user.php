<?php
include "config/db.php";
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    die("Access denied");
}

$message = '';
$error = '';

if ($_POST) {
    // Check if username already exists
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $_POST['username']);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Username already exists. Please choose another.";
    } else {
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (username, password, full_name, email, role)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssss",
            $_POST['username'],
            $hash,
            $_POST['full_name'],
            $_POST['email'],
            $_POST['role']
        );
        
        if ($stmt->execute()) {
            $message = "✅ User added successfully!";
        } else {
            $error = "Error adding user. Please try again.";
        }
        $stmt->close();
    }
    $check->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Inventory System</title>
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
            min-height: 100vh;
            background: radial-gradient(1200px 600px at 20% 10%, rgba(197,122,58,0.25), transparent 60%),
                        radial-gradient(900px 500px at 80% 20%, rgba(242,192,137,0.18), transparent 60%),
                        radial-gradient(900px 600px at 50% 100%, rgba(123,74,46,0.25), transparent 60%),
                        linear-gradient(135deg, var(--bg1), var(--bg2));
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border-right: 1px solid var(--border);
            width: 240px;
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
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
            margin-left: 240px;
            padding: 40px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 1;
        }

        /* Form Container */
        .form-container {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 48px;
            width: 100%;
            max-width: 500px;
            backdrop-filter: blur(14px);
            box-shadow: var(--shadow2);
        }

        .form-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .form-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--accent1), var(--accent2));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 16px;
            box-shadow: 0 12px 28px rgba(197,122,58,0.3);
        }

        .form-title {
            font-size: 24px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 8px;
        }

        .form-subtitle {
            color: var(--muted2);
            font-size: 14px;
        }

        /* Alert Messages */
        .alert {
            background: linear-gradient(180deg, rgba(255,255,255,0.10), rgba(255,255,255,0.04));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
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
            color: var(--text);
            font-size: 14px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
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

        .input-wrapper {
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px 14px 45px;
            background: rgba(0,0,0,0.25);
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.2s;
            color: var(--text);
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent1);
            box-shadow: var(--ring);
            background: rgba(0,0,0,0.35);
        }

        .form-group input::placeholder {
            color: var(--muted2);
            opacity: 0.5;
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23f4f4f6' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 45px;
        }

        .form-group select option {
            background: var(--bg2);
            color: var(--text);
        }

        .input-hint {
            margin-top: 6px;
            font-size: 12px;
            color: var(--muted2);
        }

        /* Role Info */
        .role-info {
            background: rgba(197,122,58,0.1);
            border: 1px solid rgba(197,122,58,0.2);
            border-radius: 12px;
            padding: 16px;
            margin: 24px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .role-icon {
            font-size: 24px;
        }

        .role-text {
            font-size: 13px;
            color: var(--muted);
        }

        .role-text strong {
            color: var(--accent3);
            display: block;
            margin-bottom: 4px;
            font-size: 14px;
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }

        .btn {
            flex: 1;
            padding: 14px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            text-align: center;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent3), var(--accent1));
            color: #1a1a1a;
            box-shadow: 0 10px 20px rgba(197,122,58,0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(197,122,58,0.3);
        }

        .btn-secondary {
            background: rgba(0,0,0,0.3);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent1);
            transform: translateY(-1px);
        }

        /* Password Strength (optional) */
        .password-strength {
            margin-top: 8px;
            height: 4px;
            background: rgba(0,0,0,0.3);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
        }

        .strength-bar.weak {
            width: 33.33%;
            background: var(--danger);
        }

        .strength-bar.medium {
            width: 66.66%;
            background: var(--warning);
        }

        .strength-bar.strong {
            width: 100%;
            background: var(--success);
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
            
            .main-content {
                margin-left: 0;
                padding: 24px;
            }
            
            .form-container {
                padding: 32px 24px;
            }
        }

        @media (max-width: 768px) {
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="noise"></div>

    <!-- LEFT NAVIGATION BAR -->
    <nav class="side-nav">
        <a href="owner_dashboard.php" class="nav-brand">Inventory System</a>
        
        <div class="nav-links">
            <div class="nav-item">
                <a href="owner_dashboard.php">Dashboard</a>
            </div>
            
            <div class="nav-item active">
                <a href="add_user.php">Add User</a>
            </div>
        </div>
        
        <div class="nav-actions">
            <a href="auth/logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">👤</div>
                <h1 class="form-title">Add New User</h1>
                <p class="form-subtitle">Create a new staff account</p>
            </div>

            <?php if ($message): ?>
                <div class="alert success">
                    <span class="alert-icon">✅</span>
                    <span class="alert-content"><?= htmlspecialchars($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error">
                    <span class="alert-icon">⚠️</span>
                    <span class="alert-content"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" id="addUserForm">
                <!-- Username -->
                <div class="form-group">
                    <label for="username" class="required">Username</label>
                    <div class="input-wrapper">
                        <span class="input-icon">👤</span>
                        <input type="text" id="username" name="username" 
                               placeholder="Enter username" 
                               required 
                               minlength="3"
                               maxlength="50"
                               pattern="[a-zA-Z0-9_]+"
                               title="Username can only contain letters, numbers, and underscores">
                    </div>
                    <div class="input-hint">Minimum 5 characters, letters, numbers, and underscores only</div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="required">Password</label>
                    <div class="input-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter password" 
                               required 
                               minlength="6">
                    </div>
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="input-hint">Minimum 6 characters</div>
                </div>

                <!-- Full Name -->
                <div class="form-group">
                    <label for="full_name" class="required">Full Name</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📝</span>
                        <input type="text" id="full_name" name="full_name" 
                               placeholder="Enter full name" 
                               required>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="required">Email Address</label>
                    <div class="input-wrapper">
                        <span class="input-icon">📧</span>
                        <input type="email" id="email" name="email" 
                               placeholder="Enter email address" 
                               required>
                    </div>
                </div>

                <!-- Role - Staff Only -->
                <div class="form-group">
                    <label for="role" class="required">User Role</label>
                    <div class="input-wrapper">
                        <span class="input-icon">⚡</span>
                        <select name="role" id="role" required>
                            <option value="staff" selected>Staff</option>
                            <!-- Owner role removed - only staff can be added -->
                        </select>
                    </div>
                    <div class="input-hint">Staff members can manage inventory but cannot approve reorders</div>
                </div>

                <!-- Role Information -->
                <div class="role-info">
                    <span class="role-icon">ℹ️</span>
                    <div class="role-text">
                        <strong>Staff Account</strong>
                        Staff members can record transactions, manage items, and create reorder suggestions. They cannot approve purchases or manage users.
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="owner_dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add User</button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check length
            if (password.length >= 6) strength += 1;
            if (password.length >= 8) strength += 1;
            
            // Check for numbers
            if (/\d/.test(password)) strength += 1;
            
            // Check for special characters
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;
            
            // Update strength bar
            strengthBar.className = 'strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });

        // Form validation
        document.getElementById('addUserForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            
            // Username validation
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long.');
                return false;
            }
            
            // Username pattern validation
            const usernamePattern = /^[a-zA-Z0-9_]+$/;
            if (!usernamePattern.test(username)) {
                e.preventDefault();
                alert('Username can only contain letters, numbers, and underscores.');
                return false;
            }
            
            // Password validation
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return false;
            }
            
            // Email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
            
            // Confirm submission
            const confirmMessage = `Add new staff member?\n\nUsername: ${username}\nFull Name: ${fullName}\nEmail: ${email}`;
            if (!confirm(confirmMessage)) {
                e.preventDefault();
                return false;
            }
            
            return true;
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>