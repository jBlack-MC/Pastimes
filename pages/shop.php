<?php
/* shop.php – Main product listing / marketplace page.
   Loads all in-stock items from tblclothes and renders a product grid.
   Each card has an Add to Cart button that calls cart_add.php via AJAX. */
session_start();

/* Unauthenticated visitors cannot browse the shop; send them to login */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Include database connection – provides $conn (MySQLi object) */
require_once __DIR__ . "/../config/DBConn.php";

/* Read session variables set at login time */
$name    = $_SESSION["name"] ?? "User";   // display name shown in welcome bar
$role    = $_SESSION["role"] ?? "user";   // "user" or "admin"
$user_id = (int)$_SESSION["user_id"];     // cast to int for prepared statements

/* Fetch every product that still has stock (stock > 0).
   Ordered newest-first so fresh listings appear at the top. */
$products = [];
$result   = mysqli_query($conn,
    "SELECT product_id, name, description, price, stock, image
     FROM   tblclothes
     WHERE  stock > 0
     ORDER  BY created_at DESC"
);
if ($result) {
    /* Build a plain PHP array of associative rows for use in the HTML loop */
    while ($row = mysqli_fetch_assoc($result)) $products[] = $row;
}

/* Get the total quantity in this user's cart so the nav badge stays accurate.
   COALESCE returns 0 when the cart is empty (SUM of NULL). */
$cartCount = 0;
$cStmt     = mysqli_prepare($conn,
    "SELECT COALESCE(SUM(quantity), 0) AS n FROM tblcart WHERE user_id = ?"
);
if ($cStmt) {
    mysqli_stmt_bind_param($cStmt, "i", $user_id); // "i" = integer
    mysqli_stmt_execute($cStmt);
    $cRow      = mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt));
    $cartCount = (int)($cRow["n"] ?? 0);
    mysqli_stmt_close($cStmt);
}

/* Close the database connection before rendering HTML */
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Marketplace</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--sec:#5a6e6e;--muted:#8a9b9b;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;
      --border:#e8e6e1;--sm:0 4px 12px rgba(0,0,0,.03);--md:0 12px 28px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:1.2rem}
    .page{max-width:1200px;margin:0 auto}
    /* top bar */
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;letter-spacing:-.3px;color:var(--green)}
    .badge{background:#eef3f1;color:var(--green);font-size:.68rem;font-weight:600;padding:4px 10px;border-radius:30px;margin-left:8px}
    .nav{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .nav a{text-decoration:none;padding:8px 14px;border-radius:40px;font-size:.82rem;font-weight:600;border:1px solid transparent;color:#fff;background:var(--green);transition:.2s}
    .nav a:hover{background:var(--dark)}
    .nav a.secondary{background:transparent;color:var(--sec);border-color:var(--border)}
    .cart-wrap{position:relative;display:inline-flex}
    .cart-count{position:absolute;top:-6px;right:-6px;background:var(--rust);color:#fff;font-size:.62rem;font-weight:700;padding:2px 5px;border-radius:30px;border:2px solid var(--bg);min-width:18px;text-align:center}
    /* welcome */
    .welcome{background:var(--white);border:1px solid var(--border);border-radius:var(--md-r);padding:.7rem 1rem;margin-bottom:1rem;color:var(--sec);font-size:.9rem;box-shadow:var(--sm)}
    /* product grid */
    .products-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem}
    .product-card{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:1rem;box-shadow:var(--sm);display:flex;flex-direction:column;transition:.2s}
    .product-card:hover{box-shadow:var(--md);transform:translateY(-2px)}
    .product-img{width:100%;height:140px;border-radius:var(--md-r);background:linear-gradient(135deg,#e9e7e2,#f2efea);display:flex;align-items:center;justify-content:center;color:var(--green);font-size:2rem;margin-bottom:.8rem;overflow:hidden}
    .product-img img{width:100%;height:100%;object-fit:cover}
    .product-card h3{font-size:.95rem;margin-bottom:.25rem;font-weight:700}
    .product-card p{color:var(--sec);font-size:.82rem;margin-bottom:.6rem;line-height:1.4;flex:1}
    .price{color:var(--rust);font-weight:700;margin-bottom:.7rem;font-size:1rem}
    .btn-group{display:grid;grid-template-columns:1fr 1fr;gap:.5rem}
    .btn-view{text-decoration:none;background:var(--green);color:#fff;border-radius:30px;padding:8px 10px;font-size:.78rem;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:.2s}
    .btn-view:hover{background:var(--dark)}
    .btn-cart{background:var(--rust);color:#fff;border:none;border-radius:30px;padding:8px 10px;font-size:.78rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:5px;transition:.2s}
    .btn-cart:hover{background:#a4542f}
    /* empty state */
    .empty{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:3rem;text-align:center;color:var(--muted)}
    .empty i{font-size:3rem;display:block;margin-bottom:.8rem;opacity:.3}
    /* toast */
    .toast{position:fixed;bottom:2rem;right:2rem;background:var(--green);color:#fff;padding:.8rem 1.2rem;border-radius:50px;box-shadow:0 4px 12px rgba(0,0,0,.15);animation:slideIn .3s ease-out;z-index:999;font-size:.88rem}
    .toast.err{background:var(--rust)}
    @keyframes slideIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    @keyframes fadeOut{from{opacity:1;transform:translateY(0)}to{opacity:0;transform:translateY(20px)}}
    @media(max-width:600px){.products-grid{grid-template-columns:1fr 1fr}.btn-group{grid-template-columns:1fr}}
    @media(max-width:420px){.products-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="page">
  <header class="top-bar">
    <a href="shop.php" class="logo-area">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div><span class="logo-text">Pastimes</span><span class="badge">local fashion</span></div>
    </a>
    <nav class="nav">
      <a href="shop.php">Shop</a>
      <div class="cart-wrap">
        <a href="checkout.php"><i class="fas fa-shopping-cart"></i> Cart</a>
        <?php if ($cartCount > 0): ?>
          <span class="cart-count"><?php echo $cartCount; ?></span>
        <?php endif; ?>
      </div>
      <a href="my_orders.php">My Orders</a>
      <a href="messages.php"><i class="fas fa-envelope"></i></a>
      <a href="sellers_hub.php">Seller Hub</a>
      <?php if ($role === "admin"): ?><a href="admin.php">Admin</a><?php endif; ?>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <div class="welcome">Welcome back, <strong><?php echo htmlspecialchars($name); ?></strong>.</div>

  <?php if (count($products) === 0): ?>
    <div class="empty">
      <i class="fas fa-tshirt"></i>
      <h3>No products found.</h3>
      <p>Check back soon — new items are added regularly.</p>
    </div>
  <?php else: ?>
  <section class="products-grid">
    <?php foreach ($products as $p):
      $hasImg = !empty($p["image"]) && $p["image"] !== "placeholder-clothing.jpg"
                && file_exists(__DIR__ . "/../uploads/" . $p["image"]);
      $desc = strlen($p["description"] ?? "") > 75
              ? substr($p["description"], 0, 75) . "…"
              : ($p["description"] ?? "");
    ?>
    <article class="product-card">
      <div class="product-img">
        <?php if ($hasImg): ?>
          <img src="../uploads/<?php echo htmlspecialchars($p["image"]); ?>" alt="<?php echo htmlspecialchars($p["name"]); ?>">
        <?php else: ?>
          <i class="fas fa-tshirt"></i>
        <?php endif; ?>
      </div>
      <h3><?php echo htmlspecialchars($p["name"]); ?></h3>
      <p><?php echo htmlspecialchars($desc); ?></p>
      <div class="price">$<?php echo number_format((float)$p["price"], 2); ?></div>
      <div class="btn-group">
        <a class="btn-view" href="product.php?id=<?php echo (int)$p["product_id"]; ?>">
          <i class="fas fa-eye"></i> View
        </a>
        <button class="btn-cart"
          onclick="addToCart(<?php echo (int)$p["product_id"]; ?>, <?php echo htmlspecialchars(json_encode($p["name"]), ENT_QUOTES); ?>)">
          <i class="fas fa-shopping-bag"></i> Add
        </button>
      </div>
    </article>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>
</div>

<script>
  /* Add a product to the cart via AJAX */
  async function addToCart(productId, productName) {
    try {
      const res = await fetch('cart_add.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + productId
      });
      const data = await res.json();
      if (data.success) {
        showToast('✓ ' + productName + ' added to cart');
        /* Update cart badge count */
        const badge = document.querySelector('.cart-count');
        if (badge && data.cart_count != null) badge.textContent = data.cart_count;
      } else {
        showToast('✗ ' + (data.message || 'Could not add to cart'), 'err');
      }
    } catch {
      showToast('✗ Error adding to cart', 'err');
    }
  }

  function showToast(msg, type = '') {
    const t = document.createElement('div');
    t.className = 'toast' + (type ? ' ' + type : '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => {
      t.style.animation = 'fadeOut .3s ease-out forwards';
      setTimeout(() => t.remove(), 300);
    }, 2500);
  }
</script>
</body>
</html>
