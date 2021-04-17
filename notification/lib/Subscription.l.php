<?php
namespace notification;

/**
 * Handle notification subscriptions
 */
class SubscriptionLib {

	private static array $types = [];


	/*
	 * Returns users that subscribe to something
	 */
	public static function getByType(Type $eType, int $reference = NULL): \Collection {

		return Subscription::model()
			->select('user')
			->whereType($eType)
			->whereReference($reference === NULL ? 0 : $reference)
			->whereSubscribed(TRUE)
			->getColumn('user');

	}

	/*
	 * User subscribes to a notification
	 */
	public static function subscribe(\user\User $eUser, Type $eType, int $reference = NULL): bool {
		return self::updateByUser(TRUE, $eUser, $eType, $reference);
	}

	/*
	 * User unsubscribes to a notification
	 */
	public static function unsubscribe(\user\User $eUser, Type $eType, int $reference = NULL): bool {
		return self::updateByUser(FALSE, $eUser, $eType, $reference);
	}

	protected static function updateByUser($subscribed, \user\User $eUser, Type $eType, int $reference = NULL): bool {

		if($eType['withReferences']) {
			if($reference === NULL) {
				return FALSE;
			}
		} else {
			$reference = NULL;
		}

		$eSubscription = new Subscription([
			'user' => $eUser,
			'type' => $eType,
			'reference' => $reference === NULL ? 0 : $reference,
			'subscribed' => $subscribed,
			'createdAt' => new \Sql('NOW()'),
			'device' => \util\DeviceLib::get()
		]);

		try {

			Subscription::model()->insert($eSubscription);

		} catch(\DuplicateException $e) {

			Subscription::model()
				->select('subscribed')
				->whereUser($eUser)
				->whereType($eType)
				->whereReference($reference)
				->update($eSubscription);

		}

		return TRUE;

	}

	/**
	 * Checks if we can send a notification to a user
	 */
	public static function canSend(\user\User $eUser, Type $eType, int $reference = NULL): bool {

		$eSubscription = Subscription::model()
			->select('subscribed')
			->whereUser($eUser)
			->whereType($eType)
			->whereReference($reference === NULL ? 0 : $reference)
			->get();

		if($eSubscription->notEmpty()) {
			return $eSubscription['subscribed'];
		}

		return $eType['defaultSubscribed'];

	}

}
?>
