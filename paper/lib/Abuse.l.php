<?php
namespace paper;

/**
 * Abuse handling
 */
class AbuseLib extends AbuseCrud {

	/**
	 * Checks if the current user can report an abuse on the given text
	 */
	public static function check(Message $eMessage) {

		$eUser = \user\ConnectionLib::getOnline();

		switch($eMessage['abuseStatus']) {

			case Message::CLEAN :
				return;

			case Message::CLOSED :
				throw new \NotExpectedAction();

			case Message::REPORTED :

				if(
					Abuse::model()
						->whereMessage($eMessage)
						->whereCreatedBy($eUser)
						->whereStatus('!=', Abuse::CLOSED)
						->exists()
				) {
					throw new \NotExistsAction('Abuse');
				}

				break;

		}

	}

	/**
	 * Count open abuses
	 *
	 * @return int
	 */
	public static function countOpen(): int {

		return Abuse::model()
			->whereStatus(Abuse::OPEN)
			->count();

	}

	/**
	 * Add a property abuseReported to each text of the publication and the message group
	 * abuseReported = TRUE if the current user already reported an abuse
	 */
	public static function assignAbuseReported(&$element) {

		$checkTableAbuse = [];

		if($element instanceof \Collection) {

			$cMessage = $element;

			foreach($cMessage as $elementKey => $eMessage) {

				$element[$elementKey]['abuseReported'] = FALSE;

				if($eMessage['abuseStatus'] === Message::REPORTED) {
					$checkTableAbuse[$eMessage['id']] = &$element[$elementKey]['abuseReported'];
				}

			}

		} else if($element instanceof \Element) {

			$eMessage = $element;

			$element['abuseReported'] = FALSE;

			if($eMessage['abuseStatus'] === Message::REPORTED) {
				$checkTableAbuse[$eMessage['id']] = &$element['abuseReported'];
			}

		}

		// Check abuse table if message status is Message::REPORTED
		if($checkTableAbuse) {

			$eUser = \user\ConnectionLib::getOnline();

			$cMessage = Abuse::model()
				->whereMessage('IN', array_keys($checkTableAbuse))
				->whereCreatedBy($eUser)
				->whereStatus('!=', Abuse::CLOSED)
				->getColumn('message');

			foreach($cMessage as $eMessage) {

				$checkTableAbuse[$eMessage['id']] = TRUE;

			}

		}

	}

	/**
	 * Get an abuse
	 */
	public static function get(int $id) {

		Abuse::model()->whereMessage($id);

		$cMessage = self::getOpen();

		if($cMessage->count() > 0) {
			return $cMessage[0];
		} else {
			return NULL;
		}

	}

	/**
	 * Get all open abuses
	 */
	public static function getOpen(): \Collection {

		$ccAbuse = Abuse::model()
			->select([
				'id',
				'message',
				'for', 'why',
				'createdAt'
			])
			->whereStatus(Abuse::OPEN)
			->getCollection(NULL, NULL, ['message', NULL]);

		$cMessage = new \Collection;

		foreach($ccAbuse as $cAbuse) {

			$eMessage = $cAbuse[0]['message'];
			$eMessage['cAbuse'] = $cAbuse;

			$cMessage[] = $eMessage;

		}

		Message::model()
			->select([
				'author' => \user\User::getSelection(),
				'createdAt',
				'forum' => ['lastMessageAt'],
				'discussion' => Discussion::getSelection(),
				'author', 'censored', 'type',
				'abuseStatus', 'abuseNumber', 'censored', 'censoredAt',
				'text' => [
					'value', 'valueAuthor', 'valueUpdatedAt'
				],
				'automatic'
			])
			->get($cMessage);

		return $cMessage;

	}

	/**
	 * Create a new abuse
	 */
	public static function create(Abuse $e): void {

		Abuse::model()->beginTransaction();

		// Update message
		$e['message']->merge([
			'abuseStatus' => Message::REPORTED,
			'abuseNumber' => new \Sql('abuseNumber + 1')
		]);

		if(Message::model()
			->select('abuseStatus', 'abuseNumber')
			->whereAbuseStatus('!=', Message::CLOSED)
			->update($e['message']) > 0) {

			// Add abuse
			Abuse::model()->insert($e);

		}

		Abuse::model()->commit();

	}

	/**
	 * Close abuses for a Message
	 */
	public static function close(Message $eMessage, string $resolution) {

		Abuse::model()->whereMessage($eMessage);
		Message::model()->whereId($eMessage);

		self::closeInternal($resolution);

	}

	/**
	 * Close abuses for all Message of a publication
	 */
	public static function closeByDiscussion(Discussion $eDiscussion, string $resolution) {

		Abuse::model()->whereDiscussion($eDiscussion);
		Message::model()->whereDiscussion($eDiscussion);

		self::closeInternal($resolution);

	}

	private static function closeInternal(string $resolution) {

		Abuse::model()->beginTransaction();

		Abuse::model()
			->update([
				'status' => Abuse::CLOSED,
				'resolution' => $resolution,
				'resolvedBy' => \user\ConnectionLib::getOnline(),
				'resolvedAt' => new \Sql('NOW()')
			]);

		Message::model()
			->update([
				'abuseStatus' => Message::CLOSED,
				'abuseNumber' => 0
			]);

		Abuse::model()->commit();

	}

}
?>
