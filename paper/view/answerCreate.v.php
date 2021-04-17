<?php
new JsonView('do', function($data, AjaxTemplate $t) {

	[$before, $beforePosition] = $data->messagesAround;

	$uiRead = new \paper\MessageUi();

	$t->qs('.discussion-stats')->outerHtml((new \paper\DiscussionUi())->getStats($data->eDiscussion));
	$t->qs('#front-discussions-messages')->innerHtml($uiRead->getMessages($data->eDiscussion, $data->cMessage, new \paper\Read()));
	$t->qs('#front-discussions-before')->innerHtml($uiRead->getMessagesBefore($data->eDiscussion, $before, $beforePosition));
	$t->qs('#front-discussions-after')
		->innerHtml('')
		->removeAttribute('data-trigger');

	$answer = (new paper\WriteUi())->createAnswer(
		$data->eDiscussion,
		$data->eDraft,
		FALSE,
		$data->eUser
	);

	if($answer) {
		$t->qs('div.front-create-answer')->outerHtml($answer);
	}

	$t->push('lastMessage', $data->eDiscussion['lastMessage']['id']);

});
?>
