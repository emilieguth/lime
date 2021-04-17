<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Traiter les abus");

	$t->header = '<div class="admin-navigation">';
		$t->header .= (new \main\AdminUi())->getNavigation('paper');
		$t->header .= (new \paper\AdminUi())->getNavigation('abuses');
	$t->header .= '</div>';

	echo (new paper\AbuseUi())->getOpen($data->cMessage);

});
?>
