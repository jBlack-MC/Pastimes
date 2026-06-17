<?php
/**
 * Seller Registration
 * 
 * Allows users to register as sellers/vendors
 * Creates record in tblseller with pending status
 */

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"] ?? "User";
$error_message = "";
$success_message = "";

// Check if user is already a seller
$checkStmt = mysqli_prepare($conn, 
    "SELECT seller_id, approval_status FROM tblseller WHERE user_id = ?"
);

if ($checkStmt) {
    mysqli_stmt_bind_param($checkStmt, "i", $user_id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    $existingSeller = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($checkStmt);
} else {
    $existingSeller = null;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if already a seller
    if ($existingSeller) {
        $error_message = "You are already registered as a seller. Status: " . ucfirst($existingSeller["approval_status"]);
    } else {
        $brand_name = trim($_POST["brand_name"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        
        // Validation
        if (empty($brand_name)) {
            $error_message = "Brand name is required.";
        } elseif (strlen($brand_name) < 3) {
            $error_message = "Brand name must be at least 3 characters.";
        } elseif (empty($description)) {
            $error_message = "Description is required.";
        } elseif (strlen($description) < 10) {
            $error_message = "Description must be at least 10 characters.";
        } elseif (empty($phone)) {
            $error_message = "Phone number is required.";
        } elseif (!preg_match("/^[0-9\s\-\+\(\)]{10,}$/", $phone)) {
            $error_message = "Phone number must be valid (at least 10 digits).";
        } else {
            // Insert into tblseller
            $insertStmt = mysqli_prepare($conn,
                "INSERT INTO tblseller (user_id, brand_name, description, phone, approval_status, created_date) 
                 VALUES (?, ?, ?, ?, 'pending', NOW())"
            );
            
            if (!$insertStmt) {
                $error_message = "Database error: " . mysqli_error($conn);
            } else {
                mysqli_stmt_bind_param($insertStmt, "isss", $user_id, $brand_name, $description, $phone);
                
                if (mysqli_stmt_execute($insertStmt)) {
                    $success_message = "Seller registration submitted! You'll be able to add products once approved by an admin.";
                    $_SESSION["is_seller"] = true;
                    $_SESSION["seller_status"] = "pending";
                    
                    // Optionally redirect after 3 seconds
                    echo "<script>setTimeout(() => { window.location.href = 'sellers_hub.php'; }, 3000);</script>";
                } else {
                    $error_message = "Error registering as seller: " . mysqli_error($conn);
                }
                
                mysqli_stmt_close($insertStmt);
            }
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Seller - Pastimes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --sand: #f6efe5;
            --paper: #fffaf4;
            --ink: #1f1a17;
            --muted: #6e635a;
            --rust: #bf5a36;
            --forest: #1f5a4d;
            --gold: #d7a85f;
            --line: rgba(31, 26, 23, 0.12);
            --shadow: 0 18px 45px rgba(31, 26, 23, 0.12);
            --radius-lg: 28px;
            --radius-md: 18px;
            --radius-sm: 12px;
        }

        body {
            font-family: system-ui, "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(180deg, #fcf7f0 0%, #f5ede2 100%);
            color: var(--ink);
            min-height: 100vh;
        }

        .app-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 1.2rem;
            background: rgba(255, 250, 244, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 60px;
            backdrop-filter: blur(12px);
            margin-bottom: 2rem;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo-icon {
            width: 42px;
            height: 42px;
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--forest), #2f7968);
            color: white;
            font-size: 1.4rem;
        }

        .logo-text {
            font-weight: 800;
            font-size: 1.6rem;
            color: var(--forest);
        }

        .nav-actions {
            display: flex;
            gap: 8px;
        }

        .nav-icon-btn {
            background: rgba(255, 250, 244, 0.8);
            border: 1px solid var(--line);
            width: 42px;
            height: 42px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            text-decoration: none;
            color: var(--ink);
        }

        .nav-icon-btn:hover {
            background: var(--sand);
        }

        .card {
            background: var(--paper);
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(255, 255, 240, 0.8);
        }

        .card h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card p {
            color: var(--muted);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .alert {
            padding: 1rem 1.2rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(191, 90, 54, 0.1);
            color: var(--rust);
            border: 1px solid rgba(191, 90, 54, 0.2);
        }

        .alert-success {
            background: rgba(31, 90, 77, 0.1);
            color: var(--forest);
            border: 1px solid rgba(31, 90, 77, 0.2);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--ink);
        }

        .form-group label span {
            color: var(--rust);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            transition: 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--forest);
            box-shadow: 0 0 0 3px rgba(31, 90, 77, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .char-count {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 0.3rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            flex: 1;
            padding: 0.9rem 1.2rem;
            border: none;
            border-radius: 40px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--forest);
            color: white;
        }

        .btn-primary:hover {
            background: #1e5247;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: transparent;
            color: var(--forest);
            border: 1px solid var(--forest);
        }

        .btn-secondary:hover {
            background: var(--sand);
        }

        .already-seller {
            text-align: center;
            padding: 3rem 2rem;
        }

        .already-seller .icon {
            font-size: 3rem;
            color: var(--forest);
            margin-bottom: 1rem;
        }

        .already-seller h2 {
            margin-bottom: 1rem;
        }

        .already-seller p {
            color: var(--muted);
            margin-bottom: 1.5rem;
        }

        @media (max-width: 480px) {
            .app-wrapper {
                padding: 1rem;
            }

            .card {
                padding: 1.5rem;
            }

            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="app-wrapper">
        <!-- Header -->
        <div class="top-bar">
            <a href="shop.php" class="logo-area">
                <div class="logo-icon"><i class="fas fa-vest"></i></div>
                <div class="logo-text">Pastimes</div>
            </a>
            <div class="nav-actions">
                <a href="shop.php" class="nav-icon-btn" title="Shop">
                    <i class="fas fa-store"></i>
                </a>
                <a href="logout.php" class="nav-icon-btn" title="Logout">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card">
            <?php if ($existingSeller): ?>
                <!-- Already a seller -->
                <div class="already-seller">
                    <div class="icon"><i class="fas fa-store"></i></div>
                    <h2>You're Already a Seller!</h2>
                    <p>Status: <strong><?php echo ucfirst($existingSeller["approval_status"]); ?></strong></p>
                    <?php if ($existingSeller["approval_status"] === "pending"): ?>
                    <p>Your seller account is pending admin approval. Once approved, you can start adding products.</p>
                    <?php elseif ($existingSeller["approval_status"] === "approved"): ?>
                    <p>Your seller account is active! You can now add and manage your products.</p>
                    <?php endif; ?>
                    <div class="form-actions">
                        <a href="sellers_hub.php" class="btn btn-primary">
                            <i class="fas fa-th-large"></i> Go to Seller Hub
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Registration Form -->
                <h1><i class="fas fa-store"></i> Become a Seller</h1>
                <p>Join Pastimes as a seller and reach our community of fashion enthusiasts.</p>

                <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success_message); ?></span>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label for="brand_name">
                            Brand Name <span>*</span>
                        </label>
                        <input 
                            type="text" 
                            id="brand_name" 
                            name="brand_name" 
                            placeholder="e.g., Threads & Dreams"
                            minlength="3"
                            maxlength="100"
                            required
                        >
                        <div class="char-count" id="brand-count">0 / 100 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="description">
                            Brand Description <span>*</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            placeholder="Tell customers about your brand, your style, and what makes you unique..."
                            minlength="10"
                            maxlength="500"
                            required
                        ></textarea>
                        <div class="char-count" id="desc-count">0 / 500 characters</div>
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            Phone Number <span>*</span>
                        </label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            placeholder="e.g., 061 234 5678"
                            pattern="[0-9\s\-\+\(\)]+"
                            required
                        >
                        <div class="char-count">For admin contact</div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle"></i> Register as Seller
                        </button>
                        <a href="shop.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                    </div>
                </form>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--line); text-align: center;">
                    <h3 style="font-size: 1rem; margin-bottom: 1rem;">What happens next?</h3>
                    <ul style="text-align: left; display: inline-block; color: var(--muted); font-size: 0.9rem;">
                        <li style="margin-bottom: 0.8rem;"><i class="fas fa-check" style="color: var(--forest);"></i> Your application will be reviewed by our admin team</li>
                        <li style="margin-bottom: 0.8rem;"><i class="fas fa-check" style="color: var(--forest);"></i> Once approved, you can add products to your shop</li>
                        <li style="margin-bottom: 0.8rem;"><i class="fas fa-check" style="color: var(--forest);"></i> Your products will appear in the marketplace</li>
                        <li style="margin-bottom: 0.8rem;"><i class="fas fa-check" style="color: var(--forest);"></i> Start receiving orders from customers</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Character counters
        const brandInput = document.getElementById('brand_name');
        const descInput = document.getElementById('description');

        if (brandInput) {
            brandInput.addEventListener('input', (e) => {
                document.getElementById('brand-count').textContent = e.target.value.length + ' / 100 characters';
            });
        }

        if (descInput) {
            descInput.addEventListener('input', (e) => {
                document.getElementById('desc-count').textContent = e.target.value.length + ' / 500 characters';
            });
        }
    </script>
</body>
</html>
