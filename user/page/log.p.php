<?php
(new Page())
	/**
	 * Display login form
	 */
	->get('form', function($data) {

		user\ConnectionLib::checkAnonymous();

		user\ConnectionLib::loadLog($data);

		throw new ViewAction($data);

	})
	/**
	 * Display forgotten password form
	 */
	->get('forgottenPassword', function($data) {

		user\ConnectionLib::checkAnonymous();

		throw new ViewAction($data);

	})
	/**
	 * Log in
	 */
	->post('in', function($data) {

		$fw = new FailWatch;

		$remember = POST('remember', 'bool');

		user\ConnectionLib::logIn(
			POST('login'),
			POST('password'),
			['remember' => $remember]
		);

		if($fw->ok()) {

			$redirect = \user\ConnectionLib::getRedirectLogin();

			if($redirect) {
				throw new RedirectAction($redirect);
			} else {
				throw new ReloadAction('user', 'User::welcome');
			}

		} else {

			throw new FailAction($fw);

		}

	})
	/**
	 * Log out
	 */
	->post('out', function($data) {

		user\ConnectionLib::checkLogged();

		$eUser = user\ConnectionLib::getOnline();

		user\ConnectionLib::logOut($eUser);

		if(post_exists('redirect')) {
			$redirectUrl = POST('redirect');
		} else {
			$redirectUrl = '/';
		}

		throw new RedirectAction($redirectUrl.'?success=user:User::bye');

	})
	/**
	 * Log in external
	 */
	->post('doLoginExternal', function($data) {

		Privilege::check('user\admin');

		$eUser = \user\UserLib::getById(
				POST('user', '?int'),
				\user\ConnectionLib::selectLogin()
			)
			->validate();

		if(user\ConnectionLib::logInExternal($eUser, $data->eUser) === TRUE) {
			if(post_exists('redirect')) {
				throw new RedirectAction(POST('redirect'));
			}
			throw new RedirectAction('/');
		}

		throw new VoidAction();

	})
	/**
	 * Log from an external login
	 */
	->post('doLogoutExternal', function($data) {

		if(user\ConnectionLib::logOutExternal()) {
			if(post_exists('redirect')) {
				throw new RedirectAction(POST('redirect'));
			}
			throw new RedirectAction('/');
		}

		throw new VoidAction();

	});
?>
