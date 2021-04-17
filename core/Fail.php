<?php
/*

Copyright (C) 2015 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Handle errors
 *
 * @author Vincent Guth
 */
class Fail {

	/**
	 * Fails list
	 *
	 * @var array
	 */
	protected static $fails = [];

	/**
	 * List of watchers
	 *
	 * @var array
	 */
	protected static $watchers = [];

	/**
	 * Id for the next created watcher
	 *
	 * @var int
	 */
	private static $nextWatcherId = 0;

	/**
	 * Size of the biggest watcher
	 *
	 * @var int
	 */
	private static $maxWatcherCount = 0;

	/**
	 * Is logging enabled?
	 *
	 * @var bool
	 */
	private static $enabled = TRUE;

	/**
	 * Bazooka throws an exception everytime a fail is logged
	 *
	 * @var bool
	 */
	private static $bazooka = FALSE;

	/**
	 * Enable logging
	 */
	public static function enable() {
		self::$enabled = TRUE;
	}

	/**
	 * Disable logging
	 */
	public static function disable() {
		self::$enabled = FALSE;
	}

	/**
	 * Enable bazooka mode
	 */
	public static function bazooka() {
		self::$bazooka = TRUE;
	}

	/**
	 * Disable bazooka mode
	 */
	public static function dove() {
		self::$bazooka = FALSE;
	}

	/**
	 * Log a new error
	 */
	public static function log(string|FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {

		if(self::$enabled === FALSE) {
			return FALSE;
		}

		if($failName instanceof FailException) {
			$arguments = $failName->arguments;
			$wrapper = $failName->wrapper;
			$failName = $failName->getMessage();
		}

		if(self::$bazooka) {
			throw new Exception($failName);
		}

		// Get namespace of the error
		if(strpos($failName, '\\') !== FALSE) {

			list($namespace, $failName) = explode('\\', $failName);

		} else {

			$namespace = NULL;

			foreach(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT) as $trace) {

				if(isset($trace['object'])) {
					$namespace = strstr(get_class($trace['object']), '\\', TRUE);
					break;
				} else if(isset($trace['class']) and strpos($trace['class'], '\\') !== FALSE) {
					$namespace = strstr($trace['class'], '\\', TRUE);
					break;
				}

			}

		}

		if($wrapper === NULL) {

			$result = NULL;

			if(preg_match('/::([a-z0-9]+)\./i', $failName, $result) > 0) {
				$wrapper = $result[1];
			}

		}

		self::$fails[] = [$namespace, $failName, $arguments, $wrapper];

		foreach(self::$watchers as $key => $count) {
			self::$watchers[$key] = $count + 1;
		}

		self::$maxWatcherCount++;

		return FALSE;

	}

	/**
	 * Creates a new fail and returns a FailWatch instance containing the fail
	 *
	 * @param string $failName Fail name
	 * @param array $arguments User-defined arguments
	 * @return FailWatcher
	 */
	public static function watch(string $failName, array $arguments = []): \FailWatch {

		$fw = new FailWatch();

		self::log($failName, $arguments);

		return $fw;

	}

	/**
	 * Create a new watcher
	 *
	 * @return int A watcher ID
	 */
	public static function createWatcher(): int {

		$watcherId = self::$nextWatcherId++;

		self::$watchers[$watcherId] = 0;

		return $watcherId;

	}

	/**
	 * Remove a watcher
	 *
	 * @param int $watcherId Watcher ID
	 */
	public static function removeWatcher(int $watcherId): bool {

		$watcherCount = self::$watchers[$watcherId];

		unset(self::$watchers[$watcherId]);

		// This watch is the bigest so clean errors array
		if(self::$maxWatcherCount === $watcherCount) {

			if(self::$watchers) {

				self::$maxWatcherCount = max(self::$watchers);
				self::$fails = array_slice(self::$fails, $watcherCount - self::$maxWatcherCount);

			} else {

				self::$maxWatcherCount = 0;
				self::$fails = [];

			}

		}

		return TRUE;

	}

	/**
	 * Get fails in the context of the given watcher
	 *
	 * @param int $watcherId
	 *
	 * @return array
	 */
	public static function get(int $watcherId): array {
		return array_slice(self::$fails, - self::$watchers[$watcherId]);
	}

	public static function replace(int $watcherId, array $fails): void {
		self::$fails = array_slice(self::$fails, 0, - self::$watchers[$watcherId]) + $fails;

	}

	/**
	 * Count fails in the context of the given watcher
	 *
	 * @param int $watcherId
	 *
	 * @return int
	 */
	public static function count(int $watcherId): int {
		return self::$watchers[$watcherId];
	}

}

/**
 * This class lets you watch fails added to Fail class
 * $fw = new FailWatch();
 * \Fail::log('test');
 *
 */
class FailWatch {

	/**
	 * ID given by Fail::createWatcher() method
	 *
	 * @var int
	 */
	protected $watcherId;

	/**
	 * Create a new fail instance
	 */
	public function __construct() {

		$this->watcherId = Fail::createWatcher();

	}

	/**
	 * Destruct watcher
	 */
	public function __destruct() {

		Fail::removeWatcher($this->watcherId);

	}

	/**
	 * Count errors
	 *
	 * @return int
	 */
	public function count(): int {
		return Fail::count($this->watcherId);
	}

	/**
	 * No fail found = returns TRUE
	 *
	 * @return bool
	 */
	public function ok(): bool {
		return Fail::count($this->watcherId) === 0;
	}

	/**
	 * Fail found = returns TRUE
	 *
	 * @return bool
	 */
	public function ko(): bool {
		return Fail::count($this->watcherId) > 0;
	}


	/**
	 * Get fails
	 *
	 * @return array
	 */
	public function get(): array {
		return Fail::get($this->watcherId);
	}


	/**
	 * CHeck for a specifid error
	 *
	 * @return array
	 */
	public function has(string $test): bool {

		foreach(Fail::get($this->watcherId) as [, $failName]) {
			if($test === $failName) {
				return TRUE;
			}
		}

		return FALSE;

	}

	/**
	 * Get a string from fails
	 */
	public function __toString(): string {

		$list = $this->format();

		$string = '';

		foreach($list as list($failName, $message)) {

			$string .= $failName.': '.$message."\n";

		}

		return $string;

	}

	/**
	 * Format fails using [app]/ui/Alert.u.php file
	 *
	 * @return array
	 */
	public function format(): array {

		$list = [];

		foreach($this->get() as list($app, $failName, $arguments, $wrapper)) {

			$class = '\\'.$app.'\\AlertUi';

			if(class_exists($class)) {
				$message = (new $class())->getError($failName, $arguments);
			} else {
				$message = NULL;
			}

			$list[] = [$failName, $message, $wrapper];

		}

		return $list;

	}

}

class FailException extends Exception {

	public function __construct(
		string $failName,
		public array $arguments = [],
		public ?string $wrapper = NULL
	) {
		parent::__construct($failName);
	}

}
?>
