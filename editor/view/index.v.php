<?php
new JsonView('configureVideo', function($data, AjaxTemplate $t) {

	$t->pushPanel((new \editor\EditorUi())->getVideoConfigure());

});

new JsonView('configureMedia', function($data, AjaxTemplate $t) {

	$t->pushPanel((new \editor\EditorUi())->getMediaConfigure($data->instanceId, $data->url, $data->xyz, $data->title, $data->license, $data->source, $data->figureSize));

});

new JsonView('getExternalLink', function($data, AjaxTemplate $t) {

	$t->push('url', $data->url);

	$t->push('link', $data->link);
	$t->push('title', $data->title);
	$t->push('description', $data->description);
	$t->push('image', $data->image);

});
?>
