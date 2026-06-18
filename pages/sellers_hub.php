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

if (!$seller_id || $seller_status !== "approved") {
    header("Location: seller_register.php");
    exit;
}

if (isset($_GET["msg"]) && $_GET["msg"] === "sent") {
    $success_message = "Message sent to admin.";
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblmessage (
    message_id  INT AUTO_INCREMENT PRIMARY KEY,
    sender_id   INT NOT NULL,
    sender_name VARCHAR(100),
    receiver_id INT NOT NULL,
    message     TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

/* ── UPDATE ORDER STATUS (seller: pending→shipped, shipped→delivered) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["seller_order_action"])) {
    $upd_order_id = (int)($_POST["upd_order_id"] ?? 0);
    $upd_status   = $_POST["upd_status"] ?? "";
    if ($upd_order_id > 0 && in_array($upd_status, ["shipped", "delivered"], true)) {
        /* Confirm this seller has products in the order */
        $owns = mysqli_prepare($conn,
            "SELECT 1 FROM tblorderline ol
             JOIN tblclothes c ON ol.product_id = c.product_id
             WHERE ol.order_id = ? AND c.seller_id = ? LIMIT 1"
        );
        if ($owns) {
            mysqli_stmt_bind_param($owns, "ii", $upd_order_id, $seller_id);
            mysqli_stmt_execute($owns);
            if (mysqli_num_rows(mysqli_stmt_get_result($owns)) > 0) {
                $updO = mysqli_prepare($conn,
                    "UPDATE tblorder SET status = ? WHERE order_id = ? AND status != 'cancelled'"
                );
                if ($updO) {
                    mysqli_stmt_bind_param($updO, "si", $upd_status, $upd_order_id);
                    mysqli_stmt_execute($updO);
                    mysqli_stmt_close($updO);
                    $success_message = "Order #" . str_pad($upd_order_id, 8, "0", STR_PAD_LEFT)
                                     . " marked as " . ucfirst($upd_status) . ".";
                }
            }
            mysqli_stmt_close($owns);
        }
    }
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
                $finfo    = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($_FILES["product_image"]["tmp_name"]);
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

/* ── SEND MESSAGE TO ADMIN ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hub_message"])) {
    $body = trim($_POST["hub_message"] ?? "");
    if ($body !== "") {
        $s = mysqli_prepare($conn,
            "INSERT INTO tblmessage (sender_id, sender_name, receiver_id, message) VALUES (?,?,-1,?)"
        );
        if ($s) {
            mysqli_stmt_bind_param($s, "iss", $user_id, $name, $body);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
        }
    }
    header("Location: sellers_hub.php?tab=messages&msg=sent");
    exit;
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

/* Fetch per-seller line items for each of those orders */
$sellerOrderLines = [];
if ($sellerOrders) {
    $oids  = implode(",", array_map(fn($o) => (int)$o["order_id"], $sellerOrders));
    $slRes = mysqli_query($conn,
        "SELECT ol.order_id, c.name AS product_name, ol.quantity, ol.unit_price
         FROM tblorderline ol
         JOIN tblclothes c ON ol.product_id = c.product_id
         WHERE ol.order_id IN ({$oids}) AND c.seller_id = {$seller_id}
         ORDER BY ol.orderline_id ASC"
    );
    if ($slRes) {
        while ($row = mysqli_fetch_assoc($slRes)) {
            $sellerOrderLines[$row["order_id"]][] = $row;
        }
    }
}

/* Fetch seller–admin message thread */
$sellerMessages = [];
$mStmt = mysqli_prepare($conn,
    "SELECT message_id, sender_id, sender_name, message, is_read, created_at
     FROM tblmessage
     WHERE (sender_id=? AND receiver_id=-1)
        OR (sender_id=-1 AND receiver_id=?)
     ORDER BY created_at ASC"
);
if ($mStmt) {
    mysqli_stmt_bind_param($mStmt, "ii", $user_id, $user_id);
    mysqli_stmt_execute($mStmt);
    $mRes = mysqli_stmt_get_result($mStmt);
    while ($row = mysqli_fetch_assoc($mRes)) $sellerMessages[] = $row;
    mysqli_stmt_close($mStmt);
}

/* Count unread replies from admin */
$unreadAdmin = 0;
$uStmt = mysqli_prepare($conn,
    "SELECT COUNT(*) AS n FROM tblmessage WHERE sender_id=-1 AND receiver_id=? AND is_read=0"
);
if ($uStmt) {
    mysqli_stmt_bind_param($uStmt, "i", $user_id);
    mysqli_stmt_execute($uStmt);
    $uRow = mysqli_fetch_assoc(mysqli_stmt_get_result($uStmt));
    $unreadAdmin = (int)($uRow["n"] ?? 0);
    mysqli_stmt_close($uStmt);
}

/* Mark admin replies to this seller as read */
$markStmt = mysqli_prepare($conn,
    "UPDATE tblmessage SET is_read=1 WHERE sender_id=-1 AND receiver_id=? AND is_read=0"
);
if ($markStmt) {
    mysqli_stmt_bind_param($markStmt, "i", $user_id);
    mysqli_stmt_execute($markStmt);
    mysqli_stmt_close($markStmt);
}

/* Fetch reviews for this seller's products */
$sellerReviews = [];
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblreview (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    product_id  INT NOT NULL,
    user_id     INT NOT NULL,
    rating      TINYINT NOT NULL,
    comment     TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (product_id, user_id)
)");
$rStmt = mysqli_prepare($conn,
    "SELECT r.rating, r.comment, r.created_at,
            c.name AS product_name, u.name AS reviewer_name
     FROM tblreview r
     JOIN tblclothes c ON r.product_id = c.product_id
     JOIN tblUser u ON r.user_id = u.user_id
     WHERE c.seller_id = ?
     ORDER BY r.created_at DESC"
);
if ($rStmt) {
    mysqli_stmt_bind_param($rStmt, "i", $seller_id);
    mysqli_stmt_execute($rStmt);
    $rRes = mysqli_stmt_get_result($rStmt);
    while ($row = mysqli_fetch_assoc($rRes)) $sellerReviews[] = $row;
    mysqli_stmt_close($rStmt);
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
    .sbadge.sbadge-ship{background:#e8f0fb;color:#2a52a4}
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
    /* message thread */
    .thread{min-height:200px;max-height:360px;overflow-y:auto;display:flex;flex-direction:column;gap:.75rem;margin-bottom:1rem;padding:.2rem .4rem}
    .bubble{max-width:80%;padding:.65rem .95rem;border-radius:var(--md-r);font-size:.86rem;line-height:1.5}
    .bubble-me{background:var(--green);color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
    .bubble-admin{background:#f0f8f6;border:1px solid var(--border);color:var(--text);align-self:flex-start;border-bottom-left-radius:4px}
    .bubble-meta{font-size:.66rem;margin-top:3px;opacity:.7}
    .compose-hub textarea{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--sm-r);font-size:.86rem;font-family:inherit;resize:vertical;min-height:80px;margin-bottom:.6rem}
    /* reviews */
    .star-display{color:#d4a259;font-size:.95rem}
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
    <button class="tab" data-tab="messages">
      <i class="fas fa-envelope"></i> Messages
      <?php if ($unreadAdmin > 0): ?>
        <span style="background:var(--rust);color:#fff;border-radius:30px;padding:1px 7px;font-size:.7rem;margin-left:4px"><?php echo $unreadAdmin; ?></span>
      <?php endif; ?>
    </button>
    <button class="tab" data-tab="reviews"><i class="fas fa-star"></i> Reviews</button>
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
          <th>Your Items</th>
          <th>Revenue</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($sellerOrders as $so):
          $oLines     = $sellerOrderLines[$so["order_id"]] ?? [];
          $canShip    = $so["status"] === "pending";
          $canDeliver = $so["status"] === "shipped";
          $isFinal    = in_array($so["status"], ["delivered", "cancelled"], true);
          $badgeCls   = match($so["status"]) {
            "delivered" => "",
            "shipped"   => "sbadge-ship",
            default     => "out",
          };
        ?>
        <tr>
          <td><strong>ORD-<?php echo str_pad((int)$so["order_id"], 8, "0", STR_PAD_LEFT); ?></strong></td>
          <td style="white-space:nowrap"><?php echo date("d M Y", strtotime($so["order_date"])); ?></td>
          <td style="min-width:160px">
            <?php foreach ($oLines as $ol): ?>
              <div style="font-size:.8rem;margin-bottom:2px">
                <?php echo htmlspecialchars($ol["product_name"]); ?>
                <span style="color:var(--muted)">×<?php echo (int)$ol["quantity"]; ?></span>
              </div>
            <?php endforeach; ?>
            <?php if (!$oLines): ?><span style="color:var(--muted)">—</span><?php endif; ?>
          </td>
          <td style="color:var(--rust);font-weight:700;white-space:nowrap">
            $<?php echo number_format((float)$so["order_revenue"], 2); ?>
          </td>
          <td>
            <span class="sbadge <?php echo $badgeCls; ?>">
              <?php echo ucfirst(htmlspecialchars($so["status"])); ?>
            </span>
          </td>
          <td>
            <?php if (!$isFinal): ?>
            <form method="POST" style="display:inline">
              <input type="hidden" name="seller_order_action" value="1">
              <input type="hidden" name="upd_order_id" value="<?php echo (int)$so["order_id"]; ?>">
              <?php if ($canShip): ?>
              <button type="submit" name="upd_status" value="shipped"
                      class="btn btn-primary" style="font-size:.75rem;padding:5px 12px">
                <i class="fas fa-truck"></i> Mark Shipped
              </button>
              <?php elseif ($canDeliver): ?>
              <button type="submit" name="upd_status" value="delivered"
                      class="btn btn-primary" style="font-size:.75rem;padding:5px 12px">
                <i class="fas fa-check-circle"></i> Mark Delivered
              </button>
              <?php endif; ?>
            </form>
            <?php else: ?>
              <span style="color:var(--muted);font-size:.8rem">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- Messages panel -->
  <div id="tab-messages" class="panel" style="display:none">
    <div class="thread" id="msgThread">
      <?php if (!$sellerMessages): ?>
        <div style="color:var(--muted);text-align:center;padding:2rem 0;font-size:.88rem">
          <i class="fas fa-comment-dots" style="display:block;font-size:2rem;margin-bottom:.5rem;opacity:.3"></i>
          No messages yet. Use the form below to contact the admin.
        </div>
      <?php else: ?>
        <?php foreach ($sellerMessages as $m):
          $isMe = (int)$m["sender_id"] === (int)$user_id;
        ?>
          <div class="bubble <?php echo $isMe ? 'bubble-me' : 'bubble-admin'; ?>">
            <?php echo nl2br(htmlspecialchars($m["message"])); ?>
            <div class="bubble-meta">
              <?php echo $isMe ? 'You' : 'Admin'; ?> &middot;
              <?php echo date("d M, g:i a", strtotime($m["created_at"])); ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="compose-hub">
      <form method="POST" action="sellers_hub.php">
        <textarea name="hub_message" placeholder="Questions about your listings, payout queries, platform issues — the admin team will reply here." required></textarea>
        <button type="submit" class="btn btn-primary" style="font-size:.85rem"><i class="fas fa-paper-plane"></i> Send Message</button>
      </form>
    </div>
  </div>

  <!-- Reviews panel -->
  <div id="tab-reviews" class="panel" style="display:none">
    <?php if (!$sellerReviews): ?>
      <div class="empty"><i class="fas fa-star"></i>No reviews yet — they appear here once customers rate your delivered products.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>Product</th><th>Reviewer</th><th>Rating</th><th>Comment</th><th>Date</th></tr>
      </thead>
      <tbody>
        <?php foreach ($sellerReviews as $rv): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($rv["product_name"]); ?></strong></td>
          <td style="font-size:.83rem"><?php echo htmlspecialchars($rv["reviewer_name"]); ?></td>
          <td>
            <span class="star-display">
              <?php for ($i = 1; $i <= 5; $i++) echo $i <= (int)$rv["rating"] ? '&#9733;' : '&#9734;'; ?>
            </span>
            <span style="color:var(--muted);font-size:.75rem;margin-left:3px"><?php echo (int)$rv["rating"]; ?>/5</span>
          </td>
          <td style="max-width:220px;font-size:.83rem;color:var(--sec)"><?php echo nl2br(htmlspecialchars($rv["comment"] ?? "")); ?></td>
          <td style="white-space:nowrap;font-size:.79rem;color:var(--muted)"><?php echo date("d M Y", strtotime($rv["created_at"])); ?></td>
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
      if (btn.dataset.tab === 'messages') {
        setTimeout(() => {
          const t = document.getElementById('msgThread');
          if (t) t.scrollTop = t.scrollHeight;
        }, 30);
      }
    });
  });

  /* Auto-switch to the tab named in ?tab= (e.g. after PRG redirect from message send) */
  const urlTab = new URLSearchParams(window.location.search).get('tab');
  if (urlTab) {
    const target = document.querySelector('[data-tab="' + urlTab + '"]');
    if (target) target.click();
  }

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
<?php require_once __DIR__ . '/../config/tab_guard.php'; ?>
</body>
</html>
