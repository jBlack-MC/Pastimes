<?php
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

include("../config/DBConn.php");

if (isset($_SESSION["user_id"])) {
    if (($_SESSION["role"] ?? "") === "admin") {
        header("Location: admin.php");
    } else {
        header("Location: shop.php");
    }
    exit;
}

$message = "";
$messageType = "";
$values = [
    "first_name" => "",
    "last_name" => "",
    "email" => "",
    "username" => "",
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $values["first_name"] = trim($_POST["first_name"] ?? "");
    $values["last_name"] = trim($_POST["last_name"] ?? "");
    $values["email"] = trim($_POST["email"] ?? "");
    $values["username"] = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";
    $terms = isset($_POST["terms"]);

    if ($values["first_name"] === "" || $values["last_name"] === "") {
        $message = "Please enter your full name.";
        $messageType = "error";
    } elseif (!filter_var($values["email"], FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $messageType = "error";
    } elseif (strlen($values["username"]) < 3) {
        $message = "Username must be at least 3 characters.";
        $messageType = "error";
    } elseif (strlen($password) < 6) {
        $message = "Password must be at least 6 characters.";
        $messageType = "error";
    } elseif ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "error";
    } elseif (!$terms) {
        $message = "You must agree to the terms.";
        $messageType = "error";
    } else {
        $duplicateStmt = mysqli_prepare($conn, "SELECT user_id FROM tblUser WHERE username = ? OR email = ? LIMIT 1");
        if (!$duplicateStmt) {
            $message = "System error. Please try again.";
            $messageType = "error";
        } else {
            mysqli_stmt_bind_param($duplicateStmt, "ss", $values["username"], $values["email"]);
            mysqli_stmt_execute($duplicateStmt);
            $duplicateResult = mysqli_stmt_get_result($duplicateStmt);
            $existing = mysqli_fetch_assoc($duplicateResult);
            mysqli_stmt_close($duplicateStmt);

            if ($existing) {
                $message = "Username or email already exists.";
                $messageType = "error";
            } else {
                $fullName = trim($values["first_name"] . " " . $values["last_name"]);
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $status = "active";

                $insertStmt = mysqli_prepare(
                    $conn,
                    "INSERT INTO tblUser (name, email, username, password, status) VALUES (?, ?, ?, ?, ?)"
                );

                if (!$insertStmt) {
                    $message = "System error. Please try again.";
                    $messageType = "error";
                } else {
                    mysqli_stmt_bind_param(
                        $insertStmt,
                        "sssss",
                        $fullName,
                        $values["email"],
                        $values["username"],
                        $hashedPassword,
                        $status
                    );

                    if (mysqli_stmt_execute($insertStmt)) {
                        mysqli_stmt_close($insertStmt);
                        header("Location: login.php?registered=1");
                        exit;
                    }

                    mysqli_stmt_close($insertStmt);
                    $message = "Could not create account right now.";
                    $messageType = "error";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes � Register</title>
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

    .register-card {
      max-width: 560px;
      width: 100%;
      background: var(--white);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      border: 1px solid var(--border);
      padding: 2rem;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 1.5rem;
      justify-content: center;
    }

    .logo-icon {
      width: 46px;
      height: 46px;
      background: linear-gradient(135deg, var(--green), #3a8a7a);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.35rem;
    }

    .logo-text {
      font-size: 1.75rem;
      font-weight: 700;
      letter-spacing: -0.3px;
      color: var(--green);
    }

    .badge {
      background: #eef3f1;
      color: var(--green);
      font-size: 0.7rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 30px;
      margin-left: 8px;
    }

    h1 {
      font-size: 1.6rem;
      font-weight: 600;
      text-align: center;
      margin-bottom: 0.35rem;
      color: var(--text-primary);
    }

    .sub {
      text-align: center;
      color: var(--text-secondary);
      font-size: 0.9rem;
      margin-bottom: 1.2rem;
    }

    .message {
      border-radius: var(--radius-sm);
      padding: 10px 14px;
      font-size: 0.82rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .message.error {
      background: #fef2e8;
      color: var(--rust);
      border-left: 3px solid var(--rust);
    }

    .name-row {
      display: flex;
      gap: 12px;
    }

    .input-group {
      margin-bottom: 1rem;
      width: 100%;
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
      width: 16px;
    }

    input {
      width: 100%;
      padding: 12px 14px;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-md);
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s;
      font-family: inherit;
    }

    input:focus {
      border-color: var(--green);
      box-shadow: 0 0 0 3px rgba(42, 107, 94, 0.08);
    }

    .checkbox {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      margin: 1rem 0 1.1rem;
    }

    .checkbox input {
      width: 18px;
      height: 18px;
      margin: 0;
      margin-top: 2px;
      accent-color: var(--green);
    }

    .checkbox label {
      margin: 0;
      font-size: 0.8rem;
      color: var(--text-secondary);
      font-weight: 500;
    }

    button {
      width: 100%;
      background: var(--green);
      color: white;
      border: none;
      padding: 13px;
      border-radius: 60px;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    button:hover {
      background: var(--green-dark);
      transform: translateY(-1px);
      box-shadow: 0 6px 14px rgba(42, 107, 94, 0.2);
    }

    .login-link {
      text-align: center;
      margin-top: 1.3rem;
      padding-top: 1rem;
      border-top: 1px solid var(--border);
      font-size: 0.85rem;
      color: var(--text-secondary);
    }

    .login-link a {
      color: var(--green);
      font-weight: 700;
      text-decoration: none;
    }

    .login-link a:hover {
      text-decoration: underline;
    }

    .secure-note {
      margin-top: 1.1rem;
      display: flex;
      gap: 14px;
      justify-content: center;
      flex-wrap: wrap;
      color: var(--text-muted);
      font-size: 0.72rem;
    }

    .secure-note i {
      color: var(--green);
    }

    @media (max-width: 560px) {
      .register-card {
        padding: 1.5rem;
      }

      .name-row {
        flex-direction: column;
        gap: 0;
      }
    }
  </style>
</head>
<body>
<div class="register-card">
  <div class="logo">
    <div class="logo-icon">
      <i class="fas fa-vest"></i>
    </div>
    <div>
      <span class="logo-text">Pastimes</span>
      <span class="badge">local fashion</span>
    </div>
  </div>

  <h1>Create account</h1>
  <div class="sub">Join the Pastimes marketplace</div>

  <?php if ($message !== ""): ?>
    <div class="message <?php echo htmlspecialchars($messageType); ?>">
      <i class="fas fa-exclamation-circle"></i>
      <span><?php echo htmlspecialchars($message); ?></span>
    </div>
  <?php endif; ?>

  <form method="POST" action="">
    <div class="name-row">
      <div class="input-group">
        <label for="first_name"><i class="fas fa-user"></i> First name</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($values["first_name"]); ?>" required>
      </div>
      <div class="input-group">
        <label for="last_name"><i class="fas fa-user-friends"></i> Last name</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($values["last_name"]); ?>" required>
      </div>
    </div>

    <div class="input-group">
      <label for="email"><i class="fas fa-envelope"></i> Email</label>
      <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($values["email"]); ?>" required>
    </div>

    <div class="input-group">
      <label for="username"><i class="fas fa-at"></i> Username</label>
      <input type="text" id="username" name="username" minlength="3" value="<?php echo htmlspecialchars($values["username"]); ?>" required>
    </div>

    <div class="input-group">
      <label for="password"><i class="fas fa-lock"></i> Password</label>
      <input type="password" id="password" name="password" minlength="6" required>
    </div>

    <div class="input-group">
      <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm password</label>
      <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
    </div>

    <div class="checkbox">
      <input type="checkbox" id="terms" name="terms" required>
      <label for="terms">I agree to the terms and privacy policy.</label>
    </div>

    <button type="submit"><i class="fas fa-user-plus"></i> Create account</button>

    <div class="login-link">
      Already have an account? <a href="login.php">Log in</a>
    </div>

    <div class="secure-note">
      <span><i class="fas fa-shield-alt"></i> Secure signup</span>
      <span><i class="fas fa-lock"></i> Encrypted data</span>
    </div>
  </form>
</div>
</body>
</html>
