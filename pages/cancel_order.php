<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . "/../config/DBConn.php";

$user_id = (int)$_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST["order_id"])) {
    header("Location: my_orders.php");
    exit;
}

$order_id = (int)$_POST["order_id"];

/* Verify the order belongs to this user and is still pending */
$chk = mysqli_prepare($conn,
    "SELECT order_id FROM tblorder WHERE order_id = ? AND user_id = ? AND status = 'pending'"
);
if ($chk) {
    mysqli_stmt_bind_param($chk, "ii", $order_id, $user_id);
    mysqli_stmt_execute($chk);
    $exists = mysqli_num_rows(mysqli_stmt_get_result($chk)) > 0;
    mysqli_stmt_close($chk);

    if ($exists) {
        /* Restore stock for every item in the order */
        $lines = mysqli_prepare($conn,
            "SELECT product_id, quantity FROM tblorderline WHERE order_id = ?"
        );
        if ($lines) {
            mysqli_stmt_bind_param($lines, "i", $order_id);
            mysqli_stmt_execute($lines);
            $lResult = mysqli_stmt_get_result($lines);
            while ($line = mysqli_fetch_assoc($lResult)) {
                $restore = mysqli_prepare($conn,
                    "UPDATE tblclothes SET stock = stock + ? WHERE product_id = ?"
                );
                if ($restore) {
                    mysqli_stmt_bind_param($restore, "ii", $line["quantity"], $line["product_id"]);
                    mysqli_stmt_execute($restore);
                    mysqli_stmt_close($restore);
                }
            }
            mysqli_stmt_close($lines);
        }

        $cancel = mysqli_prepare($conn,
            "UPDATE tblorder SET status = 'cancelled' WHERE order_id = ? AND user_id = ?"
        );
        if ($cancel) {
            mysqli_stmt_bind_param($cancel, "ii", $order_id, $user_id);
            mysqli_stmt_execute($cancel);
            mysqli_stmt_close($cancel);
        }

        header("Location: my_orders.php?msg=cancelled");
        exit;
    }
}

header("Location: my_orders.php?err=cannot_cancel");
exit;
