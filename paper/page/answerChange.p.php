<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eMessage = \paper\MessageLib::getById(REQUEST('id'));

	}))
	/**
	 * Form to update an answer
	 */
	->get('update', function($data) {

		$data->eMessage->validate(['isAnswer', 'canWrite']);

		$data->eDiscussion = $data->eMessage['discussion'];
		$data->eDiscussion['forum'] = $data->eMessage['forum'];

		$hash = \paper\DraftLib::getHash('answer', $data->eDiscussion, 'update');
		$data->eDraft = \paper\DraftLib::get($hash);

		throw new ViewAction($data);

	})
	/**
	 * Edit an answer
	 */
	->post('doUpdate', function($data) {

		$data->eMessage->validate(['isAnswer', 'canWrite']);

		$eDiscussion = $data->eMessage['discussion'];

		$fw = new FailWatch;

		\paper\DiscussionLib::buildAnswer('update', $data->eMessage, $_POST);

		if($fw->ok()) {

			\paper\DiscussionLib::updateAnswer($data->eMessage);
		}

		if($fw->ok()) {

			$hash = \paper\DraftLib::getHash('answer', $data->eMessage['discussion'], 'update');
			\paper\DraftLib::invalidate($hash);

			paper\AbuseLib::assignAbuseReported($data->eMessage);

			$url = \paper\MessageUi::url($eDiscussion, $data->eMessage);

			throw new RedirectAction($url);

		} else {
			throw new FailAction($fw);
		}

	})
	/**
	 * Delete an answer
	 */
	->post('doDelete', function($data) {

		$data->eMessage->validate(['isAnswer', 'canDelete']);

		\paper\DiscussionLib::deleteAnswer($data->eMessage);

		throw new ViewAction($data);

	});
?>
