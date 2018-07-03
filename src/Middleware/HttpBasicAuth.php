<?php

namespace App\Middleware;

/**
 * HTTP Basic Authentication
 *
 * Use this middleware with your Slim Framework application
 * to require HTTP basic auth for route
 *
 */
use Slim\Http\Request;
use Slim\Http\Response;

class HttpBasicAuth {

	/**
	 * @var string
	 */
	protected $username;

	/**
	 * @var string
	 */
	protected $password;

	/**
	 * @var string
	 */
	protected $realm;

	/**
	 * @param   string  $username   The HTTP Authentication username
	 * @param   string  $password   The HTTP Authentication password
	 * @param   string  $realm      The HTTP Authentication realm
	 */
	public function __construct($username, $password, $realm = 'Protected Area')
	{
		$this->username = $username;
		$this->password = $password;
		$this->realm = $realm;
	}

	/**
	 * This method will check the HTTP request headers for previous authentication. If
	 * the request has already authenticated, the next middleware is called. Otherwise,
	 * a 401 Authentication Required response is returned to the client.
	 */
	public function __invoke(Request $request, Response $response, $next = null)
	{
		$authUser = $request->getHeaderLine('PHP_AUTH_USER');
		$authPass = $request->getHeaderLine('PHP_AUTH_PW');
		if ($authUser && $authPass && $this->username === $authUser && $this->password === $authPass)
		{
			return $next ? $next($request, $response) : $response;
		}
		return $response
				->withStatus(401)
				->withHeader('WWW-Authenticate', sprintf('Basic realm="%s"', $this->realm));
	}

}
