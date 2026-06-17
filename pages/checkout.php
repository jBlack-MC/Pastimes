<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id = $_SESSION["user_id"];

// Get cart items
$cartStmt = mysqli_prepare($conn, 
  "SELECT MIN(c.cart_id) AS cart_id, c.product_id, SUM(c.quantity) AS quantity, p.name, p.price, p.stock 
   FROM tblcart c 
   JOIN tblclothes p ON c.product_id = p.product_id 
   WHERE c.user_id = ? 
   GROUP BY c.product_id, p.name, p.price, p.stock
   ORDER BY cart_id DESC"
);

$cartItems = [];
$cartTotal = 0;

if ($cartStmt) {
  mysqli_stmt_bind_param($cartStmt, "i", $user_id);
  mysqli_stmt_execute($cartStmt);
  $result = mysqli_stmt_get_result($cartStmt);
  
  while ($item = mysqli_fetch_assoc($result)) {
    $cartItems[] = $item;
    $cartTotal += ($item["quantity"] * $item["price"]);
  }
  mysqli_stmt_close($cartStmt);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes · Shopping Cart</title>
  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
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

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: system-ui, "Segoe UI", -apple-system, BlinkMacSystemFont, sans-serif;
      color: var(--ink);
      background: linear-gradient(180deg, #fcf7f0 0%, #f5ede2 100%);
      min-height: 100vh;
      line-height: 1.5;
    }

    .app-wrapper {
      max-width: 1280px;
      margin: 0 auto;
      padding: 0 1rem 2rem;
    }

    .top-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      padding: 0.8rem 1.2rem;
      background: rgba(255, 250, 244, 0.92);
      border: 1px solid rgba(255, 255, 255, 0.8);
      border-radius: 60px;
      backdrop-filter: blur(12px);
      box-shadow: 0 6px 18px rgba(31, 26, 23, 0.05);
      margin-bottom: 1.8rem;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 10px;
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
      letter-spacing: -0.02em;
      color: var(--forest);
    }

    .nav-actions {
      display: flex;
      align-items: center;
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
      font-size: 1.2rem;
      color: var(--ink);
      cursor: pointer;
      transition: all 0.15s;
      text-decoration: none;
    }

    .breadcrumb {
      margin-bottom: 1.8rem;
      font-size: 0.85rem;
      color: var(--muted);
    }

    .breadcrumb a {
      color: var(--forest);
      text-decoration: none;
      font-weight: 600;
    }

    h1 {
      font-size: 2rem;
      margin-bottom: 1.5rem;
    }

    .cart-container {
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 28px;
    }

    .cart-items-card {
      background: var(--paper);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      box-shadow: var(--shadow);
      border: 1px solid rgba(255, 255, 240, 0.8);
    }

    .cart-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 1.2rem;
      border: 1px solid var(--line);
      border-radius: var(--radius-md);
      margin-bottom: 1rem;
      gap: 1rem;
    }

    .item-info {
      flex: 1;
    }

    .item-name {
      font-weight: 700;
      margin-bottom: 0.3rem;
    }

    .item-price {
      font-size: 0.9rem;
      color: var(--muted);
      margin-bottom: 0.5rem;
    }

    .item-stock {
      font-size: 0.75rem;
      color: var(--muted);
    }

    .item-controls {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .qty-input {
      width: 60px;
      padding: 0.5rem;
      border: 1px solid var(--line);
      border-radius: 6px;
      text-align: center;
      font-weight: 600;
    }

    .item-total {
      font-weight: 700;
      color: var(--rust);
      min-width: 80px;
      text-align: right;
    }

    .btn-remove {
      background: none;
      border: none;
      color: var(--muted);
      cursor: pointer;
      font-size: 1.1rem;
      padding: 0.5rem;
      transition: 0.2s;
    }

    .btn-remove:hover {
      color: var(--rust);
    }

    .order-summary {
      background: var(--paper);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      box-shadow: var(--shadow);
      border: 1px solid rgba(255, 255, 240, 0.8);
      height: fit-content;
    }

    .summary-title {
      font-size: 1.3rem;
      font-weight: 800;
      margin-bottom: 1.2rem;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      padding: 0.6rem 0;
      border-bottom: 1px solid var(--line);
    }

    .summary-total {
      display: flex;
      justify-content: space-between;
      font-weight: 800;
      font-size: 1.2rem;
      color: var(--rust);
      margin-top: 1rem;
      padding-top: 0.8rem;
      border-top: 2px solid var(--ink);
    }

    .btn {
      border: none;
      padding: 0.8rem 1.2rem;
      border-radius: 40px;
      font-weight: 600;
      cursor: pointer;
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
      width: 100%;
      font-size: 1rem;
      margin-top: 1.2rem;
      padding: 1rem;
    }

    .btn-primary:hover {
      background: #1e5247;
    }

    .btn-secondary {
      background: transparent;
      color: var(--forest);
      border: 1px solid var(--line);
      width: 100%;
      margin-top: 0.5rem;
    }

    .btn-secondary:hover {
      background: var(--sand);
    }

    .empty-cart {
      text-align: center;
      padding: 3rem;
      color: var(--muted);
    }

    .empty-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
      opacity: 0.5;
    }

    .toast {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      background: var(--forest);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 50px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
      animation: slideIn 0.3s ease-out;
      z-index: 1000;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeOut {
      from {
        opacity: 1;
        transform: translateY(0);
      }
      to {
        opacity: 0;
        transform: translateY(20px);
      }
    }

    @media (max-width: 768px) {
      .cart-container {
        grid-template-columns: 1fr;
      }

      .cart-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .item-controls {
        width: 100%;
        justify-content: space-between;
      }

      .item-total {
        align-self: flex-end;
      }
    }
  </style>
</head>
<body>
  <div class="app-wrapper">
    <!-- Header -->
    <div class="top-bar">
      <div class="logo-area">
        <div class="logo-icon">👔</div>
        <div class="logo-text">Pastimes</div>
      </div>
      <nav class="nav-actions">
        <a href="shop.php" class="nav-icon-btn" title="Back to Shop">
          <i class="fas fa-arrow-left"></i>
        </a>
        <a href="logout.php" class="nav-icon-btn" title="Logout">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </nav>
    </div>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
      <a href="shop.php">Shop</a> > 
      <span>Shopping Cart</span>
    </div>

    <h1>Shopping Cart</h1>

    <?php if (count($cartItems) === 0): ?>

    <!-- Empty Cart -->
    <div class="cart-items-card">
      <div class="empty-cart">
        <div class="empty-icon"><i class="fas fa-shopping-cart"></i></div>
        <h2>Your cart is empty</h2>
        <p>Add some items to get started!</p>
        <a href="shop.php" class="btn btn-primary" style="width: auto; margin-top: 1.5rem;">
          <i class="fas fa-shopping-bag"></i> Continue Shopping
        </a>
      </div>
    </div>

    <?php else: ?>

    <!-- Cart Layout -->
    <div class="cart-container">
      <!-- Cart Items -->
      <div class="cart-items-card">
        <?php foreach ($cartItems as $item): 
          $lineTotal = $item["quantity"] * $item["price"];
        ?>
        <div class="cart-item">
          <div class="item-info">
            <div class="item-name"><?php echo htmlspecialchars($item["name"]); ?></div>
            <div class="item-price">$<?php echo number_format($item["price"], 2); ?> each</div>
            <div class="item-stock">Stock: <?php echo (int)$item["stock"]; ?></div>
          </div>

          <div class="item-controls">
            <input 
              type="number" 
              class="qty-input qty-field" 
              value="<?php echo (int)$item["quantity"]; ?>" 
              min="1" 
              max="<?php echo (int)$item["stock"]; ?>"
              data-product-id="<?php echo (int)$item["product_id"]; ?>"
              data-price="<?php echo (float)$item["price"]; ?>"
              title="Update quantity"
            >
            <div class="item-total">$<?php echo number_format($lineTotal, 2); ?></div>
            <button 
              class="btn-remove remove-btn" 
              data-product-id="<?php echo (int)$item["product_id"]; ?>"
              title="Remove from cart"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Order Summary -->
      <div class="order-summary">
        <div class="summary-title">Order Summary</div>

        <div class="summary-row">
          <span>Subtotal:</span>
          <span id="subtotal">$<?php echo number_format($cartTotal, 2); ?></span>
        </div>

        <div class="summary-row">
          <span>Tax (estimated):</span>
          <span>$0.00</span>
        </div>

        <div class="summary-row">
          <span>Shipping:</span>
          <span>Free</span>
        </div>

        <div class="summary-total">
          <span>Total:</span>
          <span id="total">$<?php echo number_format($cartTotal, 2); ?></span>
        </div>

        <a href="process_checkout.php" class="btn btn-primary">
          <i class="fas fa-credit-card"></i> Proceed to Checkout
        </a>

        <a href="shop.php" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
      </div>
    </div>

    <?php endif; ?>

  </div>

  <script>
    // Remove item from cart
    document.querySelectorAll('.remove-btn').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const productId = btn.dataset.productId;
        
        try {
          const response = await fetch('cart_remove.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'product_id=' + productId
          });
          
          const data = await response.json();
          if (data.success) {
            showToast('Item removed from cart');
            setTimeout(() => location.reload(), 500);
          } else {
            showToast('Error: ' + data.message, 'error');
          }
        } catch (error) {
          showToast('Error removing item', 'error');
          console.error(error);
        }
      });
    });

    // Update quantity
    document.querySelectorAll('.qty-field').forEach(input => {
      input.addEventListener('change', async (e) => {
        const productId = input.dataset.productId;
        const quantity = parseInt(input.value);
        
        if (quantity < 1) {
          input.value = 1;
          return;
        }
        
        try {
          const response = await fetch('cart_update.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'product_id=' + productId + '&quantity=' + quantity
          });
          
          const data = await response.json();
          if (data.success) {
            showToast('Quantity updated');
            // Update totals
            document.getElementById('subtotal').textContent = '$' + data.cart_total.toFixed(2);
            document.getElementById('total').textContent = '$' + data.cart_total.toFixed(2);
            const itemTotal = input.closest('.cart-item').querySelector('.item-total');
            if (itemTotal) {
              itemTotal.textContent = '$' + data.line_total.toFixed(2);
            }
          } else {
            showToast('Error: ' + data.message, 'error');
            setTimeout(() => location.reload(), 500);
          }
        } catch (error) {
          showToast('Error updating quantity', 'error');
          console.error(error);
        }
      });
    });

    function showToast(message, type = 'success') {
      const toast = document.createElement('div');
      toast.className = 'toast';
      toast.textContent = message;
      if (type === 'error') {
        toast.style.background = '#bf5a36';
      }
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
      }, 2000);
    }
  </script>
</body>
</html>
<?php mysqli_close($conn); ?>

