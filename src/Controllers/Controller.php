<?php

namespace App\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Description of Controller
 *
 * @author IuriiP <hardwork.mouse@gmail.com>
 */
abstract class Controller {

	/**
	 *
	 * @var \Slim\App
	 */
	protected $app = null;

	/**
	 *
	 * @var array
	 */
	protected $logged = [];

	/**
	 * @param \Slim\App $app
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * CRUD invoker
	 * 
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	final public function __invoke(Request $request, Response $response, array $args = [])
	{
		switch (strtolower($request->getMethod()))
		{
			case 'get':
				return count($args) ? $this->find($request, $response, $args) : $this->all($request, $response, []);
			case 'post':
				return $this->create($request, $response, $args);
			case 'put':
			case 'patch':
				return $this->update($request, $response, $args);
			case 'delete':
				return $this->delete($request, $response, $args);
		}
		return $this->info($request, $response, $args);
	}

	/**
	 * For not implemented operations
	 * 
	 * @param type $name
	 * @param type $arguments
	 * @return type
	 */
	public function __call($name, $arguments)
	{
		return $this->error_406(...$arguments);
	}

	private function error_406(Request $request, Response $response, array $args)
	{
		return $response->withStatus(406);
	}

	protected function log($class = '', $type = '', $text = '')
	{
		if ($class)
		{
			$this->logged[] = ['type' => $type,
			    'class' => "alert alert-{$class}",
			    'text' => $text,
			];
		}

		return $this->logged;
	}

}
