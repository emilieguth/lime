<?php
/**
 * Form to add an answer
 */
(new Page())
	->post('do', function($data) {

		\user\ConnectionLib::checkLogged();

		$id = POST('publication', '?int');
		$data->eDiscussion = \paper\DiscussionLib::getPublication($id);

		if($data->eDiscussion->empty()) {
			throw new NotExistsAction('Publication #'.$id);
		}

		$eMessage = new \paper\Message([
			'discussion' => $data->eDiscussion,
			'forum' => $data->eDiscussion['forum']
		]);

		\paper\DiscussionLib::outdated($data->eUser, $data->eDiscussion);

		if(($data->eDiscussion['isOutdated'] ?? NULL) === TRUE) {
			Discussion::fail('outdated');
		}

		$fw = new FailWatch;

		$userEmail = ($data->isLogged ? NULL : POST('email'));

		paper\MessageLib::checkDelay($data->eUser, $userEmail);

		// Check if publication is OK
		\paper\DiscussionLib::buildAnswer('create', $eMessage, $_POST);

		if($fw->ok()) {

			$eMessage['answerOf'] = new \paper\Message();

			// Create the message
			\paper\DiscussionLib::createAnswer($eMessage);

		}

		if($fw->ok()) {

			$hash = \paper\DraftLib::getHash('answer', $data->eDiscussion, 'create');
			\paper\DraftLib::invalidate($hash);

			$data->eDraft = \paper\DraftLib::get($hash);

			$data->eDiscussion['messages']++;
			$data->eDiscussion['lastMessage'] = $eMessage;


			$data->eMessage = $eMessage;

			$position = NULL;
			$number = NULL;

			$data->cMessage = \paper\DiscussionLib::getMessagesAroundMessageByDiscussion($data->eDiscussion, $eMessage, $position, $number);
			paper\MessageLib::fillFeedbacks($data->cMessage);

			$data->messagesAround = \paper\DiscussionLib::countMessagesAround($data->eDiscussion, $data->cMessage, $position);

			paper\AbuseLib::assignAbuseReported($data->cMessage);

			paper\DiscussionUnreadLib::readDiscussion($data->eDiscussion);

			\notification\PublishLib::newDiscussionAnswer($data->eDiscussion, $eMessage);

			if(POST('redirect', 'bool') === TRUE) {
				throw new RedirectAction(\paper\MessageUi::url($data->eDiscussion, $data->eDiscussion['lastMessage']));
			}

			throw new ViewAction($data);

		} else {

			throw new FailAction($fw);

		}

	});
?>
