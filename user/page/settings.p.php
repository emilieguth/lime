<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->canUpdate = user\SignUpLib::canUpdate($data->eUser);
		$data->eUser['canUpdate'] = $data->canUpdate;

	}))
	->get('index', function($data) {

		throw new ViewAction($data);

	})
	->get('updateEmail', function($data) {

		if($data->canUpdate['email'] === FALSE) {
			throw new NotExpectedAction('Can\'t update email');
		}

		throw new ViewAction($data);

	})
	->get('updatePassword', function($data) {

		throw new ViewAction($data);

	})
	->get('dropAccount', function($data) {

		if($data->canUpdate['drop'] === FALSE) {
			throw new NotExpectedAction('Can\'t drop account');
		}

		$data->canCloseDelay = \user\DropLib::canClose();

		throw new ViewAction($data);

	});
?>
