<?php
namespace notification;

/**
 * Messages by the team
 *
 * Sent with PublishLib::team() et teamLoggedRecently
 *
 */
class TeamUi {

	const BOTH_OUTAGE = 1;

	public function get(int $message): array {

		switch($message) {

			case self::BOTH_OUTAGE :
				return [
					'message' => s("Depuis ce matin 07:30, l'hébergeur de OuvreTaFerme subit de nombreux incidents, ce qui rend notre site difficilement accessible. Veuillez nous excuser pour ce problème, nous vous tiendrons informés de sa résolution."),
					'url' => '/'
				];

			default :
				throw new \Exception('Invalid message');

		}

	}

}
?>
