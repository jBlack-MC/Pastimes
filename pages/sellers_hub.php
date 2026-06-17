<?php
/**
 * Seller Hub - Seller Dashboard
 * 
 * Allows sellers to:
 * - Add new products
 * - View their products
 * - Edit products
 * - Delete products
 */

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id = $_SESSION["user_id"];
$name = $_SESSION["name"] ?? "User";
$seller_id = null;
$seller_status = null;
$seller_brand = null;
$error_message = "";
$success_message = "";

// Check if user is a seller
$sellerStmt = mysqli_prepare($conn, 
    "SELECT seller_id, approval_status, brand_name FROM tblseller WHERE user_id = ?"
);

if ($sellerStmt) {
    mysqli_stmt_bind_param($sellerStmt, "i", $user_id);
    mysqli_stmt_execute($sellerStmt);
    $sellerResult = mysqli_stmt_get_result($sellerStmt);
    $sellerRow = mysqli_fetch_assoc($sellerResult);
    mysqli_stmt_close($sellerStmt);
    
    if ($sellerRow) {
        $seller_id = $sellerRow["seller_id"];
        $seller_status = $sellerRow["approval_status"];
        $seller_brand = $sellerRow["brand_name"];
    }
}

if (!$seller_id) {
    header("Location: seller_register.php");
    exit;
}

// Handle delete product
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $product_id = (int)$_GET["delete"];
    
    // Verify ownership
    $checkStmt = mysqli_prepare($conn, 
        "SELECT product_id FROM tblclothes WHERE product_id = ? AND seller_id = ?"
    );
    
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "ii", $product_id, $seller_id);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        
        if (mysqli_num_rows($checkResult) > 0) {
            // Delete the product
            $deleteStmt = mysqli_prepare($conn, "DELETE FROM tblclothes WHERE product_id = ?");
            if ($deleteStmt) {
                mysqli_stmt_bind_param($deleteStmt, "i", $product_id);
                if (mysqli_stmt_execute($deleteStmt)) {
                    $success_message = "Product deleted successfully.";
                } else {
                    $error_message = "Error deleting product.";
                }
                mysqli_stmt_close($deleteStmt);
            }
        }
        mysqli_stmt_close($checkStmt);
    }
}

// Handle add/edit product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action = $_POST["action"];
    
    if ($action === "add" || $action === "edit") {
        $product_name = trim($_POST["product_name"] ?? "");
        $brand = trim($_POST["brand"] ?? $seller_brand);
        $description = trim($_POST["description"] ?? "");
        $price = (float)($_POST["price"] ?? 0);
        $stock = (int)($_POST["stock"] ?? 0);
        
        // Validation
        if (empty($product_name)) {
            $error_message = "Product name is required.";
        } elseif ($price <= 0) {
            $error_message = "Price must be greater than 0.";
        } elseif ($stock < 0) {
            $error_message = "Stock cannot be negative.";
        } else if ($action === "add") {
            $image = "placeholder-clothing.jpg";

            // Add new product
            $insertStmt = mysqli_prepare($conn,
                "INSERT INTO tblclothes (user_id, seller_id, name, brand, description, price, stock, image, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            
            if ($insertStmt) {
                mysqli_stmt_bind_param($insertStmt, "iisssdis", $user_id, $seller_id, $product_name, $brand, $description, $price, $stock, $image);
                
                if (mysqli_stmt_execute($insertStmt)) {
                    $success_message = "Product added successfully!";
                } else {
                    $error_message = "Error adding product: " . mysqli_error($conn);
                }
                mysqli_stmt_close($insertStmt);
            }
        } else if ($action === "edit") {
            // Edit product
            $product_id = (int)($_POST["product_id"] ?? 0);
            
            // Verify ownership
            $checkStmt = mysqli_prepare($conn,
                "SELECT product_id FROM tblclothes WHERE product_id = ? AND seller_id = ?"
            );
            
            if ($checkStmt) {
                mysqli_stmt_bind_param($checkStmt, "ii", $product_id, $seller_id);
                mysqli_stmt_execute($checkStmt);
                $checkResult = mysqli_stmt_get_result($checkStmt);
                
                if (mysqli_num_rows($checkResult) > 0) {
                    $updateStmt = mysqli_prepare($conn,
                        "UPDATE tblclothes SET name = ?, brand = ?, description = ?, price = ?, stock = ?
                         WHERE product_id = ? AND seller_id = ?"
                    );
                    
                    if ($updateStmt) {
                        mysqli_stmt_bind_param($updateStmt, "sssdiii", $product_name, $brand, $description, $price, $stock, $product_id, $seller_id);
                        
                        if (mysqli_stmt_execute($updateStmt)) {
                            $success_message = "Product updated successfully!";
                        } else {
                            $error_message = "Error updating product.";
                        }
                        mysqli_stmt_close($updateStmt);
                    }
                } else {
                    $error_message = "Product not found or you don't have permission to edit it.";
                }
                mysqli_stmt_close($checkStmt);
            }
        }
    }
}

// Fetch seller's products
$products = [];
if ($seller_id) {
    $productsStmt = mysqli_prepare($conn,
        "SELECT product_id, name, brand, description, price, stock, created_at
         FROM tblclothes
         WHERE seller_id = ?
         ORDER BY created_at DESC"
    );
    
    if ($productsStmt) {
        mysqli_stmt_bind_param($productsStmt, "i", $seller_id);
        mysqli_stmt_execute($productsStmt);
        $productsResult = mysqli_stmt_get_result($productsStmt);
        
        while ($product = mysqli_fetch_assoc($productsResult)) {
            $products[] = $product;
        }
        mysqli_stmt_close($productsStmt);
    }
}

$edit_product = null;
if (isset($_GET["edit"]) && is_numeric($_GET["edit"])) {
    $product_id = (int)$_GET["edit"];
    
    // Find product
    foreach ($products as $p) {
        if ($p["product_id"] == $product_id) {
            $edit_product = $p;
            break;
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes Â· Seller Dashboard</title>
  <!-- Font Awesome 6 -->
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
      --shadow-sm: 0 4px 12px rgba(0, 0, 0, 0.03);
      --shadow-md: 0 8px 24px rgba(0, 0, 0, 0.06);
      --radius-lg: 24px;
      --radius-md: 16px;
      --radius-sm: 12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      line-height: 1.5;
    }

    .dashboard-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 1.2rem 1.5rem 2rem;
    }

    /* header */
    .site-header {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      margin-bottom: 1.8rem;
      padding-bottom: 0.8rem;
      border-bottom: 1px solid var(--border);
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
    }

    .logo-icon {
      width: 42px;
      height: 42px;
      background: linear-gradient(135deg, var(--green), #3a8a7a);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.3rem;
    }

    .logo-text {
      font-size: 1.6rem;
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

    .seller-badge {
      background: var(--gold);
      color: #2a2a2a;
    }

    .nav-icons {
      display: flex;
      gap: 12px;
    }

    .icon-btn {
      background: var(--white);
      border: 1px solid var(--border);
      width: 42px;
      height: 42px;
      border-radius: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-secondary);
      cursor: pointer;
      transition: 0.2s;
    }

    .alert {
      border-radius: var(--radius-sm);
      padding: 0.85rem 1rem;
      margin-bottom: 1rem;
      font-size: 0.88rem;
      border-left: 4px solid;
      background: var(--white);
    }

    .alert-success {
      color: #1b6f4e;
      border-color: #1b6f4e;
    }

    .alert-error {
      color: #9b3a25;
      border-color: var(--rust);
    }

    /* stats cards */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.2rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 1.2rem 1rem;
      border: 1px solid var(--border);
      box-shadow: var(--shadow-sm);
    }

    .stat-title {
      font-size: 0.8rem;
      color: var(--text-muted);
      margin-bottom: 6px;
    }

    .stat-value {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--green);
    }

    .stat-sub {
      font-size: 0.7rem;
      color: var(--text-secondary);
      margin-top: 4px;
    }

    /* tabs */
    .tabs {
      display: flex;
      gap: 8px;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 0.5rem;
    }

    .tab-btn {
      background: none;
      border: none;
      padding: 10px 20px;
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--text-secondary);
      cursor: pointer;
      border-radius: 40px;
      transition: 0.2s;
    }

    .tab-btn.active {
      background: var(--green);
      color: white;
    }

    /* product table / grid */
    .products-section, .orders-section {
      background: var(--white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border);
      overflow-x: auto;
      padding: 1rem;
    }

    .add-product-bar {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 1rem;
    }

    .btn-primary {
      background: var(--green);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 40px;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-outline {
      background: transparent;
      border: 1px solid var(--border);
      padding: 6px 14px;
      border-radius: 30px;
      cursor: pointer;
      font-size: 0.75rem;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 12px 10px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    th {
      font-weight: 600;
      color: var(--text-secondary);
      font-size: 0.8rem;
    }

    .product-img-small {
      width: 48px;
      height: 48px;
      background: #edece4;
      border-radius: var(--radius-sm);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--green);
    }

    .status-badge {
      background: #e8f5f2;
      color: var(--green);
      padding: 4px 10px;
      border-radius: 30px;
      font-size: 0.7rem;
      font-weight: 600;
      display: inline-block;
    }

    .status-sold {
      background: #fef2e8;
      color: var(--rust);
    }

    .action-icons i {
      margin: 0 4px;
      cursor: pointer;
      color: var(--text-muted);
      transition: 0.2s;
    }

    .action-icons i:hover {
      color: var(--rust);
    }

    /* modal */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
    .modal-content {
      background: var(--white);
      max-width: 500px;
      width: 90%;
      border-radius: var(--radius-lg);
      padding: 1.8rem;
    }
    .modal input, .modal select, .modal textarea {
      width: 100%;
      padding: 10px;
      margin: 8px 0 16px;
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
    }
    .modal-buttons {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    @media (max-width: 700px) {
      .dashboard-container {
        padding: 1rem;
      }
      th, td {
        font-size: 0.75rem;
        padding: 8px 6px;
      }
    }
  </style>
</head>
<body>
<div class="dashboard-container">
  <!-- header -->
  <header class="site-header">
    <a class="logo-area" href="shop.php" style="text-decoration:none;">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div>
        <span class="logo-text">Pastimes</span>
        <span class="badge seller-badge"><i class="fas fa-store"></i> Seller Hub</span>
      </div>
    </a>
    <div class="nav-icons">
      <button class="icon-btn" id="logoutBtn" title="Logout"><i class="fas fa-sign-out-alt"></i></button>
    </div>
  </header>

  <?php if ($error_message): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>

  <?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
  <?php endif; ?>

  <!-- stats cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-title">Total Products</div>
      <div class="stat-value" id="totalProducts">0</div>
      <div class="stat-sub">active listings</div>
    </div>
    <div class="stat-card">
      <div class="stat-title">Total Sales</div>
      <div class="stat-value" id="totalSales">$0</div>
      <div class="stat-sub">lifetime revenue</div>
    </div>
    <div class="stat-card">
      <div class="stat-title">Orders</div>
      <div class="stat-value" id="orderCount">0</div>
      <div class="stat-sub">completed</div>
    </div>
    <div class="stat-card">
      <div class="stat-title">Avg Rating</div>
      <div class="stat-value" id="avgRating">0.0</div>
      <div class="stat-sub">â­ from buyers</div>
    </div>
  </div>

  <!-- tabs -->
  <div class="tabs">
    <button class="tab-btn active" data-tab="products">ðŸ“¦ My Products</button>
    <button class="tab-btn" data-tab="orders">ðŸ“‹ Sales & Orders</button>
  </div>

  <!-- products panel -->
  <div id="productsPanel" class="products-section">
    <div class="add-product-bar">
      <button class="btn-primary" id="addProductBtn"><i class="fas fa-plus"></i> Add New Item</button>
    </div>
    <div style="overflow-x: auto;">
      <table id="productsTable">
        <thead>
          <tr><th>Image</th><th>Product</th><th>Price</th><th>Status</th><th>Sales</th><th>Actions</th></tr>
        </thead>
        <tbody id="productsTableBody"></tbody>
      </table>
    </div>
  </div>

  <!-- orders panel (hidden initially) -->
  <div id="ordersPanel" class="orders-section" style="display: none;">
    <h3 style="margin-bottom: 1rem;"><i class="fas fa-truck"></i> Recent orders</h3>
    <div style="overflow-x: auto;">
      <table>
        <thead><tr><th>Order ID</th><th>Product</th><th>Buyer</th><th>Qty</th><th>Total</th><th>Date</th><th>Status</th></tr></thead>
        <tbody id="ordersTableBody"></tbody>
      </table>
    </div>
  </div>

  <!-- add/edit product modal -->
  <div id="productModal" class="modal">
    <form id="productForm" method="POST" action="sellers_hub.php" class="modal-content">
      <h3 id="modalTitle">Add New Product</h3>
      <input type="hidden" id="productAction" name="action" value="add">
      <input type="hidden" id="editProductId" name="product_id">
      <label>Product Name</label>
      <input type="text" id="productName" name="product_name" placeholder="e.g., Organic Cotton Tee" required>
      <label>Brand</label>
      <input type="text" id="productBrand" name="brand" value="<?php echo htmlspecialchars($seller_brand ?? ""); ?>">
      <label>Description</label>
      <textarea id="productDescription" name="description" rows="4" placeholder="Describe the item, fabric, fit, and condition."></textarea>
      <label>Price ($)</label>
      <input type="number" id="productPrice" name="price" step="0.01" min="0.01" placeholder="29.99" required>
      <label>Stock</label>
      <input type="number" id="productStock" name="stock" min="0" value="10" required>
      <div class="modal-buttons">
        <button type="button" class="btn-outline" id="closeModalBtn">Cancel</button>
        <button type="submit" class="btn-primary" id="saveProductBtn">Save Product</button>
      </div>
    </form>
  </div>
</div>

<script>
  const sellerBrand = <?php echo json_encode($seller_brand ?? ""); ?>;
  const sellerProducts = <?php echo json_encode(array_map(function ($product) {
    return [
      "id" => (int)$product["product_id"],
      "name" => $product["name"] ?? "",
      "brand" => $product["brand"] ?? "",
      "description" => $product["description"] ?? "",
      "price" => (float)$product["price"],
      "stock" => (int)$product["stock"],
      "icon" => "fa-tshirt",
      "sales" => 0,
    ];
  }, $products), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  const salesOrders = [];

  // Helper: update dashboard stats
  function updateStats() {
    const totalProductsCount = sellerProducts.length;
    const totalRevenue = salesOrders.reduce((sum, order) => sum + order.total, 0);
    const orderCount = salesOrders.length;
    const avgRating = 0;

    document.getElementById('totalProducts').innerText = totalProductsCount;
    document.getElementById('totalSales').innerText = `$${totalRevenue.toFixed(2)}`;
    document.getElementById('orderCount').innerText = orderCount;
    document.getElementById('avgRating').innerText = avgRating.toFixed(1);
  }

  // render products table
  function renderProducts() {
    const tbody = document.getElementById('productsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';

    if (sellerProducts.length === 0) {
      tbody.innerHTML = '<tr><td colspan="6">No products yet. Add your first listing.</td></tr>';
      return;
    }

    sellerProducts.forEach(product => {
      const row = tbody.insertRow();
      row.innerHTML = `
        <td><div class="product-img-small"><i class="fas ${product.icon}"></i></div></td>
        <td><strong>${escapeHtml(product.name)}</strong><br><small>${escapeHtml(product.brand || sellerBrand)}</small></td>
        <td>$${product.price.toFixed(2)}</td>
        <td><span class="status-badge ${product.stock === 0 ? 'status-sold' : ''}">${product.stock > 0 ? 'In stock' : 'Out of stock'}</span></td>
        <td>${product.sales || 0}</td>
        <td class="action-icons">
          <i class="fas fa-edit" data-id="${product.id}" title="Edit"></i>
          <i class="fas fa-trash-alt" data-id="${product.id}" title="Delete"></i>
        </td>
      `;
    });

    // attach edit/delete events
    document.querySelectorAll('.fa-edit').forEach(icon => {
      icon.addEventListener('click', (e) => {
        const id = parseInt(icon.getAttribute('data-id'));
        editProduct(id);
      });
    });
    document.querySelectorAll('.fa-trash-alt').forEach(icon => {
      icon.addEventListener('click', (e) => {
        const id = parseInt(icon.getAttribute('data-id'));
        if (confirm('Delete this product? It will also affect sales records.')) {
          window.location.href = 'sellers_hub.php?delete=' + encodeURIComponent(id);
        }
      });
    });
  }

  function renderOrders() {
    const tbody = document.getElementById('ordersTableBody');
    if (!tbody) return;
    tbody.innerHTML = '';
    salesOrders.forEach(order => {
      const row = tbody.insertRow();
      row.innerHTML = `
        <td>${order.id}</td>
        <td>${escapeHtml(order.productName)}</td>
        <td>${order.buyer}</td>
        <td>${order.qty}</td>
        <td>$${order.total.toFixed(2)}</td>
        <td>${order.date}</td>
        <td><span class="status-badge">${order.status}</span></td>
      `;
    });
  }

  function editProduct(id) {
    const product = sellerProducts.find(p => p.id === id);
    if (!product) return;
    document.getElementById('modalTitle').innerText = 'Edit Product';
    document.getElementById('productAction').value = 'edit';
    document.getElementById('editProductId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('productBrand').value = product.brand || sellerBrand;
    document.getElementById('productDescription').value = product.description || '';
    document.getElementById('productPrice').value = product.price;
    document.getElementById('productStock').value = product.stock;
    document.getElementById('productModal').style.display = 'flex';
  }

  function closeModal() {
    document.getElementById('productModal').style.display = 'none';
    document.getElementById('productAction').value = 'add';
    document.getElementById('editProductId').value = '';
    document.getElementById('productName').value = '';
    document.getElementById('productBrand').value = sellerBrand;
    document.getElementById('productDescription').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '10';
  }

  function escapeHtml(str) { return String(str || '').replace(/[&<>]/g, function(m){if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

  // tab switching
  const productsPanel = document.getElementById('productsPanel');
  const ordersPanel = document.getElementById('ordersPanel');
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const tab = btn.getAttribute('data-tab');
      if (tab === 'products') {
        productsPanel.style.display = 'block';
        ordersPanel.style.display = 'none';
        renderProducts();
      } else {
        productsPanel.style.display = 'none';
        ordersPanel.style.display = 'block';
        renderOrders();
      }
    });
  });

  // modal handlers
  document.getElementById('addProductBtn').addEventListener('click', () => {
    document.getElementById('modalTitle').innerText = 'Add New Product';
    document.getElementById('productAction').value = 'add';
    document.getElementById('editProductId').value = '';
    document.getElementById('productName').value = '';
    document.getElementById('productBrand').value = sellerBrand;
    document.getElementById('productDescription').value = '';
    document.getElementById('productPrice').value = '';
    document.getElementById('productStock').value = '5';
    document.getElementById('productModal').style.display = 'flex';
  });
  document.getElementById('closeModalBtn').addEventListener('click', closeModal);
  window.addEventListener('click', (e) => { if (e.target === document.getElementById('productModal')) closeModal(); });

  // logout
  document.getElementById('logoutBtn').addEventListener('click', () => {
    window.location.href = "logout.php";
  });

  // initial render
  renderProducts();
  updateStats();
  renderOrders(); // preload orders data
</script>
</body>
</html>

