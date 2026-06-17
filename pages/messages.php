<?php
/* User Messaging – inbox and compose (Phase 11) */
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id   = (int)$_SESSION["user_id"];
$user_name = $_SESSION["name"] ?? "User";

/* Create tblmessage if it does not yet exist */
mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS tblmessage (
        message_id  INT AUTO_INCREMENT PRIMARY KEY,
        sender_id   INT NOT NULL,
        sender_name VARCHAR(100),
        receiver_id INT NOT NULL,
        message     TEXT NOT NULL,
        is_read     TINYINT(1) DEFAULT 0,
        created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);

$flash = "";

/* Send a message to admin (receiver_id = -1 represents admin) */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["message"])) {
    $body = trim($_POST["message"] ?? "");
    if (strlen($body) > 0) {
        $s = mysqli_prepare($conn,
            "INSERT INTO tblmessage (sender_id, sender_name, receiver_id, message) VALUES (?,?,-1,?)"
        );
        if ($s) {
            mysqli_stmt_bind_param($s, "iss", $user_id, $user_name, $body);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            $flash = "Message sent to admin.";
        }
    }
}

/* Fetch conversation: all messages between this user and admin */
$messages = [];
$mResult = mysqli_query($conn,
    "SELECT message_id, sender_id, sender_name, message, is_read, created_at
     FROM tblmessage
     WHERE (sender_id=? AND receiver_id=-1)
        OR (sender_id=-1 AND receiver_id=?)
     ORDER BY created_at ASC"
);
/* Inline binding since the query uses the user_id twice */
if ($mResult === false) {
    /* Table may not exist yet — retry after create */
    $messages = [];
} else {
    /* Rebuild query with prepared statement for safety */
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
        while ($row = mysqli_fetch_assoc($mRes)) $messages[] = $row;
        mysqli_stmt_close($mStmt);
    }
}

/* Mark admin replies as read */
$markStmt = mysqli_prepare($conn,
    "UPDATE tblmessage SET is_read=1 WHERE sender_id=-1 AND receiver_id=? AND is_read=0"
);
if ($markStmt) {
    mysqli_stmt_bind_param($markStmt, "i", $user_id);
    mysqli_stmt_execute($markStmt);
    mysqli_stmt_close($markStmt);
}

$role = $_SESSION["role"] ?? "user";
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Messages</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--sec:#5a6e6e;--muted:#8a9b9b;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;--border:#e8e6e1;
      --sm:0 4px 12px rgba(0,0,0,.03);--md:0 12px 28px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);padding:1.2rem}
    .page{max-width:860px;margin:0 auto}
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{text-decoration:none;padding:8px 14px;border-radius:40px;font-size:.82rem;font-weight:600;border:1px solid var(--border);color:var(--sec);background:#fff}
    .nav a:hover,.nav a.active{background:var(--green);color:#fff;border-color:var(--green)}
    .flash{margin-bottom:.9rem;border-radius:var(--sm-r);background:#e8f7ef;color:#1b6f4e;padding:10px 14px;font-size:.82rem;border-left:3px solid #1b6f4e}
    h1{font-size:1.4rem;font-weight:700;margin-bottom:1rem;display:flex;align-items:center;gap:8px}
    .thread{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:1.2rem;min-height:260px;max-height:480px;overflow-y:auto;display:flex;flex-direction:column;gap:.9rem;margin-bottom:1rem;box-shadow:var(--sm)}
    .msg{max-width:78%;padding:.7rem 1rem;border-radius:var(--md-r);font-size:.88rem;line-height:1.5}
    .msg-me{background:var(--green);color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
    .msg-admin{background:var(--white);border:1px solid var(--border);color:var(--text);align-self:flex-start;border-bottom-left-radius:4px}
    .msg-meta{font-size:.68rem;margin-top:4px;opacity:.7}
    .compose{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:1.2rem;box-shadow:var(--sm)}
    .compose h3{font-size:.9rem;font-weight:700;color:var(--sec);margin-bottom:.8rem}
    textarea{width:100%;padding:10px;border:1px solid var(--border);border-radius:var(--sm-r);font-size:.88rem;font-family:inherit;resize:vertical;min-height:90px}
    .btn-send{display:inline-flex;align-items:center;gap:8px;background:var(--green);color:#fff;border:none;border-radius:40px;padding:10px 20px;font-weight:700;font-size:.88rem;cursor:pointer;margin-top:.8rem}
    .btn-send:hover{background:var(--dark)}
    .empty-msg{color:var(--muted);font-size:.88rem;text-align:center;padding:2rem 0}
    .empty-msg i{display:block;font-size:2rem;margin-bottom:.5rem;opacity:.3}
    @media(max-width:600px){.msg{max-width:90%}}
  </style>
</head>
<body>
<div class="page">
  <header class="top-bar">
    <a href="shop.php" class="logo-area">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div><span class="logo-text">Pastimes</span></div>
    </a>
    <nav class="nav">
      <a href="shop.php">Shop</a>
      <a href="checkout.php"><i class="fas fa-shopping-cart"></i> Cart</a>
      <a href="my_orders.php">My Orders</a>
      <a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
      <a href="sellers_hub.php">Seller Hub</a>
      <?php if ($role === "admin"): ?><a href="admin.php">Admin</a><?php endif; ?>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if ($flash): ?>
    <div class="flash"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <h1><i class="fas fa-comments"></i> Messages</h1>
  <p style="color:var(--muted);font-size:.85rem;margin-bottom:1rem">Your conversation with the Pastimes admin team.</p>

  <!-- Message thread -->
  <div class="thread" id="msgThread">
    <?php if (!$messages): ?>
      <div class="empty-msg"><i class="fas fa-comment-dots"></i>No messages yet. Send us a question below!</div>
    <?php else: ?>
      <?php foreach ($messages as $m):
        $isMe = (int)$m["sender_id"] === $user_id;
      ?>
        <div class="msg <?php echo $isMe ? 'msg-me' : 'msg-admin'; ?>">
          <?php echo nl2br(htmlspecialchars($m["message"])); ?>
          <div class="msg-meta">
            <?php echo $isMe ? 'You' : 'Admin'; ?> ·
            <?php echo date("d M, g:i a", strtotime($m["created_at"])); ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Compose message -->
  <div class="compose">
    <h3><i class="fas fa-pen"></i> Send a message to Admin</h3>
    <form method="POST" action="messages.php">
      <textarea name="message" placeholder="Type your question or feedback…" required></textarea>
      <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Send Message</button>
    </form>
  </div>
</div>
<script>
  /* Scroll to the bottom of the thread on load */
  const thread = document.getElementById('msgThread');
  if (thread) thread.scrollTop = thread.scrollHeight;
</script>
</body>
</html>
