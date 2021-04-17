<?php
namespace paper;

/**
 * Forum handling
 */
class AdminLib {

	/**
	 * Lock a publication
	 * Only Discussions can be locked
	 */
	public static function lock(&$cDiscussion, bool $lock): bool {

		$isElement = ($cDiscussion instanceof \Element);

		if($isElement) {
			$cDiscussion = new \Collection([$cDiscussion]);
		}

		foreach($cDiscussion as $key => $eDiscussion) {
			$cDiscussion[$key]['write'] = ($lock ? Discussion::LOCKED : Discussion::OPEN);
		}

		if($lock) {
			Discussion::model()->whereWrite(Discussion::OPEN);
			$write = Discussion::LOCKED;
		} else {
			Discussion::model()->whereWrite(Discussion::LOCKED);
			$write = Discussion::OPEN;
		}

		Discussion::model()
			->select('write')
			->whereId('IN', $cDiscussion->getIds())
			->update([
				'write' => $write,
				'writeUpdatedAt' => new \Sql('NOW()')
			]);

		if($isElement) {
			$cDiscussion = first($cDiscussion);
		}


		return TRUE;

	}

	/**
	 * Remove messages that can not be duplicated from a collection
	 *
	 * @param \Collection $cMessage
	 * @throws \Exception
	 */
	public static function cleanBeforeDuplicate(\Collection &$cMessage) {

		foreach($cMessage as $key => $eMessage) {
			if($eMessage['copied']->notEmpty() or $eMessage['censored']) {
				unset($cMessage[$key]);
			}
		}

		if($cMessage->count() === 0) {
			Discussion::fail('noMessages');
		}
	}

	/**
	 * Creates a publication from a list of messages
	 */
	public static function createFromMessages(\Collection $cMessage, string $title, \FailWatch $fw) {

		//sort messages to find the latest one (the one used as main message)
		$cMessage->sort(['createdAt']);

		Discussion::model()->beginTransaction();

		//construct publication and messages from gMessage
		$nMessage = 0;

		$eDiscussion = new Discussion();
		$eDiscussionOld = new Discussion();

		foreach($cMessage as $eMessage) {

			$eTextNew = $eMessage['text'];
			unset($eTextNew['id']);

			$eMessageNew = new Message([
				'discussion' => $eDiscussion,
				'type' => $eMessage['type'],
				'answerOf' => $eMessage['answerOf'],
				'forum' => $eMessage['forum'],
				'createdAt' => $eMessage['createdAt'],
				'text' => $eTextNew
			]);

			//first message
			if($nMessage === 0) {
				// Add publication

				$eDiscussion->add([
					'forum' => $eMessage['forum'],
					'author' => $eMessage['author'],
					'write' => $eMessage['discussion']['write'],
					'text' => $eTextNew,
					'createdAt' => $eMessage['createdAt'],
					'openMessage' => $eMessageNew,
					'lastMessageAt' => $eMessage['createdAt'],
					'pinned' => $eMessage['discussion']['pinned']
				]);

				$eDiscussion->buildProperty('title', $title);

				if($fw->ok() === FALSE) {
					return;
				}
				$eDiscussion['text']['id'] = NULL;

				DiscussionLib::createOpen($eDiscussion);

				$eDiscussionOld = $eMessage['discussion'];

			} else {


				if($fw->ok() === FALSE) {
					Discussion::model()->rollback();
					return;
				}

				MessageLib::doCreate($eMessageNew);

				$eDiscussion['lastMessageAt'] = $eMessage['createdAt'];

			}

			//set current message as copied
			Message::model()->update($eMessage, [
				'copied' => $eDiscussion]
			);

			$nMessage++;

		}

		// Update date of last message of the new publication
		Discussion::model()
			->select('lastMessageAt')
			->update($eDiscussion);

		// Update forum statistics
		Forum::model()
			->update($eDiscussion['forum'], [
				'messages' => new \Sql('messages + '.($nMessage - 1)),
				'publications' => new \Sql('publications + 1'),
			]);

		//poste un message automatique
		$eMessage = new Message([
			'discussion' => $eDiscussion,
			'forum' => $eDiscussion['forum'],
			'answerOf' => new Message()
		]);

		$fw = new \FailWatch();

		DiscussionLib::buildAnswer('create', $eMessage, [
			'value' => (new \editor\XmlLib())->prepareFromText((new ModerationUi())->getDuplicateIntroduction($eDiscussionOld))
		]);

		if($fw->ok()) {

			Discussion::model()->commit();

			$eMessage['automatic'] = TRUE;

			MessageLib::create($eMessage);

		} else {
			Discussion::model()->rollBack();
		}

		return $eDiscussion;
	}


	/**
	 * Move a publication
	 */
	public static function moveDiscussion(Discussion $eDiscussion, Forum $eForum): bool {

		$eDiscussion->expects(['forum', 'messages']);

		$eForumOld = $eDiscussion['forum'];

		Discussion::model()->beginTransaction();

		// Update forum publication
		$eDiscussion['forum'] = $eForum;

		if(Discussion::model()
			->select('forum')
			->update($eDiscussion) > 0) {

			// Update messages and texts
			Message::model()
				->whereDiscussion($eDiscussion)
				->update([
					'forum' => $eForum
				]);

			MessageHidden::model()
				->whereDiscussion($eDiscussion)
				->update([
					'forum' => $eForum
				]);

			// Update old forum
			Forum::model()
				->update($eForumOld, [
					'publications' => new \Sql('publications - 1'),
					'messages' => new \Sql('messages - '.$eDiscussion['messages'])
				] + ForumLib::calculateLastMessage($eForumOld));

			// Update new forum
			Forum::model()
				->update($eForum, [
					'publications' => new \Sql('publications + 1'),
					'messages' => new \Sql('messages + '.$eDiscussion['messages'])
				] + ForumLib::calculateLastMessage($eForum));

		}

		Discussion::model()->commit();

		return TRUE;

	}


	/**
	 * Hide a publication
	 * Set forum=NULL everything for this publication
	 */
	public static function hide(Discussion $eDiscussion) {

		$eDiscussion->expects(['id', 'forum', 'messages', 'lastMessageAt']);

		Discussion::model()->beginTransaction();

		// Set forum=NULL to all messages
		Message::model()
			->whereDiscussion($eDiscussion)
			->update([
				'forum' => NULL
			]);

		MessageHidden::model()
			->whereDiscussion($eDiscussion)
			->update([
				'forum' => NULL
			]);

		// Close all abuses
		AbuseLib::closeByDiscussion($eDiscussion, Abuse::UNKNOWN);

		// Set forum=NULL to the publication
		Discussion::model()
			->update($eDiscussion, [
				'forum' => NULL
			]);

		// Update forum statistics
		DiscussionLib::hidePublication($eDiscussion);

		Discussion::model()->commit();

	}

}
?>
