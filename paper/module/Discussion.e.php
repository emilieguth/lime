<?php
namespace paper;

trait DiscussionElement {

	public static function getSelection(): array {

		$eUser = \user\ConnectionLib::getOnline();

		return [
			'id', 'title', 'search', 'cleanTitle', 'messages', 'write', 'writeUpdatedAt', 'views',
			'author' => \user\User::getSelection(),
			'forum', 'pinned',
			'daysSinceLastMessage' => new \Sql('DATEDIFF(NOW(), lastMessageAt)' , 'int'),
			'isAuthor' => function(Discussion $eDiscussion) use($eUser) {

				if($eUser->notEmpty()) {
					return ($eDiscussion['author']['id'] === $eUser['id']);
				} else {
					return FALSE;
				}

			},
			'createdAt', 'openMessage', 'lastMessage', 'lastMessageAt'
		];

	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'pin.prepare' => function(&$value) {

				if(\Privilege::can('pin') === FALSE) {
					$value = FALSE;
				}

			},

			'title.set' => function(string $value) {

				$this->merge([
					'title' => $value,
					'cleanTitle' => ToFqn($value),
					'search' => toFqn($value, ' ')
				]);

			}

		]);

	}

}
?>