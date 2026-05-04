<?php
session_start();

if (!isset($_SESSION["user_id"])) {
  header("Location: login.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes · Product Details | Local Clothing Marketplace</title>
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
      font-family: system-ui, "Segoe UI", "Trebuchet MS", -apple-system, BlinkMacSystemFont, sans-serif;
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

    /* header */
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

    .badge-multi {
      background: rgba(31, 90, 77, 0.12);
      color: var(--forest);
      font-size: 0.7rem;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 30px;
      margin-left: 6px;
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
      position: relative;
    }

    .cart-badge {
      position: relative;
    }

    .cart-count {
      position: absolute;
      top: -6px;
      right: -6px;
      background: var(--rust);
      color: white;
      font-size: 0.65rem;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 30px;
      border: 2px solid white;
      min-width: 20px;
      text-align: center;
    }

    /* breadcrumb & back */
    .breadcrumb {
      margin-bottom: 1.8rem;
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      font-size: 0.85rem;
    }
    .breadcrumb a {
      color: var(--forest);
      text-decoration: none;
      font-weight: 500;
    }
    .back-link {
      background: var(--paper);
      padding: 6px 16px;
      border-radius: 40px;
      border: 1px solid var(--line);
      font-size: 0.8rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
    }

    /* PRODUCT DETAILS PAGE LAYOUT */
    .product-detail-container {
      background: var(--paper);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow);
      overflow: hidden;
      margin-bottom: 2.5rem;
      border: 1px solid rgba(255,255,240,0.8);
    }
    .product-detail-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
    }
    /* image gallery */
    .product-gallery {
      background: linear-gradient(145deg, #e9dfd1, #ddd2c2);
      padding: 2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 420px;
      border-right: 1px solid var(--line);
    }
    .main-image {
      font-size: 10rem;
      color: var(--forest);
      text-align: center;
      transition: transform 0.2s;
    }
    /* product info area */
    .product-info-panel {
      padding: 2rem 2rem 2rem 2rem;
    }
    .product-brand {
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: var(--rust);
      font-weight: 600;
      margin-bottom: 0.5rem;
    }
    .product-title-detail {
      font-size: 2rem;
      font-weight: 800;
      line-height: 1.2;
      margin-bottom: 0.75rem;
      color: var(--ink);
    }
    .rating-summary {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 1rem;
      flex-wrap: wrap;
    }
    .stars {
      color: var(--gold);
      letter-spacing: 2px;
      font-size: 0.9rem;
    }
    .review-count {
      color: var(--muted);
      font-size: 0.8rem;
    }
    .price-detail {
      font-size: 2.2rem;
      font-weight: 800;
      color: var(--rust);
      margin: 1rem 0 1rem;
    }
    .product-description {
      color: var(--muted);
      line-height: 1.6;
      margin: 1rem 0 1.5rem;
      border-top: 1px solid var(--line);
      padding-top: 1.2rem;
    }
    .size-selector {
      margin: 1.5rem 0;
    }
    .size-label {
      font-weight: 700;
      margin-bottom: 0.5rem;
      display: block;
    }
    .size-options {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .size-btn {
      background: var(--paper);
      border: 1.5px solid var(--line);
      padding: 8px 18px;
      border-radius: 40px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.1s;
    }
    .size-btn.selected {
      background: var(--forest);
      border-color: var(--forest);
      color: white;
    }
    .quantity-selector {
      display: flex;
      align-items: center;
      gap: 16px;
      margin: 1.5rem 0;
    }
    .qty-btn {
      width: 40px;
      height: 40px;
      border-radius: 50px;
      background: var(--paper);
      border: 1.5px solid var(--line);
      font-size: 1.2rem;
      font-weight: bold;
      cursor: pointer;
    }
    .qty-value {
      font-size: 1.2rem;
      font-weight: 700;
      min-width: 40px;
      text-align: center;
    }
    .add-to-cart-main {
      background: var(--forest);
      color: white;
      border: none;
      padding: 16px 24px;
      border-radius: 60px;
      font-weight: 800;
      font-size: 1.1rem;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      width: 100%;
      cursor: pointer;
      margin: 0.5rem 0 1rem;
      transition: background 0.2s;
    }
    .add-to-cart-main:hover {
      background: #134b3f;
    }
    .seller-info-card {
      background: rgba(215, 168, 95, 0.08);
      border-radius: var(--radius-md);
      padding: 1rem;
      margin: 1.2rem 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .wishlist-detail-btn {
      background: transparent;
      border: 1.5px solid var(--line);
      padding: 12px;
      border-radius: 60px;
      font-weight: 600;
      width: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      cursor: pointer;
    }
    /* cart toast */
    .cart-toast {
      position: fixed;
      bottom: 30px;
      right: 30px;
      background: var(--forest);
      color: white;
      padding: 12px 24px;
      border-radius: 50px;
      font-weight: 600;
      z-index: 1000;
      box-shadow: 0 8px 20px rgba(0,0,0,0.2);
      animation: fadeUp 0.2s ease-out;
      font-size: 0.9rem;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px);}
      to { opacity: 1; transform: translateY(0);}
    }
    /* related products */
    .related-section {
      margin-top: 2rem;
    }
    .related-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 1.2rem;
    }
    .related-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      gap: 20px;
    }
    .related-card {
      background: var(--paper);
      border-radius: var(--radius-md);
      padding: 12px;
      border: 1px solid var(--line);
      cursor: pointer;
      transition: all 0.15s;
    }
    .related-img {
      background: #e9dfd1;
      height: 120px;
      border-radius: var(--radius-sm);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: var(--forest);
      margin-bottom: 8px;
    }
    .related-name {
      font-weight: 700;
    }
    .related-price {
      color: var(--rust);
      font-weight: 700;
    }

    @media (max-width: 780px) {
      .product-detail-grid {
        grid-template-columns: 1fr;
      }
      .product-gallery {
        border-right: none;
        border-bottom: 1px solid var(--line);
        min-height: 280px;
      }
      .product-title-detail {
        font-size: 1.6rem;
      }
    }
    @media (max-width: 550px) {
      .app-wrapper {
        padding: 0 0.8rem 1.5rem;
      }
      .top-bar {
        padding: 0.6rem 1rem;
      }
    }
    .mockup-note {
      margin-top: 2rem;
      padding: 12px 20px;
      background: rgba(255, 250, 244, 0.8);
      border-radius: 60px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 18px;
      font-size: 0.75rem;
      color: var(--forest);
    }
  </style>
</head>
<body>
<div class="app-wrapper">
  <!-- header -->
  <header class="top-bar">
    <a class="logo-area" href="shop.php" style="text-decoration:none;">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div><span class="logo-text">Pastimes</span><span class="badge-multi"><i class="fas fa-store"></i> local fashion</span></div>
    </a>
    <div class="nav-actions">
      <button class="nav-icon-btn" id="wishlistIconBtn"><i class="far fa-heart"></i></button>
      <div class="cart-badge" id="cartIconWrapper">
        <button class="nav-icon-btn" id="cartIconBtn"><i class="fas fa-shopping-bag"></i></button>
        <span class="cart-count" id="cartCountBadge">0</span>
      </div>
      <button class="nav-icon-btn" id="accountBtn"><i class="far fa-user-circle"></i></button>
    </div>
  </header>

  <!-- breadcrumb & back to home -->
  <div class="breadcrumb">
    <a href="shop.php" id="backToHomeLink"><i class="fas fa-home"></i> Home</a> 
    <span>/</span> 
    <span style="color: var(--rust); font-weight: 500;">Product Details</span>
    <div style="margin-left: auto;"><a href="shop.php" class="back-link" id="backLinkBtn"><i class="fas fa-arrow-left"></i> Back to marketplace</a></div>
  </div>

  <!-- DYNAMIC PRODUCT DETAIL SECTION -->
  <div id="productDetailRoot"></div>

  <!-- related products section -->
  <div class="related-section">
    <div class="related-title"><i class="fas fa-tag"></i> You might also like</div>
    <div class="related-grid" id="relatedProductsGrid"></div>
  </div>

  <div class="mockup-note">
    <span><i class="fas fa-tshirt"></i> Ethically sourced</span>
    <span><i class="fas fa-truck"></i> Fast local delivery</span>
    <span><i class="fas fa-star"></i> Verified reviews</span>
  </div>
</div>

<script>
  // ---------- PRODUCT DATABASE ----------
  const productsCatalog = [
    { id: 1, name: "Organic Cotton Tee", brand: "Heritage Basics", price: 24.90, rating: 4.8, reviews: 204, description: "100% organic cotton, pre-shrunk, soft touch and sustainable fabric. Perfect everyday essential.", category: "men", imageIcon: "fa-tshirt", seller: "Heritage Basics", sellerAvatar: "HB", sizes: ["S","M","L","XL"] },
    { id: 2, name: "Linen Blend Shirt", brand: "Thread & Oak", price: 49.50, rating: 4.2, reviews: 187, description: "Breathable linen-cotton blend, relaxed fit, ideal for warm days.", category: "women", imageIcon: "fa-vest", seller: "Thread & Oak", sellerAvatar: "TO", sizes: ["XS","S","M","L"] },
    { id: 3, name: "Vintage Denim Jacket", brand: "Urban Rewind", price: 79.00, rating: 5.0, reviews: 96, description: "Classic blue denim jacket, slight worn-in look, durable cotton.", category: "unisex", imageIcon: "fa-user-tie", seller: "Urban Rewind", sellerAvatar: "UR", sizes: ["S","M","L","XL","XXL"] },
    { id: 4, name: "Handwoven Scarf", brand: "Wool&Weave", price: 34.99, rating: 4.3, reviews: 112, description: "Artisan wool scarf, fair trade, soft and warm.", category: "accessories", imageIcon: "fa-scarf", seller: "Wool&Weave", sellerAvatar: "WW", sizes: ["One size"] },
    { id: 5, name: "French Terry Joggers", brand: "Cozy Nest", price: 42.00, rating: 4.7, reviews: 203, description: "Eco-friendly french terry, tapered ankles, pockets.", category: "men", imageIcon: "fa-shoe-prints", seller: "Cozy Nest", sellerAvatar: "CN", sizes: ["S","M","L","XL"] },
    { id: 6, name: "Full-Grain Belt", brand: "Leathercraft Co", price: 28.99, rating: 4.9, reviews: 143, description: "Genuine full-grain leather, brass buckle, ages beautifully.", category: "accessories", imageIcon: "fa-gripfire", seller: "Leathercraft Co", sellerAvatar: "LC", sizes: ["30","32","34","36","38"] },
    { id: 7, name: "Linen Midi Dress", brand: "Sunday Studio", price: 67.00, rating: 4.4, reviews: 98, description: "Flowy linen dress, adjustable straps, breathable summer staple.", category: "women", imageIcon: "fa-female", seller: "Sunday Studio", sellerAvatar: "SS", sizes: ["XS","S","M","L"] }
  ];

  // related mapping (exclude current)
  // cart state
  let cartItems = []; // { id, name, price, quantity, size }
  let selectedProductId = 3; // default denim jacket

  // helper: get product by id
  function getProductById(id) {
    return productsCatalog.find(p => p.id === id);
  }

  // render product detail page based on selectedProductId
  function renderProductDetail() {
    const product = getProductById(selectedProductId);
    if (!product) return;
    const container = document.getElementById('productDetailRoot');
    // size selection state (store in memory for this product)
    let selectedSize = product.sizes && product.sizes.length ? product.sizes[0] : "One size";
    let quantity = 1;

    // build HTML
    const starsFull = Math.floor(product.rating);
    const starHalf = (product.rating % 1) >= 0.5 ? 1 : 0;
    let starsHtml = '';
    for (let i=0; i<starsFull; i++) starsHtml += '<i class="fas fa-star"></i>';
    if (starHalf) starsHtml += '<i class="fas fa-star-half-alt"></i>';
    while(starsHtml.split('fa-star').length -1 + (starHalf?1:0) < 5) starsHtml += '<i class="far fa-star"></i>';

    const sizeOptionsHtml = product.sizes.map(sz => `<button class="size-btn" data-size="${sz}">${sz}</button>`).join('');

    container.innerHTML = `
      <div class="product-detail-container">
        <div class="product-detail-grid">
          <div class="product-gallery">
            <div class="main-image"><i class="fas ${product.imageIcon}"></i></div>
          </div>
          <div class="product-info-panel">
            <div class="product-brand">${product.brand}</div>
            <h1 class="product-title-detail">${product.name}</h1>
            <div class="rating-summary">
              <div class="stars">${starsHtml}</div>
              <div class="review-count">${product.rating} ★ (${product.reviews} reviews)</div>
            </div>
            <div class="price-detail">$${product.price.toFixed(2)}</div>
            <div class="product-description">${product.description}</div>
            
            <div class="size-selector">
              <div class="size-label"><i class="fas fa-ruler-combined"></i> Select size</div>
              <div class="size-options" id="sizeOptionsContainer">${sizeOptionsHtml}</div>
            </div>

            <div class="quantity-selector">
              <button class="qty-btn" id="qtyMinusBtn">-</button>
              <span class="qty-value" id="quantityValue">${quantity}</span>
              <button class="qty-btn" id="qtyPlusBtn">+</button>
            </div>

            <button class="add-to-cart-main" id="detailAddToCartBtn"><i class="fas fa-cart-plus"></i> Add to Cart — $${product.price.toFixed(2)}</button>
            
            <div class="seller-info-card">
              <div class="seller-avatar" style="width:40px; height:40px; background:rgba(31,90,77,0.2); border-radius:40px; display:flex; align-items:center; justify-content:center;">${product.sellerAvatar}</div>
              <div><strong>${product.seller}</strong><br><span style="font-size:0.75rem;">⭐ ${product.rating} seller rating · local boutique</span></div>
            </div>
            <button class="wishlist-detail-btn" id="wishlistDetailBtn"><i class="far fa-heart"></i> Add to wishlist</button>
          </div>
        </div>
      </div>
    `;

    // attach dynamic events
    // size selection
    const sizeBtns = document.querySelectorAll('.size-btn');
    sizeBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        sizeBtns.forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedSize = btn.getAttribute('data-size');
      });
      if(btn.getAttribute('data-size') === selectedSize) btn.classList.add('selected');
    });

    // quantity controls
    const qtyMinus = document.getElementById('qtyMinusBtn');
    const qtyPlus = document.getElementById('qtyPlusBtn');
    const qtySpan = document.getElementById('quantityValue');
    let currentQty = 1;
    const updateQty = () => {
      qtySpan.innerText = currentQty;
      const cartBtn = document.getElementById('detailAddToCartBtn');
      if(cartBtn) cartBtn.innerHTML = `<i class="fas fa-cart-plus"></i> Add to Cart — $${(product.price * currentQty).toFixed(2)}`;
    };
    qtyMinus.addEventListener('click', () => { if(currentQty > 1) { currentQty--; updateQty(); } });
    qtyPlus.addEventListener('click', () => { currentQty++; updateQty(); });
    updateQty();

    // add to cart logic
    const addBtn = document.getElementById('detailAddToCartBtn');
    addBtn.addEventListener('click', () => {
      const finalSize = selectedSize;
      addItemToCart(product.id, product.name, product.price, currentQty, finalSize);
      showToast(`🛒 Added ${currentQty} × ${product.name} (${finalSize}) to cart`);
    });

    // wishlist toast
    const wishBtn = document.getElementById('wishlistDetailBtn');
    wishBtn.addEventListener('click', () => showToast(`❤️ ${product.name} saved to wishlist`));
  }

  // CART SYSTEM
  function addItemToCart(id, name, price, qty, size) {
    const existingIndex = cartItems.findIndex(item => item.id === id && item.size === size);
    if(existingIndex !== -1) {
      cartItems[existingIndex].quantity += qty;
    } else {
      cartItems.push({ id, name, price, quantity: qty, size });
    }
    updateCartUI();
  }

  function updateCartUI() {
    const totalItems = cartItems.reduce((sum, item) => sum + item.quantity, 0);
    const cartCountSpan = document.getElementById('cartCountBadge');
    if(cartCountSpan) cartCountSpan.innerText = totalItems;
    // store cart in localStorage for demo consistency
    localStorage.setItem('pastimes_cart', JSON.stringify(cartItems));
  }

  function loadCartFromStorage() {
    const stored = localStorage.getItem('pastimes_cart');
    if(stored) {
      try {
        cartItems = JSON.parse(stored);
        updateCartUI();
      } catch(e) {}
    }
  }

  function showToast(message) {
    const existing = document.querySelector('.cart-toast');
    if(existing) existing.remove();
    const toast = document.createElement('div');
    toast.className = 'cart-toast';
    toast.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2200);
  }

  // render related products (exclude current product)
  function renderRelated(currentId) {
    const related = productsCatalog.filter(p => p.id !== currentId).slice(0, 4);
    const grid = document.getElementById('relatedProductsGrid');
    if(!grid) return;
    grid.innerHTML = related.map(rel => `
      <div class="related-card" data-id="${rel.id}">
        <div class="related-img"><i class="fas ${rel.imageIcon}"></i></div>
        <div class="related-name">${rel.name}</div>
        <div class="related-price">$${rel.price.toFixed(2)}</div>
        <div style="font-size:0.7rem; color:var(--muted);">${rel.brand}</div>
      </div>
    `).join('');
    document.querySelectorAll('.related-card').forEach(card => {
      card.addEventListener('click', (e) => {
        const newId = parseInt(card.getAttribute('data-id'));
        if(!isNaN(newId)) {
          selectedProductId = newId;
          renderProductDetail();
          renderRelated(selectedProductId);
          window.scrollTo({ top: 0, behavior: 'smooth' });
        }
      });
    });
  }

    function goToHome() {
    window.location.href = "shop.php";
  }

  function backToMarketplace() {
    window.location.href = "shop.php";
  }

  // event listeners
  document.addEventListener('DOMContentLoaded', () => {
    loadCartFromStorage();
    // get product id from url param? default to 3 (denim jacket)
    const urlParams = new URLSearchParams(window.location.search);
    const productIdParam = urlParams.get('id');
    if(productIdParam && !isNaN(parseInt(productIdParam))) {
      selectedProductId = parseInt(productIdParam);
    } else {
      selectedProductId = 3; // default vintage jacket
    }
    renderProductDetail();
    renderRelated(selectedProductId);

    const backBtn = document.getElementById('backLinkBtn');
    if(backBtn) backBtn.addEventListener('click', (e) => { e.preventDefault(); backToMarketplace(); });
    const homeLink = document.getElementById('backToHomeLink');
    if(homeLink) homeLink.addEventListener('click', (e) => { e.preventDefault(); goToHome(); });
    const cartIcon = document.getElementById('cartIconBtn');
    if(cartIcon) cartIcon.addEventListener('click', () => { window.location.href = "checkout.php"; });
    const wishlistGlobal = document.getElementById('wishlistIconBtn');
    if(wishlistGlobal) wishlistGlobal.addEventListener('click', () => showToast(`❤️ Wishlist feature — save your favorite styles!`));
    const account = document.getElementById('accountBtn');
    if(account) account.addEventListener('click', () => { window.location.href = "logout.php"; });
  });
</script>
</body>
</html>

