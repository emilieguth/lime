<?php
new AdaptativeView('display', function($data, MainTemplate $t) {

	$t->title = s('Forum {forumName}', ['forumName' => $data->eForum['name']]);

	$t->header = (new \paper\ForumUi())->getHeader($data->eForum, FALSE, $data->hasNotifications);

	$t->metaDescription = $data->eForum['description'];

	echo (new \paper\DiscussionUi())->getList($data->cDiscussion, $data->cForum, $data->newPublications);

	$url = paper\ForumUi::url($data->eForum, '{page}');
	echo \util\TextUi::pagination($data->page, $data->nPage, $url);

});

new JsonView('doSubscribe', function($data, AjaxTemplate $t) {

	$t->qs('div.forum-notification-bell')->innerHtml((new \paper\ForumUi())->getSubscription($data->eForum, $data->subscribed));

});
?>
