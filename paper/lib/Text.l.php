<?php
namespace paper;

/**
 * Text handling
 */
class TextLib extends TextCrud {

	/**
	 * Edit a text
	 */
	public static function updateWithHistory(Text $eTextNew, Text $eTextOld) {

		$eTextNew->expects(['value']);
		$eTextOld->expects(Text::model()->getProperties());

		// Update text
		$eTextNew['valueAuthor'] = \user\ConnectionLib::getOnline();
		$eTextNew['valueUpdatedAt'] = Text::model()->now();

		parent::update($eTextNew, ['value', 'valueUpdatedAt', 'valueAuthor']);

		// Add an entry in the history with the old values
		$eTextHistory = new TextHistory([
			'id' => NULL,
			'text' => $eTextNew
		]);
		$eTextHistory->add($eTextOld->getArrayCopy());

		TextHistory::model()->insert($eTextHistory);

	}

}
?>
