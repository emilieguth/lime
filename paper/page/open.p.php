<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->eForum = \paper\ForumLib::getById(REQUEST('forum', '?int'))->validate(['active']);

	}))
	/**
	 * Form to create a new publication
	 */
	->get('create', function($data) {

		// We take back a draft if there is one
		$hash = \paper\DraftLib::getHash('create-open', $data->eForum);
		$data->eDraft = \paper\DraftLib::get($hash);

		// Get the number of messages of all kind posted by this user
		$data->isFirstPost = \paper\ForumLib::hasNoPost($data->eUser);

		throw new ViewAction($data);

	})
	/**
	 * Creates the publication
	 */
	->post('doCreate', function($data) {

		$fw = new FailWatch;

		$eDiscussion = new \paper\Discussion([
			// needed in MessageLib::match
			'write' => \paper\Discussion::OPEN,
			'openMessage' => new \paper\Message(),
			'forum' => $data->eForum
		]);

		// Check if publication is OK
		\paper\DiscussionLib::buildOpen('create', $eDiscussion, $_POST);

		if($fw->ok()) {

			\paper\DiscussionLib::createOpen($eDiscussion);

		}

		if($fw->ok()) {

			$hash = \paper\DraftLib::getHash('create-open', $data->eForum);
			\paper\DraftLib::invalidate($hash);

			$eType = \notification\TypeLib::getByFqn('discussion-answer');
			\notification\SubscriptionLib::subscribe($data->eUser, $eType, $eDiscussion['id']);

			\notification\PublishLib::newDiscussionOpen($eDiscussion, $eDiscussion['openMessage']);

			// Needed for the link for the redirection
			$eDiscussion['author'] = $data->eUser;

			$url = \paper\DiscussionUi::url($eDiscussion);

			$data->eDiscussion = $eDiscussion;

			throw new RedirectAction($url);

		} else {

			throw new FailAction($fw);

		}

	})
	/**
	 * Form to update a new publication
	 */
	->get('update', function($data) {

		$id = REQUEST('publication', '?int');
		$data->eDiscussion = \paper\DiscussionLib::getPublication($id);

		if($data->eDiscussion->empty()) {
			throw new NotExistsAction('Publication #'.$id);
		}

		if(\paper\DiscussionLib::canUpdate($data->eDiscussion) === FALSE) {
			throw new NotAllowedAction($data->eDiscussion);
		}

		if($data->eDiscussion['openMessage']->notEmpty()) {

			\paper\Message::model()
				->select(['text' => \paper\Text::getSelection()])
				->get($data->eDiscussion['openMessage']);

		}

		$hash = \paper\DraftLib::getHash('update-open', $data->eDiscussion);
		$data->eDraft = \paper\DraftLib::get($hash);

		throw new ViewAction($data);

	})
	/**
	 * Updates the publication
	 *
	 */
	->post('doUpdate', function($data) {

		$id = REQUEST('publication', '?int');
		$eDiscussion = \paper\DiscussionLib::getPublication($id);

		if($eDiscussion->empty()) {
			throw new NotExistsAction('Publication #'.$id);
		}

		if(\paper\DiscussionLib::canUpdate($eDiscussion) === FALSE) {
			throw new NotAllowedAction($data->eDiscussion);
		}

		if($eDiscussion['openMessage']->notEmpty()) {

			\paper\Message::model()
				->select([
					'author',
					'text' => \paper\Text::getSelection()
				])
				->get($eDiscussion['openMessage']);

		}

		$fw = new FailWatch;

		$action = POST('action', 'string', 'update');

		\paper\DiscussionLib::buildOpen($action, $eDiscussion, $_POST);

		if($fw->ok()) {

			\paper\DiscussionLib::updateOpen($action, $eDiscussion);

		}

		if($fw->ok()) {

			$hash = \paper\DraftLib::getHash('update-open', $eDiscussion);
			\paper\DraftLib::invalidate($hash);

			$data->eDiscussion = $eDiscussion;

			$url = \paper\DiscussionUi::url($eDiscussion);

			throw new RedirectAction($url);

		} else {

			throw new FailAction($fw);

		}

	});
?>
