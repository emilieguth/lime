<?php
/*

Copyright (C) 2004, 2005 Olivier Issaly & Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Action once the page has been created
 *
 * @author Olivier Issaly
 */
abstract class Action extends \Exception {

	const JSON = 'application/json; charset=utf-8';
	const HTML = 'text/html; charset=utf-8';

	/**
	 * Enable/disable Gzip compression
	 *
	 * @var bool
	 */
	protected $gzip = FALSE;

	/**
	 * Content type
	 *
	 * @var string
	 */
	protected $contentType = NULL;

	/**
	 * Content disposition
	 *
	 * @var string
	 */
	protected $contentDisposition = NULL;

	/**
	 * Execute the action
	 */
	abstract public function run();

	/**
	 * Enable/disable gzip compression
	 *
	 * @param bool $gzip
	 */
	public function setGzip($gzip) {
		$this->gzip = (bool)$gzip;
	}

	/**
	 * Change HTTP status code
	 *
	 * @param int $code New code
	 */
	public function setStatusCode(int $code): string {

		switch($code) {

			case 200 :
				$header = '200 OK';
				break;
			case 201 :
				$header = '201 Created';
				break;
			case 301 :
				$header = '301 Moved Permanently';
				break;
			case 302 :
				$header = '302 Moved Temporarily';
				break;
			case 304 :
				$header = '304 Not Modified';
				break;
			case 400 :
				$header = '400 Bad Request';
				break;
			case 401 :
				$header = '401 Unauthorized';
				break;
			case 403 :
				$header = '403 Forbidden';
				break;
			case 404 :
				$header = '404 Not Found';
				break;
			case 405 :
				$header = '405 Method Not Allowed';
				break;
			case 408 :
				$header = '408 Request Timeout';
				break;
			case 409 :
				$header = '409 Conflict';
				break;
			case 410 :
				$header = '410 Gone';
				break;
			case 500 :
				$header = '500 Internal Server Error';
				break;
			case 501 :
				$header = '501 Not Implemented';
				break;
			case 502 :
				$header = '502 Bad Gateway';
				break;
			case 503 :
				$header = '503 Service Unavailable';
				break;

			default :
				throw new Exception('Unknown HTTP code');

		}

		header("HTTP/1.0 ".$header);

		return $header;

	}

	/**
	 * Change the current content type
	 *
	 * @param string $contentType
	 */
	public function setContentType(string $contentType) {
		$this->contentType = $contentType;
	}

	/**
	 * Checks if a content type has been set
	 *
	 * @return bool
	 */
	public function hasContentType() {
		return $this->contentType !== NULL;
	}

	/**
	 * Get the current content type
	 *
	 * @param string
	 */
	public function getContentType(): string {

		if($this->contentType === NULL) {

			switch(Route::getRequestedWith()) {

				case 'http' :
				case 'cli' :
					return self::HTML;

				default  :
					return self::JSON;

			}

		} else {
			return $this->contentType;
		}

	}

	/**
	 * Send the content type
	 */
	public function sendContentType() {

		if(
			Route::getRequestedWith() !== 'cli' and
			headers_sent() === FALSE
		) {
			header('Content-Type: '.$this->getContentType());
		}

	}

	public function sendContentDisposition() {
		if($this->contentDisposition !== NULL) {
			header('Content-Disposition: '.$this->contentDisposition);
		}
	}

}

/**
 * Null action: nothing is done
 *
 * @author Olivier Issaly
 */
class VoidAction extends Action {

	/**
	 * Nothing is done
	 */
	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'ajax' :
				(new JsonAction([]))->run();
				break;

			default :
				break;

		}

	}

}

/**
 * Status action: nothing is done and a code is sent to the browser
 *
 * @author Vincent Guth
 */
class StatusAction extends Action {

	public function __construct(int $code) {

		$header = $this->setStatusCode($code);

		if(Route::getRequestedWith() === 'cli') {
			echo $header."\n";
		}

		parent::__construct();

	}

	public function run(): void {

	}

}

/**
 * Flow action: print arbitrary data
 *
 * @author Vincent Guth
 */
class DataAction extends Action {

	/**
	 * Some data
	 *
	 * @var string
	 */
	protected $data = '';

	/**
	 * Create the action with some data
	 *
	 * @param string $data
	 * @param string $contentType
	 */
	public function __construct(string $data = '', string $contentType = Action::HTML) {

		if($data !== NULL) {
			$this->set($data);
		}

		if($contentType !== NULL) {
			$this->setContentType($contentType);
		}

		parent::__construct();

	}

	/**
	 * Set some data do print
	 *
	 * @param string $data
	 */
	public function set(string $data) {
		$this->data = (string)$data;
	}

	/**
	 * Get current data
	 *
	 * @return string
	 */
	public function get() {
		return $this->data;
	}

	/**
	 * Print data
	 */
	public function run(): void {

		$this->sendContentType();

		echo $this->data;

	}

}

/**
 * User input is wrong and you don't want to explain why to him (ie: user tried to hack something)
 *
 * This action has behavior that depends the environment:
 * - dev: display an error message and save it in the database
 * - preprod: save an error message in the database and run $alternateAction (or StatusAction(404) is not defined)
 * - prod: only run $alternateAction (or StatusAction(404) is not defined)
 *
 * @author Vincent Guth
 */
abstract class NotAction extends Action {

	private $wrong = NULL;

	private $alternateAction = NULL;

	/**
	 * Create the action
	 *
	 * @param string $message Internal message (for debug only)
	 * @param Action $alternateAction Action thrown in 'preprod' and 'prod' mode (default is 404 error)
	 */
	public function __construct(string $message, Action $alternateAction = NULL) {

		if(LIME_ENV === 'dev' or LIME_ENV === 'preprod' or LIME_ENV === 'prod') {

			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

			if(
				($backtrace[1]['class'] ?? NULL) === 'Action' and
				($backtrace[1]['function'] ?? NULL) === '__callStatic'
			) {
				$start = 2;
			} else {
				$start = 1;
			}

			$backtrace = array_slice($backtrace, $start);

			ob_start();
			\dev\ErrorPhpLib::handleFromBacktrace($backtrace, $message);

			$this->wrong = ob_get_clean();

		}

		$this->alternateAction = $alternateAction;

	}

	public function run(): void {

		if(LIME_ENV === 'dev') {

			$action = $this->alternateAction ? get_class($this->alternateAction) : 'BackAction()';

			$display = substr(rtrim($this->wrong), 0, -6);
			$display .= '<p>A '.$action.' will be thrown in PROD environment</p>';
			$display .= '</div>'."\n";

			$action = new DataAction($display);
			$action->setStatusCode(404);
			$action->run();

		} else {

			if($this->alternateAction) {
				$this->alternateAction->run();
			} else {
				(new BackAction())->run();
			}

		}

	}

	protected function convert($value): string {

		$text = '';

		if($value !== NULL) {

			if($value instanceof Element) {
				$text .= ': '.$value->getModule();
				if(isset($value['id'])) {
					$text .= ' #'.$value['id'];
				}
				$text .= '';
			} else if(is_scalar($value)) {
				$text .= ': '.$value;
			}

		}

		return $text;

	}

}

class NotExistsAction extends NotAction {

	public function __construct($value = '', Action $alternateAction = NULL) {

		$message = 'Data does not exist';
		$message .= $this->convert($value);

		parent::__construct($message, $alternateAction);

	}

}

class NotExpectedAction extends NotAction {

	public function __construct($value = '', Action $alternateAction = NULL) {

		$message = 'Data is unexpected';
		$message .= $this->convert($value);

		parent::__construct($message, $alternateAction);

	}

}

class NotAllowedAction extends NotAction {

	public function __construct($value = '', Action $alternateAction = NULL) {

		$message = 'Data access is not allowed';
		$message .= $this->convert($value);

		parent::__construct($message, $alternateAction);

	}

}

/**
 * FailAction: handle fails
 *
 * @author Vincent Guth
 */
class FailAction extends Action {

	/**
	 * Fail
	 *
	 * @var string
	 */
	protected $fail;

	/**
	 * Create the action with some data
	 *
	 * @param mixed $fail A FailWatch object or a fail string
	 */
	public function __construct($fail) {

		$this->fail = $fail;

	}

	public function run(): void {

		if($this->fail instanceof FailWatch) {

			switch(Route::getRequestedWith()) {

				case 'cli' :
				case 'http' :
					(new DataAction((string)$this->fail."\n"))->run();
					break;

				default :

					$t = new AjaxTemplate();
					$t->js()->errors($this->fail);

					(new JsonAction($t
						->pushInstructions()
						->getOutput()))->run();

					break;

			}

		} else if(is_string($this->fail)) {

			(new DataAction('Fail: '.$this->fail."\n"))->run();

		}

	}

}

/**
 * Reload action: reload the current page
 *
 * @author Vincent Guth
 */
class ReloadAction extends Action {

	protected bool $layer = FALSE;

	public function __construct(
		protected string $package = '',
		protected string $fqn = ''
	) {

	}

	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'cli' :
			case 'http' :

				$url = $_SERVER['REQUEST_URI'];

				if($this->package and $this->fqn) {
					$url .= strpos($url, '?') === FALSE ? '?' : '&';
					$url .= 'success='.$this->package.':'.$this->fqn;
				}

				throw new RedirectAction($url);

			case 'ajax' :

				$t = new AjaxTemplate();

				if($this->layer) {
					$t->ajaxReloadLayer();
				} else {
					$t->ajaxReload();
				}

				if($this->package and $this->fqn) {
					$t->js()->success($this->package, $this->fqn);
				}

				(new JsonAction($t
					->pushInstructions()
					->getOutput()))->run();

		}

	}

}

class ReloadLayerAction extends ReloadAction {

	protected bool $layer = TRUE;

}

/**
 * History action: navigates in the history
 *
 * @author Vincent Guth
 */
class HistoryAction extends Action {

	public function __construct(
		protected int $number,
		protected string $package = '',
		protected string $fqn = ''
	) {

	}

	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'cli' :
			case 'http' :
				throw new DataAction('<script>history.go('.$this->number.')</script>');

			case 'ajax' :

				$t = new AjaxTemplate();
				$t->js()->moveHistory($this->number);

				if($this->package and $this->fqn) {
					$t->js()->success($this->package, $this->fqn);
				}

				(new JsonAction($t
					->pushInstructions()
					->getOutput()))->run();

		}

	}

}

/**
 * History action: go back to history
 *
 * @author Vincent Guth
 */
class BackAction extends HistoryAction {

	public function __construct(string $package = '',string $fqn = '') {
		parent::__construct(-1, $package, $fqn);
	}

}

/**
 * History action: go forward to history
 *
 * @author Vincent Guth
 */
class ForwardAction extends HistoryAction {

	public function __construct(string $package = '',string $fqn = '') {
		parent::__construct(1, $package, $fqn);
	}

}

/**
 * Action wich consists to redirect to a specific URL
 *
 * @author Olivier Issaly
 */
class RedirectAction extends Action {

	/**
	 * Http status
	 *
	 * @var string
	 */
	protected string $httpStatus = "HTTP/1.1 301 Moved Permanently";


	/**
	 * RedirectAction constructor
	 *
	 * @param string $url URL to redirect to
	 * @param stdClass $data Add data for JSON output only
	 */
	public function __construct(
		protected string $url,
		protected string $mode = 'assign'
	) {

	}

	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'http' :

				header($this->httpStatus);
				header('Location: '.$this->url);

				break;

			case 'cli' :
				echo "Redirect: ".$this->url."\n";
				break;

			default :
				$this->setContentType('application/json; charset=utf-8');
				$this->sendContentType();

				$json = (new AjaxTemplate())
				   ->redirect($this->url, $this->mode)
					->pushInstructions()
					->getOutput();

				(new JsonAction($json))->run();
				break;

		}
	}

}

/**
 * Action wich consists to redirect permanently to a specific URL
 *
 * @author Olivier Issaly
 */
class PermanentRedirectAction extends RedirectAction {


}

/**
 * Action wich consists to redirect temporarily to a specific URL
 *
 * @author Olivier Issaly
 */
class TemporaryRedirectAction extends RedirectAction {

	protected string $httpStatus = "HTTP/1.1 302 Moved Temporarily";

}

/**
 * Takes an array and produces JSON
 *
 * @author Vincent Guth
 */
class JsonAction extends Action {

	/**
	 * Content type
	 *
	 * @var string
	 */
	protected $contentType = 'application/json; charset=utf-8';

	/**
	 * Some data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Create a JsonAction with some data
	 *
	 * @param array $data
	 */
	public function __construct(array $data = []) {

		$this->data = $data;

		parent::__construct();


	}

	/**
	 * Print data
	 */
	public function run(): void {

		$this->sendContentType();

		echo JsonView::formatArray($this->data);

	}

}

/**
 * Takes an array and outputs CSV
 *
 * @author Émilie Guth
 */
class CsvAction extends Action {

	/**
	 * Content type
	 *
	 * @var string
	 */
	protected $contentType = 'application/csv; charset=utf-8';

	/**
	 * Some data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Create a CsvAction with some data
	 *
	 * @param array $data
	 */
	public function __construct(string $data, bool $attachment = FALSE, ?string $filename = NULL) {

		$this->data = $data;

		if($attachment and $filename !== NULL) {
			if(substr($filename, -4) !== '.csv') {
				$filename .= '.csv';
			}
			$this->contentDisposition = 'attachment; filename="'.$filename.'"';
		}

		parent::__construct();


	}

	/**
	 * Print data
	 */
	public function run(): void {

		$this->sendContentType();
		$this->sendContentDisposition();

		echo $this->data;

	}

}

/**
 * Action which lets a PHP script to format
 *
 * @author Olivier Issaly
 */
class ViewAction extends Action {

	/**
	 * View filename
	 *
	 */
	protected ?string $viewFile;

	/**
	 * View name
	 */
	protected ?string $viewName;

	public function __construct(
		private ?stdClass $data = NULL,
		protected ?string $path = NULL
	) {

		if($this->data === NULL) {
			$this->data = new stdClass;
		}

	}

	public function getViewFile(): ?string {
		return $this->viewFile;
	}

	public function getViewName(): ?string {
		return $this->viewName;
	}

	public function run(): void {

		if($this->path === NULL) {

			$request = Page::getRequest();

			$this->viewFile = Package::getFileFromUri($request, 'view');
			$this->viewName = Page::getName();

		} else {

			if(strpos($this->path, ':') === 0) {

				$request = Page::getRequest();

				$this->viewFile = Package::getFileFromUri($request, 'view');
				$this->viewName = substr($this->path, 1);

			} else {

				if(strpos($this->path, ':') !== FALSE) {

					$request = strstr($this->path, ':', TRUE);

					$this->viewFile = Package::getFileFromUri($request, 'view');
					$this->viewName = substr($this->path, strpos($this->path, ':') + 1);

				} else {

					$request = $this->path;

					$this->viewFile = Package::getFileFromUri($request, 'view');
					$this->viewName = 'index';

				}

			}

		}

		if($this->viewFile === NULL) {

			trigger_error("View '".$this->path."' does not exist", E_USER_ERROR);
			exit;

		} else {

			require_once $this->viewFile;

		}

		if($this->gzip) {
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}

		$view = View::get($this->viewName, $this);

		if($this->hasContentType() === FALSE) {
			$this->setContentType($view->getContentType());
		}

		$this->sendContentType();

		$view->render($this->data);

	}

}

?>
