<?php
namespace paper;

trait MessageElement {

	/**
	 * Required properties for Message element for display
	 *
	 * @return array
	 */
	public static function getSelection(): array {

		$eUser = \user\ConnectionLib::getOnline();

		return [
			'id',
			'forum',
			'author' => \user\User::getSelection(),
			'text' => Text::getSelection(),
			'createdAt', 'publishedAt', 'notifiedAt', 'type', 'status',
			'copied' => ['id', 'cleanTitle', 'title', 'messages'],
			'abuseStatus', 'abuseNumber', 'censored', 'censoredAt',
			'isAuthor' => function(Message $eMessage) use($eUser) {

				return (
					$eUser->notEmpty() and
					$eMessage['author']['id'] === $eUser['id']
				);

			},
			'automatic', 'first',
			'answerOf',
			'discussion'
		];

	}

	/**
	 * Required properties for parent element of message (discussion, forum)
	 *
	 * @param bool $author
	 * @return array
	 */
	public static function getParentSelection(): array {

		return [
			'discussion' => Discussion::getSelection(),
			'forum' => [
				'name', 'cleanName', 'description', 'active', 'lastMessageAt', 'cover'
			],
		];

	}

	public static function getFullSelection($includeCount = FALSE, int $positionCounter = 0) {

		$properties = self::getSelection() + [
			'position' => function() use(&$positionCounter) {
				return $positionCounter++;
			}
		];

		if($includeCount) {
			$properties['author']['cMessage'] = self::getCountDelegate();
		}

		return $properties;

	}

	public static function getCountDelegate() {

		return Message::model()
			->select([
				'number' => new \Sql('COUNT(*)', 'int')
			])
			->whereType('IN', [Message::OPEN, Message::ANSWER, Message::FEEDBACK])
			->whereDiscussion('!=', NULL)
			->group('author')
			->delegateElement('author');

	}

	protected function isAnswer(): bool {
		return ($this['type'] === Message::ANSWER);
	}

	protected function isFeedback(): bool {
		return ($this['type'] === Message::FEEDBACK);
	}

	protected function isNotFeedback(): bool {
		return ($this['type'] !== Message::FEEDBACK);
	}

	protected function canWrite(): bool {

		$this->expects(['isAuthor']);

		return (
			\Privilege::can('paper\moderation') or
			$this['isAuthor']
		);

	}

	protected function canDelete(): bool {

		$this->expects(['type', 'isAuthor']);

		if(
			\Privilege::can('paper\moderation') and
			$this['type'] === Message::ANSWER
		) {
			return TRUE;
		}

		return $this['isAuthor'];

	}

}
?>