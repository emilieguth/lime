<?php
namespace paper;

/**
 * Moderation features
 */
class ModerationLib {

	/**
	 * Get forum where it is possible to move a publication
	 */
	public static function getForumsForMove(Forum $eForum): \Collection {

		$eForum->expects(['id']);

		$cForum = Forum::model()
			->select('id', 'name')
			->whereId('!=', $eForum)
			->whereActive(TRUE)
			->sort('name')
			->getCollection(NULL, NULL, 'id');

		return $cForum;

	}


	/**
	 * Gets Information about messages from an Id list
	 */
	public static function getMessagesInfo(array $ids): \Collection {

		$properties = Message::model()->getProperties();
		$properties['forum'] = ['id', 'lastMessageAt'];
		$properties['discussion'] = Discussion::getSelection();
		$properties['text'] = Text::model()->getProperties();

		return Message::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection();

	}


	/**
	 * Gets Information about discussions from an Id list
	 */
	public static function getDiscussionsInfo(array $ids): \Collection {

		return Discussion::model()
			->select([
				'id', 'messages', 'lastMessageAt',
				'forum' => ['id', 'lastMessageAt', 'cleanName']
			])
			->whereId('IN', $ids)
			->getCollection();

	}

	/**
	 * Check if publications can be moved in the given forum
	 */
	public static function checkPublicationMove(Forum $eForumFrom, Forum $eForumTo): bool {

		$eForumTo->expects(['id']);

		$cForumMove = self::getForumsForMove($eForumFrom);

		return (
			isset($cForumMove[$eForumTo['id']])
		);

	}


}
?>
