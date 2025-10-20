<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Dotenv\Dotenv;

$env = Dotenv::createImmutable(__DIR__ . '/');
$env->load();

// protecciones si quieres
