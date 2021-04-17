<?php
new AdaptativeView('abuse', function($data, PanelTemplate $t) {

	return (new paper\AbuseUi())->reportAbuse($data->eMessage);

});

new JsonView('doAbuse', function($data, AjaxTemplate $t) {

	$t->qs('#front-text-'.$data->eMessage['id'])->innerHtml((new paper\MessageUi())->getText($data->eMessage, $data->eMessage['text']));

	$t->js()
		->moveHistory(-1)
		->success('paper', 'Abuse.reported');

	$t->push('message', $data->eMessage['id']);
	$t->qs('#message-report-abuse-'.$data->eMessage['id'])->remove();

});
?>
