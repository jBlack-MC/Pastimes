<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes Threads · Welcome</title>

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --bg: #fefaf5;
      --text: #2b2a27;
      --green: #3f5e4c;
      --green-dark: #2d4537;
      --gold: #e0b07f;
      --border: #ede6df;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .container {
      background: #fff;
      padding: 3rem 2rem;
      border-radius: 25px;
      text-align: center;
      width: 100%;
      max-width: 600px;
      border: 1px solid var(--border);
      box-shadow: 0 20px 40px rgba(0,0,0,0.05);
    }

    .logo {
      font-size: 2rem;
      font-weight: 700;
      color: var(--green);
      margin-bottom: 1rem;
    }

    h1 {
      font-size: 2.2rem;
      margin-bottom: 1rem;
    }

    p {
      color: #6b625c;
      margin-bottom: 2rem;
    }

    /* FIXED BUTTON LINK */
    .continue-btn {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: var(--green);
      color: white;
      padding: 16px 40px;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      transition: 0.3s;
    }

    .continue-btn:hover {
      background: var(--green-dark);
      transform: translateY(-2px);
    }

    .continue-btn i {
      transition: 0.3s;
    }

    .continue-btn:hover i {
      transform: translateX(5px);
    }

  </style>
</head>
<body>

  <div class="container">
    <div class="logo">
      <i class="fas fa-tshirt"></i> Pastimes Threads
    </div>

    <h1>Your wardrobe story</h1>

    <p>
      Thoughtfully crafted pieces for everyday ease and timeless style.<br>
      Discover slow fashion & natural textures.
    </p>

    <!-- WORKING LINK -->
    <a href="pages/login.php" class="continue-btn">
      Enter the shop
      <i class="fas fa-arrow-right"></i>
    </a>

  </div>

</body>
</html>