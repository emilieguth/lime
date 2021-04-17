<?php
namespace paper;

trait ForumElement {

	public static function getSelection(): array {

		return [
			'id',
			'cover', 'position',
			'name', 'cleanName', 'description', 'active', 'deletedAt',
			'publications', 'messages',
			'createdAt', 'lastMessageAt',
			'lastMessage' => [
				'author' => \user\User::getSelection()
			]
		];

	}

	public function active(): bool {
		return $this['active'];
	}

}
?>