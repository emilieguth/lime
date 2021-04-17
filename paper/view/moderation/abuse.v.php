<?php
new JsonView('doUpdate', function($data, AjaxTemplate $t) {

	$t->qs('#message-abuse-'.$data->eMessage['id'])->innerHtml((new paper\AbuseUi())->get($data->eMessage));

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('#message-abuse-'.$data->eMessage['id'])->remove();

});
?>
