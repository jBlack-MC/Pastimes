<?php
/* Tab isolation guard.
   Include just before </body> on every authenticated page.
   $_SESSION['user_id'] must be set before this file is included. */
$_guard_uid = (int)($_SESSION['user_id'] ?? 0);
?>
<script>
(function () {
  var KEY = 'pt_tab_uid';
  var uid = <?php echo $_guard_uid; ?>;
  if (!uid) return;
  var saved = sessionStorage.getItem(KEY);
  if (saved === null) {
    sessionStorage.setItem(KEY, String(uid));
  } else if (saved !== String(uid)) {
    sessionStorage.removeItem(KEY);
    window.location.replace('login.php?sc=1');
  }
})();
</script>
