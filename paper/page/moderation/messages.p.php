<?php
(new Page(function($data) {

		Privilege::check('paper\moderation');

		$ids = INPUT('ids', 'array');

		$data->cMessage = paper\ModerationLib::getMessagesInfo($ids);

		if($data->cMessage->empty()) {
			throw new NotExistsAction('Missing messages');
		}

	}))
	/**
	 * Hide a message
	 */
	->post('doHide', function($data) {

		$fw = new FailWatch;

		foreach($data->cMessage as $eMessage) {
			\paper\MessageLib::hide($eMessage);
		}

		if($fw->ok()) {
			throw new ViewAction($data);
		} else {
			throw new FailAction($fw);
		}

	})
	/**
	* Duplicate messages in new publication
	*/
	->get('duplicate', function($data) {

		throw new ViewAction($data);

	})
	->post('doDuplicate', function($data) {

		$fw = new FailWatch;

		paper\AdminLib::cleanBeforeDuplicate($data->cMessage);

		if($fw->ok()) {

			$title = POST('title', '?string');

			$eDiscussion = paper\AdminLib::createFromMessages($data->cMessage, $title, $fw);

			if($fw->ok()) {
				throw new RedirectAction(paper\DiscussionUi::url($eDiscussion));
			} else {
				throw new FailAction($fw);
			}

		} else {
			throw new FailAction($fw);
		}


	})
	/**
	 * Censor / Uncensor a set of Message
	 */
	->post('doCensor', function($data) {

		$censor = POST('censor', 'bool');

		paper\MessageLib::censorCollection($data->cMessage, $censor);

		if($censor) {
			throw new ReloadAction('paper', 'Message.censoredCollection');
		} else {
			throw new ReloadAction('paper', 'Message.uncensoredCollection');
		}

	});

?>
