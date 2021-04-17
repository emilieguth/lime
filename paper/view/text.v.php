<?php
new JsonView('imageMetadata', function($data, AjaxTemplate $t) {

	$uiFormatter = new \editor\ReadorFormatterUi();
	$uiFormatter->parseXml($data->eText['value']);

	$node = $uiFormatter->extractXyz($data->xyz);

	if($node === NULL) {
		$t->push('metadata', NULL);
	} else {
		$t->push('metadata', $uiFormatter->getImageMetadata($node));
	}


});
?>
