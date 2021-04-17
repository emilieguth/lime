<?php
(new Page())
	->post('weekChange', function($data) {

		$data->id = POST('id');
		$data->year = POST('year');
		$data->onclick = POST('onclick');
		$data->default = POST('default');

		throw new ViewAction($data);

	});
?>
