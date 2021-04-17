<?php

namespace user;

/**
 * Handle features relative to user and mail sending
 */
class MailLib {

	const SALT = '4?5)§79_3df;3g(-444}';

	/**
	 * Return true if the user has verified his last specified mail address and false otherwise.
	 */
	public static function isVerified(User $eUser): bool {

		$eUser->expects(['email', 'verified']);

		return (
			$eUser['email'] !== NULL and
			$eUser['verified'] === TRUE
		);

	}

	/**
	 * Generates a hash check for mail unsubscription without entry in subscription table
	 */
	public static function getHashFromEmail(string $email): string {
		return md5(\user\MailLib::SALT.$email);
	}

	/**
	 * Sends the account closing confirmation mail
	 */
	public static function sendClose(User $eUser) {

		$eUser->expects(['id', 'email']);

		if($eUser['email'] === NULL) {
			return FALSE;
		}

		$content = MailUi::getCloseMail();

		(new \mail\MandrillLib())
			->setTo($eUser['email'])
			->setContent(...$content)
			->send();
	}

	/**
	 * Send the hash to the user's by email.
	 */
	public static function sendSignUp(User $eUser) {

		$eUser->expects(['id', 'email']);

		if($eUser['email'] === NULL) {
			return FALSE;
		}

		$content = MailUi::getSignUpMail($eUser);

		(new \mail\MandrillLib())
			->setTo($eUser['email'])
			->setContent(...$content)
			->send();

	}

	/**
	 * Send the hash to the user's by email to verify the address.
	 */
	public static function sendVerify(
		User $eUser,
		bool $change
	) {

		$eUser->expects(['id', 'email']);

		$hash = self::computeHash($eUser);

		$content = MailUi::getVerifyMail($eUser, $hash, $change);

		(new \mail\MandrillLib())
			->setTo($eUser['email'])
			->setContent(...$content)
			->send();

	}

	/**
	 * Verifies an email automatically (after click or open)
	 * Used by Mandrill webhook
	 */
	public static function verifyAuto(?string $email): bool {

		if($email === NULL) {
			return false;
		}

		$eUser = new User([
			'verified' => TRUE
		]);

		return (\user\User::model()
			->select('verified')
			->whereEmail($email)
			->update($eUser) === 1);

	}

	/**
	 * Verify that the $actualHash match this specific $eUser.
	 */
	public static function validate(string $actualHash) {

		if(strpos($actualHash, '.') !== FALSE) {

			$userId = (int)explode('.', $actualHash)[1];

			$eUser = UserLib::getById($userId, ['id', 'email']);

			if($eUser->empty()) {
				return User::fail('invalidHash');
			}

		} else {

			return User::fail('invalidHash');

		}

		if(ConnectionLib::isLogged()) {

			$eUserLogged = ConnectionLib::getOnline();

			if($eUser['id'] !== $eUserLogged['id']) {
				return User::fail('hashConnectedWrongAccount');
			}

		}

		$expectedHash = self::computeHash($eUser);

		if($expectedHash === $actualHash) {

			// Swith the user as verified
			$eUser['verified'] = TRUE;

			\user\User::model()
				->select('verified')
				->update($eUser);

		} else {
			return User::fail('invalidHash');
		}

	}

	/**
	 * Create a hash based on a user id and an email.
	 */
	public static function computeHash(User $eUser) {

		$eUser->expects(['id', 'email']); // Mail can be null

		return md5(self::SALT.$eUser['id'].$eUser['email'], PASSWORD_DEFAULT).'.'.$eUser['id'];

	}

}
?>
