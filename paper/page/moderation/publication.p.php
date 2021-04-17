<?php
(new Page(function($data) {

		Privilege::check('paper\moderation');

		$id = REQUEST('publication', '?int');
		$eDiscussion = paper\DiscussionLib::getPublication($id);

		if($eDiscussion->empty()) {
			throw new NotExistsAction('Discussion #'.$id);
		}

		$data->eDiscussion = $eDiscussion;

	}))
	/**
	 * Lock / Unlock a publication
	 */
	->post('doLock', function($data) {

		$lock = POST('lock', 'bool');

		paper\AdminLib::lock($data->eDiscussion, $lock);

		if($lock) {
			throw new ReloadAction('paper', 'Discussion::locked');
		} else {
			throw new ReloadAction('paper', 'Discussion::unlocked');
		}

	})
	/**
	 * Move a publication from a forum to another
	 */
	->post('doMove', function($data) {

		$eForumMove = POST('to', 'paper\Forum');

		if($eForumMove->empty()) {
			throw new NotExistsAction('Forum');
		}

		if(paper\ModerationLib::checkPublicationMove($data->eDiscussion['forum'], $eForumMove) === FALSE) {
			throw new NotExpectedAction('Origin and destination forums');
		}

		paper\AdminLib::moveDiscussion($data->eDiscussion, $eForumMove);

		throw new ReloadAction('paper', 'Discussion::moved');

	})
	/**
	 * Hide a publication
	 */
	->post('doHide', function($data) {

		paper\AdminLib::hide($data->eDiscussion);

		$url = paper\ForumUi::url($data->eDiscussion['forum']);

		throw new RedirectAction($url.'?success=paper:Discussion::hidden');

	});
?>
