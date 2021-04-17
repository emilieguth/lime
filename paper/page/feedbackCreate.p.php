<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eMessageMaster = \paper\MessageLib::getById(REQUEST('message', '?int'))->validate(['isNotFeedback']);

		$data->eDiscussion = $data->eMessageMaster['discussion'];

		if($data->eDiscussion['write'] !== \paper\Discussion::OPEN) {
			throw new NotExpectedAction($data->eMessageMaster);
		}

	}))
	/**
	 * Form to create a feedback
	 */
	->post('form', function($data) {

		throw new ViewAction($data);

	})
	/**
	 * Form to add a feedback
	 */
	->post('do', function($data) {

		$eMessage = new \paper\Message([
			'discussion' => $data->eDiscussion,
			'forum' => $data->eMessageMaster['forum']
		]);

		\paper\DiscussionLib::outdated($data->eUser, $data->eDiscussion);

		if(($data->eDiscussion['isOutdated'] ?? NULL) === TRUE) {
			Discussion::fail('outdated');
		}

		$fw = new FailWatch;

		paper\MessageLib::checkDelay($data->eUser);

		// Check if publication is OK
		\paper\DiscussionLib::buildFeedback('create', $eMessage, $_POST);

		if($fw->ok()) {

			$eMessage['answerOf'] = $data->eMessageMaster;

			// Create the message
			\paper\DiscussionLib::createFeedback($eMessage);

		}

		if($fw->ok()) {

			\notification\PublishLib::newDiscussionFeedback($data->eDiscussion, $eMessage);

			$data->cMessageFeedback = paper\MessageLib::getByAnswerOf($data->eMessageMaster, \paper\Message::FEEDBACK, TRUE);

			\notification\PublishLib::newFeedbackSiblings($data->eDiscussion, $eMessage, $data->cMessageFeedback);

			throw new ViewAction($data);

		} else {

			throw new FailAction($fw);

		}

	});
?>
