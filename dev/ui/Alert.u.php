<?php
namespace dev;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Error.closedByMessage' => s("Les erreurs correspondantes ont bien été validées !"),
			default => NULL

		};


	}

}
?>
