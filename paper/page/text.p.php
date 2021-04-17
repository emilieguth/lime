<?php
(new Page(function($data) {

		$data->eText = \paper\TextLib::getById(REQUEST('text', '?int'))->validate();

	}))
	->post('imageMetadata', function($data) {

		$xyz = POST('xyz');

		if($xyz === '') {
			throw new NotExistsAction('xyz');
		}

		$data->xyz = $xyz;

		throw new ViewAction($data);

	});
?>
