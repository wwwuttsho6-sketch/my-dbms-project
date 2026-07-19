<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Unset and completely invalidate session cookies
$_SESSION = array();
session_destroy();

header("Location: ../index.php");
exit;
?>