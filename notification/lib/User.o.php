<?php
namespace notification;

/**
 * User observer handling
 */
class UserObserverLib {

	public static function dropDelete(\user\User $eUser) {

		SubscriptionLib::deleteByUser($eUser);

	}
}
?>
