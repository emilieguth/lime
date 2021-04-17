<?php
(new Page())
	->post('query', function($data) {

		$query = POST('query');
		$data->cUser = \user\UserLib::getFromQuery($query);

		throw new \ViewAction($data);

	});
?>