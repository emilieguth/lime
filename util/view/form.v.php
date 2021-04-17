<?php
new JsonView('weekChange', function($data, AjaxTemplate $t) {
	$t->qs('#'.$data->id)->outerHtml(\util\FormUi::weekSelector($data->year, $data->onclick, $data->default));
});
?>
