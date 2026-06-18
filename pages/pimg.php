<?php
/* Dynamic SVG product image generator.
   Usage: <img src="pimg.php?id=3" alt="...">
   Returns a styled SVG with gradient, decorative shapes, initial letter,
   product name and brand — visible immediately without any uploaded file. */
session_start();
require_once __DIR__ . '/../config/DBConn.php';

$id    = (int)($_GET['id'] ?? 0);
$name  = 'Product';
$brand = 'Pastimes';

if ($id > 0) {
    $stmt = mysqli_prepare($conn,
        "SELECT name, COALESCE(brand,'Pastimes') AS brand FROM tblclothes WHERE product_id = ? LIMIT 1"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);
        if ($row) { $name = $row['name']; $brand = $row['brand']; }
    }
}
mysqli_close($conn);

/* One palette per product slot — rotates for products beyond 7 */
$palettes = [
    ['from' => '#2a6b5e', 'to' => '#1a4a40', 'spot' => '#5fbfaa'],
    ['from' => '#c26743', 'to' => '#8b3e20', 'spot' => '#e8956d'],
    ['from' => '#4a6fa5', 'to' => '#2d4a78', 'spot' => '#7aaad4'],
    ['from' => '#9b6a1a', 'to' => '#6b430c', 'spot' => '#d4a644'],
    ['from' => '#7a4a8a', 'to' => '#4a2a60', 'spot' => '#b88ad4'],
    ['from' => '#2d7a8a', 'to' => '#1a5060', 'spot' => '#5ab4c8'],
    ['from' => '#7a4a3a', 'to' => '#4a2a1a', 'spot' => '#c4806a'],
];

$c       = $palettes[($id > 0 ? ($id - 1) : 0) % count($palettes)];
$initial = htmlspecialchars(mb_strtoupper(mb_substr($name, 0, 1)));
$label   = htmlspecialchars(mb_strlen($name) > 22 ? mb_substr($name, 0, 22) . '…' : $name);
$sub     = htmlspecialchars(mb_strlen($brand) > 24 ? mb_substr($brand, 0, 24) . '…' : $brand);
$f       = htmlspecialchars($c['from']);
$t       = htmlspecialchars($c['to']);
$s       = htmlspecialchars($c['spot']);

header('Content-Type: image/svg+xml');
header('Cache-Control: public, max-age=86400');
echo <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="400" height="440" viewBox="0 0 400 440">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="{$f}"/>
      <stop offset="100%" stop-color="{$t}"/>
    </linearGradient>
    <clipPath id="clip"><rect width="400" height="440" rx="0"/></clipPath>
  </defs>

  <!-- Background -->
  <rect width="400" height="440" fill="url(#bg)"/>

  <!-- Decorative spots -->
  <circle cx="360" cy="60"  r="130" fill="{$s}" opacity="0.18"/>
  <circle cx="50"  cy="400" r="100" fill="{$s}" opacity="0.12"/>
  <circle cx="200" cy="200" r="80"  fill="white" opacity="0.04"/>

  <!-- Subtle grid texture lines -->
  <line x1="0" y1="140" x2="400" y2="140" stroke="white" stroke-width="0.5" opacity="0.08"/>
  <line x1="0" y1="280" x2="400" y2="280" stroke="white" stroke-width="0.5" opacity="0.08"/>
  <line x1="140" y1="0" x2="140" y2="440" stroke="white" stroke-width="0.5" opacity="0.08"/>
  <line x1="260" y1="0" x2="260" y2="440" stroke="white" stroke-width="0.5" opacity="0.08"/>

  <!-- Central initial -->
  <text x="200" y="230"
    font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif"
    font-size="160" font-weight="900" fill="white" opacity="0.92"
    text-anchor="middle" dominant-baseline="middle">{$initial}</text>

  <!-- Bottom label area -->
  <rect x="0" y="330" width="400" height="110" fill="rgba(0,0,0,0.38)"/>

  <!-- Thin accent line above label -->
  <rect x="0" y="330" width="400" height="2" fill="{$s}" opacity="0.7"/>

  <text x="200" y="368"
    font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif"
    font-size="20" font-weight="700" fill="white"
    text-anchor="middle">{$label}</text>

  <text x="200" y="398"
    font-family="-apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif"
    font-size="14" font-weight="400" fill="rgba(255,255,255,0.72)"
    text-anchor="middle">{$sub}</text>
</svg>
SVG;
?>
