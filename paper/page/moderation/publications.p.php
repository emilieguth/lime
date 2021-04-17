<?php
(new Page(function($data) {

		Privilege::check('paper\moderation');

		$ids = POST('ids', 'array');

		$data->cDiscussion = paper\ModerationLib::getDiscussionsInfo($ids);

		if($data->cDiscussion->empty()) {
			throw new NotExistsAction();
		}

	}))
	/**
	 * Lock / Unlock a publication
	 */
	->post('doLock', function($data) {

		$lock = POST('lock', 'bool');

		paper\AdminLib::lock($data->cDiscussion, $lock);

		if($lock) {
			throw new ReloadAction('paper', 'Discussion::lockedCollection');
		} else {
			throw new ReloadAction('paper', 'Discussion::unlockedCollection');
		}

	})
	/**
	 * Move a publication from a forum to another
	 */
	->post('doMove', function($data) {

		$eForumMove = POST('to', 'paper\Forum');

		if($data->cDiscussion->count() === 0) {
			throw new NotExistsAction();
		}

		$eDiscussion = $data->cDiscussion->current();

		if(
			paper\ModerationLib::checkPublicationMove($eDiscussion['forum'], $eForumMove) === FALSE
		) {
			throw new NotExpectedAction();
		}

		foreach($data->cDiscussion as $eDiscussion) {
			paper\AdminLib::moveDiscussion($eDiscussion, $eForumMove);
		}

		throw new ReloadAction('paper', 'Discussion::movedCollection');

	})
	/**
	 * Hide a publication
	 */
	->post('doHide', function($data) {

		foreach($data->cDiscussion as $eDiscussion) {
			paper\AdminLib::hide($eDiscussion);
		}

		$url = paper\ForumUi::url($eDiscussion['forum']);

		throw new RedirectAction($url.'?success=paper:Discussion::hiddenCollection');

	});
?>
