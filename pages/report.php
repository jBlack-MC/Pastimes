<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}
if (($_SESSION["role"] ?? "") === "admin") {
    header("Location: admin.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id   = (int)$_SESSION["user_id"];
$user_name = $_SESSION["name"] ?? "User";
$role      = $_SESSION["role"] ?? "user";

/* Ensure the reports table exists */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblreport (
    report_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    user_name   VARCHAR(100) NOT NULL,
    order_id    INT DEFAULT NULL,
    category    VARCHAR(50) NOT NULL DEFAULT 'general',
    subject     VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    status      VARCHAR(20) DEFAULT 'open',
    admin_reply TEXT DEFAULT NULL,
    replied_at  TIMESTAMP NULL DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$flash   = "";
$flashOk = true;

/* Pre-fill order_id if coming from My Orders "Report Issue" button */
$preOrderId = (int)($_GET["order_id"] ?? 0);

/* Fetch this user's orders for the dropdown */
$myOrders = [];
$oStmt = mysqli_prepare($conn,
    "SELECT order_id, order_date, total_price FROM tblorder WHERE user_id = ? ORDER BY order_date DESC LIMIT 30"
);
if ($oStmt) {
    mysqli_stmt_bind_param($oStmt, "i", $user_id);
    mysqli_stmt_execute($oStmt);
    $oRes = mysqli_stmt_get_result($oStmt);
    while ($r = mysqli_fetch_assoc($oRes)) $myOrders[] = $r;
    mysqli_stmt_close($oStmt);
}

/* Handle report submission */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_report"])) {
    $validCats   = ["order_issue", "product_issue", "seller_complaint", "account_issue", "other"];
    $category    = in_array($_POST["category"] ?? "", $validCats, true) ? $_POST["category"] : "other";
    $subject     = trim($_POST["subject"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $order_id    = (int)($_POST["order_id"] ?? 0) ?: null;

    if (strlen($subject) < 5) {
        $flash   = "Please enter a subject (at least 5 characters).";
        $flashOk = false;
    } elseif (strlen($description) < 10) {
        $flash   = "Please describe your issue (at least 10 characters).";
        $flashOk = false;
    } else {
        if ($order_id !== null) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO tblreport (user_id, user_name, order_id, category, subject, description)
                 VALUES (?,?,?,?,?,?)"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "isisss", $user_id, $user_name, $order_id, $category, $subject, $description);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO tblreport (user_id, user_name, category, subject, description)
                 VALUES (?,?,?,?,?)"
            );
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "issss", $user_id, $user_name, $category, $subject, $description);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        $flash      = "Your report has been submitted. We'll review it and respond shortly.";
        $preOrderId = 0;
    }
}

/* Fetch this user's existing reports */
$myReports = [];
$rStmt = mysqli_prepare($conn,
    "SELECT report_id, order_id, category, subject, description, status, admin_reply, replied_at, created_at
     FROM tblreport WHERE user_id = ? ORDER BY created_at DESC"
);
if ($rStmt) {
    mysqli_stmt_bind_param($rStmt, "i", $user_id);
    mysqli_stmt_execute($rStmt);
    $rRes = mysqli_stmt_get_result($rStmt);
    while ($r = mysqli_fetch_assoc($rRes)) $myReports[] = $r;
    mysqli_stmt_close($rStmt);
}

mysqli_close($conn);

$catLabels = [
    "order_issue"       => ["label" => "Order Issue",        "icon" => "fa-box",        "cls" => "cat-order"],
    "product_issue"     => ["label" => "Product Issue",      "icon" => "fa-tshirt",     "cls" => "cat-product"],
    "seller_complaint"  => ["label" => "Seller Complaint",   "icon" => "fa-store",      "cls" => "cat-seller"],
    "account_issue"     => ["label" => "Account Issue",      "icon" => "fa-user-shield","cls" => "cat-account"],
    "other"             => ["label" => "Other",              "icon" => "fa-question",   "cls" => "cat-other"],
];

$statusMeta = [
    "open"        => ["label" => "Open",        "cls" => "st-open"],
    "in_progress" => ["label" => "In Progress", "cls" => "st-progress"],
    "resolved"    => ["label" => "Resolved",    "cls" => "st-resolved"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Submit a Report</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--sec:#5a6e6e;--muted:#8a9b9b;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;
      --border:#e8e6e1;--sm:0 4px 12px rgba(0,0,0,.03);--md:0 12px 28px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);padding:1.2rem;min-height:100vh}
    .page{max-width:900px;margin:0 auto}
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.8rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .nav{display:flex;gap:6px;flex-wrap:wrap}
    .nav a{text-decoration:none;padding:7px 13px;border-radius:40px;font-size:.8rem;font-weight:600;border:1px solid transparent;color:#fff;background:var(--green);transition:.2s}
    .nav a:hover{background:var(--dark)}
    .nav a.secondary{background:transparent;color:var(--sec);border-color:var(--border)}
    .nav a.active{outline:2px solid var(--green);outline-offset:2px}
    h1{font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:8px}
    /* flash */
    .flash{border-radius:var(--md-r);padding:.9rem 1.1rem;margin-bottom:1.2rem;display:flex;align-items:flex-start;gap:10px;font-size:.88rem}
    .flash-ok {background:#e6f4f0;color:#1b6f4e;border:1px solid #c3dfd5}
    .flash-err{background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    /* layout */
    .two-col{display:grid;grid-template-columns:1fr 360px;gap:1.5rem;align-items:start}
    @media(max-width:800px){.two-col{grid-template-columns:1fr}}
    /* form card */
    .card{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:1.5rem;box-shadow:var(--sm)}
    .card h2{font-size:1.1rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:8px;color:var(--text)}
    label{display:block;font-size:.8rem;font-weight:600;color:var(--sec);margin-bottom:5px}
    .field{margin-bottom:1rem}
    select,input[type=text],textarea{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--sm-r);font-size:.88rem;font-family:inherit;background:var(--white);transition:.15s;color:var(--text)}
    select:focus,input[type=text]:focus,textarea:focus{border-color:var(--green);outline:none;box-shadow:0 0 0 3px rgba(42,107,94,.07)}
    textarea{resize:vertical;min-height:110px}
    .btn-submit{width:100%;background:var(--green);color:#fff;border:none;padding:12px;border-radius:40px;font-size:.95rem;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;justify-content:center;gap:8px;margin-top:.5rem}
    .btn-submit:hover{background:var(--dark)}
    /* category chips */
    .cat-chips{display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.2rem}
    .cat-chip{display:flex;align-items:center;gap:6px;padding:6px 12px;border-radius:30px;font-size:.78rem;font-weight:600;cursor:pointer;border:1.5px solid var(--border);background:var(--white);color:var(--sec);transition:.2s;user-select:none}
    .cat-chip:hover{border-color:var(--green);color:var(--green)}
    .cat-chip.selected{border-color:var(--green);background:#eef7f4;color:var(--green)}
    /* history */
    .report-card{background:var(--white);border:1px solid var(--border);border-radius:var(--md-r);padding:1rem;margin-bottom:.8rem;box-shadow:var(--sm)}
    .report-top{display:flex;align-items:flex-start;justify-content:space-between;gap:.5rem;margin-bottom:.5rem}
    .report-subject{font-weight:700;font-size:.9rem;flex:1}
    .report-meta{font-size:.75rem;color:var(--muted);display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.5rem}
    .cat-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:30px;font-size:.7rem;font-weight:600}
    .cat-order  {background:#e8f0fb;color:#2a52a4}
    .cat-product{background:#fff2e7;color:#a4532f}
    .cat-seller {background:#f2e8fb;color:#6a2a9b}
    .cat-account{background:#e6f4f0;color:#1b6f4e}
    .cat-other  {background:#f2f2f2;color:#555}
    .st-open    {background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    .st-progress{background:#e8f0fb;color:#2a52a4;border:1px solid #bdd0f5}
    .st-resolved{background:#e6f4f0;color:#1b6f4e;border:1px solid #c3dfd5}
    .status-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:30px;font-size:.7rem;font-weight:600}
    .admin-reply{background:#f0fbf6;border:1px solid #c3dfd5;border-radius:var(--sm-r);padding:.7rem .9rem;margin-top:.6rem;font-size:.82rem;color:#1b6f4e}
    .admin-reply strong{display:block;margin-bottom:3px;font-size:.75rem}
    .empty-reports{text-align:center;color:var(--muted);padding:2rem;font-size:.88rem}
    .empty-reports i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}
  </style>
</head>
<body>
<div class="page">
  <header class="top-bar">
    <a href="shop.php" class="logo-area">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div><span class="logo-text">Pastimes</span></div>
    </a>
    <nav class="nav">
      <a href="shop.php">Shop</a>
      <a href="checkout.php"><i class="fas fa-shopping-cart"></i> Cart</a>
      <a href="my_orders.php">My Orders</a>
      <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
      <a href="report.php" class="active"><i class="fas fa-flag"></i> Report</a>
      <a href="sellers_hub.php">Seller Hub</a>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <h1><i class="fas fa-flag" style="color:var(--rust)"></i> Submit a Report</h1>

  <?php if ($flash): ?>
    <div class="flash <?php echo $flashOk ? 'flash-ok' : 'flash-err'; ?>">
      <i class="fas <?php echo $flashOk ? 'fa-circle-check' : 'fa-exclamation-circle'; ?>" style="margin-top:2px"></i>
      <span><?php echo htmlspecialchars($flash); ?></span>
    </div>
  <?php endif; ?>

  <div class="two-col">

    <!-- ── Submit form ── -->
    <div class="card">
      <h2><i class="fas fa-pen-to-square" style="color:var(--green)"></i> New Report</h2>

      <form method="POST" id="reportForm">

        <div class="field">
          <label>Category</label>
          <div class="cat-chips">
            <?php foreach ($catLabels as $val => $meta): ?>
            <label class="cat-chip" id="chip-<?php echo $val; ?>">
              <input type="radio" name="category" value="<?php echo $val; ?>" style="display:none"
                <?php echo (($_POST["category"] ?? "other") === $val ? "checked" : ""); ?>>
              <i class="fas <?php echo $meta['icon']; ?>"></i>
              <?php echo $meta['label']; ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="field">
          <label for="order_id">Related Order <span style="color:var(--muted);font-weight:400">(optional)</span></label>
          <select name="order_id" id="order_id">
            <option value="">— Not related to a specific order —</option>
            <?php foreach ($myOrders as $ord): ?>
            <option value="<?php echo (int)$ord["order_id"]; ?>"
              <?php echo ((int)($_POST["order_id"] ?? $preOrderId) === (int)$ord["order_id"] ? "selected" : ""); ?>>
              ORD-<?php echo str_pad($ord["order_id"], 8, "0", STR_PAD_LEFT); ?>
              — $<?php echo number_format((float)$ord["total_price"], 2); ?>
              (<?php echo date("d M Y", strtotime($ord["order_date"])); ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="subject">Subject *</label>
          <input type="text" name="subject" id="subject" maxlength="200"
            placeholder="Brief description of your issue"
            value="<?php echo htmlspecialchars($_POST["subject"] ?? ""); ?>" required>
        </div>

        <div class="field">
          <label for="description">Details *</label>
          <textarea name="description" id="description" placeholder="Please describe the problem in detail — what happened, what you expected, any relevant order or product info…" required><?php echo htmlspecialchars($_POST["description"] ?? ""); ?></textarea>
        </div>

        <button type="submit" name="submit_report" class="btn-submit">
          <i class="fas fa-paper-plane"></i> Submit Report
        </button>
      </form>
    </div>

    <!-- ── Report history ── -->
    <div>
      <div class="card" style="margin-bottom:0">
        <h2><i class="fas fa-clock-rotate-left" style="color:var(--sec)"></i> My Reports
          <span style="font-size:.8rem;color:var(--muted);font-weight:400">(<?php echo count($myReports); ?>)</span>
        </h2>

        <?php if (!$myReports): ?>
          <div class="empty-reports">
            <i class="fas fa-inbox"></i>
            <p>No reports submitted yet.</p>
          </div>
        <?php else: ?>
          <?php foreach ($myReports as $r):
            $cat = $catLabels[$r["category"]] ?? $catLabels["other"];
            $st  = $statusMeta[$r["status"]]  ?? $statusMeta["open"];
          ?>
          <div class="report-card">
            <div class="report-top">
              <div class="report-subject"><?php echo htmlspecialchars($r["subject"]); ?></div>
              <span class="status-badge <?php echo $st['cls']; ?>"><?php echo $st['label']; ?></span>
            </div>
            <div class="report-meta">
              <span class="cat-badge <?php echo $cat['cls']; ?>">
                <i class="fas <?php echo $cat['icon']; ?>"></i> <?php echo $cat['label']; ?>
              </span>
              <?php if ($r["order_id"]): ?>
              <span><i class="fas fa-receipt"></i> ORD-<?php echo str_pad($r["order_id"], 8, "0", STR_PAD_LEFT); ?></span>
              <?php endif; ?>
              <span><i class="fas fa-calendar"></i> <?php echo date("d M Y", strtotime($r["created_at"])); ?></span>
            </div>
            <?php if ($r["admin_reply"]): ?>
            <div class="admin-reply">
              <strong><i class="fas fa-shield-halved"></i> Admin Response · <?php echo date("d M Y", strtotime($r["replied_at"])); ?></strong>
              <?php echo nl2br(htmlspecialchars($r["admin_reply"])); ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
  /* Category chip visual selection */
  document.querySelectorAll('.cat-chip').forEach(chip => {
    const radio = chip.querySelector('input[type=radio]');
    if (radio && radio.checked) chip.classList.add('selected');
    chip.addEventListener('click', () => {
      document.querySelectorAll('.cat-chip').forEach(c => c.classList.remove('selected'));
      chip.classList.add('selected');
    });
  });
</script>
<?php require_once __DIR__ . '/../config/tab_guard.php'; ?>
</body>
</html>
