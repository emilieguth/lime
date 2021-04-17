<?php
namespace paper;

/**
 * Discussions
 */
class DiscussionLib {

	/**
	 * Get publications of a forum
	 *
	 */
	public static function getByForum(Forum $eForum, int $page): array {

		$eForum->expects(['id']);

		$eUser = \user\ConnectionLib::getOnline();

		$position = $page * \Setting::get('publicationsPerPage');
		$number = \Setting::get('publicationsPerPage');

		$cDiscussion = Discussion::model()
			->select([
				'id', 'title', 'cleanTitle', 'pinned', 'write', 'forum',
				'messages', 'lastMessage',
				'author' => \user\User::getSelection(),
				'notification' => DiscussionUnreadLib::propertyDiscussion($eUser),
				'createdAt', 'lastMessageAt'
			])
			->whereForum($eForum)
			->option('count')
			->sort(new \Sql('IF(pinned = 1, lastMessageAt + INTERVAL 1000 YEAR, lastMessageAt) DESC'))
			->getCollection($position, $number);

		if($eUser->notEmpty()) {

			$eNotificationForum = DiscussionUnreadLib::readForum($eForum);

			$cDiscussion->map(function(&$eDiscussion) use ($eNotificationForum) {

				if($eDiscussion['notification']->notEmpty()) {

					$eDiscussion['new'] = FALSE;
					$eDiscussion['unread'] = max(0, $eDiscussion['messages'] - $eDiscussion['notification']['messagesRead']);

				} else {

					if(strcmp($eNotificationForum['discoveredAt'], $eDiscussion['createdAt']) < 0) {
						$eDiscussion['new'] = FALSE;
					} else {
						$eDiscussion['new'] = TRUE;
					}

					$eDiscussion['unread'] = 0;

				}

			});

		} else {
			$cDiscussion->setColumn('new', FALSE);
			$cDiscussion->setColumn('unread', 0);
		}

		return [$cDiscussion, Discussion::model()->found()];

	}

	/**
	 * Checks if a publication can be updated by the current user
	 */
	public static function canUpdate(Discussion $e): bool {

		$e->expects(['isAuthor']);

		return $e['author']->notEmpty() and (
			\Privilege::can('paper\moderation') or
			$e['isAuthor']
		);

	}

	/**
	 * Check if a publication can be created using the given input for the current user
	 * - title: publication[title] *
	 * - value: text[value] *
	 */
	public static function buildOpen(string $action, Discussion $eDiscussion, array $input) {

		if($action === 'create') {

			$eUser = \user\ConnectionLib::getOnline();

			$eDiscussion['openMessage'] = new Message([
				'type' => Message::OPEN,
				'forum' => $eDiscussion['forum'],
				'discussion' => $eDiscussion,
				'text' => new Text(),
				'author' => $eUser,
				'answerOf' => new Message(),
				'first' => \paper\ForumLib::hasNoPost($eUser)
			]);

		} else {

			$eDiscussion->expects(['openMessage' => ['text']]);

			$eDiscussion['openMessage']['oldText'] = clone $eDiscussion['openMessage']['text'];
		}

		$eDiscussion->build(['title', 'pinned'], $input);

		self::buildMessage($eDiscussion['openMessage'], TRUE, $input);

	}


	/**
	 * Check if a ANSWER can be created using the given input for the current user
	 */
	public static function buildAnswer(string $action, Message $eMessage, array $input) {

		self::buildPush($action, 'answer', $eMessage, TRUE, $input);

		$eMessage['first'] = \paper\ForumLib::hasNoPost($eMessage['author']);

	}


	/**
	 * Check if a FEEDBACK can be created using the given input for the current user
	 */
	public static function buildFeedback(string $action, Message $eMessage, array $input) {

		self::buildPush($action, 'feedback', $eMessage, FALSE, $input);

	}

	private static function buildPush($action, $type, Message $eMessage, bool $acceptFigure, $input) {

		if($action === 'create') {

			$eMessage['text'] = new Text();
			$eMessage['author'] = \user\ConnectionLib::getOnline();

			$writeStatus = $eMessage['discussion']['write'];

			if($writeStatus !== Discussion::OPEN) {
				Discussion::fail('locked');
				return;
			}

		} else {

			$eMessage->expects(['text']);
			$eMessage['oldText'] = clone $eMessage['text'];

		}

		$eMessage['type'] = $type;

		self::buildMessage($eMessage, $acceptFigure, $input);

	}

	/**
	 * Apply an input to the given publication and returns a ready-to-use Text::$value
	 */
	public static function buildMessage(Message $eMessage, bool $acceptFigure, array $input): void {

		$value = $input['value'] ?? '';

		if($value === '') {
			Message::fail('value.check');
			return;
		}

		if(mb_strlen($value) > \Setting::get('paper\messageSizeMax')) {
			Message::fail('value.length');
			return;
		}

		$options = [
			'acceptFigure' => $acceptFigure,
		];

		$value = (new \editor\XmlLib())->fromHtml($value, $options);

		if($value === NULL) {
			Message::fail('value.check');
			return;
		}

		$eMessage['text']['value'] = $value;

	}

	/**
	 * Gets the meta
	 */
	public static function getMeta(Discussion $eDiscussion) {

		Message::model()
			->select([
				'text' => Text::getSelection()
			])
			->get($eDiscussion['openMessage']);

	}

	public static function getMessagesByDiscussion(Discussion $eDiscussion, int &$position = NULL, int &$number = NULL): \Collection {

		$eDiscussion->expects([
			'id',
		]);

		if(ctype_digit($position) or is_int($position)) {
			cast($position, 'int');
		} else {
			$position = 0;
		}

		if($number === NULL) {
			$number = \Setting::get('messagesPerPage');
		} else {
			$number = min($number, \Setting::get('messagesPerPage'));
		}

		$properties = Message::getFullSelection(TRUE, $position);

		$cMessage = Message::model()
			->select($properties)
			->whereType('!=', Message::FEEDBACK)
			->whereDiscussion($eDiscussion)
			->option('count')
			->sort('id')
			->getCollection($position, $number, 'id');

		return $cMessage;

	}

	public static function getMessagesAroundMessageByDiscussion(Discussion $eDiscussion, Message $eMessage, int &$position = NULL, int &$number = NULL): \Collection {

		$eDiscussion->expects([
			'id',
			'forum',
		]);

		if(Message::model()->exists($eMessage) === FALSE) {
			$eMessage = new Message();
			return self::getMessagesByDiscussion($eDiscussion, $position, $number);
		}

		$number = \Setting::get('messagesPerPage');

		$position = Message::model()
			->whereType('!=', Message::FEEDBACK)
			->whereDiscussion($eDiscussion)
			->where('id <= '.$eMessage['id'])
			->count() - (int)($number / 2) - 1;

		$position = min($position, $eDiscussion['messages'] - \Setting::get('messagesPerPage'));
		$position = max(0, $position);

		return self::getMessagesByDiscussion($eDiscussion, $position, $number);

	}

	/*
	 * In a publication count messages before and after a group of messages
	 */
	public static function countMessagesAround(Discussion $eDiscussion, \Collection $cMessage, int $position = NULL): array {

		$before = $position;
		$beforePosition = max(0, $position - \Setting::get('messagesPerPage'));

		$after = $eDiscussion['messages'] - $position - $cMessage->count();
		$afterPosition = min($eDiscussion['messages'], $position + $cMessage->count());

		return [$before, $beforePosition, $after, $afterPosition];

	}

	/**
	 * Decides if the publication is too outdated to be edited
	 */
	public static function outdated(\user\User $eUser, Discussion $eDiscussion) {

		if(
			$eUser->notEmpty() and (
				$eUser['id'] === $eDiscussion['author']['id'] or
				\paper\Message::model()
					->whereDiscussion($eDiscussion)
					->whereAuthor($eUser)
					->exists()
			)
		) {
			$eDiscussion['isOutdated'] = FALSE;
		} else {
			$eDiscussion['isOutdated'] = $eDiscussion['daysSinceLastMessage'] > \Setting::get('publicationOutdatedDelay');
		}
	}

	/**
	 * Gets a publication by the ID
	 */
	public static function getPublication(int $id, array $fields = [], string &$status = NULL): Discussion {

		$eDiscussion = \paper\Discussion::model()
			->select(
				Discussion::getSelection() +
				$fields +
				[
					'forum' => [
						'id', 'name', 'cleanName', 'active', 'lastMessageAt', 'cover'
					]
				]
			)
			->whereId($id)
			->get();

		if($eDiscussion->empty()) {
			$status = 'notFound';
			return new Discussion();
		}

		if($eDiscussion['forum']->empty()) {
			$status = 'deleted';
			return new Discussion();
		} else if($eDiscussion['forum']['active'] === FALSE) {
			$status = 'forumInactive';
			return new Discussion();
		} else {
			return $eDiscussion;
		}

	}

	/**
	 * Create a new publication with its first message
	 */
	public static function createOpen(Discussion $eDiscussion): bool {

		Message::model()->beginTransaction();

		Discussion::model()->insert($eDiscussion);

		$eMessage = $eDiscussion['openMessage'];
		$eMessage['discussion'] = $eDiscussion;

		MessageLib::create($eMessage);

		// Update publication with the ID of the message and the text
		$eDiscussion['openMessage'] = $eMessage;
		$eDiscussion['lastMessage'] = $eMessage;

		Discussion::model()
			->select('openMessage', 'lastMessage')
			->update($eDiscussion);

		Message::model()->commit();

		return TRUE;

	}

	/**
	 * Updates the publication and the information
	 */
	public static function updateOpen(string $action, Discussion $eDiscussion): bool {

		Message::model()->beginTransaction();

		$eDiscussion->expects([
			'id', 'title', 'cleanTitle',
			'openMessage' => ['text', 'author']
		]);

		\paper\Discussion::model()
			->select('title', 'cleanTitle', 'search', 'pinned')
			->update($eDiscussion);

		TextLib::updateWithHistory($eDiscussion['openMessage']['text'], $eDiscussion['openMessage']['oldText']);

		Message::model()->commit();

		return TRUE;

	}

	/**
	 * Creates an answer
	 */
	public static function createAnswer(Message $eMessage): bool {
		self::createMessage($eMessage);
		return TRUE;
	}

	/**
	 * Updates an answer
	 */
	public static function updateAnswer(Message $eMessage): bool {
		return self::updateMessage($eMessage);
	}

	/**
	 * Deletes an answer
	 */
	public static function deleteAnswer(Message $eMessage): bool {
		return self::deleteMessage($eMessage);
	}

	/**
	 * Creates a feedback
	 */
	public static function createFeedback(Message $eMessage): bool {
		self::createMessage($eMessage);
		return TRUE;
	}

	/**
	 * Updates a feedback
	 */
	public static function updateFeedback(Message $eMessage): bool {
		return self::updateMessage($eMessage);
	}

	/**
	 * Deletes a feedback
	 */
	public static function deleteFeedback(Message $eMessage): bool {
		return self::deleteMessage($eMessage);
	}


	protected static function createMessage(Message $eMessage) {

		// Create the message
		return MessageLib::create($eMessage);

	}

	protected static function updateMessage(Message $eMessage) {

		$eMessage->expects(['text', 'oldText']);

		Message::model()->beginTransaction();

		TextLib::updateWithHistory($eMessage['text'], $eMessage['oldText']);

		Message::model()->commit();

		return TRUE;

	}

	/**
	 * Deletes a text and its message
	 */
	protected static function deleteMessage(Message $eMessage) {

		MessageLib::hide($eMessage, TRUE);

		return TRUE;

	}


	/**
	 * Hide publications in forum
	 */
	public static function hidePublication(Discussion $eDiscussion) {

		$update = [
			'publications' => new \Sql('publications - 1'),
			'messages' => new \Sql('messages - '.$eDiscussion['messages'])
		];

		if($eDiscussion['lastMessageAt'] === $eDiscussion['forum']['lastMessageAt']) {

			$update += \paper\ForumLib::calculateLastMessage($eDiscussion['forum']);

		}

		Forum::model()->update($eDiscussion['forum'], $update);

	}

}
?>
