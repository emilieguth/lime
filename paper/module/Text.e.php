<?php
namespace paper;

trait TextElement {

	/**
	 * Required properties for Text element for display
	 *
	 * @return array
	 */
	public static function getSelection(): array {

		return [
			'id', 'value', 'valueAuthor', 'valueUpdatedAt', 'ip', 'sid', 'type'
		];

	}

}
?>