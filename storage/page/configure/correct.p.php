<?php
/**
 * Recreates StorageBuffer entries to correct images
 * php framework/lime.php -e prod -a ouvretaferme storage/configure/correct
 *
 */
(new Page())
	->cli('index', function($data) {

		$cMedia = \media\Media::model()
			->select('type', 'hash')
			->whereStatus('!=', \media\Media::DELETED)
			->where('updatedAt BETWEEN "2017-09-20 00:00:00" AND "2017-10-14 02:00:00"')
			->recordset()
			->getCollection();

		foreach($cMedia as $eMedia) {

			$basename = \media\MediaUi::getInstance($eMedia['type'])->getBasenameByHash($eMedia['hash']);
			$splitBasename = \storage\ServerLib::parseBasename($basename);

			$eBuffer = new \storage\Buffer([
				'type' => $eMedia['type'],
				'basename' => $splitBasename['hash'].'.'.$splitBasename['extension']
			]);

			\storage\Buffer::model()->insert($eBuffer);

		}
	});
?>
