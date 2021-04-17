<?php
namespace notification;

trait TypeElement {

	public static function getSelection(): array {

		return [
			'id', 'fqn', 'defaultSubscribed', 'withReferences'
		];

	}

}
?>