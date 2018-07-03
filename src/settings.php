<?php

return [
    'settings' => [
	'displayErrorDetails' => true, // set to false in production
	'addContentLengthHeader' => false, // Allow the web server to send the content-length header
	// Renderer settings
	'renderer' => [
	    'template_path' => BASE . '/templates/',
	],
	// Twig settings
	'view' => [
	    'path' => BASE . '/views/',
	    'cache' => false, // BASE . '/cache/',
	],
	// Monolog settings
	'logger' => [
	    'name' => 'slim-app',
	    'path' => isset($_ENV['docker']) ? 'php://stdout' : BASE . '/logs/app.log',
	    'level' => \Monolog\Logger::DEBUG,
	],
	// Database settings
	'db' => [
	    'host' => DATABASE\HOST,
	    'dbname' => DATABASE\DBNAME,
	    'user' => DATABASE\USER,
	    'pass' => DATABASE\PASS,
	],
    ],
];
