<?php
(new Page(function($data) {

		user\ConnectionLib::checkLogged();

		$data->eUser->add(\user\UserLib::getById($data->eUser['id']));
		$data->can = \user\DropLib::canClose();

	}))
	/**
	 * Form to close its account
	 */
	->get('index', function($data) {

		throw new ViewAction($data);

	})
	/**
	 * Close account
	 */
	->post('do', function($data) {

		if($data->eUser['deletedAt'] === NULL and $data->can === FALSE) {
			throw new NotExpectedAction('Too late to close account', new RedirectAction('/user/close'));
		}

		user\DropLib::changeClose($data->eUser);

		throw new ReloadAction();

	});
?>
