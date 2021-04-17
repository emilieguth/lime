<?php
namespace dev;

/**
 * Monitor errors
 *
 * @author Vincent Guth
 */
class ErrorMonitoringUi {

	public function __construct() {

		\Asset::css('dev', 'dev.css');
		\Asset::js('dev', 'dev.js');

		\Asset::css('dev', 'monitoring.css');
		\Asset::js('dev', 'monitoring.js');

	}

	/**
	 * Get form to search errors by message
	 *
	 * @return string
	 */
	public function getSearchForm(int $nError): string {

		$form = new \util\FormUi();
		
		$h = '<div id="form-backend">';
			$h .= '<form><input type="text" data-field="content" class="form-control" value="'.encode(GET('content')).'" placeholder="'.s("Message").'"/></form>';
			$h .= '<form>';
				$h .= '<div class="input-group">';
					$h .= $form->addon('#').'<input type="number" data-field="user" class="form-control" value="'.encode(GET('user')).'" placeholder="'.s("Utilisateur").'"/>';
				$h .= '</div>';
			$h .= '</form>';
			$h .= '<form><label><input type="checkbox" data-field="unexpected" value="1" '.(GET('unexpected', 'bool') ? 'checked="checked"' : '').'/> '.s("Unexpected").'</label></form>';
			$h .= '<a href="/dev/admin/" class="btn btn-secondary">'.\Asset::icon('x').'</a>';
			$h .= '<div>';
				$h .= '<span>'.p("{value} erreur", "{value} erreurs", $nError).'</span>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	/**
	 * Get form to close errors by message
	 *
	 * @return string
	 */
	public function getCloseForm(): string {

		\Asset::css('dev', 'admin.css');

		$form = new \util\FormUi();

		$h = '<div class="admin-dev-close">';

			$h .= $form->openAjax('/dev/admin/:doStatusByMessage');
				$h .= '<div class="input-group">';
					$h .= $form->text('message', '', ['style' => 'width: 300px', 'placeholder' => s("Valider les erreurs contenant...")]);
					$h .= $form->submit(\Asset::icon('check'), ['data-confirm' => s("Valider ces erreurs ?")]);
				$h .= '</div>';
			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display errors from a collection
	 */
	public function getErrors(\Collection $cError): string {

		$h = '<table id="errors-monitoring">';

		$selectedDate = NULL;

		foreach($cError as $eError) {

			$date = \util\DateUi::textual($eError['createdAt'], \util\DateUi::DATE);

			if($selectedDate !== $date) {
				$h .= '<tr class="new-day">
					<td colspan="4">'.$date.'</td>
				</tr>';
				$selectedDate = $date;
			}

			if(mb_strlen($eError['message']) > 15000) {
				$message = mb_substr($eError['message'], 0, 12000).'...';
			} else {
				$message = $eError['message'];
			}

			$h .= '<tr class="status-'.$eError['status'].'">';

			$h .= '<td>';
				$h .= '<div class="dev-error-main">';
					$h .= '<div>';
						if($eError['type'] !== Error::ANDROID and $eError['type'] !== Error::IOS) {
							$h .= substr($eError['request'], 0, 100).''.(strlen($eError['request']) > 100 ? '...' : '');
						} else {
							if($this->isAndroidRobot($eError['cParameter'])) {
								$h .= s("[Robot Google]").' ';
							}
							$h .= ucfirst($eError['type']);
						}
					$h .= '</div>';
					$h .= '<div>';
						$h .= strtoupper($eError['mode']);
						if($eError['modeVersion'] !== NULL) {
							$h .= ' '.$eError['modeVersion'];
						}
						if($eError['method'] !== NULL) {
							$h .= ' | '.$eError['method'];
						}
						if($eError['tag'] !== NULL) {
							$h .= ' | TAG '.$eError['tag'];
						}
					$h .= '</div>';
				$h .= '</div>';
			$h .= '<a data-action="admin-error-expand" class="dev-error-message"';
			if($eError['deprecated']) {
				$h .= ' style="text-decoration: line-through"';
			}
			$h .= '>'.encode($message).'</a>';

			$other = [];

			if($eError['file'] or $eError['line']) {
				$other[] = LIME_DIRECTORY.'/'.$eError['file'].' +'.$eError['line'];
			}

			if($eError['user']->notEmpty()) {

				switch($eError['app']) {
					case 'farm' :
						$other[] = s("Utilisateur {user}", ['user' => '<a href="'.\Setting::get('main\url').'/user/admin/?id='.$eError['user']['id'].'">#'.$eError['user']['id'].'</a>']);
						break;
					case 'today' :
						$other[] = s("Employé {user}", ['user' => '<a href="/people/">#'.$eError['user']['id'].'</a>']);
						break;
				}

			} else {
				$other[] = '<i>'.s("Utilisateur anonyme").'</i>';
			}

			if($eError['browser']) {
				$other[] = $eError['browser'];
			}

			if($eError['server']) {
				$other[] = $eError['server'];
			}

			if($eError['device']) {
				$other[] = $eError['device'];
			}

			$h .= '<div class="dev-error-other">'.implode(' | ', $other).'</div>';

			$h .= '<div class="dev-error-trace"';
			if($cError->count() === 1) {
				$h .= ' style="display: inline"';
			}
			$h .= '>';

			if($eError['cTrace']->count() > 0) {
				$h .= '<h5>Stack trace:</h5>';
				$h .= TraceLib::getHttp($eError['cTrace']);
			}

			if($eError['cParameter']->count() > 0) {

				$h .= '<h5>Parameters:</h5>';
				$h .= '<table class="table-striped">';

				foreach($eError['cParameter'] as $eParameter) {

					$data = unserialize($eParameter['value']);

					$h .= '<tr>
						<td>'.$eParameter['type'].'</td>
						<td>'.encode($eParameter['name']).'</td>
						<td>'.nl2br(encode(is_array($data) ? var_export($data, TRUE) : $data)).'</td>
					</tr>';
				}

				$h .= '</table>';

			}

			$h .= '<h5>Link:</h5>';
			$h .= (new \util\FormUi())->text('', \Lime::getUrl().'/dev/admin/?id='.$eError['id']);

			if($eError['referer']) {
				$h .= '<br />Referer: '.encode($eError['referer']);
			}

			$h .= '</div>';
			$h .= '</td>';

			switch($eError['type']) {

				case Error::EXCEPTION :
					$type = '<span class="color-danger">Exception</span>';
					break;

				case Error::UNEXPECTED :
					$type = 'Unexpected';
					break;

				default :

					switch($eError['code']) {

						case 'Fatal' :
							$type = '<span class="color-danger">'.$eError['code'].'</span>';
							break;

						case 'Warning' :
							$type = '<span class="color-warning">'.$eError['code'].'</span>';
							break;

						case 'Notice' :
							$type = '<span>'.$eError['code'].'</span>';
							break;

						default :
							$type = $eError['code'];
							break;

					}

			}

			$h .= '<td>'.$type.'</td>';
			$h .= '<td class="text-center">'.\util\DateUi::getTime($eError['createdAt']).'</td>';
			$h .= '<td id="error-status-'.$eError['id'].'" class="dev-error-checked">';
				$h .= $this->getErrorStatus($eError);
			$h .= '</td>';

			$h .= '</tr>';

		}

		$h .= '</table>';

		return $h;

	}

	public function getErrorStatus(Error $eError): string {

		if($eError['status'] === Error::OPEN) {
			return '<a data-ajax="/dev/admin/:doStatus" post-id="'.$eError['id'].'">'.\Asset::icon('check').'</a>';
		} else {
			return \Asset::icon('check');
		}

	}

	private function isAndroidRobot(\Collection $cParameter): bool {

		if($cParameter->count() === 0) {
			return FALSE;
		}

		foreach($cParameter as $eParameter) {

			$data = unserialize($eParameter['value']);

			if($eParameter['name'] === 'PHONE_MODEL' and strtolower($data) === 'full android on emulator') {
				return TRUE;
			}

		}

		return FALSE;

	}

}
?>
