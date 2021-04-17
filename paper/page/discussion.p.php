<?php
(new Page())
/**
 * Display messages of a publication
 */
	->match(
		['get', 'post'],
		[
			'/topic/{title}/{publication}',
			'/topic/{title}/{publication}/message/{message}',
			'/topic/{title}/{publication}/p/{position}/{number}',
			'/topic/{title}/{publication}/p/{position}'
		],
	function($data) {

		$id = REQUEST('publication', '?int');
		$status = NULL;

		$data->eDiscussion = \paper\DiscussionLib::getPublication($id, [], $status);

		if($data->eDiscussion->empty()) {

			if($status === 'deleted') {
				throw new StatusAction(410);
			} else {
				throw new NotExistsAction('Discussion #'.$id);
			}

		}

		\paper\DiscussionLib::getMeta($data->eDiscussion);

		// Notifications for the publication
		if($data->isLogged) {
			$eType = \notification\TypeLib::getByFqn('discussion-answer');
			$data->hasNotifications = \notification\SubscriptionLib::canSend($data->eUser, $eType, $data->eDiscussion['id']);
		} else {
			$data->hasNotifications = FALSE;
		}

		$number = NULL;
		$target = NULL;

		$eMessage = REQUEST('message', 'paper\Message');

		$data->where = POST('where');

		if($eMessage->notEmpty()) {

			$position = NULL;

			$data->cMessage = \paper\DiscussionLib::getMessagesAroundMessageByDiscussion($data->eDiscussion, $eMessage, $position, $number);

		} else {

			$position = REQUEST('position', 'int');
			$number = REQUEST('number', '?int');

			if($number === NULL) {

				$number = \Setting::get('messagesPerPage');

				$target = $position;
				$position = max(0, $position - $number / 2);

			}

			$data->cMessage = \paper\DiscussionLib::getMessagesByDiscussion($data->eDiscussion, $position, $number);

		}

		paper\MessageLib::fillFeedbacks($data->cMessage);

		// We take back a draft if there is one
		$hash = \paper\DraftLib::getHash('answer', $data->eDiscussion, 'create');
		$data->eDraft = \paper\DraftLib::get($hash);

		$data->messagesAround = \paper\DiscussionLib::countMessagesAround($data->eDiscussion, $data->cMessage, $position);

		$data->position = $position;
		$data->target = $target;

		paper\AbuseLib::assignAbuseReported($data->cMessage);

		\paper\DiscussionLib::outdated($data->eUser, $data->eDiscussion);

		$data->eReadElement = paper\DiscussionUnreadLib::readDiscussion($data->eDiscussion);

		if(\Privilege::can('paper\moderation')) {
			$data->cForumMove = \paper\ModerationLib::getForumsForMove($data->eDiscussion['forum']);
		} else {
			$data->cForumMove = new Collection;
		}

		if($data->isLogged) {

			// Get the number of messages of all kind posted by this user
			$data->isFirstPost = \paper\ForumLib::hasNoPost($data->eUser);

		} else {
			$data->isFirstPost = FALSE;
		}

		if(post_exists('where')) {
			throw new ViewAction($data, path: ':displayJson');
		} else {
			throw new ViewAction($data, path: ':display');
		}

	})
	/**
	 * Subscribes to a discussion
	 */
	->post('doSubscribe', function($data) {

		\user\ConnectionLib::checkLogged();

		$id = REQUEST('publication', '?int');
		$data->eDiscussion = \paper\DiscussionLib::getPublication($id);

		if($data->eDiscussion->empty()) {
			throw new NotExistsAction('Discussion #'.$id);
		}

		$eType = \notification\TypeLib::getByFqn('discussion-answer');

		if(POST('subscribe', 'bool')) {
			\notification\SubscriptionLib::subscribe($data->eUser, $eType, $data->eDiscussion['id']);
			$data->subscribed = TRUE;
		} else {
			\notification\SubscriptionLib::unsubscribe($data->eUser, $eType, $data->eDiscussion['id']);
			$data->subscribed = FALSE;
		}

		throw new ViewAction($data);

	});
?>
