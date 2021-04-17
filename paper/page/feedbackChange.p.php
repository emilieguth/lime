<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eMessage = \paper\MessageLib::getById(REQUEST('id', '?int'), ['authorCount' => TRUE])->validate(['isFeedback']);

		$data->eDiscussion = $data->eMessage['discussion'];


	}))
	/**
	 * Form to update a feedback
	 */
	->post('update', function($data) {

		$data->eMessage->validate(['canWrite']);

		throw new ViewAction($data);

	})
	/**
	 * Edit a feedback
	 */
	->post('doUpdate', function($data) {

		$data->eMessage->validate(['canWrite']);

		if(post_exists('cancel')) {
			throw new ViewAction($data);
		}

		$fw = new FailWatch;

		\paper\DiscussionLib::buildFeedback('update', $data->eMessage, $_POST);

		if($fw->ok()) {

			\paper\DiscussionLib::updateFeedback($data->eMessage);
		}

		if($fw->ok()) {

			throw new ViewAction($data);

		} else {
			throw new FailAction($fw);
		}

	})
	/**
	 * Delete a feedback
	 */
	->post('doDelete', function($data) {

		$data->eMessage->validate(['canDelete']);

		\paper\DiscussionLib::deleteFeedback($data->eMessage);

		throw new ViewAction($data);

	});
?>
