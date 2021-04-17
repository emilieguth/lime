<?php
(new Page(function($data) {

		$data->eForum = \paper\ForumLib::getById(REQUEST('id'))->validate(['active']);

	}))
	/**
	 * Display discussions of a forum
	 */
	->get(['/forum/{name}/{id}', '/forum/{name}/{id}/{page}'], function($data) {

		$name = GET('name');

		if($name !== $data->eForum['cleanName']) {
			throw new PermanentRedirectAction(\paper\ForumUi::url($data->eForum));
		}

		$data->page = GET('page', 'int');

		[$data->cDiscussion, $data->nPublication] = paper\DiscussionLib::getByForum($data->eForum, $data->page);
		$data->cForum = paper\ModerationLib::getForumsForMove($data->eForum);

		$data->nPage = $data->nPublication / Setting::get('publicationsPerPage');

		$data->newPublications = explode(',', GET('tid'));

		// Notifications for the forum
		if($data->isLogged) {
			$eType = \notification\TypeLib::getByFqn('discussion-open');
			[, $data->hasNotifications] = \notification\SubscriptionLib::canSend($data->eUser, $eType, $data->eForum['id']);
		} else {
			$data->hasNotifications = FALSE;
		}

		throw new ViewAction($data, path: ':display');

	})
	/**
	 * Subscribes to a forum
	 */
	->post('doSubscribe', function($data) {

		\user\ConnectionLib::checkLogged();

		$eType = \notification\TypeLib::getByFqn('discussion-open');

		if(POST('subscribe', 'bool')) {
			\notification\SubscriptionLib::subscribe($data->eUser, $eType, $data->eForum['id']);
			$data->subscribed = TRUE;
		} else {
			\notification\SubscriptionLib::unsubscribe($data->eUser, $eType, $data->eForum['id']);
			$data->subscribed = FALSE;
		}

		throw new ViewAction($data);

	});
?>
