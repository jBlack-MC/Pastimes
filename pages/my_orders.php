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

$user_id = (int)$_SESSION["user_id"];
$name    = $_SESSION["name"] ?? "User";
$role    = $_SESSION["role"] ?? "user";

/* Orders newest-first */
$orders = [];
$oResult = mysqli_prepare($conn,
    "SELECT order_id, total_price, delivery_address, order_date, status
     FROM tblorder
     WHERE user_id = ?
     ORDER BY order_date DESC"
);
if ($oResult) {
    mysqli_stmt_bind_param($oResult, "i", $user_id);
    mysqli_stmt_execute($oResult);
    $oRows = mysqli_stmt_get_result($oResult);
    while ($row = mysqli_fetch_assoc($oRows)) $orders[] = $row;
    mysqli_stmt_close($oResult);
}

/* Line items for all orders in one query, ordered by orderline_id */
$lineItems = [];
if ($orders) {
    $orderIds = implode(",", array_map(fn($o) => (int)$o["order_id"], $orders));
    $lResult  = mysqli_query($conn,
        "SELECT ol.order_id, ol.orderline_id, ol.quantity, ol.unit_price,
                c.product_id, c.name AS product_name, c.image
         FROM tblorderline ol
         JOIN tblclothes c ON ol.product_id = c.product_id
         WHERE ol.order_id IN ($orderIds)
         ORDER BY ol.orderline_id ASC"
    );
    if ($lResult) {
        while ($row = mysqli_fetch_assoc($lResult)) {
            $lineItems[$row["order_id"]][] = $row;
        }
    }
}

$grandTotal = array_sum(array_column($orders, "total_price"));

$flashMsg  = "";
$flashType = "";
if (isset($_GET["msg"]) && $_GET["msg"] === "cancelled") {
    $flashMsg  = "Order cancelled. Items have been restocked.";
    $flashType = "ok";
} elseif (isset($_GET["err"]) && $_GET["err"] === "cannot_cancel") {
    $flashMsg  = "This order cannot be cancelled (it may have already shipped).";
    $flashType = "err";
}

mysqli_close($conn);

/* Status display helpers */
$statusMeta = [
    "pending"   => ["icon" => "fa-clock",          "cls" => "s-pending",   "label" => "Pending"],
    "shipped"   => ["icon" => "fa-truck",           "cls" => "s-shipped",   "label" => "Shipped"],
    "delivered" => ["icon" => "fa-circle-check",    "cls" => "s-delivered", "label" => "Delivered"],
    "cancelled" => ["icon" => "fa-circle-xmark",    "cls" => "s-cancelled", "label" => "Cancelled"],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · My Orders</title>
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
    .page{max-width:920px;margin:0 auto}
    /* top bar */
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.8rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .nav{display:flex;gap:6px;flex-wrap:wrap}
    .nav a{text-decoration:none;padding:7px 13px;border-radius:40px;font-size:.8rem;font-weight:600;border:1px solid transparent;color:#fff;background:var(--green);transition:.2s}
    .nav a:hover{background:var(--dark)}
    .nav a.secondary{background:transparent;color:var(--sec);border-color:var(--border)}
    .nav a.active{outline:2px solid var(--green);outline-offset:2px}
    /* page header */
    .page-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.2rem}
    .page-header h1{font-size:1.5rem;font-weight:700;display:flex;align-items:center;gap:8px}
    .order-count{font-size:.82rem;color:var(--muted);font-weight:400}
    /* summary bar */
    .summary-bar{background:var(--white);border:1px solid var(--border);border-radius:var(--md-r);padding:.9rem 1.2rem;box-shadow:var(--sm);display:flex;justify-content:space-between;align-items:center;margin-bottom:1.2rem;flex-wrap:wrap;gap:.8rem}
    .summary-bar .lbl{font-size:.82rem;color:var(--muted)}
    .summary-bar .val{font-size:1.1rem;font-weight:700;color:var(--rust)}
    /* order card */
    .order-card{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);box-shadow:var(--sm);margin-bottom:1rem;overflow:hidden;transition:box-shadow .2s}
    .order-card:hover{box-shadow:var(--md)}
    .order-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;padding:1rem 1.2rem;gap:.8rem;cursor:pointer;user-select:none;border-bottom:1px solid transparent;transition:.15s}
    .order-head:hover{background:#fafaf8}
    .order-head.open{border-bottom-color:var(--border)}
    .order-left{display:flex;flex-direction:column;gap:4px}
    .order-num{font-weight:700;font-size:.95rem;color:var(--text);letter-spacing:.3px}
    .order-meta{display:flex;flex-wrap:wrap;gap:.6rem;font-size:.78rem;color:var(--muted)}
    .order-meta span{display:flex;align-items:center;gap:4px}
    .order-right{display:flex;align-items:center;gap:.7rem;flex-wrap:wrap}
    .order-total{font-weight:800;font-size:1rem;color:var(--rust)}
    /* status badge */
    .sbadge{display:inline-flex;align-items:center;gap:5px;padding:4px 11px;border-radius:30px;font-size:.72rem;font-weight:700;white-space:nowrap}
    .s-pending  {background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    .s-shipped  {background:#e8f0fb;color:#2a52a4;border:1px solid #bdd0f5}
    .s-delivered{background:#e6f4f0;color:#1b6f4e;border:1px solid #c3dfd5}
    .s-cancelled{background:#fdecea;color:#9b2a2a;border:1px solid #f5b7b7}
    .chevron{color:var(--muted);transition:transform .2s;font-size:.9rem}
    /* items panel */
    .order-items{display:none;padding:0}
    .order-items.open{display:block}
    .items-inner{padding:1rem 1.2rem;display:flex;flex-direction:column;gap:.6rem}
    .line-item{display:flex;align-items:center;gap:.9rem;padding:.7rem;border:1px solid var(--border);border-radius:var(--md-r);background:#fafaf8;transition:.15s}
    .line-item:hover{background:#f4f2ee}
    .item-img{width:52px;height:52px;border-radius:var(--sm-r);overflow:hidden;flex-shrink:0;background:#edece4}
    .item-img img{width:100%;height:100%;object-fit:cover}
    .item-info{flex:1;min-width:0}
    .item-name{font-weight:600;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .item-unit{font-size:.75rem;color:var(--muted);margin-top:2px}
    .item-qty{font-size:.82rem;color:var(--sec);font-weight:600;white-space:nowrap}
    .item-subtotal{font-weight:700;color:var(--rust);font-size:.9rem;white-space:nowrap}
    /* order footer */
    .order-footer{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.6rem;padding:.8rem 1.2rem;background:#f9f8f5;border-top:1px solid var(--border);font-size:.82rem}
    .addr{color:var(--muted);display:flex;align-items:center;gap:5px;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .btn-report{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:30px;font-size:.78rem;font-weight:600;border:1px solid #f0d3bf;background:#fff2e7;color:#a4532f;text-decoration:none;transition:.2s;flex-shrink:0}
    .btn-report:hover{background:#fde8d0;border-color:#e0b090}
    .btn-cancel{display:inline-flex;align-items:center;gap:6px;padding:6px 13px;border-radius:30px;font-size:.78rem;font-weight:600;border:1px solid #f5b7b7;background:#fdecea;color:#9b2a2a;cursor:pointer;transition:.2s;font-family:inherit;flex-shrink:0}
    .btn-cancel:hover{background:#f9d0d0}
    .footer-actions{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;flex-shrink:0}
    .flash-bar{border-radius:var(--md-r);padding:.8rem 1.1rem;margin-bottom:1rem;font-size:.85rem;border-left:4px solid;display:flex;align-items:center;gap:.5rem}
    .flash-ok{background:#e8f7ef;color:#1b6f4e;border-color:#1b6f4e}
    .flash-err{background:#fdecea;color:#9b2a2a;border-color:#9b2a2a}
    /* status tracker */
    .tracker{display:flex;align-items:center;padding:.8rem 1.2rem;gap:0;border-bottom:1px solid var(--border);background:#f9f8f5}
    .tracker-step{display:flex;flex-direction:column;align-items:center;gap:3px;flex:1;font-size:.7rem;color:var(--muted);text-align:center}
    .tracker-step.done{color:var(--green)}
    .tracker-step.done .step-dot{background:var(--green);border-color:var(--green);color:#fff}
    .tracker-step.active{color:var(--rust)}
    .tracker-step.active .step-dot{background:var(--rust);border-color:var(--rust);color:#fff}
    .step-dot{width:28px;height:28px;border-radius:50%;border:2px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;font-size:.75rem;color:var(--muted);margin-bottom:2px;position:relative;z-index:1}
    .tracker-line{flex:1;height:2px;background:var(--border);margin-top:-20px;position:relative;z-index:0}
    .tracker-line.done{background:var(--green)}
    /* empty */
    .empty{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:3rem;text-align:center;color:var(--muted)}
    .empty i{font-size:3rem;display:block;margin-bottom:.8rem;opacity:.3}
    .empty a{color:var(--green);font-weight:700;text-decoration:none}
    @media(max-width:600px){
      .order-right{width:100%}
      .item-img{width:40px;height:40px}
      .tracker{padding:.6rem .8rem}
    }
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
      <a href="my_orders.php" class="active">My Orders</a>
      <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
      <a href="report.php"><i class="fas fa-flag"></i> Report</a>
      <a href="sellers_hub.php">Seller Hub</a>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if ($flashMsg): ?>
    <div class="flash-bar flash-<?php echo $flashType; ?>">
      <i class="fas <?php echo $flashType === 'ok' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
      <?php echo htmlspecialchars($flashMsg); ?>
    </div>
  <?php endif; ?>

  <div class="page-header">
    <h1><i class="fas fa-receipt" style="color:var(--green)"></i> My Orders
      <span class="order-count">(<?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?>)</span>
    </h1>
  </div>

  <?php if (!$orders): ?>
    <div class="empty">
      <i class="fas fa-inbox"></i>
      <h3 style="margin-bottom:.5rem">No orders yet</h3>
      <p>Head to the <a href="shop.php">shop</a> to place your first order.</p>
    </div>

  <?php else: ?>

  <div class="summary-bar">
    <div>
      <div class="lbl">Total spent across all orders</div>
      <div class="val">$<?php echo number_format((float)$grandTotal, 2); ?></div>
    </div>
    <div style="display:flex;gap:1rem;flex-wrap:wrap">
      <?php
        $byStatus = array_count_values(array_column($orders, "status"));
        foreach (["pending" => "Pending", "shipped" => "Shipped", "delivered" => "Delivered"] as $k => $lbl):
          if (($byStatus[$k] ?? 0) > 0):
      ?>
      <div style="text-align:center">
        <div class="lbl"><?php echo $lbl; ?></div>
        <div style="font-weight:700;font-size:1.1rem"><?php echo $byStatus[$k]; ?></div>
      </div>
      <?php endif; endforeach; ?>
    </div>
  </div>

  <?php foreach ($orders as $o):
    $items  = $lineItems[$o["order_id"]] ?? [];
    $status = $o["status"] ?? "pending";
    $sm     = $statusMeta[$status] ?? $statusMeta["pending"];
    $itemCount = array_sum(array_column($items, "quantity"));
    $steps  = ["pending", "shipped", "delivered"];
    $stepIdx = array_search($status, $steps);
  ?>
  <div class="order-card">

    <!-- Clickable header -->
    <div class="order-head" id="head-<?php echo (int)$o['order_id']; ?>"
         onclick="toggleOrder(<?php echo (int)$o['order_id']; ?>)">
      <div class="order-left">
        <div class="order-num">ORD-<?php echo str_pad($o["order_id"], 8, "0", STR_PAD_LEFT); ?></div>
        <div class="order-meta">
          <span><i class="fas fa-calendar-alt"></i> <?php echo date("d M Y, g:i a", strtotime($o["order_date"])); ?></span>
          <span><i class="fas fa-box-open"></i> <?php echo $itemCount; ?> item<?php echo $itemCount !== 1 ? 's' : ''; ?></span>
        </div>
      </div>
      <div class="order-right">
        <span class="order-total">$<?php echo number_format((float)$o["total_price"], 2); ?></span>
        <span class="sbadge <?php echo $sm['cls']; ?>">
          <i class="fas <?php echo $sm['icon']; ?>"></i>
          <?php echo $sm['label']; ?>
        </span>
        <i class="fas fa-chevron-down chevron" id="icon-<?php echo (int)$o['order_id']; ?>"></i>
      </div>
    </div>

    <!-- Status tracker (always visible) -->
    <?php if ($status !== "cancelled"): ?>
    <div class="tracker">
      <?php foreach ($steps as $si => $step):
        $isDone   = $stepIdx !== false && $si < $stepIdx;
        $isActive = $si === $stepIdx;
        $cls = $isDone ? 'done' : ($isActive ? 'active' : '');
        $icons = ["fa-clock", "fa-truck", "fa-circle-check"];
        $labels = ["Placed", "Shipped", "Delivered"];
      ?>
        <?php if ($si > 0): ?>
          <div class="tracker-line <?php echo $isDone ? 'done' : ''; ?>"></div>
        <?php endif; ?>
        <div class="tracker-step <?php echo $cls; ?>">
          <div class="step-dot"><i class="fas <?php echo $icons[$si]; ?>"></i></div>
          <?php echo $labels[$si]; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Collapsible items -->
    <div class="order-items" id="items-<?php echo (int)$o['order_id']; ?>">
      <div class="items-inner">
        <?php if (!$items): ?>
          <p style="color:var(--muted);font-size:.85rem;padding:.5rem">No item details found.</p>
        <?php else: ?>
          <?php foreach ($items as $li):
            $hasImg = !empty($li["image"]) && $li["image"] !== "placeholder-clothing.jpg"
                      && file_exists(__DIR__ . "/../uploads/" . $li["image"]);
          ?>
          <div class="line-item">
            <div class="item-img">
              <?php if ($hasImg): ?>
                <img src="../uploads/<?php echo htmlspecialchars($li["image"]); ?>" alt="">
              <?php else: ?>
                <img src="pimg.php?id=<?php echo (int)$li["product_id"]; ?>" alt="<?php echo htmlspecialchars($li["product_name"]); ?>">
              <?php endif; ?>
            </div>
            <div class="item-info">
              <div class="item-name"><?php echo htmlspecialchars($li["product_name"]); ?></div>
              <div class="item-unit">$<?php echo number_format((float)$li["unit_price"], 2); ?> each</div>
            </div>
            <span class="item-qty">× <?php echo (int)$li["quantity"]; ?></span>
            <span class="item-subtotal">$<?php echo number_format((float)$li["unit_price"] * (int)$li["quantity"], 2); ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Order footer: address + actions -->
      <div class="order-footer">
        <span class="addr">
          <i class="fas fa-map-marker-alt"></i>
          <?php echo htmlspecialchars(substr($o["delivery_address"] ?? "No address", 0, 60)); ?>
        </span>
        <div class="footer-actions">
          <a href="report.php?order_id=<?php echo (int)$o['order_id']; ?>" class="btn-report">
            <i class="fas fa-flag"></i> Report Issue
          </a>
          <?php if ($status === "pending"): ?>
          <form method="POST" action="cancel_order.php"
                onsubmit="return confirm('Cancel this order? Stock will be restored.')">
            <input type="hidden" name="order_id" value="<?php echo (int)$o['order_id']; ?>">
            <button type="submit" class="btn-cancel">
              <i class="fas fa-times-circle"></i> Cancel Order
            </button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>
  <?php endforeach; ?>

  <?php endif; ?>
</div>

<script>
  function toggleOrder(id) {
    const head  = document.getElementById('head-' + id);
    const items = document.getElementById('items-' + id);
    const icon  = document.getElementById('icon-' + id);
    if (!items) return;
    const open = items.classList.toggle('open');
    head.classList.toggle('open', open);
    if (icon) icon.style.transform = open ? 'rotate(180deg)' : '';
  }
</script>
<?php require_once __DIR__ . '/../config/tab_guard.php'; ?>
</body>
</html>
