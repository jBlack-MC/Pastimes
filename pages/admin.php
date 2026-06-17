<?php
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!isset($_SESSION["user_id"]) || (($_SESSION["role"] ?? "") !== "admin")) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$filter = $_GET["filter"] ?? "all";
if (!in_array($filter, ["all", "pending", "active"], true)) {
    $filter = "all";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["verify_user_id"])) {
    $verifyUserId = (int)$_POST["verify_user_id"];
    if ($verifyUserId > 0) {
        $updateStmt = mysqli_prepare($conn, "UPDATE tblUser SET status = 'active' WHERE user_id = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "i", $verifyUserId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
    }

    header("Location: admin.php?filter=" . urlencode($filter) . "&updated=1");
    exit;
}

$statsQuery = "
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN status <> 'active' OR status IS NULL THEN 1 ELSE 0 END) AS pending
    FROM tblUser
";
$statsResult = mysqli_query($conn, $statsQuery);
$stats = ["total" => 0, "active" => 0, "pending" => 0];
if ($statsResult) {
    $stats = mysqli_fetch_assoc($statsResult) ?: $stats;
}

$whereClause = "";
if ($filter === "pending") {
    $whereClause = " WHERE status <> 'active' OR status IS NULL";
} elseif ($filter === "active") {
    $whereClause = " WHERE status = 'active'";
}

$users = [];
$userQuery = "SELECT user_id, name, email, username, status FROM tblUser" . $whereClause . " ORDER BY user_id DESC";
$userResult = mysqli_query($conn, $userQuery);
if ($userResult) {
    while ($row = mysqli_fetch_assoc($userResult)) {
        $users[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5, user-scalable=yes">
  <title>Pastimes · Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

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
      --shadow-md: 0 12px 28px rgba(0, 0, 0, 0.06);
      --radius-lg: 24px;
      --radius-md: 16px;
      --radius-sm: 12px;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      background: var(--bg-page);
      color: var(--text-primary);
      min-height: 100vh;
      padding: 1.2rem;
    }

    .page {
      max-width: 1200px;
      margin: 0 auto;
    }

    .top-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 1rem;
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 0.9rem 1rem;
      box-shadow: var(--shadow-sm);
      margin-bottom: 1rem;
    }

    .logo-area {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
    }

    .logo-icon {
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, var(--green), #3a8a7a);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 1.2rem;
    }

    .logo-text {
      font-size: 1.45rem;
      font-weight: 700;
      letter-spacing: -0.3px;
      color: var(--green);
    }

    .badge {
      background: #f6efe0;
      color: #7a5a2a;
      font-size: 0.68rem;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 30px;
      margin-left: 8px;
    }

    .nav {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .nav a {
      text-decoration: none;
      background: var(--green);
      color: white;
      border-radius: 40px;
      padding: 8px 14px;
      font-size: 0.82rem;
      font-weight: 600;
      transition: 0.2s;
      border: 1px solid transparent;
    }

    .nav a:hover {
      background: var(--green-dark);
      transform: translateY(-1px);
    }

    .nav a.secondary {
      background: transparent;
      color: var(--text-secondary);
      border-color: var(--border);
    }

    .updated {
      margin-bottom: 0.9rem;
      border-radius: var(--radius-sm);
      background: #e8f7ef;
      color: #1b6f4e;
      padding: 10px 14px;
      font-size: 0.82rem;
      border-left: 3px solid #1b6f4e;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(3, minmax(160px, 1fr));
      gap: 0.9rem;
      margin-bottom: 1rem;
    }

    .stat {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-md);
      padding: 0.9rem;
      box-shadow: var(--shadow-sm);
    }

    .stat-label {
      font-size: 0.78rem;
      color: var(--text-muted);
      margin-bottom: 0.25rem;
    }

    .stat-value {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--green);
    }

    .card {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      overflow: hidden;
    }

    .card-head {
      padding: 1rem;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.8rem;
      flex-wrap: wrap;
    }

    .card-head h2 {
      font-size: 1.1rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filters {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filters a {
      text-decoration: none;
      font-size: 0.78rem;
      padding: 6px 12px;
      border-radius: 30px;
      border: 1px solid var(--border);
      color: var(--text-secondary);
      background: #fff;
      font-weight: 600;
    }

    .filters a.active {
      background: var(--green);
      border-color: var(--green);
      color: #fff;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      text-align: left;
      padding: 0.8rem 1rem;
      border-bottom: 1px solid var(--border);
      font-size: 0.86rem;
      vertical-align: middle;
    }

    th {
      color: var(--text-secondary);
      font-weight: 600;
      background: #fcfbf9;
    }

    .status {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      border-radius: 999px;
      padding: 4px 10px;
      font-size: 0.73rem;
      font-weight: 700;
      background: #fff2e7;
      color: #a4532f;
      border: 1px solid #f0d3bf;
    }

    .status.active {
      background: #e6f4f0;
      color: #1b6f4e;
      border-color: #c3dfd5;
    }

    .verify-btn {
      border: none;
      border-radius: 30px;
      background: var(--green);
      color: #fff;
      padding: 7px 12px;
      font-size: 0.75rem;
      font-weight: 700;
      cursor: pointer;
    }

    .verify-btn:hover {
      background: var(--green-dark);
    }

    .muted {
      color: var(--text-muted);
      font-size: 0.78rem;
    }

    .empty {
      padding: 1rem;
      color: var(--text-muted);
      font-size: 0.86rem;
    }

    @media (max-width: 760px) {
      .stats { grid-template-columns: 1fr; }
      th, td { font-size: 0.8rem; }
    }
  </style>
</head>
<body>
<div class="page">
  <header class="top-bar">
    <a href="admin.php" class="logo-area">
      <div class="logo-icon"><i class="fas fa-vest"></i></div>
      <div>
        <span class="logo-text">Pastimes</span>
        <span class="badge">Admin Console</span>
      </div>
    </a>
    <nav class="nav">
      <a href="shop.php">Shop</a>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if (isset($_GET["updated"]) && $_GET["updated"] === "1"): ?>
    <div class="updated"><i class="fas fa-check-circle"></i> User status updated successfully.</div>
  <?php endif; ?>

  <section class="stats">
    <article class="stat">
      <div class="stat-label">Total users</div>
      <div class="stat-value"><?php echo (int)$stats["total"]; ?></div>
    </article>
    <article class="stat">
      <div class="stat-label">Pending users</div>
      <div class="stat-value"><?php echo (int)$stats["pending"]; ?></div>
    </article>
    <article class="stat">
      <div class="stat-label">Active users</div>
      <div class="stat-value"><?php echo (int)$stats["active"]; ?></div>
    </article>
  </section>

  <section class="card">
    <div class="card-head">
      <h2><i class="fas fa-user-shield"></i> User Verification</h2>
      <div class="filters">
        <a class="<?php echo $filter === "all" ? "active" : ""; ?>" href="admin.php?filter=all">All</a>
        <a class="<?php echo $filter === "pending" ? "active" : ""; ?>" href="admin.php?filter=pending">Pending</a>
        <a class="<?php echo $filter === "active" ? "active" : ""; ?>" href="admin.php?filter=active">Active</a>
      </div>
    </div>

    <?php if (count($users) === 0): ?>
      <div class="empty">No users found for this filter.</div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <?php $isActive = (($user["status"] ?? "") === "active"); ?>
            <tr>
              <td><?php echo htmlspecialchars($user["name"] ?? ""); ?></td>
              <td><?php echo htmlspecialchars($user["username"] ?? ""); ?></td>
              <td><?php echo htmlspecialchars($user["email"] ?? ""); ?></td>
              <td>
                <span class="status <?php echo $isActive ? "active" : ""; ?>">
                  <i class="fas <?php echo $isActive ? "fa-circle-check" : "fa-hourglass-half"; ?>"></i>
                  <?php echo $isActive ? "active" : "pending"; ?>
                </span>
              </td>
              <td>
                <?php if (!$isActive): ?>
                  <form method="POST" action="admin.php?filter=<?php echo urlencode($filter); ?>">
                    <input type="hidden" name="verify_user_id" value="<?php echo (int)$user["user_id"]; ?>">
                    <button type="submit" class="verify-btn">Verify</button>
                  </form>
                <?php else: ?>
                  <span class="muted">Already active</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>
</div>
</body>
</html>
