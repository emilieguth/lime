<?php
(new Page())
	/**
	 * Run forgotten password
	 */
	->post('do', function($data) {

		$fw = new FailWatch;

		$email = POST('email');

		$eUserAuth = user\UserLib::checkForgottenPasswordLink($email);

		if($fw->ok()) {

			user\UserLib::sendForgottenPasswordLink($eUserAuth);

			$data->email = $email;

			throw new ReloadAction('user', 'User::forgottenPasswordSend');

		} else {

			throw new FailAction($fw);

		}

	})
	/**
	 * Page to reset the password
	 *
	 */
	->get('set', function($data) {

		$hash = GET('hash');
		$email = GET('email');

		$fw = new FailWatch;

		$eUser = user\UserLib::getUserByHashAndEmail($hash, $email);

		if($fw->ok()) {

			$data->hash = $hash;
			$data->email = $eUser['email'];

			throw new ViewAction($data);

		} else {

			throw new FailAction($fw);

		}

	})
	/**
	 * Change password
	 */
	->post('doReset', function($data) {

		$hash = POST('hash');
		$email = POST('email');

		$fw = new FailWatch;

		$eUser = user\UserLib::getUserByHashAndEmail($hash, $email);

		if($fw->ok()) {

			user\SignUpLib::matchBasicPassword('reset', $eUser, $_POST);

		}

		if($fw->ok()) {

			user\SignUpLib::updatePassword($eUser);
			user\UserLib::cleanForgottenPasswordHashByUser($eUser);

		}

		if($fw->ok()) {
			throw new RedirectAction('/?success=user:User::passwordReset');
		} else {
			throw new FailAction($fw);
		}

	});
?>
