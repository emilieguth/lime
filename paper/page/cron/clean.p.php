<?php
/**
 * Forums cleaning
 *
 */
(new Page())
	->cron('index', function($data) {

		// deletes the old drafts (older than 1 day ago)
		\paper\DraftLib::clean();

		// delete forums
		\paper\ForumLib::deleteExpired();

	}, interval: '0 6 * * *');

?>
