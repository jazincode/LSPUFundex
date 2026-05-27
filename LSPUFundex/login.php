<?php
// ============================================
// LSPUFundex - Login Page
// File: login.php
// ============================================

require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    if (isAdmin())        redirect(BASE_URL . 'admin/dashboard.php');
    elseif (isCouncil())  redirect(BASE_URL . 'council/dashboard.php');
    else                  redirect(BASE_URL . 'officer/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id, full_name, username, password, role,
                    is_active, section_id, department_id
             FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $error = 'Invalid username or password.';
        } elseif ($user['is_active'] != 1) {
            $error = 'Your account has been deactivated. Contact the administrator.';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Invalid username or password.';
        } else {
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['section_id']    = $user['section_id']    ?? null;
            $_SESSION['department_id'] = $user['department_id'] ?? null;

            $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $upd->bind_param("i", $user['id']);
            $upd->execute();
            $upd->close();

            logAction($conn, $user['id'], 'LOGIN', 'Auth',
                $user['full_name'] . ' logged in successfully.');

            if ($user['role'] === 'admin')         redirect(BASE_URL . 'admin/dashboard.php');
            elseif ($user['role'] === 'council')   redirect(BASE_URL . 'council/dashboard.php');
            else                                   redirect(BASE_URL . 'officer/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — LSPUFundex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        /* ============================================
           BACKGROUND
           ============================================ */
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background:
                url('assets/images/campus bg.png')
                no-repeat center center / cover;
            position: relative;
        }

        /* Dark overlay on background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: rgba(5, 15, 35, 0.62);
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
            z-index: 0;
        }

        /* ============================================
           MAIN CARD WRAPPER
           ============================================ */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 460px;
        }

        /* ============================================
           GLASS CARD
           ============================================ */
        .glass-card {
            background: rgba(15, 30, 60, 0.55);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            padding: 48px 44px 40px;
            box-shadow:
                0 32px 64px rgba(0, 0, 0, 0.45),
                inset 0 1px 0 rgba(255,255,255,0.08);
            text-align: center;
        }

        /* ============================================
           LOGO AT TOP CENTER
           ============================================ */
        .logo-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 22px;
        }

        .logo-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 8px 32px rgba(0, 0, 0, 0.4),
                0 0 0 4px rgba(255,255,255,0.15);
            overflow: hidden;
            flex-shrink: 0;
        }

        .logo-circle img {
            width: 88px;
            height: 88px;
            object-fit: contain;
        }

        /* Fallback icon if image doesn't load */
        .logo-circle .logo-fallback {
            font-size: 42px;
            color: #3FA4D2;
        }

        /* ============================================
           TITLE
           ============================================ */
        .brand-title {
            font-size: 38px;
            font-weight: 900;
            letter-spacing: -0.5px;
            line-height: 1;
            margin-bottom: 8px;
            color: white;
        }

        .brand-title .accent {
            color: #3FA4D2;
        }

        .brand-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.55);
            font-weight: 400;
            letter-spacing: 0.2px;
            margin-bottom: 22px;
        }

        /* Blue divider line */
        .brand-divider {
            width: 48px;
            height: 2.5px;
            background: linear-gradient(90deg, #59A8A6, #3FA4D2);
            border-radius: 2px;
            margin: 0 auto 24px;
        }

        /* ============================================
           WELCOME TEXT
           ============================================ */
        .welcome-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 4px;
        }

        .welcome-sub {
            font-size: 13.5px;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 28px;
        }

        /* ============================================
           ERROR ALERT
           ============================================ */
        .alert-error {
            background: rgba(231, 76, 60, 0.18);
            border: 1px solid rgba(231, 76, 60, 0.4);
            border-left: 3px solid #e74c3c;
            border-radius: 10px;
            padding: 12px 16px;
            color: #ff8a80;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ============================================
           INPUT FIELDS — Glassmorphism style
           ============================================ */
        .input-glass-wrap {
            position: relative;
            margin-bottom: 14px;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.45);
            font-size: 17px;
            pointer-events: none;
            z-index: 2;
        }

        .glass-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.07);
            border: 1.5px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 14px 48px 14px 46px;
            font-size: 14px;
            color: white;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.25s ease, background 0.25s ease;
            outline: none;
        }

        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.35);
        }

        .glass-input:focus {
            border-color: rgba(63, 164, 210, 0.7);
            background: rgba(255, 255, 255, 0.11);
            box-shadow: 0 0 0 3px rgba(63, 164, 210, 0.12);
        }

        /* Password toggle */
        .toggle-pwd {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            font-size: 17px;
            z-index: 2;
            transition: color 0.2s;
        }

        .toggle-pwd:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        /* ============================================
           LOGIN BUTTON
           ============================================ */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(90deg, #3a8fd4, #3FA4D2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15.5px;
            font-weight: 700;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 6px 20px rgba(63, 164, 210, 0.4);
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(63, 164, 210, 0.55);
            background: linear-gradient(90deg, #3FA4D2, #59A8A6);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* ============================================
           SECURE ACCESS FOOTER
           ============================================ */
        .secure-footer {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 24px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 12px;
        }

        .secure-footer::before,
        .secure-footer::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .secure-footer span {
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* ============================================
           BACK LINK
           ============================================ */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: rgba(255, 255, 255, 0.45);
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .back-link a:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        /* ============================================
           COPYRIGHT
           ============================================ */
        .login-copyright {
            text-align: center;
            margin-top: 14px;
            font-size: 11.5px;
            color: rgba(255, 255, 255, 0.2);
        }

        /* ============================================
           RESPONSIVE
           ============================================ */
        @media (max-width: 480px) {
            .glass-card {
                padding: 40px 24px 32px;
            }
            .brand-title { font-size: 32px; }
            .logo-circle { width: 88px; height: 88px; }
            .logo-circle img { width: 76px; height: 76px; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="glass-card">

        <!-- ======== LOGO ======== -->
        <div class="logo-wrap">
            <div class="logo-circle">
                <?php
                $logoPath = 'assets/images/lspu-logo.png';
                if (file_exists($logoPath)): ?>
                    <img src="<?php echo BASE_URL; ?>assets/images/lspu-logo.png"
                         alt="LSPU Logo">
                <?php else: ?>
                    <i class="bi bi-bank2 logo-fallback"></i>
                <?php endif; ?>
            </div>
        </div>

        <!-- ======== TITLE ======== -->
        <div class="brand-title">
            LSPU<span class="accent">Fundex</span>
        </div>
        <div class="brand-subtitle">
            Campus Financial Transparency System
        </div>

        <!-- Divider -->
        <div class="brand-divider"></div>

        <!-- ======== WELCOME TEXT ======== -->
        <div class="welcome-title">Welcome back!</div>
        <div class="welcome-sub">Sign in to your account</div>

        <!-- ======== ERROR MESSAGE ======== -->
        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo clean($error); ?>
            </div>
        <?php endif; ?>

        <!-- ======== LOGIN FORM ======== -->
        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token"
                   value="<?php echo csrf_token(); ?>">

            <!-- Username -->
            <div class="input-glass-wrap">
                <i class="bi bi-person input-icon"></i>
                <input
                    type="text"
                    name="username"
                    class="glass-input"
                    placeholder="Username"
                    value="<?php echo clean($_POST['username'] ?? ''); ?>"
                    autocomplete="username"
                    required
                >
            </div>

            <!-- Password -->
            <div class="input-glass-wrap">
                <i class="bi bi-lock input-icon"></i>
                <input
                    type="password"
                    name="password"
                    id="passwordField"
                    class="glass-input"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                >
                <span class="toggle-pwd" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Log In
            </button>
        </form>

        <!-- ======== SECURE ACCESS ======== -->
        <div class="secure-footer">
            <span>
                <i class="bi bi-shield-check"></i>
                Secure access
            </span>
        </div>

    </div><!-- end glass-card -->

    <!-- Back to Public -->
    <div class="back-link">
        <a href="<?php echo BASE_URL; ?>public/dashboard.php">
            <i class="bi bi-arrow-left"></i>
            Back to Public Dashboard
        </a>
    </div>

    <!-- Copyright -->
    <div class="login-copyright">
        &copy; 2026 Laguna State Polytechnic University
    </div>

</div><!-- end login-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function togglePassword() {
        const field   = document.getElementById('passwordField');
        const eyeIcon = document.getElementById('eyeIcon');
        if (field.type === 'password') {
            field.type        = 'text';
            eyeIcon.className = 'bi bi-eye-slash';
        } else {
            field.type        = 'password';
            eyeIcon.className = 'bi bi-eye';
        }
    }
</script>
</body>
</html>