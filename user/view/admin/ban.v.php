<?php

new AdaptativeView('index', function($data, MainTemplate $t) {

	if($data->eUserSelected->empty() === FALSE) {

		$t->header = '<h1>'.s("Bannissements de {email}", ['email' => \user\UserUi::link($data->eUserSelected)]).'</h1>';
		$t->header .= '<h3>'.encode($data->eUserSelected['email']).'</h3>';

	} else {

		$t->header = '<div class="admin-navigation">';
			$t->header .= (new \main\AdminUi())->getNavigation('user');
			$t->header .= (new \user\AdminUi())->getNavigation('ban');
			$t->header .= (new \user\BanUi())->getForm($data->active);
		$t->header .= '</div>';

	}


	echo (new \user\BanUi())->getCollection($data->active, $data->cBan);

	echo \util\TextUi::pagination($data->page, $data->nPage);

	if($data->eUserSelected->empty() === FALSE) {
		echo '<p class="util-info">'.s("Attention : même si aucun bannissement n'apparait dans cette liste, cet utilisateur peut être impacté par un bannissement par adresse IP.").'</p>';
	}

});

new AdaptativeView('form', function($data, PanelTemplate $t) {

	return (new \user\BanUi())->create($data->eUserToBan, $data->userToBanIp, $data->nUserOnIp);

});

new AdaptativeView('updateEndDate', function($data, PanelTemplate $t) {

	return (new \user\BanUi())->updateEndDate($data->eBan);

});

?>
