<?php
(new Page())
	/**
	 * Saves the current draft
	 */
	->post('doSave', function($data) {

		$eUser = \user\ConnectionLib::getOnline();

		$data->online = $eUser->notEmpty();

		if($data->online === FALSE) {
			throw new ViewAction($data);
		}

		$content = POST('content');

		if($content === '') {
			throw new NotExistsAction('new content');
		}

		$content = json_decode($content, TRUE);

		if($content === NULL) {
			throw new NotExpectedAction('invalid JSON in new content');
		}

		$initialContent = POST('initialContent');

		if($initialContent === '') {
			throw new NotExistsAction('initial content');
		}

		$initialContent = json_decode($initialContent, TRUE);

		if($initialContent === NULL) {
			throw new NotExpectedAction('invalid JSON in initial content');
		}

		$content = \paper\DraftLib::convertSave($content);
		$initialContent = \paper\DraftLib::convertSave($initialContent);

		if($content === NULL) {
			throw new NotExpectedAction('can not convert');
		}

		$hash = POST('hash');

		if(strlen($hash) !== 32 or ctype_alnum($hash) === FALSE) { // Not md5 ?
			throw new NotExpectedAction('invalid hash');
		}

		$timestamp = POST('timestamp', 'int');

		if($timestamp > 0) {
			$initializedAt = toDatetime($timestamp);
		} else {
			$initializedAt = currentDatetime();
		}

		$data->eDraft = \paper\DraftLib::save($hash, $content, $initialContent, $initializedAt);

		$data->hash = $hash;

		throw new ViewAction($data);

	})
	->post('doDelete', function($data) {

		$hash = POST('hash');

		if(strlen($hash) !== 32 or ctype_alnum($hash) === FALSE) { // Not md5 ?
			throw new NotExpectedAction('invalid hash');
		}

		$fw = new FailWatch();

		\paper\DraftLib::delete($hash);

		if($fw->ok()) {

			throw new ReloadAction();

		} else {

			throw new FailAction($fw);

		}

	});

?>
