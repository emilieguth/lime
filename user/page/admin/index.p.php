<?php
(new Page(fn() => Privilege::check('user\admin')))
	->match(['get', 'post'], 'index', function($data) {

		$data->page = REQUEST('page', 'int');
		$data->order = REQUEST('order', 'string', 'id-');

		$data->condition = [
			'account' => GET('account', '?string'),
			'active' => GET('active', 'bool', TRUE),
			'id' => GET('id'),
			'lastName' => GET('lastName'),
			'email' => GET('email'),
		];

		[$data->cUser, $data->nUser] = \user\AdminLib::getUsers($data->page, $data->condition, $data->order);

		$data->isExternalConnected = \session\SessionLib::exists('userOld');

		throw new ViewAction($data);

	});

(new \user\UserPage(
		function($data) {
			Privilege::check('user\admin');
		},
		propertiesUpdate: ['email', 'birthdate', 'firstName', 'lastName']
	))
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('user', 'User::updated');
	});
?>
