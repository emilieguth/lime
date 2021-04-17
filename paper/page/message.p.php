<?php
(new Page(function($data) {

		Privilege::check('paper\abuseReport');

		$data->eMessage = \paper\MessageLib::getById(INPUT('message'))->validate();

	}))
	/**
	 * Form to report an abuse
	 */
	->get('abuse', function($data) {

		paper\AbuseLib::check($data->eMessage);

		throw new ViewAction($data);

	})
	/**
	 * Report an abuse
	 */
	->post('doAbuse', function($data) {

		paper\AbuseLib::check($data->eMessage);

		$fw = new FailWatch;

		$eAbuse = new \paper\Abuse([
			'discussion' => $data->eMessage['discussion'],
			'message' => $data->eMessage
		]);

		$eAbuse->build(['for', 'why'], $_POST);

		if($fw->ok()) {
			\paper\AbuseLib::create($eAbuse);
		}

		if($fw->ok()) {

			paper\AbuseLib::assignAbuseReported($data->eMessage);

			throw new ViewAction($data);

		} else {
			throw new FailAction($fw);
		}

	});
?>
