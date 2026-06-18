<?php
/* Product detail page – loads a single product from the database (Phase 12) */
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id = (int)$_SESSION["user_id"];
$role    = $_SESSION["role"] ?? "user";

$product_id = (int)($_GET["id"] ?? 0);

/* Fetch the requested product and its seller info */
$product = null;
if ($product_id > 0) {
    $pStmt = mysqli_prepare($conn,
        "SELECT p.product_id, p.name, p.brand, p.description, p.price, p.stock, p.image,
                COALESCE(s.brand_name, 'Pastimes') AS seller_name
         FROM tblclothes p
         LEFT JOIN tblseller s ON p.seller_id = s.seller_id
         WHERE p.product_id = ?
         LIMIT 1"
    );
    if ($pStmt) {
        mysqli_stmt_bind_param($pStmt, "i", $product_id);
        mysqli_stmt_execute($pStmt);
        $product = mysqli_fetch_assoc(mysqli_stmt_get_result($pStmt));
        mysqli_stmt_close($pStmt);
    }
}

/* Redirect to shop if product not found */
if (!$product) {
    header("Location: shop.php");
    exit;
}

/* Fetch up to 4 related products (other items, newest first) */
$related = [];
$rStmt = mysqli_prepare($conn,
    "SELECT product_id, name, price, image FROM tblclothes WHERE product_id <> ? AND stock > 0 ORDER BY created_at DESC LIMIT 4"
);
if ($rStmt) {
    mysqli_stmt_bind_param($rStmt, "i", $product_id);
    mysqli_stmt_execute($rStmt);
    $rResult = mysqli_stmt_get_result($rStmt);
    while ($row = mysqli_fetch_assoc($rResult)) $related[] = $row;
    mysqli_stmt_close($rStmt);
}

/* Cart item count for the navigation badge */
$cartCount = 0;
$cStmt = mysqli_prepare($conn, "SELECT COALESCE(SUM(quantity),0) AS n FROM tblcart WHERE user_id=?");
if ($cStmt) {
    mysqli_stmt_bind_param($cStmt, "i", $user_id);
    mysqli_stmt_execute($cStmt);
    $cRow = mysqli_fetch_assoc(mysqli_stmt_get_result($cStmt));
    $cartCount = (int)($cRow["n"] ?? 0);
    mysqli_stmt_close($cStmt);
}

/* Reviews: create table if needed, then load reviews + check eligibility */
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tblreview (
    review_id  INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id    INT NOT NULL,
    rating     TINYINT NOT NULL,
    comment    TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY one_per_user (product_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$reviews     = [];
$avgRating   = 0;
$canReview   = false;
$hasReviewed = false;

$rvStmt = mysqli_prepare($conn,
    "SELECT r.rating, r.comment, r.created_at, u.name AS reviewer_name
     FROM tblreview r JOIN tblUser u ON r.user_id = u.user_id
     WHERE r.product_id = ? ORDER BY r.created_at DESC"
);
if ($rvStmt) {
    mysqli_stmt_bind_param($rvStmt, "i", $product_id);
    mysqli_stmt_execute($rvStmt);
    $rvResult = mysqli_stmt_get_result($rvStmt);
    while ($row = mysqli_fetch_assoc($rvResult)) $reviews[] = $row;
    mysqli_stmt_close($rvStmt);
    if ($reviews) {
        $avgRating = round(array_sum(array_column($reviews, "rating")) / count($reviews), 1);
    }
}

$eligStmt = mysqli_prepare($conn,
    "SELECT 1 FROM tblorder o
     JOIN tblorderline ol ON o.order_id = ol.order_id
     WHERE o.user_id = ? AND ol.product_id = ? AND o.status = 'delivered'
     LIMIT 1"
);
if ($eligStmt) {
    mysqli_stmt_bind_param($eligStmt, "ii", $user_id, $product_id);
    mysqli_stmt_execute($eligStmt);
    $canReview = mysqli_num_rows(mysqli_stmt_get_result($eligStmt)) > 0;
    mysqli_stmt_close($eligStmt);
}
if ($canReview) {
    $rvChk = mysqli_prepare($conn,
        "SELECT 1 FROM tblreview WHERE product_id = ? AND user_id = ? LIMIT 1"
    );
    if ($rvChk) {
        mysqli_stmt_bind_param($rvChk, "ii", $product_id, $user_id);
        mysqli_stmt_execute($rvChk);
        $hasReviewed = mysqli_num_rows(mysqli_stmt_get_result($rvChk)) > 0;
        mysqli_stmt_close($rvChk);
    }
}

$reviewFlash = isset($_GET["reviewed"]) ? "ok"
             : (isset($_GET["err"]) && $_GET["err"] === "not_eligible" ? "err" : "");

mysqli_close($conn);

$hasImg = !empty($product["image"]) && $product["image"] !== "placeholder-clothing.jpg"
          && file_exists(__DIR__ . "/../uploads/" . $product["image"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · <?php echo htmlspecialchars($product["name"]); ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --sand:#f6efe5;--paper:#fffaf4;--ink:#1f1a17;--muted:#6e635a;
      --rust:#bf5a36;--forest:#1f5a4d;--gold:#d7a85f;
      --line:rgba(31,26,23,.12);--shadow:0 18px 45px rgba(31,26,23,.12);
      --lg:28px;--md:18px;--sm:12px
    }
    body{font-family:system-ui,'Segoe UI',-apple-system,sans-serif;color:var(--ink);background:linear-gradient(180deg,#fcf7f0 0%,#f5ede2 100%);min-height:100vh;line-height:1.5}
    .wrap{max-width:1200px;margin:0 auto;padding:0 1rem 2rem}
    /* nav */
    .top-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;padding:.8rem 1.2rem;background:rgba(255,250,244,.92);border:1px solid rgba(255,255,255,.8);border-radius:60px;backdrop-filter:blur(12px);box-shadow:0 6px 18px rgba(31,26,23,.05);margin-bottom:1.8rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:42px;height:42px;border-radius:30px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--forest),#2f7968);color:#fff;font-size:1.4rem}
    .logo-text{font-weight:800;font-size:1.5rem;letter-spacing:-.02em;color:var(--forest)}
    .nav-actions{display:flex;align-items:center;gap:8px}
    .nav-btn{background:rgba(255,250,244,.8);border:1px solid var(--line);padding:8px 14px;border-radius:50px;font-size:.8rem;font-weight:600;color:var(--ink);cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:.15s;position:relative}
    .nav-btn:hover{background:var(--sand)}
    .cart-count{position:absolute;top:-6px;right:-6px;background:var(--rust);color:#fff;font-size:.62rem;font-weight:700;padding:2px 5px;border-radius:30px;border:2px solid var(--paper);min-width:18px;text-align:center}
    /* breadcrumb */
    .breadcrumb{margin-bottom:1.5rem;font-size:.85rem;color:var(--muted);display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .breadcrumb a{color:var(--forest);text-decoration:none;font-weight:500}
    .back-btn{background:var(--paper);padding:6px 16px;border-radius:40px;border:1px solid var(--line);font-size:.8rem;font-weight:600;display:inline-flex;align-items:center;gap:6px;text-decoration:none;color:var(--ink)}
    /* product layout */
    .product-box{background:var(--paper);border-radius:var(--lg);box-shadow:var(--shadow);overflow:hidden;margin-bottom:2.5rem;border:1px solid rgba(255,255,240,.8)}
    .product-grid{display:grid;grid-template-columns:1fr 1fr}
    .gallery{background:linear-gradient(145deg,#e9dfd1,#ddd2c2);padding:2rem;display:flex;align-items:center;justify-content:center;min-height:380px;border-right:1px solid var(--line)}
    .gallery img{max-width:100%;max-height:340px;object-fit:contain;border-radius:var(--md)}
    .gallery-icon{font-size:8rem;color:var(--forest)}
    .info{padding:2rem}
    .brand{font-size:.82rem;text-transform:uppercase;letter-spacing:1.5px;color:var(--rust);font-weight:600;margin-bottom:.4rem}
    h1{font-size:1.9rem;font-weight:800;line-height:1.2;margin-bottom:.6rem}
    .price{font-size:2rem;font-weight:800;color:var(--rust);margin:1rem 0}
    .desc{color:var(--muted);line-height:1.6;border-top:1px solid var(--line);padding-top:1rem;margin-top:.5rem}
    .stock-info{font-size:.8rem;color:var(--muted);margin:.5rem 0}
    .qty-row{display:flex;align-items:center;gap:14px;margin:1.2rem 0}
    .qty-btn{width:38px;height:38px;border-radius:50px;background:var(--paper);border:1.5px solid var(--line);font-size:1.2rem;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center}
    .qty-val{font-size:1.1rem;font-weight:700;min-width:36px;text-align:center}
    .btn-add{background:var(--forest);color:#fff;border:none;padding:15px 22px;border-radius:60px;font-weight:800;font-size:1.05rem;display:flex;align-items:center;justify-content:center;gap:10px;width:100%;cursor:pointer;margin:.5rem 0 1rem;transition:.2s}
    .btn-add:hover{background:#134b3f}
    .seller-card{background:rgba(215,168,95,.08);border-radius:var(--md);padding:1rem;margin:1rem 0;display:flex;align-items:center;gap:12px}
    .seller-av{width:40px;height:40px;background:rgba(31,90,77,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--forest)}
    /* related */
    .related-title{font-size:1.4rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:8px}
    .related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px}
    .related-card{background:var(--paper);border-radius:var(--md);padding:12px;border:1px solid var(--line);cursor:pointer;transition:.15s;text-decoration:none;color:var(--ink);display:block}
    .related-card:hover{box-shadow:0 6px 18px rgba(31,26,23,.1);transform:translateY(-2px)}
    .related-img{background:#e9dfd1;height:110px;border-radius:var(--sm);display:flex;align-items:center;justify-content:center;color:var(--forest);margin-bottom:8px;overflow:hidden}
    .related-img img{width:100%;height:100%;object-fit:cover}
    .related-name{font-weight:700;font-size:.88rem;margin-bottom:2px}
    .related-price{color:var(--rust);font-weight:700;font-size:.88rem}
    /* toast */
    .toast{position:fixed;bottom:30px;right:30px;background:var(--forest);color:#fff;padding:12px 22px;border-radius:50px;font-weight:600;z-index:1000;box-shadow:0 8px 20px rgba(0,0,0,.2);animation:fadeUp .2s ease-out;font-size:.88rem}
    .toast.err{background:var(--rust)}
    @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    /* inline rating on product card */
    .rating-inline{display:flex;align-items:center;gap:5px;margin-bottom:.8rem;font-size:.85rem;color:var(--muted)}
    .rating-inline i{font-size:.85rem;color:#ddd}.rating-inline i.filled{color:var(--gold)}
    /* reviews section */
    .reviews-section{margin-top:2.5rem}
    .reviews-title{font-size:1.3rem;font-weight:700;display:flex;align-items:center;gap:10px;margin-bottom:1.2rem;flex-wrap:wrap}
    .rating-avg-chip{font-size:.95rem;font-weight:800;color:var(--gold);background:rgba(215,168,95,.12);padding:3px 12px;border-radius:30px}
    .rating-count{font-size:.85rem;color:var(--muted);font-weight:400}
    .review-note{font-size:.85rem;color:var(--muted);padding:.75rem 1rem;background:var(--sand);border-radius:var(--sm);margin-bottom:1.2rem;display:flex;align-items:center;gap:7px}
    .review-flash{border-radius:var(--sm);padding:.75rem 1rem;font-size:.85rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:7px}
    .review-flash.ok{background:#e8f7ef;color:#1b6f4e;border-left:3px solid #1b6f4e}
    .review-flash.err{background:#fdecea;color:#9b2a2a;border-left:3px solid #9b2a2a}
    .review-form-card{background:var(--paper);border:1px solid var(--line);border-radius:var(--md);padding:1.4rem;margin-bottom:1.6rem;box-shadow:0 4px 12px rgba(31,26,23,.04)}
    .review-form-card h3{font-size:1rem;margin-bottom:1rem;color:var(--ink)}
    /* star picker: DOM order 5→1, row-reverse flips to 1→5 visually */
    .star-picker{display:flex;flex-direction:row-reverse;justify-content:flex-start;gap:4px;margin-bottom:.9rem}
    .star-picker input{display:none}
    .star-picker label{font-size:1.9rem;color:#ddd;cursor:pointer;transition:.12s;line-height:1}
    .star-picker input:checked ~ label,
    .star-picker label:hover,
    .star-picker label:hover ~ label{color:var(--gold)}
    .review-form-card textarea{width:100%;padding:10px 12px;border:1px solid var(--line);border-radius:var(--sm);font-family:inherit;font-size:.88rem;resize:vertical;margin-bottom:1rem;background:#fffdf9;color:var(--ink)}
    .reviews-list{display:flex;flex-direction:column;gap:.9rem}
    .review-card{background:var(--paper);border:1px solid var(--line);border-radius:var(--md);padding:1.1rem}
    .review-header{display:flex;align-items:center;gap:.75rem;margin-bottom:.6rem;flex-wrap:wrap}
    .reviewer-av{width:36px;height:36px;background:rgba(31,90,77,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--forest);font-size:.9rem;flex-shrink:0}
    .review-stars{display:flex;gap:2px;margin-top:3px}
    .review-stars i{font-size:.75rem;color:#ddd}.review-stars i.filled{color:var(--gold)}
    .review-date{margin-left:auto;font-size:.75rem;color:var(--muted)}
    .review-comment{font-size:.88rem;color:var(--muted);line-height:1.6}
    .review-empty{color:var(--muted);font-size:.88rem;font-style:italic;padding:.5rem 0}
    @media(max-width:760px){.product-grid{grid-template-columns:1fr}.gallery{border-right:none;border-bottom:1px solid var(--line);min-height:240px}h1{font-size:1.5rem}}
  </style>
</head>
<body>
<div class="wrap">
  <header class="top-bar">
    <a class="logo-area" href="shop.php">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div><span class="logo-text">Pastimes</span></div>
    </a>
    <div class="nav-actions">
      <a class="nav-btn" href="shop.php"><i class="fas fa-store"></i> Shop</a>
      <a class="nav-btn" href="my_orders.php"><i class="fas fa-receipt"></i> Orders</a>
      <a class="nav-btn" href="messages.php"><i class="fas fa-envelope"></i></a>
      <a class="nav-btn" href="checkout.php">
        <i class="fas fa-shopping-bag"></i>
        <?php if ($cartCount > 0): ?><span class="cart-count"><?php echo $cartCount; ?></span><?php endif; ?>
      </a>
      <?php if ($role === "admin"): ?><a class="nav-btn" href="admin.php"><i class="fas fa-shield-alt"></i></a><?php endif; ?>
      <a class="nav-btn" href="logout.php"><i class="fas fa-sign-out-alt"></i></a>
    </div>
  </header>

  <div class="breadcrumb">
    <a href="shop.php"><i class="fas fa-home"></i> Shop</a>
    <span>/</span>
    <span style="color:var(--rust);font-weight:500"><?php echo htmlspecialchars($product["name"]); ?></span>
    <div style="margin-left:auto">
      <a href="shop.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to shop</a>
    </div>
  </div>

  <div class="product-box">
    <div class="product-grid">
      <!-- Product image -->
      <div class="gallery">
        <?php if ($hasImg): ?>
          <img src="../uploads/<?php echo htmlspecialchars($product["image"]); ?>"
               alt="<?php echo htmlspecialchars($product["name"]); ?>">
        <?php else: ?>
          <div class="gallery-icon"><i class="fas fa-tshirt"></i></div>
        <?php endif; ?>
      </div>

      <!-- Product info -->
      <div class="info">
        <div class="brand"><?php echo htmlspecialchars($product["brand"] ?? "Pastimes"); ?></div>
        <h1><?php echo htmlspecialchars($product["name"]); ?></h1>
        <div class="price">$<?php echo number_format((float)$product["price"], 2); ?></div>
        <?php if ($avgRating > 0): ?>
        <div class="rating-inline">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <i class="fas fa-star <?php echo $s <= round($avgRating) ? 'filled' : ''; ?>"></i>
          <?php endfor; ?>
          <span><?php echo $avgRating; ?> &middot; <?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?></span>
        </div>
        <?php endif; ?>
        <div class="stock-info">
          <i class="fas fa-box"></i>
          <?php echo (int)$product["stock"]; ?> in stock
        </div>

        <div class="qty-row">
          <button class="qty-btn" id="btnMinus">−</button>
          <span class="qty-val" id="qtyVal">1</span>
          <button class="qty-btn" id="btnPlus">+</button>
          <span style="font-size:.82rem;color:var(--muted)">max <?php echo (int)$product["stock"]; ?></span>
        </div>

        <button class="btn-add" id="btnAddCart">
          <i class="fas fa-cart-plus"></i>
          Add to Cart — $<?php echo number_format((float)$product["price"], 2); ?>
        </button>

        <div class="desc"><?php echo nl2br(htmlspecialchars($product["description"] ?? "")); ?></div>

        <div class="seller-card">
          <div class="seller-av"><?php echo strtoupper(substr($product["seller_name"], 0, 2)); ?></div>
          <div>
            <strong><?php echo htmlspecialchars($product["seller_name"]); ?></strong><br>
            <span style="font-size:.75rem;color:var(--muted)">Verified seller</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($related): ?>
  <div class="related-title"><i class="fas fa-tag"></i> You might also like</div>
  <div class="related-grid">
    <?php foreach ($related as $r):
      $rHasImg = !empty($r["image"]) && $r["image"] !== "placeholder-clothing.jpg"
                 && file_exists(__DIR__ . "/../uploads/" . $r["image"]);
    ?>
    <a class="related-card" href="product.php?id=<?php echo (int)$r["product_id"]; ?>">
      <div class="related-img">
        <?php if ($rHasImg): ?>
          <img src="../uploads/<?php echo htmlspecialchars($r["image"]); ?>" alt="">
        <?php else: ?>
          <i class="fas fa-tshirt" style="font-size:2rem"></i>
        <?php endif; ?>
      </div>
      <div class="related-name"><?php echo htmlspecialchars($r["name"]); ?></div>
      <div class="related-price">$<?php echo number_format((float)$r["price"], 2); ?></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Customer Reviews -->
  <section class="reviews-section">
    <h2 class="reviews-title">
      <i class="fas fa-star" style="color:var(--gold)"></i> Customer Reviews
      <?php if ($reviews): ?>
        <span class="rating-avg-chip"><?php echo $avgRating; ?> / 5</span>
        <span class="rating-count"><?php echo count($reviews); ?> review<?php echo count($reviews) !== 1 ? 's' : ''; ?></span>
      <?php endif; ?>
    </h2>

    <?php if ($reviewFlash === "ok"): ?>
      <div class="review-flash ok"><i class="fas fa-check-circle"></i> Review submitted — thank you!</div>
    <?php elseif ($reviewFlash === "err"): ?>
      <div class="review-flash err"><i class="fas fa-exclamation-circle"></i> You must purchase and receive this item before reviewing.</div>
    <?php endif; ?>

    <?php if ($canReview && !$hasReviewed): ?>
    <div class="review-form-card">
      <h3><i class="fas fa-pen" style="color:var(--forest)"></i> Write a Review</h3>
      <form method="POST" action="submit_review.php">
        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
        <!-- Star picker: DOM 5→1, flex row-reverse makes it display 1→5 -->
        <div class="star-picker">
          <?php for ($s = 5; $s >= 1; $s--): ?>
            <input type="radio" name="rating" value="<?php echo $s; ?>" id="star<?php echo $s; ?>" <?php echo $s === 5 ? 'required' : ''; ?>>
            <label for="star<?php echo $s; ?>"><i class="fas fa-star"></i></label>
          <?php endfor; ?>
        </div>
        <textarea name="comment" rows="3" placeholder="Share your thoughts about fit, quality, or anything else…"></textarea>
        <button type="submit" class="btn-add" style="width:auto;padding:11px 26px;font-size:.9rem;margin:0">
          <i class="fas fa-paper-plane"></i> Submit Review
        </button>
      </form>
    </div>
    <?php elseif ($canReview && $hasReviewed): ?>
      <p class="review-note"><i class="fas fa-check-circle" style="color:var(--forest)"></i> You have already reviewed this product.</p>
    <?php else: ?>
      <p class="review-note"><i class="fas fa-shopping-bag"></i> Purchase and receive this item to leave a review.</p>
    <?php endif; ?>

    <?php if (!$reviews): ?>
      <p class="review-empty">No reviews yet — be the first!</p>
    <?php else: ?>
    <div class="reviews-list">
      <?php foreach ($reviews as $rev): ?>
      <div class="review-card">
        <div class="review-header">
          <div class="reviewer-av"><?php echo htmlspecialchars(strtoupper(mb_substr($rev["reviewer_name"], 0, 1))); ?></div>
          <div>
            <strong style="font-size:.9rem"><?php echo htmlspecialchars($rev["reviewer_name"]); ?></strong>
            <div class="review-stars">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="fas fa-star <?php echo $s <= (int)$rev["rating"] ? 'filled' : ''; ?>"></i>
              <?php endfor; ?>
            </div>
          </div>
          <span class="review-date"><?php echo date("d M Y", strtotime($rev["created_at"])); ?></span>
        </div>
        <?php if (!empty($rev["comment"])): ?>
          <p class="review-comment"><?php echo nl2br(htmlspecialchars($rev["comment"])); ?></p>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</div>

<script>
  const maxStock = <?php echo (int)$product["stock"]; ?>;
  const price    = <?php echo (float)$product["price"]; ?>;
  const pid      = <?php echo (int)$product["product_id"]; ?>;
  let qty = 1;

  const qtyEl  = document.getElementById('qtyVal');
  const addBtn = document.getElementById('btnAddCart');

  function updateQtyDisplay() {
    qtyEl.textContent = qty;
    addBtn.innerHTML = `<i class="fas fa-cart-plus"></i> Add to Cart — $${(price * qty).toFixed(2)}`;
  }

  document.getElementById('btnMinus').addEventListener('click', () => {
    if (qty > 1) { qty--; updateQtyDisplay(); }
  });
  document.getElementById('btnPlus').addEventListener('click', () => {
    if (qty < maxStock) { qty++; updateQtyDisplay(); }
  });

  /* Add to cart via AJAX */
  addBtn.addEventListener('click', async () => {
    try {
      const res = await fetch('cart_add.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'product_id=' + pid + '&quantity=' + qty
      });
      const data = await res.json();
      if (data.success) {
        showToast('Added ' + qty + ' × <?php echo addslashes(htmlspecialchars($product["name"])); ?> to cart');
        /* Update cart badge */
        const badge = document.querySelector('.cart-count');
        if (badge && data.cart_count != null) badge.textContent = data.cart_count;
      } else {
        showToast(data.message || 'Could not add to cart', 'err');
      }
    } catch {
      showToast('Error adding to cart', 'err');
    }
  });

  function showToast(msg, type = '') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();
    const t = document.createElement('div');
    t.className = 'toast' + (type ? ' ' + type : '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2500);
  }
</script>
</body>
</html>
