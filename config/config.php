<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', $_ENV['DB_NAME']);
define('API_EXCHANGE_URL', $_ENV['API_EXCHANGE_URL']);
define('MAIL_FROM', $_ENV['MAIL_FROM']);
define('SENDGRID_API_KEY', $_ENV['SENDGRID_API_KEY']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}