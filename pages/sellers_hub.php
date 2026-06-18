<?php
/* Seller Hub – sellers manage their product listings */
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

/* Resolve the seller record for the logged-in user */
$sellerStmt = mysqli_prepare($conn,
    "SELECT seller_id, approval_status, brand_name FROM tblseller WHERE user_id = ?"
);
if ($sellerStmt) {
    mysqli_stmt_bind_param($sellerStmt, "i", $user_id);
    mysqli_stmt_execute($sellerStmt);
    $sellerRow = mysqli_fetch_assoc(mysqli_stmt_get_result($sellerStmt));
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

/* ── DELETE product ── */
if (isset($_GET["delete"]) && is_numeric($_GET["delete"])) {
    $del_id = (int)$_GET["delete"];
    $chk = mysqli_prepare($conn, "SELECT product_id, image FROM tblclothes WHERE product_id = ? AND seller_id = ?");
    if ($chk) {
        mysqli_stmt_bind_param($chk, "ii", $del_id, $seller_id);
        mysqli_stmt_execute($chk);
        $chkRow = mysqli_fetch_assoc(mysqli_stmt_get_result($chk));
        mysqli_stmt_close($chk);
        if ($chkRow) {
            $delStmt = mysqli_prepare($conn, "DELETE FROM tblclothes WHERE product_id = ?");
            if ($delStmt) {
                mysqli_stmt_bind_param($delStmt, "i", $del_id);
                if (mysqli_stmt_execute($delStmt)) {
                    /* Remove the uploaded image file if it is not the placeholder */
                    $img = $chkRow["image"] ?? "";
                    if ($img && $img !== "placeholder-clothing.jpg") {
                        $imgPath = __DIR__ . "/../uploads/" . $img;
                        if (file_exists($imgPath)) unlink($imgPath);
                    }
                    $success_message = "Product deleted successfully.";
                } else {
                    $error_message = "Error deleting product.";
                }
                mysqli_stmt_close($delStmt);
            }
        }
    }
}

/* ── ADD / EDIT product ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    $action       = $_POST["action"];
    $product_name = trim($_POST["product_name"] ?? "");
    $brand        = trim($_POST["brand"] ?? $seller_brand);
    $description  = trim($_POST["description"] ?? "");
    $price        = (float)($_POST["price"] ?? 0);
    $stock        = (int)($_POST["stock"] ?? 0);

    if (empty($product_name)) {
        $error_message = "Product name is required.";
    } elseif ($price <= 0) {
        $error_message = "Price must be greater than 0.";
    } elseif ($stock < 0) {
        $error_message = "Stock cannot be negative.";
    } else {
        /* Handle optional image upload – validate extension AND MIME type */
        $uploaded_image = null;
        if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] === UPLOAD_ERR_OK) {
            $ext     = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
            $allowed = ["jpg", "jpeg", "png", "gif", "webp"];
            if (in_array($ext, $allowed, true)) {
                /* Verify real file content, not just the filename extension */
                $finfo        = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType     = finfo_file($finfo, $_FILES["product_image"]["tmp_name"]);
                finfo_close($finfo);
                $allowedMimes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
                if (!in_array($mimeType, $allowedMimes, true)) {
                    $error_message = "Invalid image file. Please upload a real JPG, PNG, GIF, or WEBP.";
                } else {
                    $filename  = uniqid("img_") . "." . $ext;
                    $uploadDir = __DIR__ . "/../uploads/";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    /* Move the validated file to the uploads directory */
                    if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $uploadDir . $filename)) {
                        $uploaded_image = $filename;
                    } else {
                        $error_message = "Image upload failed. Please try again.";
                    }
                }
            } else {
                $error_message = "Only JPG, PNG, GIF, or WEBP images are allowed.";
            }
        }

        if (!$error_message) {
            if ($action === "add") {
                /* Insert a new product listing for this seller */
                $image = $uploaded_image ?? "placeholder-clothing.jpg";
                $ins = mysqli_prepare($conn,
                    "INSERT INTO tblclothes (user_id, seller_id, name, brand, description, price, stock, image, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                if ($ins) {
                    mysqli_stmt_bind_param($ins, "iisssdis",
                        $user_id, $seller_id, $product_name, $brand, $description, $price, $stock, $image
                    );
                    if (mysqli_stmt_execute($ins)) {
                        $success_message = "Product added successfully!";
                    } else {
                        $error_message = "Error adding product: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($ins);
                }
            } elseif ($action === "edit") {
                $product_id = (int)($_POST["product_id"] ?? 0);
                $ownChk = mysqli_prepare($conn, "SELECT product_id FROM tblclothes WHERE product_id = ? AND seller_id = ?");
                if ($ownChk) {
                    mysqli_stmt_bind_param($ownChk, "ii", $product_id, $seller_id);
                    mysqli_stmt_execute($ownChk);
                    $owns = mysqli_num_rows(mysqli_stmt_get_result($ownChk)) > 0;
                    mysqli_stmt_close($ownChk);

                    if ($owns) {
                        if ($uploaded_image) {
                            /* Update product including new image */
                            $upd = mysqli_prepare($conn,
                                "UPDATE tblclothes SET name=?, brand=?, description=?, price=?, stock=?, image=?
                                 WHERE product_id=? AND seller_id=?"
                            );
                            if ($upd) {
                                mysqli_stmt_bind_param($upd, "sssdisii",
                                    $product_name, $brand, $description, $price, $stock, $uploaded_image,
                                    $product_id, $seller_id
                                );
                                $ok = mysqli_stmt_execute($upd);
                                mysqli_stmt_close($upd);
                                $success_message = $ok ? "Product updated!" : "Error updating product.";
                            }
                        } else {
                            /* Update product without changing the image */
                            $upd = mysqli_prepare($conn,
                                "UPDATE tblclothes SET name=?, brand=?, description=?, price=?, stock=?
                                 WHERE product_id=? AND seller_id=?"
                            );
                            if ($upd) {
                                mysqli_stmt_bind_param($upd, "sssdiis",
                                    $product_name, $brand, $description, $price, $stock,
                                    $product_id, $seller_id
                                );
                                $ok = mysqli_stmt_execute($upd);
                                mysqli_stmt_close($upd);
                                if (!$ok) $error_message = "Error updating product.";
                                else $success_message = "Product updated!";
                            }
                        }
                    } else {
                        $error_message = "Product not found or permission denied.";
                    }
                }
            }
        }
    }
}

/* Fetch all products for this seller */
$products = [];
if ($seller_id) {
    $pStmt = mysqli_prepare($conn,
        "SELECT product_id, name, brand, description, price, stock, image, created_at
         FROM tblclothes
         WHERE seller_id = ?
         ORDER BY created_at DESC"
    );
    if ($pStmt) {
        mysqli_stmt_bind_param($pStmt, "i", $seller_id);
        mysqli_stmt_execute($pStmt);
        $pResult = mysqli_stmt_get_result($pStmt);
        while ($row = mysqli_fetch_assoc($pResult)) $products[] = $row;
        mysqli_stmt_close($pStmt);
    }
}

/* Fetch seller summary stats (order count + lifetime revenue) */
$orderCount = 0; $totalRevenue = 0.0;
$oSummary = mysqli_prepare($conn,
    "SELECT COUNT(DISTINCT ol.order_id) AS orders, COALESCE(SUM(ol.quantity * ol.unit_price),0) AS revenue
     FROM tblorderline ol
     JOIN tblclothes c ON ol.product_id = c.product_id
     WHERE c.seller_id = ?"
);
if ($oSummary) {
    mysqli_stmt_bind_param($oSummary, "i", $seller_id);
    mysqli_stmt_execute($oSummary);
    $oRow = mysqli_fetch_assoc(mysqli_stmt_get_result($oSummary));
    mysqli_stmt_close($oSummary);
    $orderCount   = (int)($oRow["orders"] ?? 0);
    $totalRevenue = (float)($oRow["revenue"] ?? 0);
}

/* Fetch individual orders that contain this seller's products (for the Orders tab) */
$sellerOrders = [];
$soStmt = mysqli_prepare($conn,
    "SELECT o.order_id, o.order_date, o.status,
            SUM(ol.quantity) AS total_items,
            SUM(ol.quantity * ol.unit_price) AS order_revenue
     FROM tblorder o
     JOIN tblorderline ol ON o.order_id = ol.order_id
     JOIN tblclothes c ON ol.product_id = c.product_id
     WHERE c.seller_id = ?
     GROUP BY o.order_id, o.order_date, o.status
     ORDER BY o.order_date DESC
     LIMIT 50"
);
if ($soStmt) {
    mysqli_stmt_bind_param($soStmt, "i", $seller_id);
    mysqli_stmt_execute($soStmt);
    $soResult = mysqli_stmt_get_result($soStmt);
    while ($row = mysqli_fetch_assoc($soResult)) $sellerOrders[] = $row;
    mysqli_stmt_close($soStmt);
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Seller Hub</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--muted:#8a9b9b;--sec:#5a6e6e;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;--gold:#d4a259;
      --border:#e8e6e1;--sm:0 4px 12px rgba(0,0,0,.03);--md:0 8px 24px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);line-height:1.5}
    .wrap{max-width:1400px;margin:0 auto;padding:1.2rem 1.5rem 2rem}
    /* header */
    .hdr{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1.5rem;padding-bottom:.8rem;border-bottom:1px solid var(--border)}
    .logo{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-ico{width:42px;height:42px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem}
    .logo-txt{font-size:1.5rem;font-weight:700;color:var(--green)}
    .badge{background:#eef3f1;color:var(--green);font-size:.7rem;font-weight:600;padding:4px 10px;border-radius:30px;margin-left:8px}
    .badge-gold{background:var(--gold);color:#2a2a2a}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{text-decoration:none;padding:8px 14px;border-radius:40px;font-size:.82rem;font-weight:600;border:1px solid var(--border);color:var(--sec);background:#fff;transition:.2s}
    .nav a:hover,.nav a.active{background:var(--green);color:#fff;border-color:var(--green)}
    /* alerts */
    .alert{border-radius:var(--sm-r);padding:.8rem 1rem;margin-bottom:1rem;font-size:.88rem;border-left:4px solid}
    .alert-ok{color:#1b6f4e;border-color:#1b6f4e;background:#f0fbf6}
    .alert-err{color:#9b3a25;border-color:var(--rust);background:#fdf3ef}
    /* stats */
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem}
    .stat{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:1.1rem;box-shadow:var(--sm)}
    .stat-lbl{font-size:.78rem;color:var(--muted);margin-bottom:4px}
    .stat-val{font-size:1.7rem;font-weight:700;color:var(--green)}
    /* tabs */
    .tabs{display:flex;gap:8px;margin-bottom:1.2rem;border-bottom:1px solid var(--border);padding-bottom:.5rem}
    .tab{background:none;border:none;padding:10px 20px;font-weight:600;font-size:.9rem;color:var(--sec);cursor:pointer;border-radius:40px;transition:.2s}
    .tab.active{background:var(--green);color:#fff}
    /* panel */
    .panel{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:1.2rem;overflow-x:auto}
    .bar{display:flex;justify-content:flex-end;margin-bottom:1rem}
    .btn{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:40px;font-weight:600;font-size:.85rem;cursor:pointer;border:1px solid transparent;transition:.2s}
    .btn-primary{background:var(--green);color:#fff;border:none}
    .btn-primary:hover{background:var(--dark)}
    .btn-outline{background:transparent;border-color:var(--border);color:var(--sec)}
    .btn-outline:hover{background:var(--bg)}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;text-align:left;border-bottom:1px solid var(--border);font-size:.85rem;vertical-align:middle}
    th{font-weight:600;color:var(--sec);font-size:.78rem;background:#fcfbf9}
    .prod-thumb{width:48px;height:48px;border-radius:var(--sm-r);object-fit:cover;background:#edece4;display:flex;align-items:center;justify-content:center;color:var(--green)}
    .prod-thumb img{width:48px;height:48px;border-radius:var(--sm-r);object-fit:cover}
    .sbadge{display:inline-block;padding:3px 10px;border-radius:30px;font-size:.7rem;font-weight:600;background:#e8f5f2;color:var(--green)}
    .sbadge.out{background:#fef2e8;color:var(--rust)}
    .act i{margin:0 4px;cursor:pointer;color:var(--muted);transition:.2s}
    .act i:hover{color:var(--rust)}
    /* modal */
    .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:1000}
    .modal.open{display:flex}
    .modal-box{background:#fff;max-width:520px;width:90%;border-radius:var(--lg);padding:2rem;max-height:90vh;overflow-y:auto}
    .modal-box h3{margin-bottom:1.2rem;font-size:1.1rem}
    label{font-size:.82rem;font-weight:600;color:var(--sec)}
    input[type=text],input[type=number],textarea,input[type=file]{width:100%;padding:9px 12px;margin:5px 0 14px;border:1px solid var(--border);border-radius:var(--sm-r);font-size:.88rem;font-family:inherit}
    input[type=file]{padding:6px}
    .thumb-preview{width:80px;height:80px;object-fit:cover;border-radius:var(--sm-r);display:none;margin-bottom:10px}
    .modal-btns{display:flex;gap:10px;justify-content:flex-end;margin-top:.5rem}
    .empty{padding:2rem;text-align:center;color:var(--muted);font-size:.9rem}
    .empty i{font-size:2rem;margin-bottom:.5rem;display:block;opacity:.4}
    /* approval notice */
    .notice{background:#fef9ec;border:1px solid #f0d88a;border-radius:var(--md-r);padding:1rem;margin-bottom:1.2rem;font-size:.88rem;color:#7a5a10}
    @media(max-width:700px){th,td{font-size:.75rem;padding:7px 6px}.stats{grid-template-columns:1fr 1fr}}
  </style>
</head>
<body>
<div class="wrap">
  <header class="hdr">
    <a class="logo" href="shop.php">
      <div class="logo-ico"><i class="fas fa-vest"></i></div>
      <div><span class="logo-txt">Pastimes</span><span class="badge badge-gold"><i class="fas fa-store"></i> Seller Hub</span></div>
    </a>
    <nav class="nav">
      <a href="shop.php">Shop</a>
      <a href="checkout.php"><i class="fas fa-shopping-cart"></i> Cart</a>
      <a href="my_orders.php">My Orders</a>
      <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if ($seller_status !== "approved"): ?>
    <div class="notice"><i class="fas fa-hourglass-half"></i>
      Your seller account is <strong><?php echo htmlspecialchars($seller_status); ?></strong>.
      You can set up your listings, but they will only appear in the shop once an admin approves your account.
    </div>
  <?php endif; ?>

  <?php if ($error_message): ?><div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
  <?php if ($success_message): ?><div class="alert alert-ok"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?></div><?php endif; ?>

  <div class="stats">
    <div class="stat"><div class="stat-lbl">Total Products</div><div class="stat-val"><?php echo count($products); ?></div></div>
    <div class="stat"><div class="stat-lbl">Lifetime Revenue</div><div class="stat-val">$<?php echo number_format($totalRevenue, 2); ?></div></div>
    <div class="stat"><div class="stat-lbl">Orders</div><div class="stat-val"><?php echo $orderCount; ?></div></div>
    <div class="stat"><div class="stat-lbl">Brand</div><div class="stat-val" style="font-size:1rem;padding-top:.4rem"><?php echo htmlspecialchars($seller_brand ?? "—"); ?></div></div>
  </div>

  <div class="tabs">
    <button class="tab active" data-tab="products"><i class="fas fa-boxes"></i> My Products</button>
    <button class="tab" data-tab="orders"><i class="fas fa-receipt"></i> Sales & Orders</button>
  </div>

  <!-- Products panel -->
  <div id="tab-products" class="panel">
    <div class="bar">
      <button class="btn btn-primary" id="btnAdd"><i class="fas fa-plus"></i> Add New Item</button>
    </div>
    <?php if (count($products) === 0): ?>
      <div class="empty"><i class="fas fa-tshirt"></i>No products yet. Add your first listing.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Image</th><th>Product</th><th>Price</th><th>Stock</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p):
          $hasImg = !empty($p["image"]) && $p["image"] !== "placeholder-clothing.jpg"
                    && file_exists(__DIR__ . "/../uploads/" . $p["image"]);
        ?>
        <tr>
          <td>
            <div class="prod-thumb">
              <?php if ($hasImg): ?>
                <img src="../uploads/<?php echo htmlspecialchars($p["image"]); ?>" alt="">
              <?php else: ?>
                <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:#edece4;border-radius:8px;color:var(--green)"><i class="fas fa-tshirt"></i></div>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <strong><?php echo htmlspecialchars($p["name"]); ?></strong><br>
            <small style="color:var(--muted)"><?php echo htmlspecialchars($p["brand"] ?? ""); ?></small>
          </td>
          <td>$<?php echo number_format((float)$p["price"], 2); ?></td>
          <td>
            <span class="sbadge <?php echo (int)$p["stock"] === 0 ? 'out' : ''; ?>">
              <?php echo (int)$p["stock"] > 0 ? (int)$p["stock"] . ' in stock' : 'Out of stock'; ?>
            </span>
          </td>
          <td class="act">
            <i class="fas fa-edit" data-id="<?php echo (int)$p["product_id"]; ?>"
               data-name="<?php echo htmlspecialchars($p["name"], ENT_QUOTES); ?>"
               data-brand="<?php echo htmlspecialchars($p["brand"] ?? "", ENT_QUOTES); ?>"
               data-desc="<?php echo htmlspecialchars($p["description"] ?? "", ENT_QUOTES); ?>"
               data-price="<?php echo (float)$p["price"]; ?>"
               data-stock="<?php echo (int)$p["stock"]; ?>"
               title="Edit"></i>
            <a href="sellers_hub.php?delete=<?php echo (int)$p["product_id"]; ?>"
               onclick="return confirm('Delete this product?')" style="color:var(--muted)">
              <i class="fas fa-trash-alt" title="Delete"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Orders panel -->
  <div id="tab-orders" class="panel" style="display:none">
    <?php if (!$sellerOrders): ?>
      <div class="empty"><i class="fas fa-receipt"></i>No orders yet for your products.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Date</th>
          <th>Items</th>
          <th>Revenue</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sellerOrders as $so): ?>
        <tr>
          <td><strong>ORD-<?php echo str_pad((int)$so["order_id"], 8, "0", STR_PAD_LEFT); ?></strong></td>
          <td><?php echo date("d M Y", strtotime($so["order_date"])); ?></td>
          <td><?php echo (int)$so["total_items"]; ?></td>
          <td style="color:var(--rust);font-weight:700">$<?php echo number_format((float)$so["order_revenue"], 2); ?></td>
          <td>
            <span class="sbadge <?php echo $so["status"] === "delivered" ? "" : "out"; ?>">
              <?php echo ucfirst(htmlspecialchars($so["status"])); ?>
            </span>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- Add/Edit product modal -->
<div id="modal" class="modal">
  <form id="productForm" method="POST" action="sellers_hub.php" enctype="multipart/form-data" class="modal-box">
    <h3 id="modalTitle">Add New Product</h3>
    <input type="hidden" id="fAction" name="action" value="add">
    <input type="hidden" id="fProductId" name="product_id" value="">

    <label>Product Name *</label>
    <input type="text" id="fName" name="product_name" placeholder="e.g. Organic Cotton Tee" required>

    <label>Brand</label>
    <input type="text" id="fBrand" name="brand" value="<?php echo htmlspecialchars($seller_brand ?? ""); ?>">

    <label>Description</label>
    <textarea id="fDesc" name="description" rows="3" placeholder="Describe the item, fabric, fit, condition…"></textarea>

    <label>Price ($) *</label>
    <input type="number" id="fPrice" name="price" step="0.01" min="0.01" placeholder="29.99" required>

    <label>Stock *</label>
    <input type="number" id="fStock" name="stock" min="0" value="10" required>

    <label>Product Image</label>
    <img id="imgPreview" class="thumb-preview" src="" alt="Preview">
    <input type="file" name="product_image" id="fImage" accept="image/jpeg,image/png,image/gif,image/webp">
    <small style="color:var(--muted);font-size:.75rem">JPG, PNG, GIF or WEBP. Leave blank to keep existing image.</small>

    <div class="modal-btns" style="margin-top:1rem">
      <button type="button" class="btn btn-outline" id="btnClose">Cancel</button>
      <button type="submit" class="btn btn-primary">Save Product</button>
    </div>
  </form>
</div>

<script>
  const defaultBrand = <?php echo json_encode($seller_brand ?? ""); ?>;

  /* Tab switching */
  document.querySelectorAll('.tab').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const id = 'tab-' + btn.dataset.tab;
      document.querySelectorAll('.panel').forEach(p => p.style.display = 'none');
      document.getElementById(id).style.display = 'block';
    });
  });

  /* Modal helpers */
  function openModal(title, action, data) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('fAction').value = action;
    document.getElementById('fProductId').value = data.id || '';
    document.getElementById('fName').value = data.name || '';
    document.getElementById('fBrand').value = data.brand || defaultBrand;
    document.getElementById('fDesc').value = data.desc || '';
    document.getElementById('fPrice').value = data.price || '';
    document.getElementById('fStock').value = data.stock ?? 10;
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('fImage').value = '';
    document.getElementById('modal').classList.add('open');
  }
  function closeModal() {
    document.getElementById('modal').classList.remove('open');
  }

  document.getElementById('btnAdd').addEventListener('click', () => openModal('Add New Product', 'add', {}));
  document.getElementById('btnClose').addEventListener('click', closeModal);
  document.getElementById('modal').addEventListener('click', e => { if (e.target === document.getElementById('modal')) closeModal(); });

  /* Edit buttons */
  document.querySelectorAll('.fa-edit').forEach(icon => {
    icon.addEventListener('click', () => {
      openModal('Edit Product', 'edit', {
        id:    icon.dataset.id,
        name:  icon.dataset.name,
        brand: icon.dataset.brand,
        desc:  icon.dataset.desc,
        price: icon.dataset.price,
        stock: icon.dataset.stock,
      });
    });
  });

  /* Image preview before upload */
  document.getElementById('fImage').addEventListener('change', function () {
    const file = this.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        const prev = document.getElementById('imgPreview');
        prev.src = e.target.result;
        prev.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
</script>
</body>
</html>
