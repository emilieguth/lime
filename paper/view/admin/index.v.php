<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	$t->title = s("Gérer les forums");

	$t->header = '<div class="admin-navigation">';
		$t->header .= (new \main\AdminUi())->getNavigation('paper');
		$t->header .= (new \paper\AdminUi())->getNavigation('forum');
	$t->header .= '</div>';

	echo '<div id="forums">';
		echo (new paper\AdminUi())->getForums($data->cForum);
	echo '</div>';

});

?>
