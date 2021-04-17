<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Gérer les utilisateurs");

	$uiAdmin = new \user\AdminUi();

	$t->header = '<div class="admin-navigation">';
		$t->header .= (new \main\AdminUi())->getNavigation('user');
		$t->header .= $uiAdmin->getNavigation('user');
		$t->header .= $uiAdmin->getUsersForm($data->condition, $data->nUser);
	$t->header .= '</div>';

	echo $uiAdmin->displayUsers($data->cUser, $data->nUser, $data->page, $data->order, $data->isExternalConnected);

});

new JsonView('query', function($data, AjaxTemplate $t) {
	$t->pushCollection('c', $data->c, ['id', 'firstName', 'lastName', 'email']);
});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return (new \user\AdminUi())->updateUser($data->e);
});
?>
