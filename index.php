<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title>Pastimes Threads · Timeless Style</title>

  <!-- Font Awesome 6 (free) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Google Fonts: refined serif & sans combo for elegant feel -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --cream: #fef9f2;
      --paper: #fffcf8;
      --deep-charcoal: #2c2a28;
      --moss: #4a5d4e;
      --sage: #6d7f66;
      --husk: #e2cdb1;
      --warm-stone: #d9c2a7;
      --border-light: #ede5db;
      --shadow-sm: 0 12px 30px rgba(0, 0, 0, 0.04);
      --shadow-hover: 0 25px 40px -12px rgba(0, 0, 0, 0.12);
    }

    body {
      background: linear-gradient(145deg, #fdf7ef 0%, #f8f0e7 100%);
      font-family: 'Inter', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 1.5rem;
      color: var(--deep-charcoal);
    }

    /* main card with subtle organic texture */
    .welcome-card {
      max-width: 720px;
      width: 100%;
      background: var(--paper);
      border-radius: 2rem;
      box-shadow: var(--shadow-sm);
      overflow: hidden;
      transition: transform 0.25s ease, box-shadow 0.4s ease;
      border: 1px solid rgba(226, 205, 181, 0.4);
    }

    .welcome-card:hover {
      box-shadow: var(--shadow-hover);
      transform: translateY(-2px);
    }

    /* artistic top accent line */
    .accent-strip {
      height: 4px;
      background: linear-gradient(90deg, var(--moss), var(--husk), var(--sage), var(--warm-stone));
      width: 100%;
    }

    .content-inner {
      padding: 3rem 2.5rem;
    }

    /* brand emblem */
    .brand-marker {
      display: flex;
      justify-content: center;
      margin-bottom: 1.5rem;
    }

    .icon-circle {
      background: #f3ede7;
      width: 70px;
      height: 70px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      color: var(--moss);
      box-shadow: inset 0 0 0 1px rgba(106, 87, 70, 0.08), 0 6px 12px -8px rgba(0,0,0,0.1);
    }

    .logo-text {
      font-size: 1.8rem;
      font-weight: 600;
      letter-spacing: -0.3px;
      background: linear-gradient(135deg, #3f5e4c 0%, #5b7c64 100%);
      background-clip: text;
      -webkit-background-clip: text;
      color: transparent;
      margin-top: 0.25rem;
    }

    .logo-sub {
      font-size: 0.75rem;
      letter-spacing: 2px;
      text-transform: uppercase;
      color: #9c8c7c;
      margin-top: 6px;
      font-weight: 400;
    }

    h1 {
      font-family: 'Cormorant Garamond', serif;
      font-size: 3.2rem;
      font-weight: 500;
      letter-spacing: -0.01em;
      color: #2d3328;
      margin: 0.5rem 0 0.75rem 0;
      line-height: 1.2;
    }

    .tagline {
      font-size: 1rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 3px;
      color: var(--sage);
      margin-bottom: 1rem;
    }

    .description {
      font-size: 1.05rem;
      line-height: 1.5;
      color: #5e554b;
      max-width: 480px;
      margin: 1rem auto 0;
    }

    .divider {
      width: 60px;
      height: 2px;
      background: var(--husk);
      margin: 1.8rem auto 1.8rem auto;
      border-radius: 2px;
    }

    /* feature chips - subtle brand cues */
    .pill-list {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.8rem;
      margin: 1.8rem 0 2.2rem 0;
    }

    .pill {
      background: #f7f2ec;
      padding: 0.4rem 1.2rem;
      border-radius: 60px;
      font-size: 0.8rem;
      font-weight: 500;
      color: #5b6b56;
      letter-spacing: 0.2px;
      transition: all 0.2s;
      border: 0.5px solid rgba(106, 87, 70, 0.1);
    }

    /* primary CTA */
    .btn-primary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      background: var(--moss);
      color: white;
      padding: 14px 36px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      font-size: 1rem;
      transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
      border: none;
      cursor: pointer;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
      margin-top: 0.5rem;
    }

    .btn-primary i {
      transition: transform 0.25s ease;
      font-size: 0.9rem;
    }

    .btn-primary:hover {
      background: #344d3e;
      transform: translateY(-3px);
      box-shadow: 0 12px 20px -14px rgba(58, 78, 62, 0.4);
    }

    .btn-primary:hover i {
      transform: translateX(6px);
    }

    /* secondary subtle link */
    .info-link {
      margin-top: 2rem;
      display: inline-block;
      font-size: 0.85rem;
      color: #8f7f70;
      text-decoration: none;
      border-bottom: 1px dotted #d9cdc0;
      transition: color 0.2s;
    }

    .info-link:hover {
      color: var(--moss);
      border-bottom-color: var(--moss);
    }

    /* decorative leaf icon */
    .ornament {
      font-size: 0.85rem;
      color: #d4c1ab;
      letter-spacing: 4px;
      margin: 1.2rem 0 0 0;
    }

    /* responsive */
    @media (max-width: 550px) {
      .content-inner {
        padding: 2rem 1.5rem;
      }
      h1 {
        font-size: 2.4rem;
      }
      .tagline {
        font-size: 0.8rem;
        letter-spacing: 2px;
      }
      .btn-primary {
        padding: 12px 28px;
        font-size: 0.9rem;
      }
      .pill-list {
        gap: 0.6rem;
      }
    }

    /* micro animation */
    @keyframes fadeSlideUp {
      0% {
        opacity: 0;
        transform: translateY(12px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .welcome-card .content-inner > * {
      animation: fadeSlideUp 0.5s ease backwards;
    }

    .welcome-card .content-inner .brand-marker {
      animation-delay: 0s;
    }
    .welcome-card .content-inner .tagline {
      animation-delay: 0.05s;
    }
    .welcome-card .content-inner h1 {
      animation-delay: 0.1s;
    }
    .welcome-card .content-inner .description {
      animation-delay: 0.2s;
    }
    .welcome-card .content-inner .divider {
      animation-delay: 0.25s;
    }
    .welcome-card .content-inner .pill-list {
      animation-delay: 0.3s;
    }
    .welcome-card .content-inner .btn-primary {
      animation-delay: 0.4s;
    }
    .welcome-card .content-inner .info-link {
      animation-delay: 0.5s;
    }
  </style>
</head>
<body>

<div class="welcome-card">
  <div class="accent-strip"></div>
  <div class="content-inner">
    
    <div class="brand-marker">
      <div class="icon-circle">
        <i class="fas fa-feather-alt"></i>
      </div>
    </div>
    
    <div class="logo-text">Pastimes Threads</div>
    <div class="logo-sub">Atelier · Mindful Living</div>

    <div class="tagline">heritage feels, modern soul</div>
    
    <h1>Your wardrobe story</h1>
    
    <p class="description">
      Thoughtfully crafted pieces for everyday ease and timeless style.<br>
      Discover slow fashion, natural textures & artisan quality.
    </p>

    <div class="divider"></div>

    <!-- brand commitment pills -->
    <div class="pill-list">
      <span class="pill"><i class="fas fa-seedling" style="margin-right: 6px; font-size: 0.7rem;"></i> Regenerative Cotton</span>
      <span class="pill"><i class="fas fa-hand-sparkles"></i> Hand-finished</span>
      <span class="pill"><i class="fas fa-recycle"></i> zero-waste</span>
      <span class="pill"><i class="fas fa-undo-alt"></i> heirloom quality</span>
    </div>

    <!-- main action button - working link exactly as required -->
    <a href="pages/login.php" class="btn-primary">
      Enter the shop
      <i class="fas fa-arrow-right"></i>
    </a>

    <div class="ornament">
      <i class="fas fa-asterisk"></i>   <i class="fas fa-circle" style="font-size: 0.3rem; vertical-align: middle;"></i>   <i class="fas fa-asterisk"></i>
    </div>

    <!-- small extra link with character (optional but adds warmth) -->
    <div style="margin-top: 1.5rem;">
      <a href="#" class="info-link" id="storyPreview">
        <i class="far fa-clock"></i> Our journey
      </a>
    </div>
  </div>
</div>

<!-- lightweight micro interaction for demo: just a gentle console alert / not intrusive -->
<script>
  // a touch of elegance: show a small message on "Our journey" click, but keep main flow intact.
  const storyLink = document.getElementById('storyPreview');
  if (storyLink) {
    storyLink.addEventListener('click', (e) => {
      e.preventDefault();
      // subtle non-disruptive notification (could also link to about page if needed)
      const toastMsg = document.createElement('div');
      toastMsg.innerText = '✨ Every thread holds a story — discover our ethos soon ✨';
      toastMsg.style.position = 'fixed';
      toastMsg.style.bottom = '20px';
      toastMsg.style.left = '50%';
      toastMsg.style.transform = 'translateX(-50%)';
      toastMsg.style.backgroundColor = '#2c2a28';
      toastMsg.style.color = '#f3ede7';
      toastMsg.style.padding = '0.8rem 1.8rem';
      toastMsg.style.borderRadius = '60px';
      toastMsg.style.fontSize = '0.8rem';
      toastMsg.style.fontWeight = '500';
      toastMsg.style.fontFamily = 'Inter, sans-serif';
      toastMsg.style.backdropFilter = 'blur(6px)';
      toastMsg.style.zIndex = '999';
      toastMsg.style.boxShadow = '0 8px 18px rgba(0,0,0,0.1)';
      toastMsg.style.border = '1px solid rgba(255,255,240,0.2)';
      toastMsg.style.pointerEvents = 'none';
      document.body.appendChild(toastMsg);
      setTimeout(() => {
        toastMsg.style.opacity = '0';
        toastMsg.style.transition = 'opacity 0.4s';
        setTimeout(() => toastMsg.remove(), 500);
      }, 2200);
    });
  }
</script>
</body>
</html>