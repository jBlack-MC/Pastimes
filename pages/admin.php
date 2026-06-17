<?php
/* Admin Dashboard – users, products, sellers, orders, messages (Phases 8, 9, 12) */
session_start();
error_reporting(E_ALL);
ini_set("display_errors", 1);

/* Restrict page to admin role only */
if (!isset($_SESSION["user_id"]) || (($_SESSION["role"] ?? "") !== "admin")) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

/* Determine which tab is active; default to users */
$activeTab = $_GET["tab"] ?? "users";
if (!in_array($activeTab, ["users", "products", "sellers"], true)) $activeTab = "users";

$flash = "";

/* ── USER CRUD (add / edit / delete / verify) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["user_action"])) {
    $ua = $_POST["user_action"];

    if ($ua === "add_user") {
        /* Insert a new user account (status active so admin-created accounts can log in immediately) */
        $uname  = trim($_POST["uname"]  ?? "");
        $uemail = trim($_POST["uemail"] ?? "");
        $uuser  = trim($_POST["uuser"]  ?? "");
        $upass  = trim($_POST["upass"]  ?? "");
        $ustatus = in_array($_POST["ustatus"] ?? "", ["active","pending"], true) ? $_POST["ustatus"] : "active";
        /* Validate email format and minimum password length before inserting */
        if ($uname && filter_var($uemail, FILTER_VALIDATE_EMAIL) && $uuser && strlen($upass) >= 4) {
            $hashed = password_hash($upass, PASSWORD_DEFAULT);
            $s = mysqli_prepare($conn,
                "INSERT INTO tblUser (name, email, username, password, status) VALUES (?,?,?,?,?)"
            );
            if ($s) {
                mysqli_stmt_bind_param($s, "sssss", $uname, $uemail, $uuser, $hashed, $ustatus);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
            }
        }
        header("Location: admin.php?tab=users&msg=user_added"); exit;
    }

    if ($ua === "edit_user") {
        /* Update an existing user's name, email, username, and status */
        $uid     = (int)($_POST["user_id"] ?? 0);
        $uname   = trim($_POST["uname"]   ?? "");
        $uemail  = trim($_POST["uemail"]  ?? "");
        $uuser   = trim($_POST["uuser"]   ?? "");
        $ustatus = in_array($_POST["ustatus"] ?? "", ["active","pending"], true) ? $_POST["ustatus"] : "active";
        /* Validate email format before updating */
        if ($uid > 0 && $uname && filter_var($uemail, FILTER_VALIDATE_EMAIL) && $uuser) {
            $upass = trim($_POST["upass"] ?? "");
            if ($upass !== "") {
                /* Admin provided a new password – hash and update it */
                $hashed = password_hash($upass, PASSWORD_DEFAULT);
                $s = mysqli_prepare($conn,
                    "UPDATE tblUser SET name=?,email=?,username=?,password=?,status=? WHERE user_id=?"
                );
                if ($s) {
                    mysqli_stmt_bind_param($s, "sssssi", $uname, $uemail, $uuser, $hashed, $ustatus, $uid);
                    mysqli_stmt_execute($s); mysqli_stmt_close($s);
                }
            } else {
                /* No new password – leave the existing hash unchanged */
                $s = mysqli_prepare($conn,
                    "UPDATE tblUser SET name=?,email=?,username=?,status=? WHERE user_id=?"
                );
                if ($s) {
                    mysqli_stmt_bind_param($s, "ssssi", $uname, $uemail, $uuser, $ustatus, $uid);
                    mysqli_stmt_execute($s); mysqli_stmt_close($s);
                }
            }
        }
        header("Location: admin.php?tab=users&msg=user_updated"); exit;
    }

    if ($ua === "delete_user") {
        /* Permanently remove a user account – but only if they have no orders (FK constraint) */
        $uid = (int)($_POST["user_id"] ?? 0);
        if ($uid > 0) {
            /* Check for existing orders before deleting; tblorder.user_id has a FK to tblUser */
            $chk = mysqli_prepare($conn, "SELECT COUNT(*) AS n FROM tblorder WHERE user_id=?");
            $hasOrders = false;
            if ($chk) {
                mysqli_stmt_bind_param($chk, "i", $uid);
                mysqli_stmt_execute($chk);
                $hasOrders = (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($chk))["n"] ?? 0) > 0;
                mysqli_stmt_close($chk);
            }
            if (!$hasOrders) {
                $s = mysqli_prepare($conn, "DELETE FROM tblUser WHERE user_id=?");
                if ($s) { mysqli_stmt_bind_param($s,"i",$uid); mysqli_stmt_execute($s); mysqli_stmt_close($s); }
                header("Location: admin.php?tab=users&msg=user_deleted"); exit;
            }
            header("Location: admin.php?tab=users&msg=user_has_orders"); exit;
        }
        header("Location: admin.php?tab=users&msg=user_deleted"); exit;
    }
}

/* ── USER VERIFICATION (quick approve from verify button) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["verify_user_id"])) {
    /* Set user status to active so they can log in */
    $uid = (int)$_POST["verify_user_id"];
    if ($uid > 0) {
        $s = mysqli_prepare($conn, "UPDATE tblUser SET status='active' WHERE user_id=?");
        if ($s) { mysqli_stmt_bind_param($s, "i", $uid); mysqli_stmt_execute($s); mysqli_stmt_close($s); }
    }
    header("Location: admin.php?tab=users&msg=user_verified"); exit;
}

/* ── PRODUCT CRUD (Phase 8) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["product_action"])) {
    $pa = $_POST["product_action"];

    /* Helper: handle an optional image upload with extension + MIME type validation */
    $uploadImage = function() {
        if (!isset($_FILES["pimage"]) || $_FILES["pimage"]["error"] !== UPLOAD_ERR_OK) return null;
        $ext     = strtolower(pathinfo($_FILES["pimage"]["name"], PATHINFO_EXTENSION));
        $allowed = ["jpg","jpeg","png","gif","webp"];
        if (!in_array($ext, $allowed, true)) return null;
        /* Verify the real MIME type from file content, not just the filename extension */
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES["pimage"]["tmp_name"]);
        finfo_close($finfo);
        $allowedMimes = ["image/jpeg","image/png","image/gif","image/webp"];
        if (!in_array($mimeType, $allowedMimes, true)) return null;
        $fname = uniqid("prod_") . "." . $ext;
        $dir   = __DIR__ . "/../uploads/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        /* Move validated file to uploads directory */
        if (move_uploaded_file($_FILES["pimage"]["tmp_name"], $dir . $fname)) return $fname;
        return null;
    };

    if ($pa === "add_product") {
        $pname  = trim($_POST["pname"] ?? "");
        $pdesc  = trim($_POST["pdesc"] ?? "");
        $pprice = (float)($_POST["pprice"] ?? 0);
        $pstock = (int)($_POST["pstock"] ?? 0);
        $pbrand = trim($_POST["pbrand"] ?? "");
        $pimage = $uploadImage() ?? "placeholder-clothing.jpg";

        if ($pname && $pprice > 0) {
            /* Insert a new product as an admin-created listing */
            $s = mysqli_prepare($conn,
                "INSERT INTO tblclothes (name, brand, description, price, stock, image, created_at) VALUES (?,?,?,?,?,?,NOW())"
            );
            if ($s) {
                mysqli_stmt_bind_param($s, "sssdis", $pname, $pbrand, $pdesc, $pprice, $pstock, $pimage);
                mysqli_stmt_execute($s);
                mysqli_stmt_close($s);
            }
        }
        header("Location: admin.php?tab=products&msg=product_added"); exit;
    }

    if ($pa === "edit_product") {
        $pid    = (int)($_POST["product_id"] ?? 0);
        $pname  = trim($_POST["pname"] ?? "");
        $pdesc  = trim($_POST["pdesc"] ?? "");
        $pprice = (float)($_POST["pprice"] ?? 0);
        $pstock = (int)($_POST["pstock"] ?? 0);
        $pbrand = trim($_POST["pbrand"] ?? "");
        $pimage = $uploadImage();

        if ($pid > 0 && $pname && $pprice > 0) {
            if ($pimage) {
                /* Update product including a new image */
                $s = mysqli_prepare($conn,
                    "UPDATE tblclothes SET name=?,brand=?,description=?,price=?,stock=?,image=? WHERE product_id=?"
                );
                if ($s) {
                    mysqli_stmt_bind_param($s, "sssdisi", $pname, $pbrand, $pdesc, $pprice, $pstock, $pimage, $pid);
                    mysqli_stmt_execute($s);
                    mysqli_stmt_close($s);
                }
            } else {
                $s = mysqli_prepare($conn,
                    "UPDATE tblclothes SET name=?,brand=?,description=?,price=?,stock=? WHERE product_id=?"
                );
                if ($s) {
                    mysqli_stmt_bind_param($s, "sssdi" . "i", $pname, $pbrand, $pdesc, $pprice, $pstock, $pid);
                    mysqli_stmt_execute($s);
                    mysqli_stmt_close($s);
                }
            }
        }
        header("Location: admin.php?tab=products&msg=product_updated"); exit;
    }

    if ($pa === "delete_product") {
        $pid = (int)($_POST["product_id"] ?? 0);
        if ($pid > 0) {
            /* Retrieve image filename before deleting so we can remove the file */
            $imgQ = mysqli_query($conn, "SELECT image FROM tblclothes WHERE product_id=$pid");
            if ($imgQ) {
                $imgRow = mysqli_fetch_assoc($imgQ);
                $img = $imgRow["image"] ?? "";
                if ($img && $img !== "placeholder-clothing.jpg") {
                    $f = __DIR__ . "/../uploads/" . $img;
                    if (file_exists($f)) unlink($f);
                }
            }
            $s = mysqli_prepare($conn, "DELETE FROM tblclothes WHERE product_id=?");
            if ($s) { mysqli_stmt_bind_param($s,"i",$pid); mysqli_stmt_execute($s); mysqli_stmt_close($s); }
        }
        header("Location: admin.php?tab=products&msg=product_deleted"); exit;
    }
}

/* ── SELLER APPROVAL (Phase 9) ── */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["seller_action"])) {
    $sa  = $_POST["seller_action"];
    $sid = (int)($_POST["seller_id"] ?? 0);
    if ($sid > 0) {
        if ($sa === "approve") {
            /* Approve the seller application and record the timestamp */
            $s = mysqli_prepare($conn, "UPDATE tblseller SET approval_status='approved', approved_date=NOW() WHERE seller_id=?");
        } elseif ($sa === "reject") {
            $s = mysqli_prepare($conn, "UPDATE tblseller SET approval_status='rejected' WHERE seller_id=?");
        }
        if (isset($s) && $s) {
            mysqli_stmt_bind_param($s, "i", $sid);
            mysqli_stmt_execute($s);
            mysqli_stmt_close($s);
        }
    }
    header("Location: admin.php?tab=sellers&msg=seller_updated"); exit;
}

/* ── LOAD DATA FOR ACTIVE TAB ── */

/* Users (always needed for stats) */
$filter = $_GET["filter"] ?? "all";
if (!in_array($filter, ["all","pending","active"], true)) $filter = "all";
$statsQ = mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(status='active') AS active, SUM(status<>'active' OR status IS NULL) AS pending FROM tblUser");
$stats  = mysqli_fetch_assoc($statsQ) ?: ["total"=>0,"active"=>0,"pending"=>0];

$where = $filter === "pending" ? " WHERE status<>'active' OR status IS NULL"
       : ($filter === "active" ? " WHERE status='active'" : "");
$users = [];
$uResult = mysqli_query($conn, "SELECT user_id,name,email,username,status FROM tblUser{$where} ORDER BY user_id DESC");
if ($uResult) while ($r = mysqli_fetch_assoc($uResult)) $users[] = $r;

/* Products */
$products = [];
$pResult = mysqli_query($conn,
    "SELECT p.product_id, p.name, p.brand, p.description, p.price, p.stock, p.image,
            COALESCE(s.brand_name,'Admin') AS seller_name
     FROM tblclothes p
     LEFT JOIN tblseller s ON p.seller_id = s.seller_id
     ORDER BY p.created_at DESC"
);
if ($pResult) while ($r = mysqli_fetch_assoc($pResult)) $products[] = $r;

/* Sellers */
$sellerFilter = $_GET["sf"] ?? "all";
if (!in_array($sellerFilter, ["all","pending","approved","rejected"], true)) $sellerFilter = "all";
$swhere = $sellerFilter !== "all" ? " WHERE s.approval_status='" . mysqli_real_escape_string($conn,$sellerFilter) . "'" : "";
$sellers = [];
$sResult = mysqli_query($conn,
    "SELECT s.seller_id, s.brand_name, s.phone, s.approval_status, s.created_date,
            u.name AS user_name, u.email AS user_email
     FROM tblseller s
     JOIN tblUser u ON s.user_id=u.user_id
     {$swhere}
     ORDER BY s.created_date DESC"
);
if ($sResult) while ($r = mysqli_fetch_assoc($sResult)) $sellers[] = $r;

/* Global stat counts */
$totalProducts = count($products);
$pendingSellers = count(array_filter($sellers, fn($s) => $s["approval_status"] === "pending"));
$totalOrdersQ = mysqli_query($conn, "SELECT COUNT(*) AS c FROM tblorder");
$totalOrders = $totalOrdersQ ? (int)(mysqli_fetch_assoc($totalOrdersQ)["c"] ?? 0) : 0;

/* Flash message map – maps URL ?msg= param to human-readable text */
$msgMap = [
    "user_added"      => "User added successfully.",
    "user_updated"    => "User updated successfully.",
    "user_deleted"    => "User deleted successfully.",
    "user_verified"   => "User verified and can now log in.",
    "user_has_orders" => "Cannot delete user – they have existing orders.",
    "product_added"  => "Product added successfully.",
    "product_updated"=> "Product updated successfully.",
    "product_deleted"=> "Product deleted successfully.",
    "seller_updated" => "Seller status updated.",
];
$flash = $msgMap[$_GET["msg"] ?? ""] ?? "";

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pastimes · Admin Console</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    :root{
      --bg:#f9f7f4;--white:#fff;--text:#1e2a2a;--sec:#5a6e6e;--muted:#8a9b9b;
      --green:#2a6b5e;--dark:#1e5247;--rust:#c26743;--gold:#d4a259;
      --border:#e8e6e1;--sm:0 4px 12px rgba(0,0,0,.03);--md:0 12px 28px rgba(0,0,0,.06);
      --lg:24px;--md-r:16px;--sm-r:12px
    }
    body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding:1.2rem}
    .page{max-width:1200px;margin:0 auto}
    /* top bar */
    .top-bar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;background:var(--white);border:1px solid var(--border);border-radius:var(--lg);padding:.9rem 1rem;box-shadow:var(--sm);margin-bottom:1rem}
    .logo-area{display:flex;align-items:center;gap:10px;text-decoration:none}
    .logo-icon{width:44px;height:44px;background:linear-gradient(135deg,var(--green),#3a8a7a);border-radius:14px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.2rem}
    .logo-text{font-size:1.45rem;font-weight:700;color:var(--green)}
    .badge{background:#f6efe0;color:#7a5a2a;font-size:.68rem;font-weight:600;padding:4px 10px;border-radius:30px;margin-left:8px}
    .nav{display:flex;gap:8px;flex-wrap:wrap}
    .nav a{text-decoration:none;background:var(--green);color:#fff;border-radius:40px;padding:8px 14px;font-size:.82rem;font-weight:600;transition:.2s;border:1px solid transparent}
    .nav a:hover{background:var(--dark)}
    .nav a.secondary{background:transparent;color:var(--sec);border-color:var(--border)}
    /* flash */
    .flash{margin-bottom:.9rem;border-radius:var(--sm-r);background:#e8f7ef;color:#1b6f4e;padding:10px 14px;font-size:.82rem;border-left:3px solid #1b6f4e}
    /* stat grid */
    .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:.8rem;margin-bottom:1rem}
    .stat{background:var(--white);border:1px solid var(--border);border-radius:var(--md-r);padding:.9rem;box-shadow:var(--sm)}
    .stat-label{font-size:.75rem;color:var(--muted);margin-bottom:.2rem}
    .stat-value{font-size:1.4rem;font-weight:700;color:var(--green)}
    /* tabs */
    .tab-bar{display:flex;gap:8px;margin-bottom:1rem;flex-wrap:wrap}
    .tab-link{text-decoration:none;padding:8px 16px;border-radius:40px;border:1px solid var(--border);color:var(--sec);font-size:.82rem;font-weight:600;background:#fff;transition:.2s}
    .tab-link.active{background:var(--green);color:#fff;border-color:var(--green)}
    /* card */
    .card{background:var(--white);border:1px solid var(--border);border-radius:var(--lg);box-shadow:var(--md);overflow:hidden}
    .card-head{padding:1rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:.8rem;flex-wrap:wrap}
    .card-head h2{font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:8px}
    .filters{display:flex;gap:8px;flex-wrap:wrap}
    .filters a{text-decoration:none;font-size:.78rem;padding:6px 12px;border-radius:30px;border:1px solid var(--border);color:var(--sec);background:#fff;font-weight:600}
    .filters a.active{background:var(--green);border-color:var(--green);color:#fff}
    /* table */
    table{width:100%;border-collapse:collapse}
    th,td{text-align:left;padding:.75rem 1rem;border-bottom:1px solid var(--border);font-size:.85rem;vertical-align:middle}
    th{color:var(--sec);font-weight:600;background:#fcfbf9;font-size:.78rem}
    .status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:4px 10px;font-size:.72rem;font-weight:700;background:#fff2e7;color:#a4532f;border:1px solid #f0d3bf}
    .status.active{background:#e6f4f0;color:#1b6f4e;border-color:#c3dfd5}
    .status.approved{background:#e6f4f0;color:#1b6f4e;border-color:#c3dfd5}
    .status.rejected{background:#fdecea;color:#9b2a2a;border-color:#f5b7b7}
    .btn{display:inline-flex;align-items:center;gap:6px;border:none;border-radius:30px;padding:7px 12px;font-size:.75rem;font-weight:700;cursor:pointer;transition:.2s}
    .btn-green{background:var(--green);color:#fff}
    .btn-green:hover{background:var(--dark)}
    .btn-rust{background:var(--rust);color:#fff}
    .btn-rust:hover{background:#a4542f}
    .btn-outline{background:transparent;border:1px solid var(--border);color:var(--sec)}
    .btn-outline:hover{background:var(--bg)}
    .empty{padding:1.5rem;color:var(--muted);font-size:.86rem;text-align:center}
    .empty i{display:block;font-size:2rem;margin-bottom:.5rem;opacity:.3}
    .muted{color:var(--muted);font-size:.78rem}
    .prod-thumb{width:40px;height:40px;border-radius:8px;object-fit:cover;background:#edece4}
    /* modal */
    .modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:1000}
    .modal.open{display:flex}
    .modal-box{background:#fff;max-width:520px;width:90%;border-radius:var(--lg);padding:2rem;max-height:90vh;overflow-y:auto}
    .modal-box h3{margin-bottom:1.2rem}
    label{font-size:.82rem;font-weight:600;color:var(--sec);display:block;margin-bottom:4px}
    input[type=text],input[type=number],textarea,input[type=file],select{width:100%;padding:9px 12px;margin-bottom:14px;border:1px solid var(--border);border-radius:var(--sm-r);font-size:.88rem;font-family:inherit}
    input[type=file]{padding:6px}
    .modal-btns{display:flex;gap:10px;justify-content:flex-end;margin-top:.5rem}
    @media(max-width:700px){th,td{font-size:.78rem;padding:.6rem .7rem}.stats{grid-template-columns:repeat(2,1fr)}}
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
      <a href="shop.php">Shop</a>
      <a href="admin_orders.php">Orders</a>
      <a href="admin_messages.php">Messages</a>
      <a class="secondary" href="logout.php">Logout</a>
    </nav>
  </header>

  <?php if ($flash): ?>
    <div class="flash"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($flash); ?></div>
  <?php endif; ?>

  <!-- Global stats -->
  <section class="stats">
    <article class="stat"><div class="stat-label">Total users</div><div class="stat-value"><?php echo (int)$stats["total"]; ?></div></article>
    <article class="stat"><div class="stat-label">Pending users</div><div class="stat-value"><?php echo (int)$stats["pending"]; ?></div></article>
    <article class="stat"><div class="stat-label">Products</div><div class="stat-value"><?php echo $totalProducts; ?></div></article>
    <article class="stat"><div class="stat-label">Pending sellers</div><div class="stat-value"><?php echo $pendingSellers; ?></div></article>
    <article class="stat"><div class="stat-label">Orders</div><div class="stat-value"><?php echo $totalOrders; ?></div></article>
  </section>

  <!-- Tab navigation -->
  <nav class="tab-bar">
    <a class="tab-link <?php echo $activeTab==='users'?'active':''; ?>" href="admin.php?tab=users"><i class="fas fa-users"></i> Users</a>
    <a class="tab-link <?php echo $activeTab==='products'?'active':''; ?>" href="admin.php?tab=products"><i class="fas fa-tshirt"></i> Products</a>
    <a class="tab-link <?php echo $activeTab==='sellers'?'active':''; ?>" href="admin.php?tab=sellers"><i class="fas fa-store"></i> Sellers</a>
    <a class="tab-link" href="admin_orders.php"><i class="fas fa-receipt"></i> Orders</a>
    <a class="tab-link" href="admin_messages.php"><i class="fas fa-envelope"></i> Messages</a>
  </nav>

  <!-- ═══════════ USERS TAB ═══════════ -->
  <?php if ($activeTab === "users"): ?>
  <section class="card">
    <div class="card-head">
      <h2><i class="fas fa-user-shield"></i> User Management</h2>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <!-- Status filter links -->
        <div class="filters">
          <a class="<?php echo $filter==='all'?'active':''; ?>" href="admin.php?tab=users&filter=all">All</a>
          <a class="<?php echo $filter==='pending'?'active':''; ?>" href="admin.php?tab=users&filter=pending">Pending</a>
          <a class="<?php echo $filter==='active'?'active':''; ?>" href="admin.php?tab=users&filter=active">Active</a>
        </div>
        <!-- Button to open the Add User modal -->
        <button class="btn btn-green" id="btnAddUser"><i class="fas fa-user-plus"></i> Add User</button>
      </div>
    </div>
    <?php if (!$users): ?>
      <div class="empty"><i class="fas fa-users"></i>No users found for this filter.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u):
          /* Determine if this user's account is currently active */
          $isActive = (($u["status"] ?? "") === "active");
        ?>
        <tr>
          <td class="muted"><?php echo (int)$u["user_id"]; ?></td>
          <td><?php echo htmlspecialchars($u["name"] ?? ""); ?></td>
          <td><?php echo htmlspecialchars($u["username"] ?? ""); ?></td>
          <td><?php echo htmlspecialchars($u["email"] ?? ""); ?></td>
          <td>
            <!-- Colour-coded status badge: green = active, amber = pending -->
            <span class="status <?php echo $isActive?'active':''; ?>">
              <i class="fas <?php echo $isActive?'fa-circle-check':'fa-hourglass-half'; ?>"></i>
              <?php echo $isActive ? 'Active' : 'Pending'; ?>
            </span>
          </td>
          <td style="white-space:nowrap">
            <?php if (!$isActive): ?>
              <!-- Verify button: sets status='active' so the user can log in -->
              <form method="POST" style="display:inline" action="admin.php?tab=users&filter=<?php echo urlencode($filter); ?>">
                <input type="hidden" name="verify_user_id" value="<?php echo (int)$u["user_id"]; ?>">
                <button type="submit" class="btn btn-green"><i class="fas fa-check"></i> Verify</button>
              </form>
            <?php endif; ?>
            <!-- Edit user button: opens the Edit User modal pre-filled with this row's data -->
            <button class="btn btn-outline btn-edit-user"
              data-id="<?php echo (int)$u["user_id"]; ?>"
              data-name="<?php echo htmlspecialchars($u["name"] ?? "", ENT_QUOTES); ?>"
              data-email="<?php echo htmlspecialchars($u["email"] ?? "", ENT_QUOTES); ?>"
              data-username="<?php echo htmlspecialchars($u["username"] ?? "", ENT_QUOTES); ?>"
              data-status="<?php echo htmlspecialchars($u["status"] ?? "pending", ENT_QUOTES); ?>">
              <i class="fas fa-edit"></i> Edit
            </button>
            <!-- Delete user form: asks for confirmation before removing the record -->
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user permanently?')">
              <input type="hidden" name="user_action" value="delete_user">
              <input type="hidden" name="user_id" value="<?php echo (int)$u["user_id"]; ?>">
              <button type="submit" class="btn btn-rust"><i class="fas fa-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ═══════════ PRODUCTS TAB ═══════════ -->
  <?php if ($activeTab === "products"): ?>
  <section class="card">
    <div class="card-head">
      <h2><i class="fas fa-tshirt"></i> Product Management</h2>
      <button class="btn btn-green" id="btnAddProd"><i class="fas fa-plus"></i> Add Product</button>
    </div>
    <?php if (!$products): ?>
      <div class="empty"><i class="fas fa-tshirt"></i>No products in the catalogue yet.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Image</th><th>Product</th><th>Brand</th><th>Price</th><th>Stock</th><th>Seller</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($products as $p):
          $hasImg = !empty($p["image"]) && $p["image"] !== "placeholder-clothing.jpg"
                    && file_exists(__DIR__ . "/../uploads/" . $p["image"]);
        ?>
        <tr>
          <td>
            <?php if ($hasImg): ?>
              <img class="prod-thumb" src="../uploads/<?php echo htmlspecialchars($p["image"]); ?>" alt="">
            <?php else: ?>
              <div class="prod-thumb" style="display:flex;align-items:center;justify-content:center;color:var(--green)"><i class="fas fa-tshirt"></i></div>
            <?php endif; ?>
          </td>
          <td><strong><?php echo htmlspecialchars($p["name"]); ?></strong></td>
          <td><?php echo htmlspecialchars($p["brand"] ?? "—"); ?></td>
          <td>$<?php echo number_format((float)$p["price"], 2); ?></td>
          <td><?php echo (int)$p["stock"]; ?></td>
          <td><?php echo htmlspecialchars($p["seller_name"]); ?></td>
          <td style="white-space:nowrap">
            <button class="btn btn-outline btn-edit-prod"
              data-id="<?php echo (int)$p["product_id"]; ?>"
              data-name="<?php echo htmlspecialchars($p["name"],ENT_QUOTES); ?>"
              data-brand="<?php echo htmlspecialchars($p["brand"]??"",ENT_QUOTES); ?>"
              data-desc="<?php echo htmlspecialchars($p["description"]??"",ENT_QUOTES); ?>"
              data-price="<?php echo (float)$p["price"]; ?>"
              data-stock="<?php echo (int)$p["stock"]; ?>">
              <i class="fas fa-edit"></i> Edit
            </button>
            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this product?')">
              <input type="hidden" name="product_action" value="delete_product">
              <input type="hidden" name="product_id" value="<?php echo (int)$p["product_id"]; ?>">
              <button type="submit" class="btn btn-rust"><i class="fas fa-trash"></i> Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <!-- ═══════════ SELLERS TAB ═══════════ -->
  <?php if ($activeTab === "sellers"): ?>
  <section class="card">
    <div class="card-head">
      <h2><i class="fas fa-store"></i> Seller Applications</h2>
      <div class="filters">
        <a class="<?php echo $sellerFilter==='all'?'active':''; ?>" href="admin.php?tab=sellers&sf=all">All</a>
        <a class="<?php echo $sellerFilter==='pending'?'active':''; ?>" href="admin.php?tab=sellers&sf=pending">Pending</a>
        <a class="<?php echo $sellerFilter==='approved'?'active':''; ?>" href="admin.php?tab=sellers&sf=approved">Approved</a>
        <a class="<?php echo $sellerFilter==='rejected'?'active':''; ?>" href="admin.php?tab=sellers&sf=rejected">Rejected</a>
      </div>
    </div>
    <?php if (!$sellers): ?>
      <div class="empty"><i class="fas fa-store"></i>No seller applications found.</div>
    <?php else: ?>
    <table>
      <thead><tr><th>Brand</th><th>Owner</th><th>Email</th><th>Phone</th><th>Applied</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($sellers as $s): ?>
        <tr>
          <td><strong><?php echo htmlspecialchars($s["brand_name"]); ?></strong></td>
          <td><?php echo htmlspecialchars($s["user_name"]); ?></td>
          <td><?php echo htmlspecialchars($s["user_email"]); ?></td>
          <td><?php echo htmlspecialchars($s["phone"] ?? "—"); ?></td>
          <td><?php echo date("d M Y", strtotime($s["created_date"])); ?></td>
          <td>
            <span class="status <?php echo htmlspecialchars($s["approval_status"]); ?>">
              <?php echo ucfirst(htmlspecialchars($s["approval_status"])); ?>
            </span>
          </td>
          <td style="white-space:nowrap">
            <?php if ($s["approval_status"] !== "approved"): ?>
              <!-- Approve the seller so they can list products -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="seller_action" value="approve">
                <input type="hidden" name="seller_id" value="<?php echo (int)$s["seller_id"]; ?>">
                <button type="submit" class="btn btn-green"><i class="fas fa-check"></i> Approve</button>
              </form>
            <?php endif; ?>
            <?php if ($s["approval_status"] !== "rejected"): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="seller_action" value="reject">
                <input type="hidden" name="seller_id" value="<?php echo (int)$s["seller_id"]; ?>">
                <button type="submit" class="btn btn-rust" onclick="return confirm('Reject this seller?')"><i class="fas fa-times"></i> Reject</button>
              </form>
            <?php endif; ?>
            <?php if ($s["approval_status"] === "approved" && $s["approval_status"] === "rejected"): ?>
              <span class="muted">No action</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>

<!-- Add User Modal -->
<div id="modalAddUser" class="modal">
  <form class="modal-box" method="POST" action="admin.php?tab=users">
    <h3><i class="fas fa-user-plus"></i> Add User</h3>
    <input type="hidden" name="user_action" value="add_user">
    <label>Full Name *</label>
    <input type="text" name="uname" placeholder="Jane Doe" required>
    <label>Email *</label>
    <input type="text" name="uemail" placeholder="jane@example.com" required>
    <label>Username *</label>
    <input type="text" name="uuser" placeholder="jane123" required>
    <label>Password * (min 4 chars)</label>
    <input type="password" name="upass" placeholder="Password" required>
    <label>Account Status</label>
    <select name="ustatus">
      <option value="active">Active – can log in immediately</option>
      <option value="pending">Pending – requires verification</option>
    </select>
    <div class="modal-btns">
      <button type="button" class="btn btn-outline" onclick="closeModal('modalAddUser')">Cancel</button>
      <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Add User</button>
    </div>
  </form>
</div>

<!-- Edit User Modal -->
<div id="modalEditUser" class="modal">
  <form class="modal-box" method="POST" action="admin.php?tab=users">
    <h3><i class="fas fa-user-edit"></i> Edit User</h3>
    <input type="hidden" name="user_action" value="edit_user">
    <input type="hidden" name="user_id" id="editUserId">
    <label>Full Name *</label>
    <input type="text" name="uname" id="editUserName" required>
    <label>Email *</label>
    <input type="text" name="uemail" id="editUserEmail" required>
    <label>Username *</label>
    <input type="text" name="uuser" id="editUserUsername" required>
    <label>New Password (leave blank to keep current)</label>
    <input type="password" name="upass" placeholder="Leave blank to keep unchanged">
    <label>Account Status</label>
    <select name="ustatus" id="editUserStatus">
      <option value="active">Active – can log in</option>
      <option value="pending">Pending – awaiting verification</option>
    </select>
    <div class="modal-btns">
      <button type="button" class="btn btn-outline" onclick="closeModal('modalEditUser')">Cancel</button>
      <button type="submit" class="btn btn-green"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </form>
</div>

<!-- Add Product Modal -->
<div id="modalAddProd" class="modal">
  <form class="modal-box" method="POST" action="admin.php?tab=products" enctype="multipart/form-data">
    <h3>Add Product</h3>
    <input type="hidden" name="product_action" value="add_product">
    <label>Product Name *</label>
    <input type="text" name="pname" placeholder="e.g. Classic Denim Jacket" required>
    <label>Brand</label>
    <input type="text" name="pbrand" placeholder="Brand name">
    <label>Description</label>
    <textarea name="pdesc" rows="3" placeholder="Product description…"></textarea>
    <label>Price ($) *</label>
    <input type="number" name="pprice" step="0.01" min="0.01" placeholder="49.99" required>
    <label>Stock *</label>
    <input type="number" name="pstock" min="0" value="10" required>
    <label>Product Image</label>
    <input type="file" name="pimage" accept="image/jpeg,image/png,image/gif,image/webp">
    <div class="modal-btns">
      <button type="button" class="btn btn-outline" onclick="closeModal('modalAddProd')">Cancel</button>
      <button type="submit" class="btn btn-green">Add Product</button>
    </div>
  </form>
</div>

<!-- Edit Product Modal -->
<div id="modalEditProd" class="modal">
  <form class="modal-box" method="POST" action="admin.php?tab=products" enctype="multipart/form-data">
    <h3>Edit Product</h3>
    <input type="hidden" name="product_action" value="edit_product">
    <input type="hidden" name="product_id" id="editProdId">
    <label>Product Name *</label>
    <input type="text" name="pname" id="editProdName" required>
    <label>Brand</label>
    <input type="text" name="pbrand" id="editProdBrand">
    <label>Description</label>
    <textarea name="pdesc" id="editProdDesc" rows="3" placeholder="Product description…"></textarea>
    <label>Price ($) *</label>
    <input type="number" name="pprice" id="editProdPrice" step="0.01" min="0.01" required>
    <label>Stock *</label>
    <input type="number" name="pstock" id="editProdStock" min="0" required>
    <label>New Image (optional)</label>
    <input type="file" name="pimage" accept="image/jpeg,image/png,image/gif,image/webp">
    <div class="modal-btns">
      <button type="button" class="btn btn-outline" onclick="closeModal('modalEditProd')">Cancel</button>
      <button type="submit" class="btn btn-green">Save Changes</button>
    </div>
  </form>
</div>

<script>
  /* Open / close any modal by its element ID */
  function openModal(id) { document.getElementById(id).classList.add('open'); }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }

  /* Close modal when clicking the dark backdrop behind it */
  document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
  });

  /* ── User modals ── */
  const btnAddUser = document.getElementById('btnAddUser');
  if (btnAddUser) btnAddUser.addEventListener('click', () => openModal('modalAddUser'));

  /* Edit user: populate modal fields from the row's data-* attributes */
  document.querySelectorAll('.btn-edit-user').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('editUserId').value       = btn.dataset.id;
      document.getElementById('editUserName').value     = btn.dataset.name;
      document.getElementById('editUserEmail').value    = btn.dataset.email;
      document.getElementById('editUserUsername').value = btn.dataset.username;
      /* Pre-select the correct status option */
      const sel = document.getElementById('editUserStatus');
      for (let i = 0; i < sel.options.length; i++) {
        sel.options[i].selected = (sel.options[i].value === btn.dataset.status);
      }
      openModal('modalEditUser');
    });
  });

  /* ── Product modals ── */
  const btnAdd = document.getElementById('btnAddProd');
  if (btnAdd) btnAdd.addEventListener('click', () => openModal('modalAddProd'));

  /* Edit product: populate modal fields from the row's data-* attributes */
  document.querySelectorAll('.btn-edit-prod').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('editProdId').value    = btn.dataset.id;
      document.getElementById('editProdName').value  = btn.dataset.name;
      document.getElementById('editProdBrand').value = btn.dataset.brand;
      document.getElementById('editProdDesc').value  = btn.dataset.desc;
      document.getElementById('editProdPrice').value = btn.dataset.price;
      document.getElementById('editProdStock').value = btn.dataset.stock;
      openModal('modalEditProd');
    });
  });
</script>
</body>
</html>
