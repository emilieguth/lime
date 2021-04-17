<?php
/**
 * Webhooks for mandrill
 */
(new Page())
	->post('verifyEmail', function($data) {

		$mandrillEvents = POST('mandrill_events', 'array');

		foreach($mandrillEvents as $mandrillEvent) {

			$events = json_decode($mandrillEvent, TRUE);

			foreach($events as $event) {

				$type = $event['event'];

				if(in_array($type, ['open', 'click']) === FALSE) {
					continue;
				}

				$email = $event['msg']['email'] ?? NULL;
				\user\MailLib::verifyAuto($email);

			}

		}

		throw new VoidAction();

	});
?>
