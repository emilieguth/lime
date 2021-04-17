<?php
/**
 * Help about the editor
 */
(new Page())
	->http('editor', function($data) {

		throw new ViewAction($data);

	});
?>
