<?php
/*

Copyright (C) 2004, 2005 Olivier Issaly & Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Classes to select the page and perform the requested ressource, once data are retrieved
 */

class Page {

	/**
	 * List of registered pages
	 *
	 * @var array
	 */
	private static $pages = [];

	/**
	 * List of initialization routines
	 *
	 * @var array
	 */
	private static $init = [
		'http' => [],
		'cli' => [],
		'cron' => [],
		'*' => []
	];

	/**
	 * Constructors
	 */
	private static $constructors = [];

	/**
	 * Current request
	 *
	 * @var string
	 */
	private static $currentRequest = NULL;

	/**
	 * Current page name
	 *
	 * @var string
	 */
	private static $currentName = NULL;

	/**
	 * Current page package
	 *
	 * @var string
	 */
	private static $includePackage = NULL;

	/**
	 * Current page path
	 *
	 * @var string
	 */
	private static $includePath = NULL;

	/**
	 * First path tested
	 *
	 * @var string
	 */
	private static $originalPath = NULL;


	public function __construct(
		protected ?Closure $start = NULL
	) {
	}

	/**
	 * Add a routine for page initialization
	 *
	 * @param mixed $access Access type (http, cli, cron)
	 * @param callable $initializer
	 */
	public static function init($access, callable $initializer = NULL) {

		if(is_callable($access)) {
			$initializer = $access;
			$access = '*';
		}

		self::$init[$access][] = $initializer;

	}

	/**
	 * Create a common page
	 *
	 * @param callable $constructor
	 * @param int $priority Constructor priority for call
	 */
	public static function construct(Closure $constructor, int $priority = 100) {
		self::$constructors[$priority][] = $constructor;
	}

	/**
	 * Clean all registered pages
	 *
	 * @return array
	 */
	public static function clean() {
		self::$pages = [];
		self::$constructors = [];
	}

	/**
	 * Returns all registered pages
	 *
	 * @return array
	 */
	public static function all() {
		return self::$pages;
	}

	/**
	 * Create a HTTP page (can be GET, POST, ...)
	 *
	 * @param string $pageList Pages name
	 * @param callable $create
	 */
	public function http(string|array $pageList, Closure $callback): Page {
		return $this->save(__FUNCTION__, ['GET', 'POST', 'DELETE', 'HEAD', 'PUT'], (array)$pageList, $callback);
	}

	/**
	 * Create a page that match given access
	 * Allowed methods in $methods (ie: ['get', 'post']
	 */
	public function match(array|string $methods, string|array $pageList, Closure $callback): Page {

		$methods = array_map('strtoupper', (array)$methods);

		return $this->save('http', $methods, (array)$pageList, $callback);

	}

	/**
	 * Create a GET page
	 */
	public function get(string|array $pageList, Closure $callback): Page {
		return $this->save(__FUNCTION__, ['GET'], (array)$pageList, $callback);
	}

	/**
	 * Create a POST page
	 */
	public function post(string|array $pageList, Closure $callback): Page {
		return $this->save(__FUNCTION__, ['POST'], (array)$pageList, $callback);
	}

	/**
	 * Create a PUT page
	 */
	public function put(string|array $pageList, Closure $callback): Page {
		return $this->save(__FUNCTION__, ['PUT'], (array)$pageList, $callback);
	}

	/**
	 * Create a DELETE page
	 */
	public function delete(string|array $pageList, Closure $callback): Page {
		return $this->save(__FUNCTION__, ['DELETE'], (array)$pageList, $callback);
	}

	/**
	 * Create a HEAD page
	 */
	public function head(string|array $pageList, Closure $callback): Page {
		return $this->save(__FUNCTION__, ['HEAD'], (array)$pageList, $callback);
	}

	/**
	 * Create a Cli page
	 */
	public function cli(string|array $pageList, Closure $callback): Page {
		return $this->save(__FUNCTION__, NULL, (array)$pageList, $callback);
	}

	/**
	 * Create a Cron page
	 * $interval An array ['cron interval', callback] such as ['* * * * *', function() {}] or ['persistent@@interval in second', function() {}]
	 */
	public function cron(string|array $pageList, Closure $callback, $interval): Page {
		return $this->save(__FUNCTION__, NULL, (array)$pageList, $callback, ['interval' => $interval]);
	}

	private function save(string $type, ?array $request, array $names, callable $create, array $more = []): Page {

		foreach($names as $value) {

			$priority = 5;

			if(is_array($value)) {

				$name = $value[0];

				$filters = [];

				foreach($value as $filterName => $filterMask) {
					if(ctype_alpha($filterName)) {
						$filters[$filterName] = $filterMask;
					}
				}

			} else {
				$name = $value;
				$filters = [];
			}

			self::$pages[self::$includePath][] = [
				'name' => $name,
				'type' => $type,
				'request' => $request,
				'create' => function($data) use ($create) {
					if($this->start !== NULL) {
						$this->start->call($this, $data);
					}
					$create($data);
				},
				'priority' => $priority,
				'filters' => $filters,
				'package' => self::$includePackage
			] + $more;

		}

		return $this;

	}


	/**
	 * Create the page with the right page
	 *
	 * @param array $uris URI list
	 * @throws Action
	 */
	public static function run(array $uris) {

		$page = self::find($uris);

		switch($page['type']) {

			case 'http' :
			case 'get' :
			case 'post' :
			case 'put' :
			case 'delete' :
			case 'head' :
				return self::runHttp($page);

			case 'cli' :
				return self::runCli($page);

			case 'cron' :
				return self::runCron($page);

		}

	}

	/**
	 * Create a 404 error page
	 *
	 * @throws Action
	 */
	public static function run404() {
		self::run([
			['error', 'index', []]
		]);
	}

	private static function runHttp(array $page) {

		self::doInit('http');
		self::doRun($page);

	}

	private static function runCli(array $page) {

		if(Route::getRequestedWith() !== 'cli') {
			throw new DisabledPage('Access '.Route::getRequestedWith());
		}

		self::doInit('cli');
		self::doRun($page);

	}

	private static function runCron(array $page) {

		if(Route::getRequestedWith() !== 'cli') {
			throw new DisabledPage('Access '.Route::getRequestedWith());
		}

		set_time_limit(0);

		self::doInit('cron');

		// Start cron
		$eCron = \dev\CronLib::saveBegin();

		// Register shutdown function
		register_shutdown_function(function() use($eCron) {
			\dev\CronLib::saveEnd($eCron);
		});

		// Create the page
		$interval = $page['interval'];
		$action = NULL;

		if(strpos($interval, 'permanent@') === 0) {

			$frequency = (int)substr($interval, 10);
			$end = time() + Setting::get('dev\cronPermanentLifetime') - 1;

			while($end > time()) {

				try {
					self::doRun($page);
				}
				catch(Action $action) {
				}

				sleep($frequency);

			}

		} else {

			self::doRun($page);

		}

		if($action !== NULL) {
			throw $action;
		}

	}

	public static function doInit($type) {

		$initializers = array_merge(self::$init[$type], self::$init['*']);

		foreach($initializers as $initializer) {
			$initializer();
		}

	}

	private static function doRun(array $page) {

		$data = new stdClass();
		$action = NULL;

		try {

			ksort(self::$constructors);

			foreach(self::$constructors as $constructorsByPriority)  {
				foreach($constructorsByPriority as $constructor) {
					$constructor($data);
				}
			}


			$page['create']($data);

		} catch(Action $action) {

		}

		if($action !== NULL) {
			throw $action;
		}

	}

	/**
	 * Get name of the current page
	 *
	 * @return string
	 */
	public static function getName() {
		return self::$currentName;
	}

	/**
	 * Get request of the current page
	 *
	 * @return string
	 */
	public static function getRequest() {
		return self::$currentRequest;
	}

	/**
	 * Returns original tested path
	 *
	 * @return string
	 */
	public static function getOriginalPath() {
		return self::$originalPath;
	}

	/**
	 * Find the right page
	 *
	 * @param array $uris URI list
	 * @return string
	 */
	private static function find(array $uris) {

		foreach($uris as $uri) {

			$page = self::build(...$uri);

			if($page !== NULL) {
				return $page;
			}

		}

		trigger_error('Can not load \''.$uri[0].'\' page', E_USER_ERROR);
		exit;

	}

	private static function build(string $pageRequest, string $pageName, array $newArguments, ?string $redirect = NULL) {

		// Redirect if needed
		if($redirect !== NULL) {
			throw new PermanentRedirectAction('/'.$redirect);
		}

		// Get page path
		$pagePath = Package::getFileFromUri(
			$pageRequest,
			'page'
		);

		if(self::$originalPath === NULL) {
			self::$originalPath = Package::getLastFile();
		}

		if($pagePath === NULL) {
			return NULL;
		}

		// Include page file
		self::includeFile($pagePath);

		// Define parameters of the page
		self::$currentRequest = $pageRequest;
		self::$currentName = $pageName;

		// Returns the right page
		$filter = function(array $filters, array $arguments) {

			foreach($filters as $name => $mask) {

				if(Filter::check($mask, $arguments[$name]) === FALSE) {
					return FALSE;
				}

			}

			return TRUE;

		};

		foreach(self::$pages[$pagePath] ?? [] as $page) {

			if(
				$page['name'] === $pageName and
				(
					$page['request'] === NULL or
					in_array(Route::getRequestMethod(), $page['request'])
				) and
				$filter($page['filters'], $newArguments)
			) {

				array_utf8($newArguments);

				$_REQUEST = $newArguments + $_REQUEST;
				$_GET = $newArguments + $_GET;

				return $page;
			}

		}

	}

	public static function includeFile(string $path) {

		self::$includePath = $path;
		self::$includePackage = Package::getPackageFromPath($path);

		require_once $path;

	}

}

/**
 * Exception thrown if a page is disabled
 * In this case, go to 404 error page
 */
class DisabledPage extends Exception {

	public function __construct($disabled) {
		return parent::__construct($disabled.' is disabled');
	}

}
?>
