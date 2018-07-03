<?php

namespace App\Controllers\Database;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Description of Backup
 *
 * @author IuriiP <hardwork.mouse@gmail.com>
 */
class Backup extends \App\Controllers\Controller {

	/**
	 * @var \Psr\Container\ContainerInterface 
	 */
	private $container = null;

	/**
	 * @var \PDO
	 */
	private $db = null;

	public function __construct(\Slim\App $app)
	{
		parent::__construct($app);

		$this->container = $app->getContainer();
		$this->db = $this->container->get('db');
	}

	/**
	 * GET
	 * 
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function all(Request $request, Response $response, array $args)
	{
		return $response->withHeader('Content-Type', 'application/force-download')
				->withHeader('Content-Type', 'application/octet-stream')
				->withHeader('Content-Type', 'application/download')
				->withHeader('Content-Description', 'File Transfer')
				->withHeader('Content-Transfer-Encoding', 'binary')
				->withHeader('Content-Disposition', 'attachment; filename="backup-' . date('Y-m-d-His') . '.json"')
				->withHeader('Expires', '0')
				->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
				->withHeader('Pragma', 'public')
				->withJson(["tables" => $this->listTables()], 200);
	}

	/**
	 * @return array
	 */
	private function listTables()
	{
		$containers = [];

		$tables = $this->db->query('SHOW TABLES', \PDO::FETCH_COLUMN, 0)->fetchAll();
		foreach ($tables as $table)
		{
			$containers[$table] = [
			    'table' => $table,
			    'fields' => $this->getColumns($table),
			    'records' => $this->db->query("SELECT * FROM `$table`")->fetchAll(),
			];
		}

		return $containers;
	}

	private function getColumns($table)
	{
		$set = [];
		foreach ($this->db->query("SHOW COLUMNS IN `$table`") as $record)
		{
			$set[$record['Field']] = $record['Type'];
		}
		return $set;
	}

}
