<?php
(new Page(fn() => Privilege::check('paper\admin')))
	/**
	 * Delete a forum
	 */
	->post('doDelete', function($data) {

		$data->eForum = \paper\ForumLib::getById(POST('id'));

		$fw = new FailWatch;

		paper\ForumLib::changeDelete($data->eForum);

		if($fw->ok()) {

			$data->eForum['lastPublications'] = new Collection();

			throw new ViewAction($data);
		} else {
			throw new FailAction($fw);
		}

	})
	/**
	 * Invert active status of a forum
	 */
	->post('doActive', function($data) {

		$data->eForum = \paper\ForumLib::getById(POST('id'));

		paper\ForumLib::changeActive($data->eForum);

		$data->eForum['lastPublications'] = new Collection();

		throw new ViewAction($data);

	})
	/**
	 * Change position of forums
	 */
	->post('doPosition', function($data) {

		$positions = POST('positions', 'array');

		$cForum = Collection::fromArray($positions, 'paper\Forum');

		paper\ForumLib::changePosition($cForum);

		throw new VoidAction();

	});

(new \paper\ForumPage(
		function($data) {
			Privilege::check('paper\admin');
		},
		propertiesCreate: ['name', 'description'],
		propertiesUpdate: ['name', 'description']
	))
	->create()
	->doCreate(function($data) {
		throw new BackAction('paper', 'Forum::created');
	})
	->update()
	->doUpdate(function($data) {
		throw new BackAction('paper', 'Forum::updated');
	});
?>
