<?php
new AdaptativeView('update', function($data, MainTemplate $t) {

	$t->title = s("Éditer cette réponse");

	$t->header = (new paper\DiscussionUi())->getSimpleHeader($data->eDiscussion);

	echo (new paper\WriteUi())->updateAnswer(
		$data->eDiscussion,
		$data->eMessage,
		$data->eDraft
	);

});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->qs('#front-text-'.$data->eMessage['id'])->innerHtml((new paper\MessageUi())->getDeletedMessage());
	$t->qs('#front-message-info-'.$data->eMessage['id'])->remove();

});
?>
