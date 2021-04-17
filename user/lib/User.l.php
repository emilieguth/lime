<?php
namespace user;

/**
 * User basic functions
 */
class UserLib extends UserCrud {

	use \Notifiable;

	/**
	 * Cache for country list
	 *
	 * @var \Collection
	 */
	private static ?\Collection $cCountryList = NULL;

	public static function count(): int {

		return \Cache::redis()->query('user-count', function() {

			return User::model()
				->whereStatus(User::ACTIVE)
				->count();

		}, 86400);

	}

	public static function getFromQuery(string $query, ?array $properties = []): \Collection {

		if(strpos($query, '#') === 0 and ctype_digit(substr($query, 1))) {

			\user\User::model()->whereId(substr($query, 1));

		} else {

			\user\User::model()->where('
				CONCAT(firstName, " ", lastName) LIKE '.\user\User::model()->format('%'.$query.'%').' OR
				email = '.\user\User::model()->format($query).'
			');

		}

		return \user\User::model()
			->select($properties ?: User::getSelection())
			->whereStatus(\user\User::ACTIVE)
			->getCollection(0, 20);

	}

	/**
	 * Gets a user by his email
	 */
	public static function getByEmail(string $email, array $properties): User {

		if(empty($email)) {
			return new User();
		}

		return User::model()
			->select($properties)
			->whereEmail($email)
			->get();
	}

	/**
	 * Triggers the email verification send
	 */
	public static function triggerSendVerifyEmail(User $eUser, bool $change) {
		self::notify('sendVerifyEmail', $eUser, $change);
	}

	/**
	 * Return the last known IP of a user by searching the login logs.
	 */
	public static function getLastKnownIp(User $eUser) {

		$eUser->expects(['id']);

		return Log::model()->select('ip')
			->whereUser($eUser)
			->whereAction(Log::LOGIN)
			->sort(['createdAt' => 'DESC'])
			->getValue('ip');

	}

	/**
	 * Get all user which login actions were on the same $ip address.
	 * /!\ This method is slow and requests all the log tables /!\
	 */
	public static function getByIp(string $ip): array {

		$cLog = Log::model()
			->select(['uniqUser' => new \Sql('DISTINCT(user)')])
			->whereAction(Log::LOGIN)
			->whereIp($ip)
			->getCollection(NULL, NULL, 'uniqUser');

		return $cLog->getColumn('uniqUser');
	}

	/**
	 * Count all user which login actions were on the same $ip address.
	 * /!\ This method is slow and requests all the log tables /!\
	 */
	public static function countByIp(string $ip): int {

		return count(self::getByIp($ip));

	}

	/**
	 * Register privileges of the given user
	 */
	public static function registerPrivileges(User $eUser) {

		if($eUser->empty()) {
			return;
		}

		$eUser->expects(['role']);

		$can = $eUser['role']['can'];

		foreach($can as $package => $privileges) {
			\Privilege::register($package, $privileges, TRUE);
		}

	}

	/**
	 * Checks that the email can be used to reset the password
	 */
	public static function checkForgottenPasswordLink(string $email): ?UserAuth {

		// 1. check the email is a basic auth
		$eUser = User::model()
			->select('id', 'email')
			->whereEmail($email)
			->get();

		if($eUser->empty()) {
			User::fail('email.check');
			return NULL;
		}

		$cUserAuth = UserAuth::model()
			->select('id', 'user', 'type')
			->whereUser($eUser)
			->getCollection(NULL, NULL, 'type');

		if($cUserAuth->count() === 0) {
			User::fail('internal');
			return NULL;
		}

		foreach([UserAuth::BASIC] as $type) {

			if(isset($cUserAuth[$type])) {

				if($type === UserAuth::BASIC) {

					// 2. generate the hash
					$eUserAuth = new UserAuth([
						'hashExpirationDate' => new \Sql('NOW() + INTERVAL 3 HOUR'),
						'passwordHash' => md5(password_hash(\Setting::get('user\forgottenPasswordSalt').$eUser['id'].time())),
						'user' => $eUser
					]);

					return $eUserAuth;

				}
			}

		}


	}

	/**
	 * Sets the hash, the expiration date of the hash and sends the email
	 */
	public static function sendForgottenPasswordLink(UserAuth $eUserAuth): bool {

		$affected = UserAuth::model()
			->select('passwordHash', 'hashExpirationDate')
			->whereType(UserAuth::BASIC)
			->whereUser($eUserAuth['user'])
			->update($eUserAuth);

		if($affected === 1) {

			$content = MailUi::getForgottenPasswordMail(
				$eUserAuth['passwordHash'],
				$eUserAuth['user']['email']
			);

			(new \mail\MandrillLib())
				->setTo($eUserAuth['user']['email'])
				->setContent(...$content)
				->send();

			return TRUE;
		} else {
			return FALSE;
		}

	}

	/**
	 * Deletes old password hashes
	 *
	 */
	public static function cleanForgottenPasswordHash(): int {

		return UserAuth::model()
			->whereType(UserAuth::BASIC)
			->where('hashExpirationDate < NOW()')
			->update('passwordHash = NULL, hashExpirationDate = NULL');

	}

	/**
	 * Deletes old password hashes
	 *
	 */
	public static function cleanForgottenPasswordHashByUser(User $eUser): int {

		return UserAuth::model()
			->whereUser($eUser)
			->where('hashExpirationDate >= NOW()')
			->whereType(UserAuth::BASIC)
			->update('passwordHash = NULL, hashExpirationDate = NULL');

	}

	/**
	 * Checks that the user has this hash
	 */
	public static function getUserByHashAndEmail(string $hash, string $email): User {

		$eUser = User::model()
			->select('id', 'email')
			->whereEmail($email)
			->get();

		if($eUser->empty()) {
			User::fail('internal');
			return new User();
		}

		$eUserAuth = UserAuth::model()
			->select('id')
			->whereUser($eUser)
			->whereType(UserAuth::BASIC)
			->wherePasswordHash($hash)
			->where('hashExpirationDate > NOW()')
			->get();

		if($eUserAuth->empty()) {
			User::fail('invalidLinkForgot');
			return new User();
		}

		$eUser['auth'] = $eUserAuth;

		return $eUser;

	}


	/**
	 * Update seniority of users
	 */
	public static function updateSeniority(): void {

		User::model()
			->whereOnlineToday(TRUE)
			->update([
				'onlineToday' => FALSE,
				'seniority' => new \Sql('seniority + 1')
			]);
	}

	public static function create(User $e): void {
		throw new \Exception('Not implemented yet');
	}

	/**
	 * Update an existing user profile
	 *
	 */
	public static function update(User $e, array $properties): void {

		$e->expects(['id']);

		// Special case for e-mail
		$email = array_search('email', $properties);

		if($email !== FALSE) {

			unset($properties[$email]);
			SignUpLib::updateEmail($e, TRUE);

		}

		User::model()
			->select($properties)
			->update($e);

	}

	public static function delete(User $e): void {
		throw new \Exception('Not implemented yet');
	}

	/**
	 * Get a list of countries
	 *
	 * @return \Collection
	 */
	public static function getCountries(): \Collection {

		if(self::$cCountryList === NULL) {

			$cCountry = Country::model()
				->select([
					'id', 'name', 'code'
				])
				->sort('name')
				->getCollection(NULL, NULL, 'id');

			self::$cCountryList = $cCountry;

		}

		return self::$cCountryList;

	}

}
?>
