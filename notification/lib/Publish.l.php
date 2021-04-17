<?php
namespace notification;

/**
 * Create events
 */
class PublishLib {

	/**
	 * Sends a notification for siblings feedbacks (except author of the feedback)
	 *
	 */
	public static function newFeedbackSiblings(\paper\Discussion $eDiscussion, \paper\Message $eMessageFeedback, \Collection $cMessageSibling) {

		$eMessageParent = $eMessageFeedback['answerOf'];

		$cUser = new \Collection();

		foreach($cMessageSibling as $eMessageSibling) {

			if($eMessageSibling['author']->empty()) {
				continue;
			}

			// Do not send notification for feedback author and parent message author
			if(
				$eMessageSibling['author']['id'] === $eDiscussion['author']['id'] or
				($eMessageFeedback['author']->notEmpty() and $eMessageSibling['author']['id'] === $eMessageFeedback['author']['id']) or
				($eMessageParent['author']->notEmpty() and $eMessageSibling['author']['id'] === $eMessageParent['author']['id'])
			) {
				continue;
			}

			$cUser[$eMessageSibling['author']['id']] = $eMessageSibling['author'];

		}

		$item = [
			'aboutUser' => $eMessageFeedback['author'],
			'aboutText' => $eMessageFeedback['text']
		];

		$eType = TypeLib::getByFqn('discussion-feedback-sibling');

		$group = function($mEvent) {
			$mEvent->whereRead(FALSE);
		};

		self::publish($cUser, $eType, $eMessageParent['id'], $group, $item);

	}

	/**
	 * Sends a notification creating a new discussion
	 */
	public static function newDiscussionOpen(\paper\Discussion $eDiscussion, \paper\Message $eMessage) {

		$eDiscussion->expects(['forum']);

		$eType = TypeLib::getByFqn('discussion-open');

		$reference = $eDiscussion['forum']['id'];

		$cUser = \notification\SubscriptionLib::getByType($eType, $reference);

		// users automatically subscribe to the discussion
		$eTypeAnswer = \notification\TypeLib::getByFqn('discussion-answer');

		foreach($cUser as $eUser) {
			SubscriptionLib::subscribe($eUser, $eTypeAnswer, $eDiscussion['id']);
		}

		$item = [
			'aboutUser' => $eMessage['author'],
			'aboutText' => $eMessage['text']
		];

		$group = function($mEvent) {
			$mEvent->whereRead(FALSE);
		};

		self::publish($cUser, $eType, $reference, $group, $item);

	}

	/**
	 * Sends a notification after an answer in a publication
	 */
	public static function newDiscussionAnswer(\paper\Discussion $eDiscussion, \paper\Message $eMessage) {

		$reference = $eDiscussion['id'];

		$eType = TypeLib::getByFqn('discussion-answer');
		$cUser = \notification\SubscriptionLib::getByType($eType, $reference);

		$item = [
			'aboutUser' => $eMessage['author'],
			'aboutText' => $eMessage['text']
		];

		$group = function($mEvent) {
			$mEvent->whereRead(FALSE);
		};

		self::publish($cUser, $eType, $reference, $group, $item);

	}

	/**
	 * Sends a notification after a feedback in a publication
	 */
	public static function newDiscussionFeedback(\paper\Discussion $eDiscussion, \paper\Message $eMessage) {

		// Answer of the feedback
		$eMessageAnswer = $eMessage['answerOf'];

		$item = [
			'aboutUser' => $eMessage['author'],
			'aboutText' => $eMessageAnswer['text'] // Redirect to the answer, not the feedback
		];

		$group = function($mEvent) {
			$mEvent->where($mEvent->field('read').' = 0 OR DATE(date) = CURDATE()');
		};

		$eType = TypeLib::getByFqn('discussion-feedback');
		self::publish($eMessageAnswer['author'], $eType, $eMessageAnswer['id'], $group, $item);

	}

	/**
	 * Sends a notification from the team
	 * php lime/lime.php -e dev -a ouvretaferme -r '\notification\PublishLib::team();'
	 *
	 */
	public static function team(int $message, \Iterator $cUser) {

		$eType = TypeLib::getByFqn('team');

		$position = 0;

		foreach($cUser as $eUser) {

			if(\Route::getRequestedWith() === 'cli' and $position % 10 === 0) {
				echo "\r".$position;
			}

			self::publish($eUser, $eType, $message, NULL, []);

			$position++;

		}

		if(\Route::getRequestedWith() === 'cli') {
			echo "\r".$position;
			echo "\n";
		}

	}

	public static function teamLoggedRecently(int $message) {

		$cUser = \user\User::model()
			->select('id')
			->where('loggedAt > NOW() - INTERVAL 20 DAY')
			->where('seniority >= 3')
			->whereStatus(\user\User::ACTIVE)
			->recordset()
			->getCollection();

		return self::team($message, $cUser);

	}

	/**
	 * Publish a notification
	 */
	protected static function publish($user, Type $eType, int $reference = NULL, \Closure $group = NULL, array $item) {

		if($user instanceof \Collection) {
			$cUser = $user;
		} else {
			$cUser = new \Collection([$user]);
		}

		$eUserConnected = \user\ConnectionLib::getOnline();

		// Construct the list of the arguments
		foreach($cUser as $eUser) {

			// Connected user can't save notifications for himself
			// Except for team notifications
			if(
				$eUserConnected->notEmpty() and $eUserConnected['id'] === $eUser['id'] and
				strpos($eType['fqn'], 'team') === FALSE
			) {
				continue;
			}

			$eEvent = self::getEvent($eUser, $eType, $reference, $group);

			if($eEvent->notEmpty()) {

				$eEventItem = new EventItem([
					'event' => $eEvent,
					'user' => $eUser,
					'type' => $eType
				] + $item);

				EventItem::model()
					->option('add-replace')
					->insert($eEventItem);

			}

		}

	}

	protected static function getEvent(\user\User $eUser, Type $eType, int $reference = NULL, \Closure $group = NULL) {

		// Tries to get a similar event to group them
		if($reference !== NULL and $group !== NULL) {

			$group(Event::model());

			$eEvent = Event::model()
				->select('id', 'read', 'type')
				->whereUser($eUser)
				->whereType($eType)
				->whereReference($reference)
				->get();

			// Sets event as unread again
			if($eEvent->notEmpty()) {

				$properties = [
					'date' => new \Sql('NOW()')
				];

				if($eEvent['read'] === NULL) {
					$properties['read'] = FALSE;
				}

				Event::model()
					->whereUser($eUser)
					->update($eEvent, $properties);

				return $eEvent;

			}

		}

		// Adds new event
		$eEvent = new Event([
			'user' => $eUser,
			'type' => $eType,
			'reference' => $reference
		]);

		try {
			Event::model()->insert($eEvent);
		} catch(\DuplicateException $e) {
			return new Event();
		}

		return $eEvent;

	}

	/**
	 * Deletes the eventItem and the corresponding event
	 * for the given user and type
	 * and for the given options (about*)
	 *
	 */
	protected static function deleteItem(\user\User $eUser, Type $eType, array $conditions) {

		foreach($conditions as $property => $value) {
			EventItem::model()->where($property, $value);
		}

		EventItem::model()
			->whereUser($eUser)
			->whereType($eType)
			->delete();

	}

}
?>
