<?php
/**
 * Password Rehashing Utility
 * 
 * This script rehashes all plaintext passwords in the database
 * to use bcrypt (PASSWORD_DEFAULT) hashing.
 * 
 * Run this AFTER applying migration_phase1.sql
 * 
 * Usage: 
 *  - Browser: http://localhost/Pastimes/scripts/rehash_passwords.php
 *  - CLI: php scripts/rehash_passwords.php
 */

require_once __DIR__ . "/../config/DBConn.php";

echo "====================================================\n";
echo "PASSWORD REHASHING UTILITY\n";
echo "====================================================\n\n";

// Function to display results
function display_result($table, $result) {
    if ($result === true) {
        echo "✓ $table passwords rehashed successfully\n";
    } else {
        echo "✗ $table rehashing FAILED\n";
    }
}

// ===== REHASH TBLUSER =====
echo "Processing tbluser...\n";
echo "─────────────────────────────────────\n";

$userQuery = "SELECT user_id, username, password FROM tbluser WHERE password NOT LIKE '$2y$10$%' AND password NOT LIKE '$2y$12$%'";
$userResult = mysqli_query($conn, $userQuery);

if ($userResult) {
    $count = mysqli_num_rows($userResult);
    echo "Found $count plaintext passwords in tbluser\n\n";
    
    $rehashed = 0;
    $failed = 0;
    
    while ($row = mysqli_fetch_assoc($userResult)) {
        $userId = $row["user_id"];
        $username = $row["username"];
        $plainPassword = $row["password"];
        
        // Hash the plaintext password
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        // Update database
        $updateStmt = mysqli_prepare($conn, "UPDATE tbluser SET password = ? WHERE user_id = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $userId);
            
            if (mysqli_stmt_execute($updateStmt)) {
                echo "  ✓ User #$userId ($username) - rehashed successfully\n";
                $rehashed++;
            } else {
                echo "  ✗ User #$userId ($username) - UPDATE FAILED\n";
                $failed++;
            }
            mysqli_stmt_close($updateStmt);
        } else {
            echo "  ✗ User #$userId ($username) - PREPARE FAILED\n";
            $failed++;
        }
    }
    
    echo "\n  Summary: $rehashed rehashed, $failed failed\n";
} else {
    echo "Error querying tbluser: " . mysqli_error($conn) . "\n";
}

// ===== REHASH TBLADMIN =====
echo "\n\nProcessing tbladmin...\n";
echo "─────────────────────────────────────\n";

$adminQuery = "SELECT admin_id, username, password FROM tbladmin WHERE password NOT LIKE '$2y$10$%' AND password NOT LIKE '$2y$12$%'";
$adminResult = mysqli_query($conn, $adminQuery);

if ($adminResult) {
    $count = mysqli_num_rows($adminResult);
    echo "Found $count plaintext passwords in tbladmin\n\n";
    
    $rehashed = 0;
    $failed = 0;
    
    while ($row = mysqli_fetch_assoc($adminResult)) {
        $adminId = $row["admin_id"];
        $username = $row["username"];
        $plainPassword = $row["password"];
        
        // Hash the plaintext password
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        // Update database
        $updateStmt = mysqli_prepare($conn, "UPDATE tbladmin SET password = ? WHERE admin_id = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, "si", $hashedPassword, $adminId);
            
            if (mysqli_stmt_execute($updateStmt)) {
                echo "  ✓ Admin #$adminId ($username) - rehashed successfully\n";
                $rehashed++;
            } else {
                echo "  ✗ Admin #$adminId ($username) - UPDATE FAILED\n";
                $failed++;
            }
            mysqli_stmt_close($updateStmt);
        } else {
            echo "  ✗ Admin #$adminId ($username) - PREPARE FAILED\n";
            $failed++;
        }
    }
    
    echo "\n  Summary: $rehashed rehashed, $failed failed\n";
} else {
    echo "Error querying tbladmin: " . mysqli_error($conn) . "\n";
}

// ===== FINAL STATUS =====
echo "\n\n====================================================\n";
echo "REHASHING COMPLETE\n";
echo "====================================================\n\n";

echo "You can now log in with hashed passwords:\n";
echo "  User: john123 / 123456\n";
echo "  Admin: admin123 / admin123\n";
echo "  (Or any other existing credentials)\n\n";

echo "New registrations will use 'pending' status.\n";
echo "Admin must verify them in the admin dashboard.\n\n";

// Verify all passwords are hashed
$plainUserCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as count FROM tbluser WHERE password NOT LIKE '$2y$%'")
)["count"];

$plainAdminCount = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) as count FROM tbladmin WHERE password NOT LIKE '$2y$%'")
)["count"];

if ($plainUserCount == 0 && $plainAdminCount == 0) {
    echo "✓ All passwords have been successfully hashed!\n";
} else {
    echo "⚠ WARNING: Still found plaintext passwords:\n";
    echo "  tbluser: $plainUserCount\n";
    echo "  tbladmin: $plainAdminCount\n";
}

mysqli_close($conn);
?>
