<?php
/* My Orders – user's order history (Phase 12) */
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id = (int)$_SESSION["user_id"];
$name    = $_SESSION["name"] ?? "User";
$role    = $_SESSION["role"] ?? "user";

/* Fetch all orders for this user, newest first */
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

/* Fetch line items for all orders in one query */
$lineItems = [];
if ($orders) {
    $orderIds = implode(",", array_map(fn($o) => (int)$o["order_id"], $orders));
    $lResult = mysqli_query($conn,
        "SELECT ol.order_id, ol.quantity, ol.unit_price, c.name AS product_name, c.image
         FROM tblorderline ol
         JOIN tblclothes c ON ol.product_id = c.product_id
         WHERE ol.order_id IN ($orderIds)"
    );
    if ($lResult) {
        while ($row = mysqli_fetch_assoc($lResult)) {
            $lineItems[$row["order_id"]][] = $row;
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
  <title>Pastimes · My Orders</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--sec:#5a6e6e;--muted:#8a9b9b;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;
      --border:#e8e6e1;--sm:0 4px 12px rgba(0,0,0,.03);--md:0 12px 28px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);padding:1.2rem}
    .page{max-width:960px;margin:0 auto}
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{text-decoration:none;padding:8px 14px;border-radius:40px;font-size:.82rem;font-weight:600;border:1px solid transparent;color:#fff;background:var(--green)}
    .nav a:hover{background:var(--dark)}
    .nav a.secondary{background:transparent;color:var(--sec);border-color:var(--border)}
    .nav a.active{outline:2px solid var(--green);outline-offset:2px}
    h1{font-size:1.5rem;font-weight:700;margin-bottom:1.2rem;display:flex;align-items:center;gap:8px}
    /* order card */
    .order-card{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);box-shadow:var(--sm);margin-bottom:1rem;overflow:hidden}
    .order-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;padding:1rem 1.2rem;border-bottom:1px solid var(--border);gap:.8rem;cursor:pointer;user-select:none}
    .order-num{font-weight:700;font-size:.95rem}
    .order-meta{display:flex;flex-wrap:wrap;gap:.8rem;font-size:.82rem;color:var(--sec)}
    .status-badge{display:inline-block;padding:4px 10px;border-radius:30px;font-size:.72rem;font-weight:700}
    .s-pending{background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    .s-shipped{background:#e8f0fb;color:#2a52a4;border:1px solid #bdd0f5}
    .s-delivered{background:#e6f4f0;color:#1b6f4e;border:1px solid #c3dfd5}
    .toggle-icon{color:var(--muted);transition:.2s}
    /* order items */
    .order-items{padding:1rem 1.2rem;display:none}
    .order-items.open{display:block}
    .line-item{display:flex;align-items:center;gap:.8rem;padding:.5rem 0;border-bottom:1px solid var(--border);font-size:.85rem}
    .line-item:last-child{border-bottom:none}
    .item-thumb{width:40px;height:40px;border-radius:8px;background:#edece4;display:flex;align-items:center;justify-content:center;color:var(--green);overflow:hidden;flex-shrink:0}
    .item-thumb img{width:100%;height:100%;object-fit:cover}
    .item-name{flex:1;font-weight:600}
    .item-total{font-weight:700;color:var(--rust)}
    /* empty */
    .empty{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:3rem;text-align:center;color:var(--muted)}
    .empty i{font-size:3rem;display:block;margin-bottom:.8rem;opacity:.3}
    @media(max-width:600px){.order-meta{gap:.4rem}}
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
      <a href="sellers_hub.php">Seller Hub</a>
      <?php if ($role === "admin"): ?><a href="admin.php">Admin</a><?php endif; ?>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <h1><i class="fas fa-receipt"></i> My Orders</h1>

  <?php if (!$orders): ?>
    <div class="empty">
      <i class="fas fa-inbox"></i>
      <h3>No orders yet.</h3>
      <p>Head to the <a href="shop.php" style="color:var(--green)">shop</a> to place your first order.</p>
    </div>
  <?php else: ?>
    <?php foreach ($orders as $o):
      $items = $lineItems[$o["order_id"]] ?? [];
      $status = $o["status"] ?? "pending";
    ?>
    <div class="order-card">
      <div class="order-head" onclick="toggleOrder(<?php echo (int)$o['order_id']; ?>)">
        <div>
          <div class="order-num">ORD-<?php echo str_pad($o["order_id"], 8, "0", STR_PAD_LEFT); ?></div>
          <div class="order-meta">
            <span><i class="fas fa-calendar"></i> <?php echo date("d M Y", strtotime($o["order_date"])); ?></span>
            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars(substr($o["delivery_address"] ?? "", 0, 40)); ?></span>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:.8rem">
          <strong style="color:var(--rust)">$<?php echo number_format((float)$o["total_price"], 2); ?></strong>
          <span class="status-badge s-<?php echo htmlspecialchars($status); ?>"><?php echo ucfirst(htmlspecialchars($status)); ?></span>
          <i class="fas fa-chevron-down toggle-icon" id="icon-<?php echo (int)$o['order_id']; ?>"></i>
        </div>
      </div>
      <div class="order-items" id="items-<?php echo (int)$o['order_id']; ?>">
        <?php if (!$items): ?>
          <p style="color:var(--muted);font-size:.85rem">No item details available.</p>
        <?php else: ?>
          <?php foreach ($items as $li):
            $hasImg = !empty($li["image"]) && $li["image"] !== "placeholder-clothing.jpg"
                      && file_exists(__DIR__ . "/../uploads/" . $li["image"]);
          ?>
          <div class="line-item">
            <div class="item-thumb">
              <?php if ($hasImg): ?>
                <img src="../uploads/<?php echo htmlspecialchars($li["image"]); ?>" alt="">
              <?php else: ?>
                <i class="fas fa-tshirt"></i>
              <?php endif; ?>
            </div>
            <span class="item-name"><?php echo htmlspecialchars($li["product_name"]); ?></span>
            <span style="color:var(--muted);font-size:.8rem">×<?php echo (int)$li["quantity"]; ?></span>
            <span class="item-total">$<?php echo number_format((float)$li["unit_price"] * (int)$li["quantity"], 2); ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
  /* Toggle an order's item list open/closed */
  function toggleOrder(id) {
    const items = document.getElementById('items-' + id);
    const icon  = document.getElementById('icon-' + id);
    if (!items) return;
    const open = items.classList.toggle('open');
    if (icon) icon.style.transform = open ? 'rotate(180deg)' : '';
  }
</script>
</body>
</html>
