<?php
(new Page(function($data) {
		Feature::check('user\signUp');
	}))
	/**
	 * Display sign up form
	 */
	->get('index', function($data) {

		user\ConnectionLib::checkAnonymous();

		throw new ViewAction($data, path: Setting::get('user\signUpView'));

	})
	/**
	 * Check that the email/password requested for sign up are valid.
	 * /!\ Used for mobile api only
	 */
	->post('check', function($data) {

		$fw = new FailWatch;

		$eUser = new \user\User();

		user\SignUpLib::match(user\UserAuth::BASIC, $eUser, $_POST);
		user\SignUpLib::matchBasicPassword('check', $eUser, $_POST);

		if($fw->ok()) {
			throw new VoidAction();
		} else {
			throw new FailAction($fw);
		}

	})
	/**
	 * Run sign up
	 */
	->post('create', function($data) {

		$fw = new FailWatch;

		$redirect = POST('redirect', '?string');

		$eUser = new \user\User();

		user\SignUpLib::match(user\UserAuth::BASIC, $eUser, $_POST);
		user\SignUpLib::matchBasicPassword('create', $eUser, $_POST);
		user\SignUpLib::checkTos($_POST);

		if($fw->ok()) {
			user\SignUpLib::create($eUser, FALSE);
		}

		if($fw->ok()) {

			user\ConnectionLib::logInUser($eUser, POST('remember', 'bool'));

			if($redirect) {
				if(strpos($redirect, '?') === FALSE) {
					$redirect .= '?';
				}
				throw new RedirectAction($redirect.'&success=user:User::welcomeCreate');
			}

			throw new RedirectAction(Lime::getUrl().'?success=user:User::welcomeCreate');

		} else {

			throw new FailAction($fw);

		}

	});
?>
