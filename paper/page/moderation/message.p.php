<?php
(new Page(function($data) {

		Privilege::check('paper\moderation');

		$data->eMessage = \paper\MessageLib::getById(REQUEST('message', '?int'))->validate();

	}))
	/**
	 * Hide a message
	 */
	->post('doHide', function($data) {

		$fw = new FailWatch;

		\paper\MessageLib::hide($data->eMessage);

		if($fw->ok()) {
			throw new ViewAction($data);
		} else {
			throw new FailAction($fw);
		}

	})
	/**
	 * Censor / Uncensor a Message
	 */
	->post('doCensor', function($data) {

		$censor = POST('censor', 'bool');

		\paper\MessageLib::censor($data->eMessage, $censor);

		if($censor) {
			throw new ReloadAction('paper', 'Message.censored');
		} else {
			throw new ReloadAction('paper', 'Message.uncensored');
		}

	});
?>
