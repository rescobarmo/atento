<?php
session_start();

define('APP_NAME', 'Atento');
define('APP_VERSION', '2.0.0');
define('APP_URL', '');

date_default_timezone_set('America/Santiago');
setlocale(LC_TIME, 'spanish');

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
