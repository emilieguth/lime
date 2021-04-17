<?php
new AdaptativeView('duplicate', function($data, PanelTemplate $t) {

	return (new \paper\ModerationUi())->getDuplicate($data->cMessage);

});

new JsonView('doHide', function($data, AjaxTemplate $t) {

	foreach($data->cMessage as $eMessage) {
		$t->qs('#front-message-'.$eMessage['id'])->remove();
	}

	$t->js()->success('paper', 'Message.hiddenCollection');

});
?>
