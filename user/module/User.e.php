<?php
namespace user;

trait UserElement {

	public static function getSelection(): array {

		return [
			'id', 'email',
			'firstName', 'lastName', 'country', 'birthdate',
			'bio',
			'createdAt', 'seniority', 'status',
			'vignette'
		];

	}

	public function active(): bool {
		return ($this['status'] === User::ACTIVE);
	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$emailKey = array_search('email', $properties);

		if($emailKey !== FALSE) {
			$this->buildEmail(UserAuth::BASIC, $input);
			unset($properties[$emailKey]);
		}

		return parent::build($properties, $input, [

			'birthdate.future' => function(?string $date): bool {

				if($date === NULL) {
					return TRUE;
				}

				return (\util\DateLib::compare($date, currentDate()) < 0);

			},

			'country.check' => function($eCountry): bool {

				return (
					$eCountry->empty() === FALSE and
					Country::model()->exists($eCountry)
				);

			}

		]);

	}

	/**
	 * Check if a user can be created with basic authentication using the given input
	 * - email: user[email] *
	 */
	public function buildEmail(string $auth, array $input): bool {

		$this->add([
			'auth' => new UserAuth()
		]);

		$fw = new \FailWatch;

		parent::build(['email'], $input, [

			'email.auth' => function($email) use($auth) {
				return ($auth === UserAuth::BASIC);
			},

			'email.empty' => function($email) use($auth) {
				return ($email !== NULL);
			},

			'email.duplicate' => function($email) use($auth) {

				// User did not change his email address
				if(
					$this->offsetExists('email') and
					$this->offsetGet('email') === $email
				) {
					return TRUE;
				}

				// Block emails that can be used with IMAP auth
				foreach(\Setting::get('user\auth') as $key => $params) {

					if($key === \user\UserAuth::IMAP) {

						if(substr($email, -strlen($params['domain'])) === $params['domain']) {
							return FALSE;
						}

					}

				}

				// Checks that email is not already used
				return (User::model()
						->whereEmail($email)
						->exists() === FALSE);
			}

		]);

		if($fw->ok()) {

			$this['auth']['login'] = $this['email'];
			return TRUE;

		} else {
			return FALSE;
		}

	}
	
}
?>