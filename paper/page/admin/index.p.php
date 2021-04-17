<?php
/**
 * Show forums
 */
(new Page())
	->get('index', function($data) {

		$data->cForum = paper\ForumLib::getList();
		$data->cForum->setColumn('lastPublications', new Collection());

		throw new ViewAction($data);

	});

?>
