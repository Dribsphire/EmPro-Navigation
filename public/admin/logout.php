<?php
require_once __DIR__ . '/../../services/Auth.php';

$auth = new Auth();
$auth->logout();

