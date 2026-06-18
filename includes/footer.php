<?php
/* Reusable customer-facing footer.
   Include near the end of any customer page:
       require_once __DIR__ . "/../includes/footer.php";
   Styles are scoped with the "ptf-" prefix so they never clash with the
   host page's own CSS. Font Awesome is assumed to already be loaded. */
?>
<footer class="ptf">
  <div class="ptf-inner">
    <div class="ptf-grid">
      <div class="ptf-brand">
        <div class="ptf-logo"><span class="ptf-logo-icon"><i class="fas fa-vest"></i></span> Pastimes</div>
        <p class="ptf-blurb">Slow fashion, pre-loved finds, and handcrafted pieces from independent South African sellers — given a second life.</p>
        <div class="ptf-social" aria-label="Social media">
          <a href="info.php?page=contact" title="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="info.php?page=contact" title="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="info.php?page=contact" title="X"><i class="fab fa-x-twitter"></i></a>
          <a href="info.php?page=contact" title="TikTok"><i class="fab fa-tiktok"></i></a>
        </div>
      </div>

      <div class="ptf-col">
        <h4>Shop</h4>
        <a href="shop.php">All Products</a>
        <a href="my_orders.php">My Orders</a>
        <a href="checkout.php">My Cart</a>
        <a href="seller_register.php">Sell on Pastimes</a>
      </div>

      <div class="ptf-col">
        <h4>Company</h4>
        <a href="info.php?page=about">About Us</a>
        <a href="info.php?page=contact">Contact</a>
        <a href="info.php?page=faq">FAQ</a>
      </div>

      <div class="ptf-col">
        <h4>Support</h4>
        <a href="messages.php">Message Support</a>
        <a href="info.php?page=shipping">Shipping &amp; Returns</a>
        <a href="my_orders.php">Track Your Order</a>
      </div>

      <div class="ptf-col">
        <h4>Legal</h4>
        <a href="info.php?page=privacy">Privacy Policy</a>
        <a href="info.php?page=terms">Terms of Service</a>
      </div>

      <div class="ptf-news">
        <h4>Stay in the loop</h4>
        <p>New arrivals and seller stories, straight to your inbox.</p>
        <form class="ptf-news-form" id="ptfNews">
          <input type="email" placeholder="Your email address" aria-label="Email address" required>
          <button type="submit" title="Subscribe"><i class="fas fa-paper-plane"></i></button>
        </form>
        <p class="ptf-news-ok" id="ptfNewsOk">Thanks for subscribing! 🎉</p>
      </div>
    </div>

    <div class="ptf-bottom">
      <span>© <?php echo date("Y"); ?> Pastimes Threads. All rights reserved.</span>
      <span class="ptf-pay" aria-label="Accepted payments">
        <i class="fab fa-cc-visa" title="Visa"></i>
        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
        <i class="fas fa-money-bill-wave" title="Cash on delivery"></i>
      </span>
      <span class="ptf-made">Made with <i class="fas fa-heart"></i> in South Africa</span>
    </div>
  </div>
</footer>

<style>
  .ptf{background:#1e2a2a;color:#c8d4d0;margin-top:3rem;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif}
  .ptf-inner{max-width:1200px;margin:0 auto;padding:2.6rem 1.2rem 1.4rem}
  .ptf-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr 1fr;gap:1.8rem;padding-bottom:1.8rem;border-bottom:1px solid rgba(255,255,255,.1)}
  .ptf-brand{max-width:320px}
  .ptf-logo{display:flex;align-items:center;gap:9px;font-size:1.4rem;font-weight:800;color:#fff;margin-bottom:.7rem}
  .ptf-logo-icon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#2a6b5e,#3a8a7a);color:#fff;font-size:1.1rem}
  .ptf-blurb{font-size:.84rem;line-height:1.6;color:#9fb0ab;margin-bottom:1rem}
  .ptf-social{display:flex;gap:10px}
  .ptf-social a{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.07);display:flex;align-items:center;justify-content:center;color:#c8d4d0;text-decoration:none;transition:.18s;font-size:.95rem}
  .ptf-social a:hover{background:#2a6b5e;color:#fff;transform:translateY(-2px)}
  .ptf-col h4,.ptf-news h4{font-size:.82rem;font-weight:700;color:#fff;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.9rem}
  .ptf-col a{display:block;font-size:.85rem;color:#9fb0ab;text-decoration:none;margin-bottom:.55rem;transition:.15s}
  .ptf-col a:hover{color:#fff;padding-left:3px}
  .ptf-news p{font-size:.82rem;line-height:1.55;color:#9fb0ab;margin-bottom:.8rem}
  .ptf-news-form{display:flex;gap:6px}
  .ptf-news-form input{flex:1;min-width:0;padding:9px 12px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.06);border-radius:40px;color:#fff;font-size:.82rem;font-family:inherit;outline:none}
  .ptf-news-form input::placeholder{color:#7c8c88}
  .ptf-news-form input:focus{border-color:#3a8a7a}
  .ptf-news-form button{flex-shrink:0;width:40px;height:40px;border:none;border-radius:50%;background:#2a6b5e;color:#fff;cursor:pointer;transition:.18s}
  .ptf-news-form button:hover{background:#3a8a7a}
  .ptf-news-ok{display:none;font-size:.82rem;color:#7fd1bb;margin-top:.7rem}
  .ptf-news-ok.show{display:block}
  .ptf-bottom{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:.8rem;padding-top:1.3rem;font-size:.78rem;color:#8a9b97}
  .ptf-pay{display:flex;gap:12px;font-size:1.35rem;color:#b6c4c0}
  .ptf-made i{color:#c26743}
  @media(max-width:900px){.ptf-grid{grid-template-columns:1fr 1fr;gap:1.5rem}.ptf-brand{grid-column:1/-1;max-width:none}}
  @media(max-width:520px){.ptf-grid{grid-template-columns:1fr}.ptf-bottom{flex-direction:column;text-align:center}}
</style>
<script>
  (function () {
    var f = document.getElementById('ptfNews');
    if (!f) return;
    f.addEventListener('submit', function (e) {
      e.preventDefault();
      f.style.display = 'none';
      var ok = document.getElementById('ptfNewsOk');
      if (ok) ok.classList.add('show');
    });
  })();
</script>
