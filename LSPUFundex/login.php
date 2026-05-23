<?php
// ============================================
// LSPUFundex - Login Page
// File: login.php
// Location: C:\xampp\htdocs\LSPUFundex\
// ============================================

require_once 'config/app.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// If already logged in, redirect away from login page
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(BASE_URL . 'admin/dashboard.php');
    } else {
        redirect(BASE_URL . 'officer/dashboard.php');
    }
}

$error   = '';
$success = '';

// ============================================
// PROCESS LOGIN FORM
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';

    } else {

        // Look up user by username — using prepared statement (SQL injection safe)
        $stmt = $conn->prepare(
            "SELECT id, full_name, username, password, role, is_active
             FROM users
             WHERE username = ?
             LIMIT 1"
        );
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if (!$user) {
            // Username not found
            $error = 'Invalid username or password.';

        } elseif ($user['is_active'] != 1) {
            // Account is disabled
            $error = 'Your account has been deactivated. Contact the administrator.';

        } elseif (!password_verify($password, $user['password'])) {
            // Password wrong
            $error = 'Invalid username or password.';

        } else {
            // ✅ Login successful — save to session
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['full_name']  = $user['full_name'];
            $_SESSION['username']   = $user['username'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['section_id'] = $user['section_id'] ?? null;


            // Update last login timestamp
            $updateStmt = $conn->prepare(
                "UPDATE users SET last_login = NOW() WHERE id = ?"
            );
            $updateStmt->bind_param("i", $user['id']);
            $updateStmt->execute();

            // Record in audit log
            logAction(
                $conn,
                $user['id'],
                'LOGIN',
                'Auth',
                $user['full_name'] . ' logged in successfully.'
            );

            // Redirect based on role
            if ($user['role'] === 'admin') {
                redirect(BASE_URL . 'admin/dashboard.php');
            } else {
                redirect(BASE_URL . 'officer/dashboard.php');
            }
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

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --dark-blue:  #0d1b2a;
            --royal-blue: #1b4f72;
            --mid-blue:   #2980b9;
            --emerald:    #27ae60;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--dark-blue) 0%, var(--royal-blue) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
        }

        /* --- Logo Area --- */
        .login-brand {
            text-align: center;
            margin-bottom: 28px;
        }

        .login-brand-icon {
            width: 70px; height: 70px;
            background: rgba(255,255,255,0.12);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            margin-bottom: 14px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
        }

        .login-brand h1 {
            font-size: 26px;
            font-weight: 800;
            color: white;
            letter-spacing: 0.5px;
        }

        .login-brand p {
            font-size: 13px;
            color: rgba(255,255,255,0.6);
            margin-top: 4px;
        }

        /* --- Card --- */
        .login-card {
            background: white;
            border-radius: 18px;
            padding: 36px 38px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.35);
        }

        .login-card h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--royal-blue);
            margin-bottom: 6px;
        }

        .login-card .subtitle {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 24px;
        }

        /* --- Form --- */
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 6px;
        }

        .input-group-text {
            background: #f4f6f9;
            border: 1.5px solid #e0e6ed;
            border-right: none;
            color: var(--royal-blue);
        }

        .form-control {
            border: 1.5px solid #e0e6ed;
            border-left: none;
            padding: 10px 14px;
            font-size: 14px;
            border-radius: 0 8px 8px 0 !important;
        }

        .form-control:focus {
            border-color: var(--mid-blue);
            box-shadow: 0 0 0 3px rgba(41,128,185,0.1);
        }

        .input-group-text {
            border-radius: 8px 0 0 8px !important;
        }

        /* --- Toggle Password --- */
        .toggle-password {
            cursor: pointer;
            border: 1.5px solid #e0e6ed;
            border-left: none;
            background: #f4f6f9;
            border-radius: 0 8px 8px 0 !important;
            padding: 0 14px;
            color: #7f8c8d;
        }

        .toggle-password:hover { color: var(--royal-blue); }

        /* --- Button --- */
        .btn-login {
            background: linear-gradient(90deg, var(--royal-blue), var(--mid-blue));
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(27,79,114,0.4);
        }

        .btn-login:active { transform: translateY(0); }

        /* --- Error Alert --- */
        .alert-error {
            background: #fdedec;
            border: 1px solid #f5b7b1;
            border-left: 4px solid #e74c3c;
            border-radius: 8px;
            padding: 12px 16px;
            color: #922b21;
            font-size: 13.5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* --- Back Link --- */
        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: rgba(255,255,255,0.7);
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link a:hover { color: white; }

        /* --- Footer Note --- */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }
    </style>
</head>
<body>

<div class="login-wrapper">

    <!-- Brand -->
    <div class="login-brand">
        <div class="login-brand-icon">
            <i class="bi bi-bank2"></i>
        </div>
        <h1>LSPUFundex</h1>
        <p>Laguna State Polytechnic University</p>
    </div>

    <!-- Login Card -->
    <div class="login-card">
        <h2>Welcome back!</h2>
        <p class="subtitle">Sign in to your officer or admin account</p>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-circle-fill"></i>
                <?php echo clean($error); ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">

            <!-- Username -->
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                    <input
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="Enter your username"
                        value="<?php echo clean($_POST['username'] ?? ''); ?>"
                        autocomplete="username"
                        required
                    >
                </div>
            </div>

            <!-- Password -->
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input
                        type="password"
                        name="password"
                        id="passwordField"
                        class="form-control"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >
                    <span class="toggle-password" onclick="togglePassword()" title="Show/Hide password">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
            </button>

        </form>
    </div>

    <!-- Back to Public -->
    <div class="back-link">
        <a href="<?php echo BASE_URL; ?>public/dashboard.php">
            <i class="bi bi-arrow-left me-1"></i>Back to Public Dashboard
        </a>
    </div>

    <!-- Footer -->
    <div class="login-footer">
        LSPUFundex &copy; <?php echo date('Y'); ?> — Financial Transparency System
    </div>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Show/hide password toggle
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