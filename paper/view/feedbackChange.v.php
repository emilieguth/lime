<?php
new JsonView('update', function($data, AjaxTemplate $t) {

	$t->qs('#front-message-'.$data->eMessage['id'])->outerHtml((new paper\MessageUi())->getFeedback(
		$data->eDiscussion,
		$data->eMessage,
		'update'
	));

});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->qs('#front-message-'.$data->eMessage['id'])->outerHtml((new paper\MessageUi())->getFeedback(
		$data->eDiscussion,
		$data->eMessage
	));

	$t->push('message', $data->eMessage['id']);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('#front-message-'.$data->eMessage['id'])->remove();

});
?>
