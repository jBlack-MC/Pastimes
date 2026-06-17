<?php
/* Admin Messaging Dashboard – view all conversations and reply (Phase 11) */
session_start();

if (!isset($_SESSION["user_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

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

/* Send admin reply to a specific user (receiver_id = user_id) */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reply_to"], $_POST["reply_message"])) {
    $to   = (int)$_POST["reply_to"];
    $body = trim($_POST["reply_message"] ?? "");
    if ($to > 0 && $body !== "") {
        /* sender_id = -1 represents the admin account */
        $s = mysqli_prepare($conn,
            "INSERT INTO tblmessage (sender_id, sender_name, receiver_id, message) VALUES (-1,'Admin',?,?)"
        );
        if ($s) {
            mysqli_stmt_bind_param($s, "is", $to, $body);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
            $flash = "Reply sent.";
        }
    }
    header("Location: admin_messages.php?user=" . urlencode($to) . "&msg=sent");
    exit;
}

/* Get the selected conversation user from the query string */
$selectedUser = (int)($_GET["user"] ?? 0);
if (isset($_GET["msg"]) && $_GET["msg"] === "sent") $flash = "Reply sent.";

/* Fetch all users who have sent at least one message */
$contacts = [];
$cResult = mysqli_query($conn,
    "SELECT DISTINCT m.sender_id, u.name AS user_name, u.email,
            SUM(m.is_read=0 AND m.sender_id<>-1) AS unread
     FROM tblmessage m
     JOIN tblUser u ON m.sender_id = u.user_id
     WHERE m.sender_id > 0
     GROUP BY m.sender_id, u.name, u.email
     ORDER BY MAX(m.created_at) DESC"
);
if ($cResult) {
    while ($row = mysqli_fetch_assoc($cResult)) $contacts[] = $row;
}

/* Fetch thread for the selected user */
$thread = [];
if ($selectedUser > 0) {
    $tStmt = mysqli_prepare($conn,
        "SELECT sender_id, sender_name, message, is_read, created_at
         FROM tblmessage
         WHERE (sender_id=? AND receiver_id=-1)
            OR (sender_id=-1 AND receiver_id=?)
         ORDER BY created_at ASC"
    );
    if ($tStmt) {
        mysqli_stmt_bind_param($tStmt, "ii", $selectedUser, $selectedUser);
        mysqli_stmt_execute($tStmt);
        $tRes = mysqli_stmt_get_result($tStmt);
        while ($row = mysqli_fetch_assoc($tRes)) $thread[] = $row;
        mysqli_stmt_close($tStmt);
    }

    /* Mark all messages from this user as read */
    $markStmt = mysqli_prepare($conn,
        "UPDATE tblmessage SET is_read=1 WHERE sender_id=? AND is_read=0"
    );
    if ($markStmt) {
        mysqli_stmt_bind_param($markStmt, "i", $selectedUser);
        mysqli_stmt_execute($markStmt);
        mysqli_stmt_close($markStmt);
    }
}

/* Find the name of the currently selected user */
$selectedName = "";
foreach ($contacts as $c) {
    if ((int)$c["sender_id"] === $selectedUser) {
        $selectedName = $c["user_name"];
        break;
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Admin Messages</title>
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
    .page{max-width:1100px;margin:0 auto}
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .badge{background:#f6efe0;color:#7a5a2a;font-size:.68rem;font-weight:600;padding:4px 10px;border-radius:30px;margin-left:8px}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{text-decoration:none;padding:8px 14px;border-radius:40px;font-size:.82rem;font-weight:600;border:1px solid var(--border);color:var(--sec);background:#fff}
    .nav a:hover,.nav a.active{background:var(--green);color:#fff;border-color:var(--green)}
    .flash{margin-bottom:.9rem;border-radius:var(--sm-r);background:#e8f7ef;color:#1b6f4e;padding:10px 14px;font-size:.82rem;border-left:3px solid #1b6f4e}
    .layout{display:grid;grid-template-columns:280px 1fr;gap:1rem}
    /* Contact sidebar */
    .contacts{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);overflow:hidden;box-shadow:var(--sm)}
    .contacts-head{padding:1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:8px}
    .contact-item{display:flex;align-items:center;gap:10px;padding:.75rem 1rem;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);transition:.15s}
    .contact-item:hover,.contact-item.active{background:#f0f8f6}
    .contact-avatar{width:38px;height:38px;background:var(--green);border-radius:50%;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0}
    .contact-info{flex:1;min-width:0}
    .contact-name{font-weight:600;font-size:.88rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .contact-email{font-size:.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .unread-dot{width:8px;height:8px;background:var(--rust);border-radius:50%;flex-shrink:0}
    /* Thread panel */
    .thread-panel{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);box-shadow:var(--sm);display:flex;flex-direction:column;overflow:hidden}
    .thread-head{padding:1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:8px}
    .thread-body{flex:1;padding:1.2rem;overflow-y:auto;min-height:300px;max-height:440px;display:flex;flex-direction:column;gap:.9rem}
    .msg{max-width:78%;padding:.7rem 1rem;border-radius:var(--md-r);font-size:.88rem;line-height:1.5}
    .msg-user{background:#f0f8f6;border:1px solid var(--border);align-self:flex-start;border-bottom-left-radius:4px}
    .msg-admin{background:var(--green);color:#fff;align-self:flex-end;border-bottom-right-radius:4px}
    .msg-meta{font-size:.68rem;margin-top:4px;opacity:.7}
    .compose-area{padding:1rem;border-top:1px solid var(--border)}
    .compose-area h4{font-size:.82rem;font-weight:700;color:var(--sec);margin-bottom:.6rem}
    textarea{width:100%;padding:9px;border:1px solid var(--border);border-radius:var(--sm-r);font-size:.85rem;font-family:inherit;resize:vertical;min-height:80px}
    .btn-send{display:inline-flex;align-items:center;gap:8px;background:var(--green);color:#fff;border:none;border-radius:40px;padding:9px 18px;font-weight:700;font-size:.85rem;cursor:pointer;margin-top:.6rem}
    .btn-send:hover{background:var(--dark)}
    .empty-panel{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted);padding:2rem;text-align:center}
    .empty-panel i{font-size:2.5rem;margin-bottom:.8rem;opacity:.2}
    .no-contacts{padding:1.5rem;color:var(--muted);font-size:.85rem;text-align:center}
    @media(max-width:720px){.layout{grid-template-columns:1fr}.contacts{max-height:220px;overflow-y:auto}}
  </style>
</head>
<body>
<div class="page">
  <header class="top-bar">
    <a href="admin.php" class="logo-area">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div><span class="logo-text">Pastimes</span><span class="badge">Admin Console</span></div>
    </a>
    <nav class="nav">
      <a href="admin.php">Dashboard</a>
      <a href="admin_orders.php">Orders</a>
      <a href="admin_messages.php" class="active">Messages</a>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if ($flash): ?>
    <div class="flash"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <div class="layout">
    <!-- Contacts sidebar -->
    <div class="contacts">
      <div class="contacts-head"><i class="fas fa-users"></i> Conversations</div>
      <?php if (!$contacts): ?>
        <div class="no-contacts">No messages from users yet.</div>
      <?php else: ?>
        <?php foreach ($contacts as $c):
          $initials = strtoupper(substr($c["user_name"], 0, 2));
          $isActive = (int)$c["sender_id"] === $selectedUser;
        ?>
          <a class="contact-item <?php echo $isActive?'active':''; ?>"
             href="admin_messages.php?user=<?php echo (int)$c["sender_id"]; ?>">
            <div class="contact-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="contact-info">
              <div class="contact-name"><?php echo htmlspecialchars($c["user_name"]); ?></div>
              <div class="contact-email"><?php echo htmlspecialchars($c["email"]); ?></div>
            </div>
            <?php if ((int)$c["unread"] > 0): ?>
              <div class="unread-dot" title="<?php echo (int)$c['unread']; ?> unread"></div>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Thread panel -->
    <div class="thread-panel">
      <?php if ($selectedUser > 0 && $selectedName): ?>
        <div class="thread-head">
          <i class="fas fa-comment-dots"></i>
          Conversation with <?php echo htmlspecialchars($selectedName); ?>
        </div>
        <div class="thread-body" id="threadBody">
          <?php if (!$thread): ?>
            <div style="color:var(--muted);text-align:center;padding-top:2rem">No messages yet.</div>
          <?php else: ?>
            <?php foreach ($thread as $m):
              $fromAdmin = (int)$m["sender_id"] === -1;
            ?>
              <div class="msg <?php echo $fromAdmin ? 'msg-admin' : 'msg-user'; ?>">
                <?php echo nl2br(htmlspecialchars($m["message"])); ?>
                <div class="msg-meta">
                  <?php echo $fromAdmin ? 'Admin' : htmlspecialchars($m["sender_name"] ?? "User"); ?> ·
                  <?php echo date("d M, g:i a", strtotime($m["created_at"])); ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <!-- Reply form -->
        <div class="compose-area">
          <h4><i class="fas fa-reply"></i> Reply to <?php echo htmlspecialchars($selectedName); ?></h4>
          <form method="POST" action="admin_messages.php">
            <input type="hidden" name="reply_to" value="<?php echo $selectedUser; ?>">
            <textarea name="reply_message" placeholder="Type your reply…" required></textarea>
            <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i> Send Reply</button>
          </form>
        </div>
      <?php else: ?>
        <div class="empty-panel">
          <i class="fas fa-inbox"></i>
          <p>Select a conversation from the left to view messages.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script>
  /* Scroll to bottom of thread */
  const body = document.getElementById('threadBody');
  if (body) body.scrollTop = body.scrollHeight;
</script>
</body>
</html>
