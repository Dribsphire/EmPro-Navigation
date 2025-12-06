<?php
// Prevent caching - force browser to not cache this page
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../services/Auth.php';

$auth = new Auth();
$auth->logout();

