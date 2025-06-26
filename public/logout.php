<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/Auth.php';

use App\Auth;

$auth = new Auth();
$auth->logout();

header('Location: index.php');
exit;