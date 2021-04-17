<?php
namespace user;

/**
 * Users admin
 */
class AdminLib {

	/**
	 * Get all users
	 *
	 */
	public static function getUsers(int $page, array $condition, string $orderField): array {

		array_expects($condition, ['active', 'lastName', 'email']);

		$number = 100;
		$position = $page * $number;

		if(substr($orderField, -1) === '-') {
			$orderDirection = 'DESC';
			$orderField = substr($orderField, 0, -1);
		} else {
			$orderDirection = 'ASC';
		}

		if(in_array($orderField, ['id', 'email', 'lastName', 'ping']) === FALSE) {
			$orderField = 'id';
		}

		User::model()
			->select([
				'id',
				'firstName', 'lastName',
				'vignette',
				'createdAt', 'ping', 'email', 'status',
				'role' => ['name'],
				'auths' => \user\UserAuth::model()
					->select('type')
					->delegateCollection('user'),

			]);

		if($condition['active']) {
			User::model()->whereStatus(\user\User::ACTIVE);
		}

		if($condition['lastName']) {
			User::model()->whereLastName('LIKE', $condition['lastName']);
		}

		if($condition['email']) {
			User::model()->whereEmail('LIKE', $condition['email']);
		}

		if($condition['id']) {
			User::model()->whereId($condition['id']);
		}

		User::model()
			->sort([
				$orderField => $orderDirection
			])
			->option('count');

		$cUser = User::model()->getCollection($position, $number);

		return [$cUser, User::model()->found()];

	}
	
}
?>
