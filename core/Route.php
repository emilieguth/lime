<?php
/*

Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Generic access class
 */
abstract class Route {

	use Notifiable;


	/**
	 * All registered routes
	 */
	private static array $routes = [];

	/**
	 * Selected page
	 */
	private static array $pages = [];

	/**
	 * Had an action already be thrown?
	 *
	 * @var bool
	 */
	private static bool $actionThrown = FALSE;

	/**
	 * Request build by constructor
	 */
	protected ?string $request;

	/**
	 * Requested with?
	 */
	protected static ?string $requestedWith = NULL;

	/**
	 * Origin of the request
	 */
	protected static ?string $requestedOrigin = NULL;

	/**
	 * Request method (GET, POST...)
	 */
	protected static ?string $requestMethod = NULL;

	/**
	 * Register a list of routes
	 *
	 * @param array $routes
	 */
	public function register(array $routes) {
		self::$routes += $routes;
	}

	/**
	 * Returns selected page class
	 *
	 * @return string Class name
	 */
	public function run() {

		// Clean inputs
		array_utf8($_GET);
		array_utf8($_POST);
		array_utf8($_REQUEST);

		// Defines current host
		if(Route::getRequestedWith() !== 'cli') {
			define("LIME_HOST", $_SERVER['HTTP_HOST'] ?? Lime::getHost());
		}

		// Handle fatal errors
		$this->handleErrors();

		// Build compatible pages from the request
		$this->buildPages();

		try {

			try {

				Page::run(self::$pages);

			}
			catch(DisabledPage $e) {

				if(LIME_ENV === 'dev') {
					dev\ErrorPhpLib::handle($e);
				}

				Page::run404();

			}

			throw new VoidAction();

		}
		catch(Action $action) {

			$limit = 5;

			for($count = 0; $count < $limit; $count++) {

				try {
					$action->run();
					$count = -1;
					break;
				}
				catch(Action $action) {

				}

			}

			if($count !== -1) {
				throw new Exception('Can not pile up action more than '.$limit.' times...');
			}

			self::$actionThrown = TRUE;
		}

	}

	public static function getRequestedWith(): ?string {
		return self::$requestedWith;
	}

	public static function getRequestedOrigin(): ?string {
		return self::$requestedOrigin;
	}

	public static function getRequestMethod(): string {
		return self::$requestMethod;
	}

	/**
	 * Load lime.c.php file
	 */
	protected function loadConf(string $request) {

		require Lime::getPath().'/lime.c.php';

		if(LIME_ENV === 'dev') {

			require_once Lime::getPath('framework').'/dev/lib/Package.l.php';

			try {

				$libPackage = new dev\PackageLib($request);
				$libPackage->buildPackage();
				$libPackage->buildRoute();

			} catch(Exception $e) {

			}

		}

		require_once Lime::getPath().'/package.c.php';
		require_once Lime::getPath().'/route.c.php';

		if(LIME_ENV === 'dev') {
			self::notify('loadConf');
		}

	}

	/**
	 * Get page
	 */
	protected function buildPages() {

		$request = $this->request;

		self::$pages = $this->getCompatiblePages($request);
		self::$pages[201] = $this->getIndexPages($request);
		self::$pages[202] = ['error', 'index', [], NULL];

		ksort(self::$pages);

	}

	protected function getCompatiblePages($request): array {

		// Look for compatible route
		$requestChunk = explode('/', rtrim($request, '/'));
		$requestSize = count($requestChunk);
		$requestSlash = (substr($request, -1) === '/');

		$pages = [];

		$routes = self::$routes[Route::getRequestMethod()] ?? [];

		foreach($routes as $name => $route) {

			$routeSize = count($route['route']);
			$routeSlash = (substr($name, -1) === '/');

			if($routeSize !== $requestSize) {
				continue;
			}

			$requestCompatible = TRUE;
			$requestInput = [];

			for($i = 0; $i < $routeSize; $i++) {

				$routePart = $route['route'][$i];
				$requestPart = $requestChunk[$i];

				$trailingRouteIndex = strpos($routePart, ':');
				$trailingRequestIndex = strpos($requestPart, ':');
				$trailingRoutePart = '';
				$trailingRequestPart = '';

				if($trailingRouteIndex !== FALSE) {
					$trailingRoutePart = substr($routePart, $trailingRouteIndex);
				}
				if($trailingRequestIndex !== FALSE) {
					$trailingRequestPart = substr($requestPart, $trailingRequestIndex);
				}

				if($trailingRequestPart !== $trailingRoutePart) {
					$requestCompatible = FALSE;
					break;
				}

				if($routePart !== '' and $routePart[0] === '{') {

					if($trailingRequestIndex !== FALSE) {
						$key = substr($routePart, 1, strpos($routePart, '}') - 1);
						$value = substr($requestPart, 0, -strlen($trailingRequestPart));
					} else {
						$key = substr($routePart, 1, -1);
						$value = $requestPart;
					}

					if(strpos($key, '@') !== FALSE) {

						[$key, $cast] = explode('@', $key, 2);

						$requestCompatible = Filter::check($cast, $value);

					} else {
						$requestCompatible = TRUE;
					}

					$requestInput[$key] = $value;

				} else {
					$requestCompatible = ($routePart === $requestPart);
				}

				if($requestCompatible === FALSE) {
					break;
				}

			}

			if($requestCompatible === TRUE) {

				$priority = $route['priority'];

				if($requestSlash !== $routeSlash) {

					if($requestSlash) {
						$redirect = rtrim($request, '/');
					} else {
						$redirect = $request.'/';
					}

					$priority += 100;

				} else {
					$redirect = NULL;
				}

				$pages[$priority] = [
					$route['request'],
					$name,
					$requestInput,
					$redirect
				];

			}

		}

		return $pages;

	}

	private function getIndexPages($request) {

		// No compatible route found
		if($request === '' or substr($request, -1) === '/') {
			$request .= 'index';
		}

		if(strpos($request, ':') === 0) {
			$request = 'index'.$request;
		}

		if(strpos($request, '/:') !== FALSE) {
			$request = str_replace('/:', '/index:', $request);
		}

		if(strpos($request, ':') === FALSE) {
			$request .= ':index';
		}

		$page = explode(':', $request, 2);

		// Page name must be alpha-numeric
		if(ctype_alnum($page[1]) === FALSE) {
			$page[1] = 'index';
		}

		$page[2] = [];
		$page[3] = NULL;

		return $page;

	}


	/**
	 * Get tested pages
	 *
	 * @return string
	 */
	public static function getPages() {
		return self::$pages;
	}

	protected function handleErrors() {

		// Start error handler
		set_error_handler(function($code, $message, $file, $line) {
			dev\ErrorPhpLib::handle(dev\Error::PHP, $code, $message, $file, $line);
			error_clear_last();
		});

		// Handle exceptions
		set_exception_handler(function($e) {

			dev\ErrorPhpLib::handle($e);
			ModuleModel::rollBackEverything();

			(new StatusAction(500))->run();

		});

		// Shutdown function for errors
		register_shutdown_function(function() {

			$lastError = error_get_last();

			if($lastError) {
				dev\ErrorPhpLib::handle(dev\Error::PHP, $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
			}

		});

		// Shutdown function that send default content type if no action has been thrown
		register_shutdown_function(function() {

			if(self::$actionThrown === FALSE) {

				(new VoidAction())->run();

			}

		});

	}

}

/**
 * Run lime in http mode
 */
class HttpRoute extends Route {

	/**
	 * Build environment
	 */
	public function __construct() {

		ob_start();

		// Get env
		$env = GET('limeEnv', '?string');

		if($env === NULL) {
			throw new Exception('Lime env is not defined');
		}

		unset($_GET['limeEnv'], $_REQUEST['limeEnv']);
		define('LIME_ENV', $env);

		// Get access
		if(
			LIME_ENV === 'dev' and
			get_exists('limeAccess')
		) {

			self::$requestedWith = GET('limeAccess');

		} else {

			if(server_exists('HTTP_X_REQUESTED_WITH')) {

				self::$requestedWith = SERVER('HTTP_X_REQUESTED_WITH');

				if(self::$requestedWith === 'ajax') {
					self::$requestedOrigin = SERVER('HTTP_X_REQUESTED_ORIGIN', '?string');
				}

			} else {
				self::$requestedWith = 'http';
			}

		}

		// Get app
		$app = GET('limeApp', '?string');
		$request = GET('limeName');
		$request = ltrim($request, '/');

		str_utf8($request);

		Lime::init($app);

		// Get required configuration files
		$this->loadConf($request);

		// Clean GET and REQUEST arrays
		unset($_GET['limeName'], $_REQUEST['limeName']);
		unset($_GET['limeMode'], $_REQUEST['limeMode']);
		unset($_GET['limeApp'], $_REQUEST['limeApp']);

		// Save request
		$limeRequest = $_SERVER['REQUEST_URI'];
		$limeRequestArgs = strpos($limeRequest, '?');

		if($limeRequestArgs !== FALSE) {
			$limeRequestPath = substr($limeRequest, 0, $limeRequestArgs);
		} else {
			$limeRequestPath = $limeRequest;
		}

		define('LIME_URL', Lime::getProtocol().'://'.SERVER('HTTP_HOST').SERVER('REQUEST_URI'));
		define('LIME_REQUEST', $limeRequest);
		define('LIME_REQUEST_PATH', $limeRequestPath);

		// Save request method
		self::$requestMethod = SERVER('REQUEST_METHOD', 'string', 'GET');

		if(in_array(Route::getRequestMethod(), ['GET', 'POST', 'DELETE', 'HEAD', 'PUT']) === FALSE) {
			header("HTTP/1.0 405 Method Not Allowed");
			exit;
		}

		$this->request = $request;

	}

}

/**
 * Run lime in cli mode
 *
 */
class CliRoute extends Route {

	/**
	 * Build environment
	 */
	public function __construct() {

		self::$requestedWith = 'cli';

		/*
		 * Get all arguments
		 */
		$args = array_slice(SERVER('argv', 'array'), 1);

		if(empty($args)) {
			$this->getHelp();
		}

		$param = NULL;
		$run = NULL;

		$_SERVER['constants'] = [];

		foreach($args as $position => $arg) {

			if($param !== NULL) {

				switch($param) {

					case 'app' :
						Lime::init($arg);
						break;

					case 'env' :
						define('LIME_ENV', $arg);
						break;

					case 'constant' :
						$constant = $this->getArgument($arg, FALSE);
						if($constant !== NULL) {
							foreach($constant as $name => $value) {
								define($name, $value);
								$_SERVER['constants'][$name] = $value;
							}
						} else {
							$this->getHelp();
						}
						break;

					default :
						break(2);

				}

				$param = NULL;

			} else {

				switch($arg) {

					case '-a' :
						$param = 'app';
						break;

					case '-e' :
						$param = 'env';
						break;

					case '-c' :
						$param = 'constant';
						break;

					case '-r' :
						$position++;
						$run = $args[$position];
						break(2);

					default :
						break(2);

				}

			}

		}

		if(defined('LIME_APP') === FALSE) {
			echo "Error: App is missing\n";
			exit;
		}

		if(defined('LIME_ENV') === FALSE) {
			echo "Error: Env is missing\n";
			exit;
		}

		$this->extractArguments($args, $position);

		// Save request
		$request = $args[$position];

		str_utf8($request);

		define('LIME_REQUEST_PATH', '/'.$request);
		define('LIME_REQUEST', '/'.$request.($_GET ? '?'.http_build_query($_GET) : ''));

		// Save request method
		self::$requestMethod = 'GET';

		// Get required configuration files
		$this->loadConf($request);

		$this->request = $request;

		if($run) {

			eval('class Php {
				public function run() {
					'.$run.'
				}
			}');

			if(class_exists('Php', FALSE)) {

				Page::doInit('cli');

				$object = new Php;
				$object->run();
				exit;

			} else {
				echo "Error: Your PHP code is invalid\n";
				exit;
			}

		}

	}


	/**
	 * Extract get and request arguments
	 *
	 * @param array $args
	 * @param int $position
	 */
	private function extractArguments(array $args, int $position) {

		$args = array_slice($args, $position + 1);

		foreach($args as $arg) {

			if($arg === "") {
				continue;
			}

			$argument = $this->getArgument($arg, TRUE);

			if($argument !== NULL) {
				$_GET = array_merge_recursive($_GET, $argument);
				$_REQUEST = array_merge_recursive($_REQUEST, $argument);
			} else {
				echo "Error: Page arguments must have the following syntax: name=value ('".$arg."' found)\n";
				exit;
			}

		}

	}


	/**
	 * Get argument name and value
	 */
	private function getArgument(string $string, bool $withArrays): ?array {

		if(preg_match("/^([a-z0-9\_\-".($withArrays ? '\\[\\]' : '')."]+)=(.*)$/si", $string, $match) ) {

			$name = $match[1];
			$value = $match[2];

			$first = substr($value, 0, 1);
			$last = substr($value, -1);

			if(
				$first === $last and
				($first === '"' or $first === '\'')
			) {

				$value = substr($value, 1, -1);
				$value = stripcslashes($value);

			}

			if(strpos($name, '[')) {

				if(preg_match('/^([a-z0-9\_\-]+)(\[[a-z0-9\_\-]+\])+$/si', $name, $list) > 0) {

					$names = explode('[', str_replace(']', '', $name));

					$arg = [];
					$currentArg = &$arg;

					foreach(array_slice($names, 0, -1) as $name) {
						$currentArg[$name] = [];
						$currentArg = &$currentArg[$name];
					}

					$currentArg[last($names)] = $value;

					return $arg;

				} else {
					echo "Error: Invalid argument name: ".$name."\n";
					exit;
				}

			} else {
				return [$name => $value];
			}

		} else {
			return NULL;
		}

	}


	/**
	 * Need help
	 */
	private function getHelp() {

		echo "Usage: php lime.php [options] [page] [arguments]\n\n".
		"[options]\n".
		"	-a appName\n".
		"		Use selected app\n".
		"	-r 'phpCode'\n".
		"		Run PHP code\n".
		"	-c NAME=VALUE\n".
		"		Define a PHP constant\n".
		"[page]\n".
		"	Page to run\n".
		"[arguments]\n".
		"	name=value\n".
		"		Add GET arguments to the page\n\n".
		"";

		exit;

	}

}
?>
