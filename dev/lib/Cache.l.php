<?php
namespace dev;

/*
Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Generate a cache.c.php file from a cache.ini file
 */
class CacheLib {

	/**
	 * File PHP Database file from INI files
	 *
	 * @param string $mode (prod, dev, preprod)
	 */
	public static function build(string $mode = LIME_ENV): bool {

		$ini = self::parseIni();

		$content = [];

		$content[] = '<?php';
		$content[] = '/**';
		$content[] = ' * Cache configuration file automatically generated for '.LIME_APP.' on '.currentDatetime();
		$content[] = ' * Edit cache.ini in order to change cache configuration';
		$content[] = ' */';

		// Enable/disable debug
		self::buildDebug($ini, $content);

		// Add Memcache information
		self::buildMemcache($ini, $content);

		// Add Redis information
		self::buildRedis($ini, $content);

		$content[] = '?>';

		$stringContent = implode("\n", $content);

		// Replace current cache.c.php file content by new one
		$file = \Lime::getPath()."/cache.c.php";
		file_put_contents($file, $stringContent);

		return TRUE;

	}

	/**
	 * Open cache.ini for current app
	 *
	 */
	protected static function parseIni() {

		$file = \Lime::getPath()."/cache.ini";

		if(is_file($file)) {
			return parse_ini_file($file, TRUE);
		} else {
			throw new \Exception("File '".$file."' does not exist");
		}

	}

	/**
	 * Adds debug status to $content
	 *
	 * @param array $ini Content of cache.ini file
	 * @param array $content Output
	 */
	protected static function buildDebug(array $ini, array &$content) {

		$hasDebug = strtolower($ini['global']['debug'] ?? 'Off');

		if(
			$hasDebug !== '' or
			($hasDebug === 'dev' and LIME_ENV === 'dev') or
			($hasDebug === 'preprod' and in_array(LIME_ENV, ['dev', 'preprod']))
		) {

			$content[] = '';
			$content[] = 'Cache::setDebug(get_exists(\'cache\'));';

		}

	}

	/**
	 * Adds memcache servers to $content
	 *
	 * @param array $ini Content of cache.ini file
	 * @param array $content Output
	 */
	protected static function buildMemcache(array $ini, array &$content) {

		return self::buildServer($ini, $content, 'memcache', 'MemCacheCache', '127.0.0.1', '11211', '2');

	}

	/**
	 * Adds redis servers to $content
	 *
	 * @param array $ini Content of cache.ini file
	 * @param array $content Output
	 */
	protected static function buildRedis(array $ini, array &$content) {

		return self::buildServer($ini, $content, 'redis', 'RedisCache', '127.0.0.1', '6379', '2');

	}

	protected static function buildServer(array $ini, array &$content, string $iniGroup, string $class, string $defaultHost, string $defaultPort, int $defaultTimeout) {

		$servers = [];

		foreach($ini as $key => $values) {

			if(strpos($key, $iniGroup.'-') !== 0) {
				continue;
			}

			$name = substr($key, strlen($iniGroup) + 1);

			$host = $values['host'] ?? $defaultHost;
			$port = $values['port'] ?? $defaultPort;
			$timeout = $values['timeout'] ?? $defaultTimeout;

			$options = [];

			if($timeout !== '2') {
				$options[] = "'timeout' => ".$timeout;
			}

			if($options) {
				$more = ', ['.implode(", ", $options).']';
			} else {
				$more = '';
			}

			$servers[] = $class."::addServer('".$name."', '".$host."', ".$port."".$more.");";

		}

		if($servers) {
			$content[] = '';
			$content = array_merge($content, $servers);
		}

		// Enable/disable monitoring
		$statusMon = $ini[$iniGroup]['mon'] ?? 'Off';

		if($statusMon === '1') {

			$content[] = '';
			$content[] = $class.'::startMon(new \dev\CacheLib());';
		}

	}

}
?>
