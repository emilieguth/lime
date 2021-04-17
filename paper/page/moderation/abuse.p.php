<?php
(new Page(function($data) {

		Privilege::check('paper\moderation');

		$id = POST('message', '?int');
		$eMessage = paper\AbuseLib::get($id);

		if($eMessage->empty()) {
			throw new NotExistsAction('Message #'.$id.' has no valid abuse', new ReloadAction());
		}

		$data->eMessage = $eMessage;

	}))
	/**
	 * Censor a publication
	 */
	->post('doCensor', function($data) {

		\paper\MessageLib::censor($data->eMessage, TRUE);

		throw new ViewAction($data, path: ':doUpdate');

	})
	/**
	 * Lock a publication
	 */
	->post('doLock', function($data) {

		paper\AdminLib::lock($data->eMessage['discussion'], TRUE);

		throw new ViewAction($data, path: ':doUpdate');

	})
	/**
	 * Delete a message
	 */
	->post('doHide', function($data) {

		\paper\MessageLib::hide($data->eMessage, TRUE);

		throw new ViewAction($data, path: ':doDelete');

	})
	/**
	 * Mark as abusive
	 */
	->post('doYes', function($data) {

		paper\AbuseLib::close($data->eMessage, paper\Abuse::ABUSIVE);

		throw new ViewAction($data, path: ':doDelete');

	})
	/**
	 * Mark as not abusive
	 */
	->post('doNo', function($data) {

		paper\AbuseLib::close($data->eMessage, paper\Abuse::NOT_ABUSIVE);

		throw new ViewAction($data, path: ':doDelete');

	});
?>
