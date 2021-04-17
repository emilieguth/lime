<?php
/*
Copyright (C) 2004-2012 Olivier Issaly & Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/
declare(strict_types = 1);

// Current time
define("LIME_TIME", microtime(TRUE));

// Configuration
mb_internal_encoding('UTF-8');
ignore_user_abort(TRUE);

// Path to Lime
define('LIME_DIRECTORY', realpath( __DIR__.'/..'));

/**
 * Lime main configuration class
 *
 */
class Lime {

	/*
	 * Site URL (protocol + host + port)
	 */
	private static ?string $url = NULL;

	/*
	 * Site host
	 */
	private static ?string $host = NULL;

	/*
	 * Site domain
	 */
	private static ?string $domain = NULL;

	/*
	 * Site protocol
	 */
	private static ?string $protocol = NULL;

	/*
	 * Site port
	 */
	private static ?int $port = NULL;

	/*
	 * Site name
	 */
	private static ?string $name = NULL;

	/**
	 * Registered apps
	 *
	 * @var array
	 */
	private static array $apps = [];

	/**
	 * Sibling apps for internal builds
	 *
	 * @var array
	 */
	private static array $siblings = [];

	/**
	 * Define main app
	 *
	 * @param string $app
	 */
	public static function init(string $app): void {

		if(ctype_alpha($app) === FALSE) {
			trigger_error("App name must only contain alphabetic characters", E_USER_ERROR);
			exit;
		} else {
			define('LIME_APP', $app);
		}

	}

	/**
	 * Get the path for the given app
	 *
	 * @param string $app
	 */
	public static function getPath(string $app = LIME_APP): string {
		return LIME_DIRECTORY.'/'.$app;
	}


	/**
	 * Set apps
	 *
	 * @param array $apps
	 */
	public static function setApps(array $apps): void {
		self::$apps = $apps;
	}

	/**
	 * Get apps
	 *
	 * @return array
	 */
	public static function getApps(): array {
		return self::$apps;
	}

	/**
	 * Set siblings
	 *
	 * @param array $siblings
	 */
	public static function setSiblings(array $siblings): void {
		self::$siblings = $siblings;
	}

	/**
	 * Get siblings
	 *
	 * @return array
	 */
	public static function getSiblings(): array {
		return self::$siblings;
	}

	/**
	 * Set urls for each mode (dev, prod...) and select the right URL according to the current mode
	 *
	 * @param array $urls
	 */
	public static function setUrls(array $urls): void {

		if(isset($urls[LIME_ENV]) === FALSE) {
			trigger_error("No URL for mode '".LIME_ENV."'", E_USER_ERROR);
			exit;
		}

		$url = $urls[LIME_ENV];

		if(preg_match('/(http[s]?):\/\/(([a-z0-9\.\-]+\.)?([a-z0-9\-]+\.[a-z]+))(:([0-9]+))?\/?/i', $url, $match) > 0) {

			self::$url = $url;
			self::$host = $match[2];
			self::$domain = $match[4];
			self::$protocol = strtolower($match[1]);

			if(isset($match[6])) {
				self::$port = (int)$match[6];
			}

		} else {
			throw new Exception('Url \''.$url.'\' is invalid');
		}

	}

	/**
	 * Set site name
	 *
	 * @return array
	 */
	public static function setName(string $name): void {
		self::$name = $name;
	}

	/**
	 * Get site name
	 *
	 * @return array
	 */
	public static function getName(): ?string {
		return self::$name;
	}

	/**
	 * Get url
	 *
	 * @return array
	 */
	public static function getUrl(): ?string {
		return self::$url;
	}

	/**
	 * Get host from url
	 *
	 * @return array
	 */
	public static function getHost(): ?string {
		return self::$host;
	}

	/**
	 * Get domain from url
	 *
	 * @return array
	 */
	public static function getDomain(): ?string {
		return self::$domain;
	}

	/**
	 * Get protocol from url
	 *
	 * @return array
	 */
	public static function getProtocol(): ?string {
		return self::$protocol;
	}

	/**
	 * Get port from url
	 *
	 * @return array
	 */
	public static function getPort(): ?int {
		return self::$port;
	}

}

// Required files
require_once LIME_DIRECTORY.'/framework/core/Function.php';
require_once LIME_DIRECTORY.'/framework/core/Package.php';
require_once LIME_DIRECTORY.'/framework/core/Route.php';
require_once LIME_DIRECTORY.'/framework/core/Page.php';
require_once LIME_DIRECTORY.'/framework/core/Action.php';
require_once LIME_DIRECTORY.'/framework/core/Instruction.php';
require_once LIME_DIRECTORY.'/framework/core/Template.php';
require_once LIME_DIRECTORY.'/framework/core/View.php';
require_once LIME_DIRECTORY.'/framework/core/Module.php';
require_once LIME_DIRECTORY.'/framework/core/Data.php';

// Get instance depending http or cli access
if(server_exists('SERVER_NAME')) {
	$access = new HttpRoute();
} else {
	$access = new CliRoute();
}


// Do everything
$access->run();
?>
