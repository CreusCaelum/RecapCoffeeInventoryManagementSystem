<?php


if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Café Inventory Login</title>
   <style>
    :root{
        --bg1:#0f0f12;
        --bg2:#1b1b22;
        --card: rgba(255,255,255,0.10);
        --card2: rgba(255,255,255,0.06);
        --border: rgba(255,255,255,0.18);
        --text: #f4f4f6;
        --muted: rgba(244,244,246,0.75);

        /* Coffee accents */
        --accent1:#c57a3a; /* caramel */
        --accent2:#7b4a2e; /* coffee */
        --accent3:#f2c089; /* latte */
        --shadow: 0 24px 70px rgba(0,0,0,0.35);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, sans-serif;
    }

    body {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text);
        position: relative;
        overflow: hidden;

        /* Lively background gradient */
        background: radial-gradient(1200px 600px at 20% 10%, rgba(197,122,58,0.25), transparent 60%),
                    radial-gradient(900px 500px at 80% 20%, rgba(242,192,137,0.18), transparent 60%),
                    radial-gradient(900px 600px at 50% 100%, rgba(123,74,46,0.25), transparent 60%),
                    linear-gradient(135deg, var(--bg1), var(--bg2));
    }

    /* Animated blobs */
    body::before, body::after {
        content: "";
        position: absolute;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        filter: blur(32px);
        opacity: 0.55;
        z-index: -2;
        animation: floaty 10s ease-in-out infinite;
    }

    body::before{
        top: -120px;
        left: -140px;
        background: radial-gradient(circle at 30% 30%, rgba(197,122,58,0.85), rgba(197,122,58,0.05) 60%, transparent 70%);
    }

    body::after{
        bottom: -160px;
        right: -160px;
        background: radial-gradient(circle at 30% 30%, rgba(242,192,137,0.75), rgba(242,192,137,0.08) 60%, transparent 70%);
        animation-delay: 1.5s;
    }

    @keyframes floaty{
        0%{ transform: translate(0,0) scale(1); }
        50%{ transform: translate(25px, -20px) scale(1.05); }
        100%{ transform: translate(0,0) scale(1); }
    }

    /* Subtle noise overlay */
    .noise {
        position: absolute;
        inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='180' height='180' filter='url(%23n)' opacity='.16'/%3E%3C/svg%3E");
        opacity: 0.18;
        pointer-events: none;
        z-index: -1;
        mix-blend-mode: overlay;
    }

    /* Brand Logo */
    .brand-logo {
        position: absolute;
        top: 28px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 800;
        letter-spacing: 0.4px;
        color: var(--text);
        text-shadow: 0 10px 30px rgba(0,0,0,0.45);
    }

    .brand-logo .dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--accent1), var(--accent3));
        box-shadow: 0 10px 22px rgba(197,122,58,0.35);
    }

    /* Login container */
    .login-container {
        display: flex;
        align-items: center;
        gap: 70px;
        max-width: 1040px;
        width: 92%;
        padding: 40px;
        animation: fadeIn 0.9s ease-out;
    }

    /* Coffee Machine container */
    .coffee-machine-container {
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        min-width: 300px;
        filter: drop-shadow(0 18px 30px rgba(0,0,0,0.35));
        transform: translateZ(0);
    }

    /* Coffee machine (keep your structure, just add nicer colors) */
    .container { width: 300px; height: 280px; position: relative; }

    .coffee-header {
        width: 100%;
        height: 80px;
        position: absolute;
        top: 0; left: 0;
        background: linear-gradient(180deg, rgba(255,255,255,0.55), rgba(255,255,255,0.18));
        border: 1px solid rgba(255,255,255,0.22);
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        backdrop-filter: blur(10px);
    }

    .coffee-header__buttons {
        width: 25px; height: 25px;
        position: absolute;
        top: 25px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--accent1), var(--accent2));
        box-shadow: 0 10px 18px rgba(197,122,58,0.25);
    }
    .coffee-header__buttons::after {
        content: "";
        width: 8px; height: 8px;
        position: absolute;
        bottom: -8px;
        left: calc(50% - 4px);
        background-color: rgba(255,255,255,0.35);
        border-radius: 2px;
    }
    .coffee-header__button-one { left: 15px; }
    .coffee-header__button-two { left: 50px; }

    .coffee-header__display {
        width: 54px; height: 54px;
        position: absolute;
        top: calc(50% - 27px);
        left: calc(50% - 27px);
        border-radius: 50%;
        background: radial-gradient(circle at 30% 30%, rgba(242,192,137,0.8), rgba(255,255,255,0.14));
        border: 5px solid rgba(255,255,255,0.25);
    }
    .coffee-header__details {
        width: 8px; height: 20px;
        position: absolute;
        top: 10px; right: 12px;
        background-color: rgba(255,255,255,0.35);
        box-shadow: -12px 0 0 rgba(255,255,255,0.35), -24px 0 0 rgba(255,255,255,0.35);
    }

    .coffee-medium {
        width: 90%;
        height: 160px;
        position: absolute;
        top: 80px;
        left: calc(50% - 45%);
        background: linear-gradient(180deg, rgba(255,255,255,0.20), rgba(255,255,255,0.10));
        border: 1px solid rgba(255,255,255,0.18);
        backdrop-filter: blur(10px);
        border-radius: 14px;
    }
    .coffee-medium:before {
        content: "";
        width: 90%;
        height: 100px;
        background: linear-gradient(180deg, rgba(123,74,46,0.22), rgba(0,0,0,0.20));
        position: absolute;
        bottom: 0;
        left: calc(50% - 45%);
        border-radius: 18px 18px 10px 10px;
    }

    .coffe-medium__exit {
        width: 60px; height: 20px;
        position: absolute; top: 0;
        left: calc(50% - 30px);
        background: linear-gradient(180deg, rgba(0,0,0,0.75), rgba(0,0,0,0.45));
        border-radius: 6px;
    }
    .coffe-medium__exit::before {
        content: "";
        width: 50px; height: 20px;
        border-radius: 0 0 50% 50%;
        position: absolute;
        bottom: -20px;
        left: calc(50% - 25px);
        background: rgba(0,0,0,0.55);
    }
    .coffe-medium__exit::after {
        content: "";
        width: 10px; height: 10px;
        position: absolute;
        bottom: -30px;
        left: calc(50% - 5px);
        background: rgba(0,0,0,0.55);
        border-radius: 2px;
    }

    .coffee-medium__arm {
        width: 70px; height: 20px;
        position: absolute;
        top: 15px; right: 25px;
        background: rgba(0,0,0,0.55);
        border-radius: 6px;
    }
    .coffee-medium__arm::before {
        content: "";
        width: 15px; height: 5px;
        position: absolute;
        top: 7px; left: -15px;
        background-color: rgba(255,255,255,0.35);
        border-radius: 2px;
    }

    .coffee-medium__cup {
        width: 80px; height: 47px;
        position: absolute;
        bottom: 0;
        left: calc(50% - 40px);
        background: rgba(255,255,255,0.88);
        border-radius: 0 0 70px 70px / 0 0 110px 110px;
        border: 2px solid rgba(255,255,255,0.25);
        box-shadow: 0 16px 30px rgba(0,0,0,0.22);
    }
    .coffee-medium__cup::after {
        content: "";
        width: 20px; height: 20px;
        position: absolute;
        top: 6px; right: -13px;
        border: 5px solid rgba(255,255,255,0.88);
        border-radius: 50%;
    }

    @keyframes liquid {
        0%, 5% { height: 0px; opacity: 1; }
        20%, 95% { height: 62px; opacity: 1; }
        100% { height: 62px; opacity: 0; }
    }
    .coffee-medium__liquid {
        width: 6px; height: 63px;
        opacity: 0;
        position: absolute;
        top: 50px;
        left: calc(50% - 3px);
        background: linear-gradient(180deg, var(--accent3), var(--accent2));
        animation: liquid 4s 3.5s linear infinite;
        border-radius: 6px;
    }

    .coffee-medium__smoke {
        width: 8px; height: 20px;
        position: absolute;
        border-radius: 999px;
        background-color: rgba(255,255,255,0.45);
        filter: blur(0.2px);
    }

    @keyframes smokeOne {
        0% { bottom: 20px; opacity: 0; }
        40% { bottom: 50px; opacity: 0.55; }
        80% { bottom: 80px; opacity: 0.25; }
        100% { bottom: 85px; opacity: 0; }
    }
    @keyframes smokeTwo {
        0% { bottom: 40px; opacity: 0; }
        40% { bottom: 70px; opacity: 0.55; }
        80% { bottom: 88px; opacity: 0.25; }
        100% { bottom: 90px; opacity: 0; }
    }
    .coffee-medium__smoke-one { opacity: 0; bottom: 50px; left: 102px; animation: smokeOne 3s 3.5s linear infinite; }
    .coffee-medium__smoke-two { opacity: 0; bottom: 70px; left: 118px; animation: smokeTwo 3s 4.2s linear infinite; }
    .coffee-medium__smoke-three { opacity: 0; bottom: 65px; right: 118px; animation: smokeTwo 3s 4.7s linear infinite; }
    .coffee-medium__smoke-for { opacity: 0; bottom: 50px; right: 102px; animation: smokeOne 3s 4.0s linear infinite; }

    .coffee-footer {
        width: 95%; height: 15px;
        position: absolute;
        bottom: 25px;
        left: calc(50% - 47.5%);
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.18);
        border-radius: 10px;
        backdrop-filter: blur(10px);
    }
    .coffee-footer::after {
        content: "";
        width: 106%;
        height: 26px;
        position: absolute;
        bottom: -25px;
        left: -8px;
        background: rgba(0,0,0,0.60);
        border-radius: 0 0 16px 16px;
    }

    /* Login Box (glass) */
    .login-box {
        flex: 1;
        max-width: 420px;
        padding: 48px 42px;
        border-radius: 18px;
        background: linear-gradient(180deg, var(--card), var(--card2));
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        backdrop-filter: blur(14px);
        animation: slideIn 0.75s ease-out;
        position: relative;
        overflow: hidden;
    }

    /* Soft shine */
    .login-box::before{
        content:"";
        position:absolute;
        top:-40%;
        left:-30%;
        width: 70%;
        height: 70%;
        transform: rotate(25deg);
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
        pointer-events:none;
        animation: shine 6.5s ease-in-out infinite;
    }
    @keyframes shine{
        0%, 40%{ transform: translateX(-40%) rotate(25deg); opacity: 0; }
        50%{ opacity: 1; }
        100%{ transform: translateX(140%) rotate(25deg); opacity: 0; }
    }

    .login-box h2 {
        font-size: 34px;
        font-weight: 800;
        margin-bottom: 8px;
        text-align: center;
        color: var(--text);
        letter-spacing: 0.4px;
    }

    .login-box p {
        text-align: center;
        color: var(--muted);
        margin-bottom: 28px;
        font-size: 14px;
    }

    .form-group { margin-bottom: 18px; position: relative; }
    .form-group label {
        display:block;
        font-size: 13px;
        font-weight: 600;
        color: rgba(244,244,246,0.85);
        margin-bottom: 8px;
    }

    /* Icon label */
    .form-group::before{
        position:absolute;
        left: 16px;
        top: 47px;
        transform: translateY(-50%);
        font-size: 16px;
        opacity: 0.9;
    }
    .form-group:nth-of-type(1)::before{ content:"👤"; }
    .form-group:nth-of-type(2)::before{ content:"🔒"; }

    .form-group input {
        width: 100%;
        padding: 15px 16px 15px 48px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.18);
        background: rgba(0,0,0,0.20);
        color: var(--text);
        font-size: 14px;
        transition: transform .15s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
        outline: none;
    }

    .form-group input::placeholder{
        color: rgba(244,244,246,0.45);
    }

    .form-group input:focus{
        border-color: rgba(242,192,137,0.55);
        box-shadow: 0 0 0 4px rgba(197,122,58,0.18);
        background: rgba(0,0,0,0.28);
        transform: translateY(-1px);
    }

    /* Button */
    .login-btn {
        width: 100%;
        border: none;
        padding: 15px 16px;
        border-radius: 12px;
        font-weight: 800;
        letter-spacing: 0.5px;
        cursor: pointer;
        color: #1a1a1a;
        background: linear-gradient(135deg, var(--accent3), var(--accent1), var(--accent2));
        box-shadow: 0 18px 35px rgba(197,122,58,0.24);
        transition: transform .16s ease, box-shadow .25s ease, filter .2s ease;
        position: relative;
        overflow: hidden;
        margin-top: 10px;
    }

    /* Button shine on hover */
    .login-btn::after{
        content:"";
        position:absolute;
        top:-120%;
        left:-20%;
        width: 60%;
        height: 260%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.35), transparent);
        transform: rotate(25deg);
        transition: transform .4s ease;
        opacity: 0;
    }

    .login-btn:hover{
        transform: translateY(-2px);
        box-shadow: 0 22px 48px rgba(197,122,58,0.30);
        filter: brightness(1.02);
    }
    .login-btn:hover::after{
        opacity: 1;
        transform: translateX(220%) rotate(25deg);
    }
    .login-btn:active{ transform: translateY(0px) scale(0.99); }

    .error-message {
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 16px;
        font-size: 14px;
        display: none;
        background: rgba(255, 92, 92, 0.10);
        border: 1px solid rgba(255, 92, 92, 0.20);
        color: rgba(255,255,255,0.9);
        backdrop-filter: blur(10px);
    }
    .error-message.show { display:block; animation: fadeIn .25s ease; }

    /* Footer */
    .form-footer{
        text-align:center;
        margin-top: 26px;
        padding-top: 18px;
        border-top: 1px solid rgba(255,255,255,0.12);
        font-size: 13px;
        color: rgba(244,244,246,0.65);
    }

    /* Version tag */
    .version-tag{
        position: absolute;
        bottom: 18px;
        right: 18px;
        font-size: 12px;
        color: rgba(244,244,246,0.65);
        background: rgba(0,0,0,0.25);
        border: 1px solid rgba(255,255,255,0.12);
        padding: 6px 12px;
        border-radius: 999px;
        backdrop-filter: blur(10px);
    }

    @keyframes fadeIn{
        from{ opacity: 0; transform: translateY(12px); }
        to{ opacity: 1; transform: translateY(0); }
    }
    @keyframes slideIn{
        from{ opacity: 0; transform: translateX(26px); }
        to{ opacity: 1; transform: translateX(0); }
    }

    /* Responsive */
    @media (max-width: 900px){
        .login-container{ flex-direction: column; gap: 34px; padding: 22px; }
        .coffee-machine-container{ order: 2; }
        .login-box{ order: 1; width: 100%; max-width: 420px; }
    }
    @media (max-width: 480px){
        .login-box{ padding: 34px 22px; }
        .login-box h2{ font-size: 28px; }
    }
</style>
</head>
<body>
    <!-- Brand Logo -->
    <div class="brand-logo">
        <span>Recap Café</span>
    </div>

    <!-- Login Container -->
    <div class="login-container">
        <!-- Coffee Machine Animation -->
        <div class="coffee-machine-container">
            <!-- From Uiverse.io by AnnixArt - Monochrome version -->
            <div class="container">
                <div class="coffee-header">
                    <div class="coffee-header__buttons coffee-header__button-one"></div>
                    <div class="coffee-header__buttons coffee-header__button-two"></div>
                    <div class="coffee-header__display"></div>
                    <div class="coffee-header__details"></div>
                </div>
                <div class="coffee-medium">
                    <div class="coffe-medium__exit"></div>
                    <div class="coffee-medium__arm"></div>
                    <div class="coffee-medium__liquid"></div>
                    <div class="coffee-medium__smoke coffee-medium__smoke-one"></div>
                    <div class="coffee-medium__smoke coffee-medium__smoke-two"></div>
                    <div class="coffee-medium__smoke coffee-medium__smoke-three"></div>
                    <div class="coffee-medium__smoke coffee-medium__smoke-for"></div>
                    <div class="coffee-medium__cup"></div>
                </div>
                <div class="coffee-footer"></div>
            </div>
        </div>

        <!-- Login Form -->
        <form method="POST" action="auth/login.php" class="login-box" id="loginForm">
            <h2>System Access</h2>
            <p>Enter your credentials to continue</p>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="error-message show" id="errorMessage">
                <?php 
                $errors = [
                    'invalid' => 'Invalid username or password.',
                    'empty' => 'Please fill in all fields.',
                    'inactive' => 'Your account is inactive.',
                    'session' => 'Session expired. Please login again.'
                ];
                echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred. Please try again.');
                ?>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required 
                       value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="login-btn">
                Sign In
            </button>
            
            <div class="form-footer">
                <span>© <?php echo date('Y'); ?> Café Inventory System</span>
            </div>
        </form>
    </div>

    <!-- Version Tag -->
    <div class="version-tag">
        v1.0.0
    </div>

    <script>
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const errorMessage = document.getElementById('errorMessage');
            
            if (!username || !password) {
                e.preventDefault();
                
                if (!errorMessage) {
                    const newError = document.createElement('div');
                    newError.className = 'error-message show';
                    newError.textContent = 'Please fill in all fields.';
                    document.querySelector('.login-box').insertBefore(newError, document.querySelector('.form-group'));
                } else {
                    errorMessage.textContent = 'Please fill in all fields.';
                    errorMessage.classList.add('show');
                }
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.login-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Signing in...';
            submitBtn.disabled = true;
            
            // Reset button after 3 seconds if still on page
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            }, 3000);
            
            return true;
        });

        // Clear error on input
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                const errorMessage = document.getElementById('errorMessage');
                if (errorMessage) {
                    errorMessage.classList.remove('show');
                    setTimeout(() => {
                        if (!errorMessage.classList.contains('show')) {
                            errorMessage.remove();
                        }
                    }, 300);
                }
            });
        });

        // Add focus styles
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.borderColor = '#777';
                this.style.boxShadow = '0 0 0 3px rgba(119, 119, 119, 0.1)';
            });
            
            input.addEventListener('blur', function() {
                this.style.borderColor = '#e0e0e0';
                this.style.boxShadow = 'none';
            });
        });

        // Add typing effect for demo (optional)
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form inputs sequentially
            const inputs = document.querySelectorAll('input');
            inputs.forEach((input, index) => {
                input.style.opacity = '0';
                input.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    input.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                    input.style.opacity = '1';
                    input.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animate coffee machine container
            const coffeeMachine = document.querySelector('.coffee-machine-container');
            coffeeMachine.style.opacity = '0';
            coffeeMachine.style.transform = 'translateY(20px)';
            setTimeout(() => {
                coffeeMachine.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
                coffeeMachine.style.opacity = '1';
                coffeeMachine.style.transform = 'translateY(0)';
            }, 300);
        });
    </script>
</body>
</html>