<?php
/*

Copyright (C) 2004, 2005 Olivier Issaly & Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Abstract class for all views
 *
 * @author Vincent Guth
 */
abstract class View {

	/**
	 * List of registered views
	 *
	 * @var array
	 */
	private static $views = [];

	/**
	 * Callback function
	 */
	protected \Closure $callback;

	public function __construct(array|string|null $names, \Closure $callback) {

		if($names !== NULL) {

			if(is_string($names)) {
				$names = [$names];
			}

			foreach($names as $name) {

				if(isset(self::$views[$name])) {
					throw new Exception('Duplicate view \''.$name.'\'');
				}

				self::$views[$name] = $this;

			}

		}

		$this->callback = $callback;

	}

	/**
	 * Returns content type
	 *
	 */
	abstract public function getContentType(): string;

	abstract public function render(stdClass $data): void;

	public static function get(string $name, ViewAction $action): View {

		if(isset(self::$views[$name]) === FALSE) {
			trigger_error('View '.$name.' does not exist in '.$action->getViewFile().'', E_USER_ERROR);
			exit;
		}

		return self::$views[$name];

	}

	/**
	 * Get output with the given template
	 */
	protected function getOutput(stdClass $data): mixed {

		$template = $this->getTemplate($data);
		$callback = $this->callback;

		return $template->build(fn() => $callback->call($this, $data, $template));

	}

	protected function getTemplate(stdClass $data): Template {

		$parameters = (new ReflectionFunction($this->callback))->getParameters();

		if(count($parameters) !== 2) {
			throw new Exception('Callback function takes exactly 2 parameters (stdClass $data, Template $t)');
		}

		$name = $parameters[1]->getType()->getName();

		try {

			$reflection = new ReflectionClass($name);

			if($reflection->isSubclassOf('Template') === FALSE) {
				throw new Exception();
			}

			$t = $reflection->newInstance();
			$t->setData($data);

			return $t;

		} catch(Exception) {
			throw new Exception('Parameter 2 of callback function must be a Template object');
		}

	}

}

/**
 * Json views
 */
class JsonView extends View {

	/**
	 * Returns content type
	 *
	 */
	public function getContentType(): string {
		return 'application/json; charset=utf-8';
	}

	/**
	 * Create Json page
	 *
	 */
	public function render(stdClass $data): void {

		$output = $this->getOutput($data);

		echo self::formatArray($output);

	}

	/**
	 * Format an $output array to JSON
	 */
	public static function formatArray(array $output): string {

		if(LIME_ENV === 'dev') {
			$options = JSON_PRETTY_PRINT;
		} else {
			$options = NULL;
		}

		return json_encode($output, $options);

	}

}

/**
 * HTML views
 */
class HtmlView extends View {


	/**
	 * Returns content type
	 *
	 * @return string
	 */
	public function getContentType(): string {
		return 'text/html; charset=utf-8';
	}

	public function render(stdClass $data): void {
		echo $this->getOutput($data);
	}

}

/**
 * Controls AJAX navigation
 */
class AdaptativeView extends View {

	public function getContentType(): string {

		return match(Route::getRequestedWith()) {

			'ajax' => 'application/json; charset=utf-8',
			default => 'text/html; charset=utf-8'

		};

	}

	public function render(stdClass $data): void {

		$output = $this->getOutput($data);

		echo match(Route::getRequestedWith()) {

			'ajax' => JsonView::formatArray($output),
			default => $output

		};


	}

}
?>
