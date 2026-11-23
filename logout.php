<?php
session_start();
if (isset($_SESSION['user_id']) && function_exists('logUserActivity')) {
	require_once 'includes/config.php';
	logUserActivity($_SESSION['user_id'], 'logout', ['source' => 'web']);
}
session_destroy();
header("Location: login.php");
exit();
?>