<?php
namespace paper;

/**
 * Message handling
 */
class MessageLib extends MessageCrud {

	/**
	 * Get a message
	 *
	 */
	public static function getById($id, array $properties = []): Message {

		$e = new Message();

		if($id === NULL) {
			return $e;
		}

		$select = Message::getSelection() + Message::getParentSelection();

		if($properties['authorCount'] ?? FALSE) {
			$select['author']['cMessage'] = Message::getCountDelegate();
		}

		if(Message::model()
			->select($select)
			->whereId($id)
			->get($e) === FALSE) {
			$e->setGhost($id);
		} else if($e['forum']['active'] === FALSE) {
			$e = new Message();
		}

		return $e;

	}

	/**
	 * Get the answers of a discussion
	 *
	 */
	public static function getByAnswerOf($value, string $type, bool $includeCount = FALSE): \Collection {

		$properties = Message::getFullSelection($includeCount);

		\paper\Message::model()
			->select($properties)
			->whereType($type)
			->sort('createdAt');

		if($value instanceof \Collection) {

			return \paper\Message::model()
				->whereAnswerOf('IN', $value)
				->getCollection(NULL, NULL, ['answerOf', 'id']);

		} else {

			return \paper\Message::model()
				->whereAnswerOf($value)
				->getCollection(NULL, NULL, ['id']);

		}

	}

	/**
	 * Fill feedbacks of a collection
	 *
	 * @param Collection $cMessage
	 */
	public static function fillFeedbacks(\Collection $cMessage) {

		$ccMessageFeedback = self::getByAnswerOf($cMessage, \paper\Message::FEEDBACK, TRUE);

		foreach($cMessage as $key => $eMessage) {

			if($ccMessageFeedback->offsetExists($eMessage['id'])) {
				$cMessage[$key]['feedbacks'] = $ccMessageFeedback[$eMessage['id']];
			} else {
				$cMessage[$key]['feedbacks'] = new \Collection();
			}

		}
	}

	/**
	 * Create a new message
	 */
	public static function create(Message $e): void {

		$e->expects(['text', 'answerOf', 'forum']);

		Message::model()->beginTransaction();

		self::doCreate($e);

		// Update forum statistics
		if(
			$e['type'] !== Message::FEEDBACK and
			isset($e['forum']['id'])
		) {

			$update = [
				'messages' => new \Sql('messages + 1'),
				'lastMessageAt' => new \Sql('NOW()'),
				'lastMessage' => $e			];

			if($e['type'] === Message::OPEN) {
				$update['publications'] = new \Sql('publications + 1');
			}

			Forum::model()->update($e['forum'], $update);

		}

		Message::model()->commit();

	}

	public static function doCreate(Message $e): void {

		if(in_array($e['type'] ?? NULL, Message::model()->getPropertyEnum('type')) === FALSE) {
			$e['type'] = Message::ANSWER;
		}

		$eText = $e['text'];
		$eText['type'] = $e['type'];

		$e['text'] = new Text();
		$e['createdAt'] = new \Sql('NOW()');

		// Add message
		Message::model()->insert($e);

		// Text has the same ID of message
		$eText['id'] = $e['id'];

		TextLib::create($eText);

		$e['text'] = $eText;

		// Update message with the ID of the text and answerOf field
		if($e['answerOf']->empty()) {
			$e['answerOf'] = $e;
		}

		Message::model()
			->select('text', 'answerOf')
			->update($e);

		// Update publication statistics
		$newProperties = [];

		if($e['type'] !== Message::FEEDBACK) {

			$newProperties += [
				'messages' => new \Sql('messages + 1'),
				'lastMessageAt' => new \Sql('NOW()'),
				'lastMessage' => $e
			];

		}

		if($newProperties) {
			Discussion::model()->update($e['discussion'], $newProperties);
		}

	}

	/**
	 * Hide a message
	 * Remove it from Message table, add it to MessageHidden table
	 * Also remove related abuses.
	 *
	 */
	public static function hide(Message $e, bool $dynamic = FALSE): bool {

		$e->expects(Message::model()->getProperties(), $dynamic ? function() {

			if(Message::model()
				->select(Message::model()->getProperties())
				->get($e) === FALSE) {
				return;
			}

			if($e['forum']->notEmpty()) {

				Forum::model()
					->select('lastMessageAt')
					->get($e['forum']);

			}

		} : NULL);

		switch($e['type']) {

			case Message::OPEN :
				return FALSE;

			case Message::FEEDBACK :
				return self::hideFeedback($e);

			case Message::ANSWER :
				return self::hideMessage($e);

		}

	}

	protected static function hideFeedback(Message $e): bool {

		Message::model()->delete($e);

		return TRUE;

	}

	protected static function hideMessage(Message $e): bool {

		Message::model()->beginTransaction();

		// Hide message
		$affected = Message::model()->delete($e);

		if($affected === 0) {
			Message::model()->rollBack();
			return FALSE;
		}

		MessageHidden::model()->insert($e);

		Message::model()
			->whereType(Message::FEEDBACK)
			->whereAnswerOf($e)
			->delete();

		// Close abuses for the message
		AbuseLib::close($e, Abuse::UNKNOWN);

		// Update publication statistics
		$eMessageLast = Message::model()
			->select('id', 'text', 'createdAt')
			->whereDiscussion($e['discussion'])
			->sort(['text' => 'DESC'])
			->get();

		if($eMessageLast->empty()) {
			$createdAt = $e['discussion']['createdAt'];
		} else {
			$createdAt = $eMessageLast['createdAt'];
		}

		$newProperties = [
			'messages' => new \Sql('messages - 1'),
			'lastMessage' => $eMessageLast,
			'lastMessageAt' => $createdAt
		];

		Discussion::model()->update($e['discussion'], $newProperties);

		$update = [
			'messages' => new \Sql('messages - 1')
		];

		if($e['createdAt'] === $e['forum']['lastMessageAt']) {
			$update += ForumLib::calculateLastMessage($e['forum']);
		}

		Forum::model()->update($e['forum'], $update);

		Message::model()->commit();

		return TRUE;

	}

	/**
	 * Checks that the user didn't post a message less than 5 seconds ago (flood protection)
	 */
	public static function checkDelay(\user\User $eUser): void {

		$seconds = Message::model()
			->whereAuthor($eUser)
			->getValue(new \Sql('TIME_TO_SEC(MIN(TIMEDIFF(NOW(), createdAt)))', 'int'));


		if($seconds !== NULL and $seconds < \Setting::get('messageFloodDelay')) {
			Message::fail('value.flood');
		}
	}


	/**
	 * Censor a message
	 */
	public static function censor(Message $e, bool $censor): bool {

		if($censor) {
			Message::model()->whereCensored(FALSE);
			$e['censored'] = TRUE;
			$e['censoredAt'] = new \Sql('NOW()');
		} else {
			Message::model()->whereCensored(TRUE);
			$e['censored'] = FALSE;
			$e['censoredAt'] = NULL;
		}


		Message::model()
			->select('censored', 'censoredAt')
			->update($e);

		return TRUE;

	}

	/**
	 * Censor a collection of Message
	 */
	public static function censorCollection(\Collection $cMessage, bool $censor): bool {

		$values = [];

		if($censor) {
			Message::model()->whereCensored(FALSE);
			$values['censored'] = TRUE;
			$values['censoredAt'] = new \Sql('NOW()');
		} else {
			Message::model()->whereCensored(TRUE);
			$values['censored'] = FALSE;
			$values['censoredAt'] = NULL;
		}

		Message::model()
			->whereId('IN', $cMessage->getIds())
			->update($values);

		return TRUE;

	}

}
?>
