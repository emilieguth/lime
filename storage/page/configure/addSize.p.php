<?php
/**
 * Add a new size for an image
 *
 */
(new Page())
	->cli('index', function($data) {

		$type = GET('type');

		if(\storage\ImageLib::checkType($type) === FALSE) {
			throw new FailAction('Invalid type='.$type.'');
		}

		$sizeString = GET('size');
		$sizeInt = Setting::get('storage\\'.$type)['imageFormat'][$sizeString] ?? NULL;

		if($sizeInt === NULL) {
			throw new FailAction('Invalid size='.$sizeString.' (see setting \'imageFormat\')');
		}

		echo "Add:\n";
		echo "* Type: ".$type."\n";
		echo "* Format: ".$sizeString."\n";

		for($i = 5; $i >= 0; $i--) {
			echo $i."\n";
			sleep(1);
		}


		\storage\ServerLib::browse($type, function($file) use($type, $sizeInt, $sizeString) {

			$fileSource = $file->getPathName();
			$typeSource = exif_imagetype($fileSource);

			$pathDestination = \storage\ServerLib::getAbsolutePath($type, $sizeInt, $file->getFileName());
			$dirDestination = dirname($pathDestination);

			if(is_dir($dirDestination) === FALSE) {
				mkdir($dirDestination, 0777, TRUE);
			}

			$pathSource = substr($fileSource, strlen(\util\XyzLib::directory()) + 1);

			$bounds = \Setting::get('media\mediaDriver')->getMetadata($pathSource)['crop'] ?? NULL;

			$resource = new \Imagick($fileSource);

			if($bounds) {
				\storage\ImageLib::extractImagePortion($bounds, $resource, $type);
			}

			\storage\ImageLib::resize($sizeInt, $resource, \Setting::get('storage\\'.$type)['imageFormatConstraint'] ?? NULL);

			\storage\ServerLib::build($type, $sizeString, $resource, $pathDestination, $typeSource);

			echo '.';

		});


	});

?>
