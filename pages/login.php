<?php
/* Login page – handles both customer and admin authentication */
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

require_once __DIR__ . "/../config/DBConn.php";

/* Redirect already-logged-in users away from this page */
if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: admin.php");
    } else {
        header("Location: shop.php");
    }
    exit;
}

/* $message holds any feedback text; $messageType is 'error' or 'success' */
$message = "";
$messageType = "";

/* Show a notice if returning from an admin-approved redirect (future use) */
if (isset($_GET["verified"]) && $_GET["verified"] === "1") {
    $message = "Your account has been verified. You can now log in.";
    $messageType = "success";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    /* Sanitise form input before any DB interaction */
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $message = "Please enter username and password.";
        $messageType = "error";
    } else {
        $loggedIn = false;

        /* Step 1: Check tblUser for a matching customer account */
        $userStmt = mysqli_prepare($conn, "SELECT user_id, name, username, password, status FROM tblUser WHERE username = ? LIMIT 1");
        if ($userStmt) {
            mysqli_stmt_bind_param($userStmt, "s", $username);
            mysqli_stmt_execute($userStmt);
            $userResult = mysqli_stmt_get_result($userStmt);
            $user = mysqli_fetch_assoc($userResult);
            mysqli_stmt_close($userStmt);

            if ($user) {
                if (($user["status"] ?? "") !== "active") {
                    /* User registered but admin has not yet verified the account */
                    $message = "Your account is pending admin approval. Please wait for verification before logging in.";
                    $messageType = "error";
                } else {
                    /* Validate the supplied password against the stored bcrypt hash */
                    $isValid = password_verify($password, $user["password"]);

                    if ($isValid) {
                        $_SESSION["user_id"] = (int)$user["user_id"];
                        $_SESSION["username"] = $user["username"];
                        $_SESSION["name"] = $user["name"];
                        $_SESSION["role"] = "user";

                        header("Location: shop.php");
                        exit;
                    }

                    $message = "Invalid credentials";
                    $messageType = "error";
                }

                $loggedIn = true;
            }
        }

        /* Step 2: If no customer matched (or customer login failed), check tbladmin */
        if (!$loggedIn && $message === "") {
            $adminStmt = mysqli_prepare($conn, "SELECT admin_id, username, password FROM tbladmin WHERE username = ? LIMIT 1");
            if ($adminStmt) {
                mysqli_stmt_bind_param($adminStmt, "s", $username);
                mysqli_stmt_execute($adminStmt);
                $adminResult = mysqli_stmt_get_result($adminStmt);
                $admin = mysqli_fetch_assoc($adminResult);
                mysqli_stmt_close($adminStmt);

                if ($admin) {
                    $isAdminValid = password_verify($password, $admin["password"]);

                    if ($isAdminValid) {
                        $_SESSION["user_id"] = (int)$admin["admin_id"];
                        $_SESSION["username"] = $admin["username"];
                        $_SESSION["name"] = $admin["username"];
                        $_SESSION["role"] = "admin";

                        header("Location: admin.php");
                        exit;
                    }
                }
            }

            $message = "Invalid credentials";
            $messageType = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes � Sign in</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --bg-page: #f9f7f4;
      --white: #ffffff;
      --text-primary: #1e2a2a;
      --text-secondary: #5a6e6e;
      --text-muted: #8a9b9b;
      --green: #2a6b5e;
      --green-dark: #1e5247;
      --rust: #c26743;
      --gold: #d4a259;
      --border: #e8e6e1;
      --shadow-md: 0 12px 28px rgba(0, 0, 0, 0.06);
      --radius-lg: 28px;
      --radius-md: 16px;
      --radius-sm: 12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .login-container {
      max-width: 460px;
      width: 100%;
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border);
      padding: 2rem 2rem 2.2rem;
    }

    .logo-area {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 1.8rem;
      flex-wrap: wrap;
    }

    .logo-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, var(--green), #3a8a7a);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.5rem;
    }

    .logo-text {
      font-size: 1.8rem;
      font-weight: 700;
      letter-spacing: -0.3px;
      color: var(--green);
    }

    .badge {
      background: #eef3f1;
      color: var(--green);
      font-size: 0.7rem;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 30px;
      margin-left: 8px;
    }

    h1 {
      font-size: 1.7rem;
      font-weight: 600;
      text-align: center;
      margin-bottom: 0.4rem;
      color: var(--text-primary);
    }

    .subtitle {
      text-align: center;
      color: var(--text-secondary);
      font-size: 0.9rem;
      margin-bottom: 1.1rem;
    }

    .notice {
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      font-size: 0.82rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .notice.error {
      background: #fef2e8;
      color: var(--rust);
      border-left: 3px solid var(--rust);
    }

    .notice.success {
      background: #e8f7ef;
      color: #1b6f4e;
      border-left: 3px solid #1b6f4e;
    }

    .input-group {
      margin-bottom: 1.2rem;
    }

    label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      margin-bottom: 6px;
      color: var(--text-primary);
    }

    label i {
      margin-right: 6px;
      color: var(--green);
      width: 18px;
    }

    .input-field {
      width: 100%;
      padding: 12px 16px;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-md);
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s;
      font-family: inherit;
    }

    .input-field:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(42, 107, 94, 0.08);
    }

    .password-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }

    .forgot-link {
      font-size: 0.75rem;
      color: var(--rust);
      text-decoration: none;
      font-weight: 500;
    }

    .forgot-link:hover {
      text-decoration: underline;
    }

    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 1rem 0 1.2rem;
    }

    .checkbox-group input {
      width: 18px;
      height: 18px;
      accent-color: var(--green);
      cursor: pointer;
    }

    .checkbox-group label {
      margin: 0;
      font-size: 0.8rem;
      font-weight: normal;
      color: var(--text-secondary);
    }

    .login-btn {
      width: 100%;
      background: var(--green);
      color: white;
      border: none;
      padding: 14px;
      border-radius: 60px;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-top: 6px;
    }

    .login-btn:hover {
      background: var(--green-dark);
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(42, 107, 94, 0.2);
    }

    .register-link {
      text-align: center;
      margin-top: 1.6rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border);
      font-size: 0.85rem;
      color: var(--text-secondary);
    }

    .register-link a {
      color: var(--green);
      font-weight: 700;
      text-decoration: none;
    }

    .register-link a:hover {
      text-decoration: underline;
    }

    .secure-footer {
      margin-top: 1.5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 16px;
      flex-wrap: wrap;
      font-size: 0.7rem;
      color: var(--text-muted);
    }

    .secure-footer i {
      color: var(--green);
    }

    @media (max-width: 500px) {
      .login-container {
        padding: 1.5rem;
      }

      h1 {
        font-size: 1.5rem;
      }

      .logo-text {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
<div class="login-container">
  <div class="logo-area">
    <div class="logo-icon">
      <i class="fas fa-vest"></i>
    </div>
    <div>
      <span class="logo-text">Pastimes</span>
      <span class="badge">local fashion</span>
    </div>
  </div>

  <h1>Welcome back</h1>
  <div class="subtitle">Sign in to your account</div>

  <?php if ($message !== ""): ?>
    <div class="notice <?php echo htmlspecialchars($messageType); ?>">
      <i class="fas <?php echo $messageType === "success" ? "fa-check-circle" : "fa-exclamation-circle"; ?>"></i>
      <span><?php echo htmlspecialchars($message); ?></span>
    </div>
  <?php endif; ?>

  <form id="loginForm" method="POST" action="">
    <div class="input-group">
      <label for="username"><i class="fas fa-user"></i> Username</label>
      <input type="text" id="username" name="username" class="input-field" placeholder="john123" value="<?php echo htmlspecialchars($_POST["username"] ?? ""); ?>" required>
    </div>

    <div class="input-group">
      <div class="password-row">
        <label for="password"><i class="fas fa-lock"></i> Password</label>
        <a href="register.php" class="forgot-link">Create account</a>
      </div>
      <input type="password" id="password" name="password" class="input-field" placeholder="Enter your password" required>
    </div>

    <div class="checkbox-group">
      <input type="checkbox" id="rememberMe" disabled>
      <label for="rememberMe">Remember me (coming soon)</label>
    </div>

    <button type="submit" class="login-btn">
      <i class="fas fa-arrow-right-to-bracket"></i> Sign in
    </button>
  </form>

  <div class="register-link">
    Don't have an account? <a href="register.php">Create account</a>
  </div>

  <div class="secure-footer">
    <span><i class="fas fa-shield-alt"></i> Secure login</span>
    <span><i class="fas fa-lock"></i> Encrypted</span>
    <span><i class="fas fa-store"></i> Trusted marketplace</span>
  </div>
</div>
</body>
</html>
