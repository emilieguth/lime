<?php
(new Page(function($data) {
	
		user\ConnectionLib::checkLogged();
	
	}))
	/**
	 * Form to change its email
	 */
	->get('email', function($data) {

		if(user\SignUpLib::canUpdate($data->eUser)['email'] === FALSE) {
			throw new NotExpectedAction('Can not update email for this user');
		}

		match(Route::getRequestedWith()) {
			'api' => throw new ViewAction($data, path: ':email.api'),
			default => throw new ViewAction($data)
		};

	})
	/**
	 * E-mail address verified!
	 */
	->get('emailVerified', function($data) {

		throw new ViewAction($data);

	})
	/**
	 * Change email
	 */
	->post('doEmail', function($data) {

		if(user\SignUpLib::canUpdate($data->eUser)['email'] === FALSE) {
			throw new NotExpectedAction('Can not update email for this user');
		}

		$fw = new FailWatch;

		$eUser = user\ConnectionLib::getOnline();
		$eUser->buildEmail(user\UserAuth::BASIC, $_POST);

		if($fw->ok()) {
			user\SignUpLib::updateEmail($eUser);
		}

		if($fw->ok()) {
			throw new ReloadAction('user', 'User::emailUpdated');
		} else {
			throw new FailAction($fw);
		}

	})
	/**
	 * Form to change its password
	 */
	->get('password', function($data) {

		if(user\SignUpLib::canUpdate($data->eUser)['password'] === FALSE) {
			throw new NotExpectedAction('Can not update password for this user');
		}

		throw new ViewAction($data);

	})
	/**
	 * Change password
	 */
	->post('doPassword', function($data) {

		$fw = new FailWatch;

		$eUser = \user\ConnectionLib::getOnline();

		user\SignUpLib::matchBasicPassword('update', $eUser, $_POST);

		if($fw->ok()) {
			user\SignUpLib::updatePassword($eUser);
		}

		if($fw->ok()) {
			throw new ReloadAction('user', 'User::passwordUpdated');
		} else {
			throw new FailAction($fw);
		}

	});
?>
