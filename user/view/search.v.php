<?php
new JsonView('query', function($data, AjaxTemplate $t) {

	$results = [];

	foreach($data->cUser as $eUser) {

		$results[] = (new \user\UserUi())->getAutocomplete($eUser);

	}
	$t->push('results', $results);

});