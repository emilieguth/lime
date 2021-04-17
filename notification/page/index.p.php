<?php
(new Page(function($data) {
		\user\ConnectionLib::checkLogged();
	}))
	/**
	 * Get a complete list of notifications for a user
	 */
	->get('list', function($data) {

		$number = Setting::get('notification\notificationsPerPage');
		$hasUnread = NULL;
		$hasUnclicked = NULL;

		$data->cEvent = \notification\NotificationLib::get($data->eUser, 0, $number, $hasUnread, $hasUnclicked);
		$data->nEvent = \notification\NotificationLib::count($data->eUser);

		if($hasUnread) {
			$data->unread = \notification\NotificationLib::updateUnread($data->eUser, $data->cEvent);
		} else {
			$data->unread = 0;
		}

		$data->hasUnclicked = $hasUnclicked;

		throw new ViewAction($data);
	})
	/**
	 * Get a complete list of notifications for a user
	 */
	->http('get', function($data) {

		$number = Setting::get('notification\notificationsPerPage');
		$data->offset = REQUEST('offset', 'int');

		$data->cEvent = \notification\NotificationLib::get($data->eUser, $data->offset, $number);
		$data->nEvent = \notification\NotificationLib::count($data->eUser);

		$data->unread = REQUEST('unread', 'int');

		throw new ViewAction($data);

	})
	->post('readAll', function($data) {

		\notification\NotificationLib::markAsClicked();

		throw new VoidAction();

	})

	->post('doClick', function($data) {

		\notification\NotificationLib::markAsClicked(POST('notification', '?int'));

		throw new VoidAction();

	})
	->post('readAll', function($data) {

		\notification\NotificationLib::updateUnread($data->eUser);

		$data->unreadNotificationsList = [];

		throw new ViewAction($data);

	});
?>
