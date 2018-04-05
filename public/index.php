<?php
session_start();
error_reporting(E_ALL);
require __DIR__ . '/../vendor/autoload.php';

$dotini = \Dotini\Dotini::set(__DIR__,false,true);
// Instantiate the app
$settings = require SOURCE. '/settings.php';
$app = new \Slim\App($settings);

// Set up dependencies
require SOURCE. '/dependencies.php';

// Register middleware
require SOURCE. '/middleware.php';

// Register routes
require SOURCE . '/routes.php';

// Run app
$app->run();
