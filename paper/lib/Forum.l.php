<?php
namespace paper;

/**
 * Forum handling
 */
class ForumLib extends ForumCrud {

	public static function getById($id, array $properties = []): Forum {

		Forum::model()->where('deletedAt IS NULL OR deletedAt >= CURDATE()');

		return parent::getById($id, $properties);

	}

	public static function create(Forum $e): void {

		$e['cleanName'] = ToFqn($e['name']);

		parent::create($e);

	}

	public static function update(Forum $e, array $properties): void {

		$e['cleanName'] = ToFqn($e['name']);

		// Update forum
		Forum::model()
			->select('name', 'cleanName', 'description')
			->update($e);

	}

	public static function delete(Forum $e): void {

		$e->expects(['id', 'active']);

		if($e['active']) {
			throw new \Exception('Can not delete an active forum');
		}

		Forum::model()->beginTransaction();

		Text::model()
			->join(Message::model(), 'm1.id = m2.text')
			->where('m2.forum', $e['id'])
			->delete();

		TextHistory::model()
			->join(Message::model(), 'm1.id = m2.text')
			->where('m2.forum', $e['id'])
			->delete();

		Read::model()
			->join(Discussion::model(), 'm1.discussion = m2.id')
			->where('m2.forum', $e['id'])
			->delete();

		Abuse::model()
			->join(Discussion::model(), 'm1.discussion = m2.id')
			->where('m2.forum', $e['id'])
			->delete();


		Message::model()
			->whereForum($e)
			->delete();

		MessageHidden::model()
			->whereForum($e)
			->delete();

		Discussion::model()
			->whereForum($e)
			->delete();

		ForumRead::model()
			->whereForum($e)
			->delete();

		Forum::model()->delete($e);

		Forum::model()->commit();

	}

	/**
	 * Invert active status of a forum
	 *
	 *
	 */
	public static function changeActive(Forum $eForum) {

		$eForum->expects(['id', 'active']);

		$eForum['active'] = !$eForum['active'];

		Forum::model()
			->select('active')
			->update($eForum);

	}

	/**
	 * Delete expired forums
	 */
	public static function deleteExpired() {

		$cForum = Forum::model()
			->select('id', 'active')
			->where('deletedAt < NOW() - INTERVAL '.\Setting::get('deleteTimeout').' DAY')
			->whereActive(FALSE)
			->getCollection();

		foreach($cForum as $eForum) {
			self::delete($eForum);
		}

	}

	/**
	 * Delete a forum
	 */
	public static function changeDelete(Forum $eForum) {

		$eForum->expects(['id', 'active']);

		if($eForum['active']) {
			return Forum::fail('deleteActive');
		}

		if($eForum['deletedAt']) {
			$eForum['deletedAt'] = NULL;
		} else {
			$eForum['deletedAt'] = Forum::model()->now('datetime', \Setting::get('deleteTimeout').' DAY');
		}

		Forum::model()
			->select('deletedAt')
			->whereActive(FALSE)
			->update($eForum);

	}


	/**
	 * Change position of forums
	 *
	 */
	public static function changePosition(\Collection $cForum): void {

		// Get all valid forums
		$cForumValid = Forum::model()
			->select('id')
			->getCollection(NULL, NULL, 'id');

		// Update positions and categories
		Forum::model()->beginTransaction();

		$position = 0;

		foreach($cForum as $eForum) {

			if(isset($cForumValid[$eForum['id']]) === FALSE) {
				continue;
			}

			$eForum['position'] = $position++;

			Forum::model()
				->select('position')
				->update($eForum);

		}

		Forum::model()->commit();

	}

	/**
	 * Get forums
	 *
	 */
	public static function getList(bool $onlyActive = FALSE): \Collection {

		if($onlyActive) {
			Forum::model()->whereActive(TRUE);
		}

		return Forum::model()
			->select(Forum::getSelection())
			->where('deletedAt IS NULL OR deletedAt >= CURDATE()')
			->sort(['position' => 'ASC', 'id' => 'ASC'])
			->getCollection();

	}

	/**
	 * Add last publications to a group of forums
	 *
	 */
	public static function pushPublications(\Collection $cForum, int $number = 4) {

		foreach($cForum as $eForum) {

			$cDiscussion = Discussion::model()
				->select([
					'id', 'cleanTitle', 'title', 'createdAt',
					'messages', 'forum',
					'author' => \user\User::getSelection(),
					'lastMessage' => [
						'author' => \user\User::getSelection(),
						'createdAt',
					]

				])
				->sort(['lastMessageAt' => 'DESC'])
				->whereForum($eForum)
				->getCollection(0, $number);

			$eForum['lastPublications'] = $cDiscussion;

		}

	}

	/**
	 * Calculate Forum::$lastMessageAt value from Discussion::$lastMessageAt values
	 *
	 */
	public static function calculateLastMessage(Forum $eForum): array {

		return Discussion::model()
			->select('lastMessage', 'lastMessageAt')
			->whereForum($eForum)
			->sort(['lastMessageAt' => 'DESC'])
			->get()
			->getArrayCopy() + [
				'lastMessage' => NULL,
				'lastMessageAt' => NULL
			];

	}

	/**
	 * Get the number of messages posted by a specific user
	 * Load users info if this is the first post
	 *
	 */
	public static function hasNoPost(\user\User $eUser): bool {

		if($eUser->empty()) {
			return FALSE;
		}

		return \paper\Message::model()
			->whereAuthor($eUser)
			->whereDiscussion('!=', NULL)
			->count() === 0;

	}

}
?>
