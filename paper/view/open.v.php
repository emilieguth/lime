<?php
new AdaptativeView('create', function($data, MainTemplate $t) {

	$t->title = s("Commencer une nouvelle discussion");

	$t->header = (new paper\ForumUi())->getHeader($data->eForum, TRUE);

	echo (new \paper\WriteUi())->createOpen(
		$data->eForum,
		$data->eDraft,
		$data->isFirstPost,
		$data->eUser
	);

});

new AdaptativeView('update', function($data, MainTemplate $t) {

	$t->title = s("Éditer une discussion");

	$t->header = (new paper\ForumUi())->getHeader($data->eForum, TRUE);

	echo (new \paper\WriteUi())->updateOpen(
		$data->eForum,
		$data->eDraft,
		$data->eUser,
		$data->eDiscussion
	);

});
?>
