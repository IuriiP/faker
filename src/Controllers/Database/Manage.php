<?php

namespace App\Controllers\Database;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Description of Manage
 *
 * @author IuriiP <hardwork.mouse@gmail.com>
 */
class Manage extends \App\Controllers\Controller {

	/**
	 * @var \Psr\Container\ContainerInterface 
	 */
	private $container = null;

	/**
	 * @var \PDO
	 */
	private $db = null;

	/**
	 *
	 * @var array 
	 */
	private $types = [
	    'set' => 'VARCHAR(64)',
	    'link' => 'INT',
	    'auto' => 'INT AUTO_INCREMENT',
	    'unique' => 'VARCHAR(64)',
	    'randomNumber' => 'INT',
	    'word' => 'VARCHAR(64)',
	    'sentence' => 'TEXT',
	    'paragraph' => 'TEXT',
	    'text' => 'TEXT',
	    'realText' => 'TEXT',
	    'company' => 'TINYTEXT',
	    'phone' => 'VARCHAR(31)',
	    'age' => 'DATE',
	    'date' => 'DATE',
	    'time' => 'TIME',
	    'datetime' => 'DATETIME',
	    'email' => 'VARCHAR(256)',
	    'gender' => "SET('F','M')",
	    'title' => 'VARCHAR(16)',
	    'first' => 'VARCHAR(32)',
	    'last' => 'VARCHAR(32)',
	    'login' => 'VARCHAR(32)',
	    'name' => 'VARCHAR(128)',
	    'country' => 'VARCHAR(64)',
	    'stateAbbr' => 'VARCHAR(4)',
	    'state' => 'VARCHAR(32)',
	    'city' => 'VARCHAR(32)',
	    'streetAddr' => 'VARCHAR(64)',
	    'address' => 'VARCHAR(256)',
	    'creditType' => 'VARCHAR(16)',
	    'creditNumber' => 'CHAR(16)',
	    'creditDate' => 'VARCHAR(5)',
	    'creditName' => 'VARCHAR(64)',
	];
	private $fields = [];
	private $faker = null;

	public function __construct(\Slim\App $app)
	{
		parent::__construct($app);
		$this->faker = \Faker\Factory::create();

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
		/**
		 * With parameters
		 */
		if ($drop = $request->getParam('drop'))
		{
			if (preg_match('/^[A-z][A-z0-9_]*$/', $drop))
			{
				$this->db->exec("DROP TABLE `$drop`");
				$einfo = $this->db->errorInfo();
				if (isset($einfo[2]))
				{
					$this->log('danger', 'Database error', $einfo[2]);
				}
				else
				{
					$this->log('success', 'Successfully', "Table `$drop` dropped");
				}
			}
			else
			{
				$this->log('danger', 'Request error', 'Invalid table name');
			}
		}

		return $this->container->get('view')->render($response, 'database.manage.twig', [
			    'containers' => $this->listTables(),
			    'logs' => $this->log(),
		]);
	}

	/**
	 * POST
	 * 
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function create(Request $request, Response $response, array $args)
	{
		/**
		 * With parameters
		 */
		$types = $request->getParam('type');
		$names = $request->getParam('field');
		$from = $request->getParam('from');
		$till = $request->getParam('till');

		/**
		 * Fields
		 */
		foreach ($types as $i => $type)
		{
			if ($type)
			{
				$this->fields[$names[$i] ?: $type] = [
				    $this->types[$type],
				    $type,
				    $from[$i] ?: 0,
				    $till[$i] ?: 0
				];
			}
		}

		$table = $request->getParam('table');
		$this->createTable($table);

		$rows = $request->getParam('rows');
		$records = [];
		for ($i = 0; $i < $rows; $i++)
		{
			$records[] = $this->generate();
		}
		$this->fillTable($table, $records);

		return $this->container->get('view')->render($response, 'database.manage.twig', [
			    'containers' => $this->listTables(),
			    'logs' => $this->log(),
		]);
	}

	/**
	 * PUT
	 * 
	 * @param Request $request
	 * @param Response $response
	 * @param array $args
	 * @return Response
	 */
	public function update(Request $request, Response $response, array $args)
	{
		$files = $request->getUploadedFiles();
		if (isset($files['json']))
		{
			try {
				$json = json_decode($files['json']->getStream()->getContents(), true);
				if (!$json)
				{
					return $response->withStatus(422);
				}
			} catch (Exception $e) {
				return $response->withStatus(415);
			}
		}
		else
		{
			return $response->withStatus(406);
		}

		if (isset($json['tables']))
		{
			// multiple restore
			foreach ($json['tables'] as $table)
			{
				$this->restoreTable($table);
			}
		}
		else
		{
			$this->restoreTable($json);
		}

		return $this->container->get('view')->render($response, 'database.manage.twig', [
			    'containers' => $this->listTables(),
			    'logs' => $this->log(),
		]);
	}

	private function restoreTable($json)
	{

		try {
			$table = $json['table'];
			$fields = $json['fields'];
			$records = $json['records'];
		} catch (Exception $exc) {
			return $response->withStatus(422);
		}

		/**
		 * Fields
		 */
		foreach ($fields as $name => $type)
		{
			$this->fields[$name] = [
			    $type,
			    $type,
			    0,
			    0
			];
		}

		$this->createTable($table);
		$this->fillTable($table, $records);
	}

	/**
	 * 
	 * @param \PDO $db
	 * @return array
	 */
	private function listTables()
	{
		/**
		 * Existed tables
		 */
		$containers = [];
		foreach ($this->db->query('SHOW TABLES', \PDO::FETCH_COLUMN, 0) as $table)
		{
			$containers[$table] = [
			    'header' => $table,
			    'data' => $this->db->query("DESCRIBE `$table`", \PDO::FETCH_COLUMN, 0),
			    'actions' => [
				['name' => 'Delete',
				    'tag' => 'a',
				    'data' => [
					'href' => "?drop={$table}",
					'data-toggle' => 'confirmation',
					'data-title' => 'Drop table?',
					'data-content' => "This might be dangerous",
				    ],
				],
			    ],
			];
		}

		/**
		 * Create new table
		 */
		$containers[] = [
		    'header' => 'New...',
		    'data' => 'Create new table',
		    'actions' => [
			['name' => 'Create...',
			    'tag' => 'button',
			    'data' => [
				'type' => 'button',
				'data-toggle' => 'modal',
				'data-target' => '#modalCreate'
			    ],
			],
			['name' => 'Load',
			    'tag' => 'button',
			    'data' => [
				'type' => 'button',
				'data-toggle' => 'modal',
				'data-target' => '#modalLoad'
			    ],
			],
		    ],
		];

		return $containers;
	}

	/**
	 * 
	 * @param string $name
	 * @param int $rows
	 */
	private function createTable($name)
	{
		$flds = array_map(function($k, $v) {
			return "`{$k}` {$v[0]} COMMENT '{$v[1]},{$v[2]},{$v[3]}'";
		}, array_keys($this->fields), $this->fields);

		if (!isset($this->fields['id']))
		{
			array_unshift($flds, "`id` INT NOT NULL AUTO_INCREMENT COMMENT 'auto,0,0'");
		}

		$this->db->exec("CREATE TABLE `{$name}` (" . join(",\n", $flds) . ",\nPRIMARY KEY (`id`)) ENGINE = InnoDB");
		$einfo = $this->db->errorInfo();
		if (isset($einfo[2]))
		{
			$this->log('danger', 'Database error', $einfo[2]);
		}
		else
		{
			$this->log('warning', 'Successfully', "Table `$name` created");
		}
	}

	private function fillTable(string $name, array $set)
	{
		$insert = $this->db->prepare("INSERT INTO `$name` (`"
			. join('`,`', array_keys($this->fields))
			. '`) VALUES (:'
			. join(',:', array_keys($this->fields))
			. ')');
		foreach ($set as $value)
		{
			if (!$insert->execute($value))
			{
				$einfo = $this->db->errorInfo();
				if (isset($einfo[2]))
				{
					$this->log('danger', 'Database error', $einfo[2]);
				}
			}
		}

		$this->log('success', 'Table filled', $rows);
	}

	private function generate()
	{
		$set = [];
		$gender = $this->faker->randomElement(['male', 'female']);
		$credit = $this->faker->creditCardDetails();
		$firstName = $this->faker->firstName($gender);
		$lastName = $this->faker->lastName();
		list($name, $domain) = explode('@', $this->faker->email());
		$login = $this->unique($firstName, '###');
		$email = strtolower(strpos($name,'.')?"{$firstName}.{$lastName}":$login) . '@' . $domain;
		$this->person = [
		    'gender' => $gender,
		    'title' => $this->faker->title($gender),
		    'firstName' => $firstName,
		    'lastName' => $lastName,
		    'login' => $login,
		    'email' => $email,
		    'creditType' => $credit['type'],
		    'creditNumber' => $credit['number'],
		    'creditExpirationDate' => $credit['expirationDate'],
		];
		foreach ($this->fields as $key => $value)
		{
			$set[$key] = call_user_func([$this, $value[1]], $value[2], $value[3]);
		}

		return $set;
	}

	private function set($from, $key = '')
	{
		$base = explode(',', $from);
		$bias = explode(',', $key);
		$link = implode('-', $base);
		if (!isset($this->links[$link]))
		{
			$this->links[$link] = array_merge($base, $bias);
		}
		return $this->faker->randomElement($this->links[$link]);
	}

	private function link($from, $key)
	{
		if (!isset($this->links["{$from}_{$key}"]))
		{
			$select = $this->db
				->prepare("SELECT `{$key}` FROM `{$from}`");
			if (!$select->execute())
			{
				$einfo = $this->db->errorInfo();
				if (isset($einfo[2]))
				{
					$this->log('danger', 'Database error', $einfo[2]);
				}
			}
			$set = $select->fetchAll(\PDO::FETCH_COLUMN, 0);
			$this->links["{$from}_{$key}"] = $set;
		}
		return $this->faker->randomElement($this->links["{$from}_{$key}"]);
	}

	private function auto($min = 0, $max = 0)
	{
		return $this->faker->unique()->randomNumber();
	}

	private function unique($word = 0, $mask = 0)
	{
		if(!$word) {
			$word = $this->faker->word;
		}
		for($cnt=0; isset($this->uniq[strtolower($word)]) && $cnt<1000; $cnt++) {
			$word = $word.$this->faker->bothify($mask?:'**##');
		}
		return $this->uniq[strtolower($word)] = $word;
	}

	private function randomNumber($min = 0, $max = 0)
	{
		if ($max)
		{
			return $this->faker->numberBetween($min, $max);
		}
		elseif ($min)
		{
			return $this->faker->numberBetween($min, \PHP_INT_MAX);
		}
		else
		{
			return $this->faker->randomNumber();
		}
	}

	private function getWord($mask, $uniq)
	{
		$word = $mask ? $this->faker->bothify($mask) : $this->faker->word;

		if($uniq) {
			$word = $this->unique($word,$mask);
		}

		return $word;
	}

	private function word($min = 0, $max = 0)
	{
		$word = $this->getWord($max, 'unique' === strtolower($min));

		if ($min === strtolower($min))
		{
			$word = strtolower($word);
		}
		elseif ($min === strtoupper($min))
		{
			$word = strtoupper($word);
		}
		elseif ($min === ucfirst($min))
		{
			$word = ucfirst($word);
		}

		return $word;
	}

	private function sentence($min = 0, $max = 0)
	{
		return $this->faker->sentence($this->faker->numberBetween($min ?: 3, $max ?: 12));
	}

	private function paragraph($min = 0, $max = 0)
	{
		return $this->faker->paragraph($this->faker->numberBetween($min ?: 3, $max ?: 12));
	}

	private function text($min = 0, $max = 0)
	{
		return $this->faker->paragraphs($this->faker->numberBetween($min ?: 3, $max ?: 12), true);
	}

	private function realText($min = 0, $max = 0)
	{
		return $this->faker->realText($this->faker->numberBetween($min ?: 200, $max ?: 1000));
	}

	private function company($min = 0, $max = 0)
	{
		return $this->faker->company();
	}

	private function phone($min = 0, $max = 0)
	{
		return $this->faker->e164PhoneNumber();
	}

	private function age($min = 0, $max = 0)
	{
		$min = $min ?: 18;
		$max = $max ?: 80;
		return $this->faker->dateTimeBetween("-{$max} years", "-{$min} years")->format('Y-m-d');
	}

	private function date($min = 0, $max = 0)
	{
		return $this->faker->date();
	}

	private function time($min = 0, $max = 0)
	{
		return $this->faker->time();
	}

	private function dateTime($min = 0, $max = 0)
	{
		return $this->faker->dateTime()->format('Y-m-d');
	}

	private function login($min = 0, $max = 0)
	{
		return $this->person['login'];
	}

	private function email($min = 0, $max = 0)
	{
		return $this->person['email'];
	}

	private function gender($min = 0, $max = 0)
	{
		return 'male' === $this->person['gender'] ? 'M' : 'F';
	}

	private function title($min = 0, $max = 0)
	{
		return $this->person['title'];
	}

	private function first($min = 0, $max = 0)
	{
		return $this->person['firstName'];
	}

	private function last($min = 0, $max = 0)
	{
		return $this->person['lastName'];
	}

	private function name($min = 0, $max = 0)
	{
		return $this->person['title'] . ' ' . $this->person['firstName'] . ' ' . $this->person['lastName'];
	}

	private function creditName($min = 0, $max = 0)
	{
		return $this->person['firstName'] . ' ' . $this->person['lastName'];
	}

	private function creditType($min = 0, $max = 0)
	{
		return $this->person['creditType'];
	}

	private function creditNumber($min = 0, $max = 0)
	{
		return $this->person['creditNumber'];
	}

	private function creditDate($min = 0, $max = 0)
	{
		return $this->person['creditExpirationDate'];
	}

	private function country($min = 0, $max = 0)
	{
		return $this->faker->country();
	}

	private function state($min = 0, $max = 0)
	{
		return $this->faker->state();
	}

	private function city($min = 0, $max = 0)
	{
		return $this->faker->city();
	}

	private function streetAddr($min = 0, $max = 0)
	{
		return $this->faker->streetAddress();
	}

	private function address($min = 0, $max = 0)
	{
		return $this->faker->address();
	}

}
