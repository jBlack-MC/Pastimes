<?php
/* Admin Order Management – view and update order statuses (Phase 10) */
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

/* Update order status when admin clicks Pending / Shipped / Delivered */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["order_id"], $_POST["status"])) {
    $order_id  = (int)$_POST["order_id"];
    $newStatus = $_POST["status"];
    if (in_array($newStatus, ["pending", "shipped", "delivered"], true) && $order_id > 0) {
        $s = mysqli_prepare($conn, "UPDATE tblorder SET status=? WHERE order_id=?");
        if ($s) {
            mysqli_stmt_bind_param($s, "si", $newStatus, $order_id);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
        }
    }
    header("Location: admin_orders.php?msg=updated");
    exit;
}

/* Fetch all orders with customer info */
$orders = [];
$result = mysqli_query($conn,
    "SELECT o.order_id, o.total_price, o.delivery_address, o.order_date, o.status,
            u.name AS customer_name, u.email AS customer_email
     FROM tblorder o
     JOIN tblUser u ON o.user_id = u.user_id
     ORDER BY o.order_date DESC"
);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) $orders[] = $row;
}

/* Stats for quick overview */
$pending   = count(array_filter($orders, fn($o) => $o["status"] === "pending"));
$shipped   = count(array_filter($orders, fn($o) => $o["status"] === "shipped"));
$delivered = count(array_filter($orders, fn($o) => $o["status"] === "delivered"));

$flash = (isset($_GET["msg"]) && $_GET["msg"] === "updated") ? "Order status updated." : "";

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Order Management</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--sec:#5a6e6e;--muted:#8a9b9b;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;--gold:#d4a259;
      --border:#e8e6e1;--sm:0 4px 12px rgba(0,0,0,.03);--md:0 12px 28px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);padding:1.2rem}
    .page{max-width:1200px;margin:0 auto}
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .badge{background:#f6efe0;color:#7a5a2a;font-size:.68rem;font-weight:600;padding:4px 10px;border-radius:30px;margin-left:8px}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{text-decoration:none;padding:8px 14px;border-radius:40px;font-size:.82rem;font-weight:600;border:1px solid var(--border);color:var(--sec);background:#fff}
    .nav a:hover,.nav a.active{background:var(--green);color:#fff;border-color:var(--green)}
    .flash{margin-bottom:.9rem;border-radius:var(--sm-r);background:#e8f7ef;color:#1b6f4e;padding:10px 14px;font-size:.82rem;border-left:3px solid #1b6f4e}
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.8rem;margin-bottom:1rem}
    .stat{background:var(--white);border:1px solid var(--border);border-radius:var(--md-r);padding:.9rem;box-shadow:var(--sm)}
    .stat-label{font-size:.75rem;color:var(--muted);margin-bottom:.2rem}
    .stat-value{font-size:1.4rem;font-weight:700;color:var(--green)}
    .card{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);box-shadow:var(--md);overflow:hidden}
    .card-head{padding:1rem;border-bottom:1px solid var(--border)}
    .card-head h2{font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:8px}
    table{width:100%;border-collapse:collapse}
    th,td{text-align:left;padding:.75rem 1rem;border-bottom:1px solid var(--border);font-size:.85rem;vertical-align:middle}
    th{color:var(--sec);font-weight:600;background:#fcfbf9;font-size:.78rem}
    .status-badge{display:inline-block;padding:4px 10px;border-radius:30px;font-size:.72rem;font-weight:700}
    .s-pending{background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    .s-shipped{background:#e8f0fb;color:#2a52a4;border:1px solid #bdd0f5}
    .s-delivered{background:#e6f4f0;color:#1b6f4e;border:1px solid #c3dfd5}
    .btn{display:inline-flex;align-items:center;gap:5px;border:none;border-radius:30px;padding:6px 11px;font-size:.73rem;font-weight:700;cursor:pointer;transition:.2s}
    .btn-pending{background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    .btn-pending:hover{background:#f5dfc9}
    .btn-shipped{background:#e8f0fb;color:#2a52a4;border:1px solid #bdd0f5}
    .btn-shipped:hover{background:#cfdaf6}
    .btn-delivered{background:#e6f4f0;color:#1b6f4e;border:1px solid #c3dfd5}
    .btn-delivered:hover{background:#c7e6da}
    .btn-active{outline:2px solid currentColor;outline-offset:1px}
    .empty{padding:2rem;text-align:center;color:var(--muted)}
    .empty i{font-size:2rem;display:block;margin-bottom:.5rem;opacity:.3}
    @media(max-width:700px){th,td{font-size:.78rem;padding:.6rem .5rem}.stats{grid-template-columns:repeat(2,1fr)}}
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
      <a href="admin_orders.php" class="active">Orders</a>
      <a href="admin_messages.php">Messages</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if ($flash): ?>
    <div class="flash"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <section class="stats">
    <article class="stat"><div class="stat-label">Total Orders</div><div class="stat-value"><?php echo count($orders); ?></div></article>
    <article class="stat"><div class="stat-label">Pending</div><div class="stat-value"><?php echo $pending; ?></div></article>
    <article class="stat"><div class="stat-label">Shipped</div><div class="stat-value"><?php echo $shipped; ?></div></article>
    <article class="stat"><div class="stat-label">Delivered</div><div class="stat-value"><?php echo $delivered; ?></div></article>
  </section>

  <section class="card">
    <div class="card-head">
      <h2><i class="fas fa-receipt"></i> All Orders</h2>
    </div>

    <?php if (!$orders): ?>
      <div class="empty"><i class="fas fa-inbox"></i>No orders yet.</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Customer</th>
          <th>Email</th>
          <th>Total</th>
          <th>Date</th>
          <th>Status</th>
          <th>Update Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
        <tr>
          <td><strong>ORD-<?php echo str_pad($o["order_id"], 8, "0", STR_PAD_LEFT); ?></strong></td>
          <td><?php echo htmlspecialchars($o["customer_name"]); ?></td>
          <td><?php echo htmlspecialchars($o["customer_email"]); ?></td>
          <td><strong>$<?php echo number_format((float)$o["total_price"], 2); ?></strong></td>
          <td><?php echo date("d M Y", strtotime($o["order_date"])); ?></td>
          <td>
            <span class="status-badge s-<?php echo htmlspecialchars($o["status"]); ?>">
              <?php echo ucfirst(htmlspecialchars($o["status"])); ?>
            </span>
          </td>
          <td style="white-space:nowrap">
            <!-- Buttons to set the order to each available status -->
            <?php foreach (["pending","shipped","delivered"] as $st): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="order_id" value="<?php echo (int)$o["order_id"]; ?>">
                <input type="hidden" name="status" value="<?php echo $st; ?>">
                <button type="submit"
                  class="btn btn-<?php echo $st; ?> <?php echo $o['status']===$st?'btn-active':''; ?>"
                  <?php echo $o['status']===$st?'disabled':''; ?>>
                  <?php echo ucfirst($st); ?>
                </button>
              </form>
            <?php endforeach; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php endif; ?>
  </section>
</div>
</body>
</html>
