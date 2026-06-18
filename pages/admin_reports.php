<?php
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

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

$flash = "";

/* Handle admin reply + status update */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["report_id"])) {
    $rid    = (int)$_POST["report_id"];
    $reply  = trim($_POST["admin_reply"] ?? "");
    $valid  = ["open", "in_progress", "resolved"];
    $status = in_array($_POST["status"] ?? "", $valid, true) ? $_POST["status"] : "in_progress";

    if ($rid > 0) {
        if ($reply !== "") {
            $s = mysqli_prepare($conn,
                "UPDATE tblreport SET admin_reply=?, replied_at=NOW(), status=? WHERE report_id=?"
            );
            if ($s) {
                mysqli_stmt_bind_param($s, "ssi", $reply, $status, $rid);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
            }
        } else {
            $s = mysqli_prepare($conn,
                "UPDATE tblreport SET status=? WHERE report_id=?"
            );
            if ($s) {
                mysqli_stmt_bind_param($s, "si", $status, $rid);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
            }
        }
    }
    header("Location: admin_reports.php?sf=" . urlencode($_GET["sf"] ?? "all") . "&msg=updated");
    exit;
}

$flash = match ($_GET["msg"] ?? "") {
    "updated" => "Report updated successfully.",
    default   => "",
};

/* Filter */
$sf    = $_GET["sf"] ?? "all";
$valid = ["all", "open", "in_progress", "resolved"];
if (!in_array($sf, $valid, true)) $sf = "all";

$where = $sf !== "all" ? "WHERE r.status = '" . mysqli_real_escape_string($conn, $sf) . "'" : "";

/* Load reports */
$reports = [];
$rResult = mysqli_query($conn,
    "SELECT r.report_id, r.user_id, r.user_name, r.order_id, r.category,
            r.subject, r.description, r.status, r.admin_reply, r.replied_at, r.created_at
     FROM tblreport r
     $where
     ORDER BY r.created_at DESC"
);
if ($rResult) {
    while ($row = mysqli_fetch_assoc($rResult)) $reports[] = $row;
}

/* Counts per status for the filter tabs */
$countsQ = mysqli_query($conn, "SELECT status, COUNT(*) AS n FROM tblreport GROUP BY status");
$counts  = ["open" => 0, "in_progress" => 0, "resolved" => 0, "all" => 0];
if ($countsQ) {
    while ($c = mysqli_fetch_assoc($countsQ)) {
        $counts[$c["status"]] = (int)$c["n"];
        $counts["all"] += (int)$c["n"];
    }
}

mysqli_close($conn);

$catLabels = [
    "order_issue"      => ["label" => "Order Issue",      "icon" => "fa-box",         "cls" => "cat-order"],
    "product_issue"    => ["label" => "Product Issue",    "icon" => "fa-tshirt",      "cls" => "cat-product"],
    "seller_complaint" => ["label" => "Seller Complaint", "icon" => "fa-store",       "cls" => "cat-seller"],
    "account_issue"    => ["label" => "Account Issue",    "icon" => "fa-user-shield", "cls" => "cat-account"],
    "other"            => ["label" => "Other",            "icon" => "fa-question",    "cls" => "cat-other"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Admin Reports</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--sec:#5a6e6e;--muted:#8a9b9b;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;--gold:#d4a259;
      --border:#e8e6e1;--sm:0 4px 12px rgba(0,0,0,.03);--md:0 12px 28px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);padding:1.2rem;min-height:100vh}
    .page{max-width:1100px;margin:0 auto}
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.8rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .badge{background:#f6efe0;color:#7a5a2a;font-size:.68rem;font-weight:600;padding:4px 10px;border-radius:30px;margin-left:8px}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{text-decoration:none;background:var(--green);color:#fff;border-radius:40px;padding:8px 14px;font-size:.82rem;font-weight:600;transition:.2s}
    .nav a:hover{background:var(--dark)}
    .nav a.secondary{background:transparent;color:var(--sec);border:1px solid var(--border)}
    /* flash */
    .flash{margin-bottom:1rem;border-radius:var(--sm-r);background:#e8f7ef;color:#1b6f4e;padding:10px 14px;font-size:.82rem;border-left:3px solid #1b6f4e}
    /* stats row */
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:.8rem;margin-bottom:1.2rem}
    .stat{background:var(--white);border:1px solid var(--border);border-radius:var(--md-r);padding:.9rem;box-shadow:var(--sm)}
    .stat-lbl{font-size:.75rem;color:var(--muted);margin-bottom:.2rem}
    .stat-val{font-size:1.4rem;font-weight:700;color:var(--green)}
    /* filter tabs */
    .filter-bar{display:flex;gap:8px;margin-bottom:1rem;flex-wrap:wrap}
    .ftab{text-decoration:none;padding:7px 16px;border-radius:40px;border:1px solid var(--border);color:var(--sec);font-size:.82rem;font-weight:600;background:#fff;transition:.2s}
    .ftab:hover{background:var(--bg)}
    .ftab.active{background:var(--green);color:#fff;border-color:var(--green)}
    .ftab .cnt{background:rgba(255,255,255,.3);border-radius:99px;padding:1px 7px;font-size:.7rem;margin-left:4px}
    .ftab:not(.active) .cnt{background:var(--border);color:var(--sec)}
    /* report cards */
    .report-card{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);box-shadow:var(--sm);margin-bottom:.9rem;overflow:hidden}
    .r-head{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;padding:1rem 1.2rem;gap:.8rem;cursor:pointer;border-bottom:1px solid transparent;transition:.15s}
    .r-head:hover{background:#fafaf8}
    .r-head.open{border-bottom-color:var(--border)}
    .r-meta{display:flex;flex-wrap:wrap;gap:.5rem;margin-top:.3rem;align-items:center}
    .r-subject{font-weight:700;font-size:.95rem}
    .r-user{font-size:.8rem;color:var(--sec)}
    .r-body{display:none;padding:1.2rem}
    .r-body.open{display:block}
    /* badges */
    .cat-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:30px;font-size:.7rem;font-weight:600}
    .cat-order  {background:#e8f0fb;color:#2a52a4}
    .cat-product{background:#fff2e7;color:#a4532f}
    .cat-seller {background:#f2e8fb;color:#6a2a9b}
    .cat-account{background:#e6f4f0;color:#1b6f4e}
    .cat-other  {background:#f2f2f2;color:#555}
    .st-open    {background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    .st-progress{background:#e8f0fb;color:#2a52a4;border:1px solid #bdd0f5}
    .st-resolved{background:#e6f4f0;color:#1b6f4e;border:1px solid #c3dfd5}
    .sbadge{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:30px;font-size:.72rem;font-weight:600}
    .muted{color:var(--muted);font-size:.78rem}
    /* description box */
    .desc-box{background:var(--bg);border:1px solid var(--border);border-radius:var(--sm-r);padding:.8rem 1rem;font-size:.86rem;line-height:1.6;margin-bottom:1rem;white-space:pre-wrap}
    /* existing reply */
    .prev-reply{background:#e6f4f0;border:1px solid #c3dfd5;border-radius:var(--sm-r);padding:.8rem 1rem;font-size:.86rem;margin-bottom:1rem}
    .prev-reply strong{display:block;font-size:.75rem;color:#1b6f4e;margin-bottom:.3rem}
    /* reply form */
    .reply-form label{font-size:.8rem;font-weight:600;color:var(--sec);display:block;margin-bottom:5px}
    .reply-form textarea{width:100%;padding:10px 12px;border:1.5px solid var(--border);border-radius:var(--sm-r);font-size:.86rem;font-family:inherit;resize:vertical;min-height:90px;transition:.15s}
    .reply-form textarea:focus{border-color:var(--green);outline:none}
    .reply-form select{padding:8px 12px;border:1.5px solid var(--border);border-radius:var(--sm-r);font-size:.86rem;font-family:inherit;margin-bottom:.8rem;width:100%}
    .reply-btns{display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.7rem}
    .btn{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:30px;padding:8px 16px;font-size:.8rem;font-weight:700;cursor:pointer;transition:.2s}
    .btn-green{background:var(--green);color:#fff}
    .btn-green:hover{background:var(--dark)}
    .btn-outline{background:transparent;border:1px solid var(--border);color:var(--sec)}
    .btn-outline:hover{background:var(--bg)}
    .empty{padding:2rem;text-align:center;color:var(--muted);background:var(--white);border:1px solid var(--border);border-radius:var(--lg)}
    .empty i{font-size:2.5rem;display:block;margin-bottom:.7rem;opacity:.3}
    .chevron{transition:transform .2s;color:var(--muted)}
    @media(max-width:600px){.r-head{flex-direction:column}}
  </style>
</head>
<body>
<div class="page">
  <header class="top-bar">
    <a href="admin.php" class="logo-area">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div><span class="logo-text">Pastimes</span><span class="badge">Admin Console</span></div>
    </a>
    <nav class="nav">
      <a href="admin.php">Dashboard</a>
      <a href="admin_orders.php">Orders</a>
      <a href="admin_messages.php">Messages</a>
      <a href="admin_reports.php">Reports</a>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if ($flash): ?>
    <div class="flash"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats">
    <div class="stat" style="border-left:3px solid var(--rust)">
      <div class="stat-lbl">Open</div>
      <div class="stat-val" style="color:var(--rust)"><?php echo $counts["open"]; ?></div>
    </div>
    <div class="stat" style="border-left:3px solid #2a52a4">
      <div class="stat-lbl">In Progress</div>
      <div class="stat-val" style="color:#2a52a4"><?php echo $counts["in_progress"]; ?></div>
    </div>
    <div class="stat" style="border-left:3px solid var(--green)">
      <div class="stat-lbl">Resolved</div>
      <div class="stat-val"><?php echo $counts["resolved"]; ?></div>
    </div>
    <div class="stat">
      <div class="stat-lbl">Total Reports</div>
      <div class="stat-val"><?php echo $counts["all"]; ?></div>
    </div>
  </div>

  <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:.9rem;display:flex;align-items:center;gap:8px">
    <i class="fas fa-flag" style="color:var(--rust)"></i> Customer Reports
  </h2>

  <!-- Filter tabs -->
  <div class="filter-bar">
    <?php foreach (["all" => "All", "open" => "Open", "in_progress" => "In Progress", "resolved" => "Resolved"] as $k => $lbl): ?>
      <a class="ftab <?php echo $sf === $k ? 'active' : ''; ?>"
         href="admin_reports.php?sf=<?php echo $k; ?>">
        <?php echo $lbl; ?>
        <span class="cnt"><?php echo $counts[$k] ?? 0; ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$reports): ?>
    <div class="empty">
      <i class="fas fa-inbox"></i>
      <p>No reports <?php echo $sf !== "all" ? "with status \"$sf\"" : ""; ?> found.</p>
    </div>

  <?php else: ?>
    <?php foreach ($reports as $i => $r):
      $cat = $catLabels[$r["category"]] ?? $catLabels["other"];
      $stCls = match($r["status"]) {
          "in_progress" => "st-progress",
          "resolved"    => "st-resolved",
          default       => "st-open",
      };
      $stLbl = match($r["status"]) {
          "in_progress" => "In Progress",
          "resolved"    => "Resolved",
          default       => "Open",
      };
    ?>
    <div class="report-card">
      <div class="r-head" id="rhead-<?php echo $r['report_id']; ?>"
           onclick="toggleReport(<?php echo $r['report_id']; ?>)">
        <div style="flex:1;min-width:0">
          <div class="r-subject"><?php echo htmlspecialchars($r["subject"]); ?></div>
          <div class="r-meta">
            <span class="cat-badge <?php echo $cat['cls']; ?>">
              <i class="fas <?php echo $cat['icon']; ?>"></i> <?php echo $cat['label']; ?>
            </span>
            <?php if ($r["order_id"]): ?>
              <span class="muted"><i class="fas fa-receipt"></i> ORD-<?php echo str_pad($r["order_id"], 8, "0", STR_PAD_LEFT); ?></span>
            <?php endif; ?>
            <span class="muted"><i class="fas fa-user"></i> <?php echo htmlspecialchars($r["user_name"]); ?></span>
            <span class="muted"><i class="fas fa-calendar"></i> <?php echo date("d M Y, g:i a", strtotime($r["created_at"])); ?></span>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:.6rem;flex-shrink:0">
          <span class="sbadge <?php echo $stCls; ?>"><?php echo $stLbl; ?></span>
          <i class="fas fa-chevron-down chevron" id="ricon-<?php echo $r['report_id']; ?>"></i>
        </div>
      </div>

      <div class="r-body" id="rbody-<?php echo $r['report_id']; ?>">

        <!-- Customer's description -->
        <div style="margin-bottom:.8rem">
          <div style="font-size:.78rem;font-weight:600;color:var(--muted);margin-bottom:.4rem">
            <i class="fas fa-user"></i> Customer's report
          </div>
          <div class="desc-box"><?php echo htmlspecialchars($r["description"]); ?></div>
        </div>

        <!-- Existing reply (if any) -->
        <?php if ($r["admin_reply"]): ?>
        <div class="prev-reply">
          <strong><i class="fas fa-shield-halved"></i> Your previous reply · <?php echo date("d M Y, g:i a", strtotime($r["replied_at"])); ?></strong>
          <?php echo nl2br(htmlspecialchars($r["admin_reply"])); ?>
        </div>
        <?php endif; ?>

        <!-- Reply form -->
        <form class="reply-form" method="POST">
          <input type="hidden" name="report_id" value="<?php echo (int)$r['report_id']; ?>">

          <label>Update Status</label>
          <select name="status">
            <option value="open"        <?php echo $r["status"] === "open"        ? "selected" : ""; ?>>Open – needs attention</option>
            <option value="in_progress" <?php echo $r["status"] === "in_progress" ? "selected" : ""; ?>>In Progress – being handled</option>
            <option value="resolved"    <?php echo $r["status"] === "resolved"    ? "selected" : ""; ?>>Resolved – issue closed</option>
          </select>

          <label><?php echo $r["admin_reply"] ? "Update Reply" : "Reply to Customer"; ?></label>
          <textarea name="admin_reply" placeholder="Write your response to the customer…"></textarea>

          <div class="reply-btns">
            <button type="submit" class="btn btn-green">
              <i class="fas fa-paper-plane"></i>
              <?php echo $r["admin_reply"] ? "Update Reply" : "Send Reply"; ?>
            </button>
            <button type="submit" name="admin_reply" value="" class="btn btn-outline">
              <i class="fas fa-sync-alt"></i> Update Status Only
            </button>
          </div>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  function toggleReport(id) {
    const head = document.getElementById('rhead-' + id);
    const body = document.getElementById('rbody-' + id);
    const icon = document.getElementById('ricon-' + id);
    const open = body.classList.toggle('open');
    head.classList.toggle('open', open);
    if (icon) icon.style.transform = open ? 'rotate(180deg)' : '';
  }
</script>
</body>
</html>
