<?php
namespace notification;

/**
 * Handles notifications
 *
 */
class NotificationLib {

	/**
	 * Count notifications for a user
	 */
	public static function count(\user\User $eUser): int {

		return Event::model()
			->whereUser($eUser)
			->count();

	}

	/**
	 * Count unread notifications for a user
	 */
	public static function listUnread(\user\User $eUser): array {

		return Event::model()
			->select('id')
			->whereUser($eUser)
			->whereRead(FALSE)
			->getColumn('id');

	}

	public static function getEvent(Event $eEvent, \user\User $eUser): Event {

		Event::model()->whereId($eEvent['id']);

		$cEvent = self::get($eUser, 0, 1);

		if($cEvent->count() === 1) {
			return $cEvent->first();
		}

		return new Event();

	}

	/**
	 * Checks if user has more recent notifications
	 *
	 */
	public static function hasRecently(\user\User $eUser, string $lastEvent): bool {

		return Event::model()
			->whereUser($eUser)
			->where('date > '.Event::model()->format($lastEvent))
			->exists();

	}

	/**
	 * Get the notifications that have been sent
	 *
	 */
	public static function get(\user\User $eUser, int $position, int $number, bool &$hasUnread = NULL, bool &$hasUnclicked = NULL): \Collection {

		$eUser->expects(['id']);

		$cEvent = Event::model()
			->select([
				'id',
				'type' => ['fqn'],
				'reference', 'read', 'clicked',
				'item' => EventItem::model()
					->select([
						'id',
						'event',
						'aboutText' => [
							// Need to display info and type of the message
							// Do not need the value itself
							'message' => \paper\Message::model()
								->select([
									'id', 'answerOf', 'type'
								] + \paper\Message::getParentSelection())
								->delegateElement('text')
						],
						'aboutUser' => \user\User::getSelection(),
						'aboutValue',
						'read', 'date'
					])
					->whereUser($eUser)
					->sort([
						'date' => 'desc',
						'id' => 'desc'
					])
					->delegateCollection('event'),
				'date'
			])
			->whereUser($eUser)
			->sort(['data' => 'DESC'])
			->getCollection($position, $number);

		$cEventValid = new \Collection();
		$cEventUseless = new \Collection();
		$cEventItemUseless = new \Collection();

		foreach($cEvent as $key => $eEvent) {

			// Schedule deletion of items that refer to invalid data
			$eEvent['item'] = self::cleanUselessItems($eEvent['item'], $cEventItemUseless);

			// Schedule deletion of events that have no item
			if($eEvent['item']->count() === 0) {
				$cEventUseless[] = $eEvent;
			} else {
				$cEventValid[] = $eEvent + ['user' => $eUser];

				if($eEvent['read'] === FALSE) {
					$hasUnread = TRUE;
				}

				if($eEvent['clicked'] === FALSE) {
					$hasUnclicked = TRUE;
				}

			}

		}

		self::deleteUseless($eUser, $cEventUseless, $cEventItemUseless);

		return $cEventValid;

	}

	/**
	 * Sets the event as clicked
	 */
	public static function markAsClicked(int $id = NULL) {

		$eUser = \user\ConnectionLib::getOnline();

		if($eUser->empty()) {
			return;
		}


		if($id !== NULL) {
			Event::model()->whereId($id);
		}

		Event::model()
			->whereUser($eUser)
			->with($eUser)
			->update('clicked = 1, clickedAt = IF(clickedAt IS NULL, NOW(), clickedAt)');

	}

	/**
	 * Set all notifications as read for a user
	 */
	public static function updateUnread(\user\User $eUser, \Collection $cEvent = NULL): int {

		Event::model()->beginTransaction();

		$update = [
			'read' => NULL,
		];

		if($cEvent !== NULL and $cEvent->count() > 0) {
			$update['readAt'] = new \Sql('IF(readAt IS NULL and id IN('.join(',', $cEvent->getIds()).'), NOW(), readAt)');
		} else {
			$update['readAt'] = new \Sql('IF(readAt IS NULL, NOW(), readAt)');
		}

		$affected = Event::model()
			->whereUser($eUser)
			->update($update);

		EventItem::model()
			->whereUser($eUser)
			->update([
				'read' => NULL
			]);

		Event::model()->commit();

		return $affected;

	}

	protected static function cleanUselessItems(\Collection $cEventItem, \Collection $cEventItemUseless): \Collection {

		$cEventItemValid = new \Collection();

		foreach($cEventItem as $key => $eEventItem) {

			// Check for user
			$eUser = $eEventItem['aboutUser'];

			if($eUser->notEmpty()) {

				if($eUser['status'] === \user\User::CLOSED) {

					$cEventItemUseless[] = $eEventItem;
					continue;

				}

			}

			// Check for text
			$eText = $eEventItem['aboutText'];

			if($eText->notEmpty()) {

				$eMessage = $eText['message'];

				if(
					$eMessage->empty() or
					($eMessage['forum']->empty() and $eMessage['discussion']->notEmpty())
				) {

					$cEventItemUseless[] = $eEventItem;
					continue;

				}

			}

		}

		return $cEventItemValid;

	}

	protected static function deleteUseless(\user\User $eUser, \Collection $cEventUseless, \Collection $cEventItemUseless) {

		if($cEventItemUseless->count() > 0) {

			EventItem::model()
				->whereUser($eUser)
				->delete($cEventItemUseless);

		}

		if($cEventUseless->count() > 0) {

			Event::model()
				->whereUser($eUser)
				->delete($cEventUseless);

		}

	}

}

?>
