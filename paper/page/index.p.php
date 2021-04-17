<?php
(new Page())
	->get('/forums', function($data) {

		$data->cForum = paper\ForumLib::getList(TRUE);

		\paper\ForumLib::pushPublications($data->cForum);

		throw new ViewAction($data);

	});
?>