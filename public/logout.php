<?php
require_once __DIR__ . '/../app_core/php/main.php';

session_unset();
session_destroy();

redireccionar('login.php');
