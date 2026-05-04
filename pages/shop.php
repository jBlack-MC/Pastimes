<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$name = $_SESSION["name"] ?? "User";
$role = $_SESSION["role"] ?? "user";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes · Marketplace</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

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
      --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
      --shadow-md: 0 12px 28px rgba(0, 0, 0, 0.06);
      --radius-lg: 24px;
      --radius-md: 16px;
      --radius-sm: 12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      min-height: 100vh;
      padding: 1.2rem;
    }

    .page {
      max-width: 1200px;
      margin: 0 auto;
    }

    .top-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 0.9rem 1rem;
      box-shadow: var(--shadow-sm);
      margin-bottom: 1rem;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .logo-icon {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, var(--green), #3a8a7a);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
    }

    .logo-text {
      font-size: 1.45rem;
      font-weight: 700;
      letter-spacing: -0.3px;
      color: var(--green);
    }

    .badge {
      background: #eef3f1;
      color: var(--green);
      font-size: 0.68rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 30px;
      margin-left: 8px;
    }

    .nav {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .nav a {
      text-decoration: none;
      background: var(--green);
      color: white;
      border-radius: 40px;
      padding: 8px 14px;
      font-size: 0.82rem;
      font-weight: 600;
      transition: 0.2s;
      border: 1px solid transparent;
    }

    .nav a:hover {
      background: var(--green-dark);
      transform: translateY(-1px);
    }

    .nav a.secondary {
      background: transparent;
      color: var(--text-secondary);
      border-color: var(--border);
    }

    .welcome {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
      color: var(--text-secondary);
      box-shadow: var(--shadow-sm);
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 1rem;
    }

    .product-card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1rem;
      box-shadow: var(--shadow-sm);
    }

    .product-icon {
      width: 100%;
      height: 130px;
      border-radius: var(--radius-md);
      background: linear-gradient(135deg, #e9e7e2, #f2efea);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--green);
      font-size: 2rem;
      margin-bottom: 0.8rem;
    }

    .product-card h3 {
      font-size: 1rem;
      margin-bottom: 0.3rem;
    }

    .product-card p {
      color: var(--text-secondary);
      font-size: 0.85rem;
      margin-bottom: 0.6rem;
    }

    .price {
      color: var(--rust);
      font-weight: 700;
      margin-bottom: 0.7rem;
      font-size: 1.05rem;
    }

    .product-link {
      text-decoration: none;
      background: var(--green);
      color: white;
      border-radius: 30px;
      padding: 8px 12px;
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .product-link:hover {
      background: var(--green-dark);
    }
  </style>
</head>
<body>
<div class="page">
  <header class="top-bar">
    <a href="shop.php" class="logo-area">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div>
        <span class="logo-text">Pastimes</span>
        <span class="badge">local fashion</span>
      </div>
    </a>
    <nav class="nav">
      <a href="shop.php">Shop</a>
      <a href="product.php?id=1">Products</a>
      <a href="checkout.php">Cart</a>
      <a href="sellers_hub.php">Seller Hub</a>
      <?php if ($role === "admin"): ?>
        <a href="admin.php">Admin</a>
      <?php endif; ?>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <div class="welcome">Welcome back, <?php echo htmlspecialchars($name); ?>.</div>

  <section class="products-grid">
    <article class="product-card">
      <div class="product-icon"><i class="fas fa-tshirt"></i></div>
      <h3>Organic Cotton Tee</h3>
      <p>Soft, breathable, everyday wear.</p>
      <div class="price">$24.90</div>
      <a class="product-link" href="product.php?id=1"><i class="fas fa-eye"></i> View product</a>
    </article>

    <article class="product-card">
      <div class="product-icon"><i class="fas fa-vest"></i></div>
      <h3>Linen Blend Shirt</h3>
      <p>Lightweight fit for warm weather.</p>
      <div class="price">$49.50</div>
      <a class="product-link" href="product.php?id=2"><i class="fas fa-eye"></i> View product</a>
    </article>

    <article class="product-card">
      <div class="product-icon"><i class="fas fa-user-tie"></i></div>
      <h3>Vintage Denim Jacket</h3>
      <p>Classic style with durable finish.</p>
      <div class="price">$79.00</div>
      <a class="product-link" href="product.php?id=3"><i class="fas fa-eye"></i> View product</a>
    </article>

    <article class="product-card">
      <div class="product-icon"><i class="fas fa-scarf"></i></div>
      <h3>Handwoven Scarf</h3>
      <p>Handmade weave with premium wool.</p>
      <div class="price">$34.99</div>
      <a class="product-link" href="product.php?id=4"><i class="fas fa-eye"></i> View product</a>
    </article>
  </section>
</div>
</body>
</html>
