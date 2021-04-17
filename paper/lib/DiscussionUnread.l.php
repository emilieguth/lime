<?php
namespace paper;

/**
 * Handle notifications
 */
class DiscussionUnreadLib {

	/**
	 * Get and create notification status for a forum and the current user
	 *
	 */
	public static function readForum(Forum $eForum) {

		$eForum->expects(['id']);

		$eUser = \user\ConnectionLib::getOnline();

		if($eUser->empty()) {
			return;
		}

		$cacheKey = 'forum-read-'.$eUser['id'].'-'.$eForum['id'];
		$cacheTimeout = 86400;

		return \Cache::redis()->query($cacheKey, function() use($eUser, $eForum) {

			$eForumRead = ForumRead::model()
				->select('discoveredAt')
				->whereUser($eUser)
				->whereForum($eForum)
				->get();

			if($eForumRead->empty()) {

				$eForumRead = new ForumRead([
					'user' => $eUser,
					'forum' => $eForum,
					'discoveredAt' => ForumRead::model()->now()
				]);

				ForumRead::model()->insert($eForumRead);

			}

			return $eForumRead;

		}, $cacheTimeout);

	}

	/**
	 * Select notifications for a publication
	 *
	 */
	public static function propertyDiscussion(\user\User $eUser) {

		if($eUser->empty()) {
			return NULL;
		}

		return Read::model()
			->select('messagesRead', 'lastMessageRead')
			->whereUser($eUser)
			->delegateElement('discussion');
	}

	/**
	 * Update notifications status when the user read a publication
	 */
	public static function readDiscussion(Discussion $eDiscussion): Read {

		$eDiscussion->expects(['id', 'messages', 'lastMessage']);

		$eUser = \user\ConnectionLib::getOnline();

		if($eUser->empty()) {
			return new Read();
		}

		$eReadOld = Read::model()
			->select('messagesRead', 'lastMessageRead')
			->whereUser($eUser)
			->whereDiscussion($eDiscussion)
			->get();

		$eReadNew = new Read([
			'user' => $eUser,
			'discussion' => $eDiscussion,
			'messagesRead' => $eDiscussion['messages'],
			'lastMessageRead' => $eDiscussion['lastMessage']
		]);

		Read::model()
			->option('add-replace')
			->insert($eReadNew);

		return $eReadOld;

	}

}
?>
