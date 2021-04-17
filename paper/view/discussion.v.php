<?php
new AdaptativeView('display', function($data, MainTemplate $t) {

	$eDiscussion = $data->eDiscussion;

	list(
		$t->title,
		$t->metaDescription
	) = (new paper\DiscussionUi())->getMeta($eDiscussion);

	$t->header = (new paper\DiscussionUi())->getHeader($eDiscussion, $data->cForumMove, $data->hasNotifications);

	if(LIME_REQUEST_PATH !== \paper\DiscussionUi::url($eDiscussion)) {
		$t->canonical = \paper\DiscussionUi::url($eDiscussion);
	}

	list($before, $beforePosition, $after, $afterPosition) = $data->messagesAround;

	if(\Privilege::can('paper\moderation')) {
		echo '<div id="message-moderation-select">';
	}

	echo '<div id="front-discussions-before">';
	echo (new paper\MessageUi())->getMessagesBefore($eDiscussion, $before, $beforePosition);
	echo '</div>';
	echo '<div id="front-discussions-messages">';
	echo (new paper\MessageUi())->getMessages($eDiscussion, $data->cMessage, $data->eReadElement);
	echo '</div>';

	echo '<div id="front-discussions-after" data-notification="publication-after-'.$eDiscussion['id'].'">';
	echo (new paper\MessageUi())->getMessagesAfter($eDiscussion, $after, $afterPosition);
	echo '</div>';

	if(\Privilege::can('paper\moderation')) {
		echo '</div>';
	}

	if($data->isLogged === FALSE) {

		echo '<div class="util-info">'.s("<signUp>Inscrivez-vous</signUp> ou <logIn>connectez-vous</logIn> pour participer !", ['signUp' => '<a href="/user/signUp">', 'logIn' => '<a href="/user/log:form">']).'</div>';

	} else {

		if(\Privilege::can('paper\moderation')) {
			echo (new paper\ModerationUi())->getMessagesActions($eDiscussion);
		}

	}

	if($data->isLogged) {

		echo (new paper\WriteUi())->createAnswer(
			$eDiscussion,
			$data->eDraft,
			$data->isFirstPost,
			$data->eUser
		);

	}


});

new JsonView('displayJson', function($data, AjaxTemplate $t) {

	list($before, $beforePosition, $after, $afterPosition) = $data->messagesAround;

	$t->push('target', $data->target);

	$messages = (new paper\MessageUi())->getMessages($data->eDiscussion, $data->cMessage, $data->eReadElement);

	if($data->where === 'before' or $data->where === 'replace' or $data->where === 'replace-top' or $data->where === 'replace-bottom') {
		$t->qs('#front-discussions-messages')->insertAdjacentHtml('afterbegin', $messages);
		$t->qs('#front-discussions-before')->innerHtml((new paper\MessageUi())->getMessagesBefore($data->eDiscussion, $before, $beforePosition));
	}

	if($data->where === 'after' or $data->where === 'replace' or $data->where === 'replace-top' or $data->where === 'replace-bottom') {
		$t->qs('#front-discussions-messages')->insertAdjacentHtml('beforeend', $messages);
		$t->qs('#front-discussions-after')->innerHtml((new paper\MessageUi())->getMessagesAfter($data->eDiscussion, $after, $afterPosition));
	}

});

new JsonView('doSubscribe', function($data, AjaxTemplate $t) {

	$t->qs('div.discussion-notification-bell')->innerHtml((new \paper\DiscussionUi())->getSubscription($data->eDiscussion, $data->subscribed));

});
?>
