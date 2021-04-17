<?php
new JsonView('doHide', function($data, AjaxTemplate $t) {

	$t->qs('#front-message-'.$data->eMessage['id'])->remove();
	$t->qs('#answer-'.$data->eMessage['id'])->remove();

	$t->js()->success('paper', 'Message.hidden');

});
?>
