<?php

use Slim\Http\Request;
use Slim\Http\Response;

//
// Routes

$app->get('/about/tablecreate', function (Request $request, Response $response, array $args) {
	return $this->view->render($response, "about/tablecreate.twig", $args);
});
$app->get('/about[/{page}]', function (Request $request, Response $response, array $args) {
	return $this->view->render($response, "about.twig", $args);
});

/**
 * /rest
 */
$app->group('/rest', function () use ($app) {
	$app->any('/{resource}[/{id}]', new App\Controllers\Rest($app));
	$app->get('[/]', function (Request $request, Response $response, array $args) {
		return $this->view->render($response, 'rest.twig', $args);
	});
})
;

$app->group('/database', function () use ($app) {
		$app->any('/manage', new \App\Controllers\Database\Manage($app));
		$app->any('/backup', new App\Controllers\Database\Backup($app));
//		$app->any('/generate[/{id}]', new App\Controllers\Database\Generate($app));
		$app->get('[/]', function (Request $request, Response $response, array $args) {
			return $this->view->render($response, 'database.twig', $args);
		});
	})
	->add(new \App\Middleware\HttpBasicAuth('user', 'password', 'Database access needs the correct credentials'))
;


$app->get('/', function (Request $request, Response $response, array $args) {
// Sample log message
//$this->logger->info("Slim-Skeleton '/' route");
// Render index view
	return $this->view->render($response, 'main.twig', $args);
});
