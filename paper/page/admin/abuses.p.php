<?php
(new Page(fn() => Privilege::check('paper\admin')))
	/**
	 * Display abuses
	 */
	->get('index', function($data) {

		$data->cMessage = paper\AbuseLib::getOpen();

		throw new ViewAction($data);

	});
?>
