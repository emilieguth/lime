<?php

new AdaptativeView('create', function($data, PanelTemplate $t) {

	return (new paper\AdminUi())->create();

});

new AdaptativeView('update', function($data, PanelTemplate $t) {

	return (new \paper\AdminUi())->update($data->e);

});

new JsonView('doActive', function($data, AjaxTemplate $t) {

	$t->qs('#forum-admin-'.$data->eForum['id'])->innerHtml((new paper\AdminUi())->getForumAdmin($data->eForum));

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('#forum-admin-'.$data->eForum['id'])->innerHtml((new paper\AdminUi())->getForumAdmin($data->eForum));

});
?>
