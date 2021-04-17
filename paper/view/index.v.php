<?php
new AdaptativeView('/forums', function($data, MainTemplate $t) {

	$t->title = s("Les forums");
	$t->metaDescription = s("Partagez vos questions et vos connaissances sur le maraichage et l'arboriculture biologiques.");

	$t->header = '<h1 class="header-main">'.encode($t->title).'</h1>';

	echo '<div class="forum-items-wrapper">';

		echo (new \paper\ForumUi())->getList($data->cForum);

		if($data->eUser->empty()) {

			echo '<div class="util-block-gradient">';

				echo '<h3>'.s('{siteName}').'</h3>';

				echo '<p>';
					echo (new \main\AboutUi())->getBaseline();
				echo '</p>';

				echo '<p>';
					echo '<a href="/">'.s("Découvrir le site").'</a>';
				echo '</p>';

			echo '</div>';
		}

	echo '</div>';

});
?>
