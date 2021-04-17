<?php
new JsonView('form', function($data, AjaxTemplate $t) {

	$t->qs('#create-feedback')->remove();

	$t->qs('#front-feedback-'.$data->eMessageMaster['id'])->insertAdjacentHtml('beforeend', (new paper\WriteUi())->createFeedback($data->eMessageMaster));

	$t->qs('#create-feedback [data-field="feedback"]')->focus();

});

new JsonView('do', function($data, AjaxTemplate $t) {

	$t->qs('#front-feedback-'.$data->eMessageMaster['id'])->innerHtml((new \paper\MessageUi())->getFeedbacks(
		$data->eDiscussion,
		$data->cMessageFeedback
	));

	$t->push('master', $data->eMessageMaster['id']);

});
?>
