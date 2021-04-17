<?php
namespace paper;

/**
 * Discussions
 *
 */
class MessageUi {

	/**
	 * Build  class
	 */
	public function __construct() {

		\Asset::js('paper', 'draft.js');

		\Asset::css('paper', 'draft.css');
		\Asset::css('paper', 'discussion.css');

	}

	/**
	 * Url to a publication, target a text
	 *
	 */
	public static function url(Discussion $eDiscussion, Message $eMessage): string {

		$eDiscussion->expects(['id', 'cleanTitle']);

		try {

			$eMessage->expects(['type']);

			switch($eMessage['type']) {

				case Message::FEEDBACK :
					return DiscussionUi::url($eDiscussion).'/message/'.$eMessage['answerOf']['id'].'#front-message-'.$eMessage['id'];

				default :
					return DiscussionUi::url($eDiscussion).'/message/'.$eMessage['id'].'#front-message-'.$eMessage['id'];


			}

		} catch(\Exception $e) {
			return DiscussionUi::url($eDiscussion).'/message/'.$eMessage['id'].'#front-message-'.$eMessage['id'];
		}


	}

	/**
	 * Get a link to display more older messages
	 *
	 */
	public function getMessagesBefore(Discussion $eDiscussion, int $number, int $position): string {

		if($number === 0) {
			return '';
		}

		$number = min($number, \Setting::get('messagesPerPage'));
		$url = DiscussionUi::url($eDiscussion).'/p/'.$position.'/'.$number;

		return '<div class="front-discussions-around">
			<a href="'.$url.'" data-ajax="'.$url.'" data-ajax-navigation="no"post-where="before">'.p("{value} message plus ancien", "{value} messages plus anciens", $number).'</a>
		</div>';

	}

	/**
	 * Get a link to display more newest messages
	 */
	public function getMessagesAfter(Discussion $eDiscussion, int $number, int $position): string {

		if($number === 0) {
			return '';
		}

		$h = '<div class="front-discussions-around" data-notification="publication-'.$eDiscussion['id'].'">';
		if($number > 0) {
			$url = DiscussionUi::url($eDiscussion).'/p/'.$position.'/'.$number;
			$h .= '<a href="'.$url.'" data-ajax="'.$url.'" data-ajax-navigation="non" post-where="after">'.p("{value} message plus récent", "{value} messages plus récents", $number).'</a>';
		}
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display a list of messages in a publication
	 *
	 */
	public function getMessages(Discussion $eDiscussion, \Collection $cMessage, Read $eReadElement): string {

		$eDiscussion->expects(['createdAt']);

		if($cMessage->count() === 0) {
			return '';
		}

		$h = '';

		$position = 0;

		$lastMessageCreationDate = $eDiscussion['createdAt'];

		foreach($cMessage as $eMessage) {

			$unread = (
				$eReadElement->notEmpty() and
				$eMessage['position'] >= $eReadElement['messagesRead']
			);

			// Display time between 2 messages box if they are spaced enough in time
			$h .= $this->getBetweenMessageBox($position, $lastMessageCreationDate, $eDiscussion, $eMessage);
			$lastMessageCreationDate = $eMessage['createdAt'];

			$h .= $this->getMessage($eDiscussion, $eMessage, $unread);

			$position++;

		}

		return $h;

	}

	/**
	 * Display a message of a forum
	 *
	 */
	public function getMessage(Discussion $eDiscussion, Message $eMessage, bool $unread): string {

		$eMessage->expects(['type', 'position', 'status', 'copied', 'censored']);

		$eDiscussion->expects(['isAuthor']);

		$classCopied = '';
		if($eMessage['copied']->notEmpty()) {
			$classCopied .= ' copied';
		}
		if($eMessage['censored']) {
			$classCopied .= ' copied';
		}


		$h = '<div class="front-message-discussion front-message-'.$eMessage['type'].' '.$classCopied.'" data-front-message-text="'.$eMessage['text']['id'].'" data-post-reference="'.($eMessage['answerOf']->empty() ? '' : $eMessage['answerOf']['id']).'" data-position="'.$eMessage['position'].'" id="front-message-'.$eMessage['id'].'">';
			
			$h .= '<div class="discussion-section" id="front-message-position-'.$eMessage['position'].'">';
	
				$h .= $this->getHeaderMessage($eDiscussion, $eMessage, $unread);
	
				$h .= '<div id="front-text-'.$eMessage['id'].'" class="front-message-text">';
	
					$h .= $this->getText($eMessage, $eMessage['text']);
	
					if($eMessage['copied']->notEmpty()) {
						$h .= '<p class="util-info">'.s("Ce message a été déplacé dans la discussion {publication}", ['publication' => DiscussionUi::link($eMessage['copied'])]).'</p>';
					}
	
					if($eMessage['first'] === TRUE) {
						$h .= $this->getFirstPublishingBlock($eMessage);
					}
	
				$h .= '</div>';
	
				if($eMessage['type'] === Message::ANSWER or $eMessage['type'] === Message::OPEN) {
	
					$h .= '<div id="front-feedback-'.$eMessage['id'].'" class="front-message-feedback">';
						$h .= $this->getFeedbacks($eDiscussion, $eMessage['feedbacks']);
					$h .= '</div>';
	
				}
	
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}


	/**
	 * Display a list of feedbacks
	 */
	public function getFeedbacks(Discussion $eDiscussion, \Collection $cMessage): string {

		$h = '';

		foreach($cMessage as $eMessage) {

			$h .= $this->getFeedback($eDiscussion, $eMessage);

		}

		return $h;

	}

	/**
	 * Display a feedback
	 */
	public function getFeedback(Discussion $eDiscussion, Message $eMessage, string $mode = 'read'): string {

		$h = '<div id="front-message-'.$eMessage['id'].'" class="'.$eMessage['type'].'-content discussion-'.$eMessage['type'].'-content">';
			$h .= $this->getHeaderMessage($eDiscussion, $eMessage, FALSE);

			if($mode === 'read') {
				$h .= $this->getText($eMessage, $eMessage['text']);
			} else {
				$h .= (new WriteUi())->updateFeedback($eMessage);
			}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display a text
	 */
	public function getText(Message $eMessage, Text $eText): string {

		$h = '';

		if($eMessage['censored']) {

			$h .= '<div class="color-warning">
				'.\Asset::icon('shield-fill-exclamation').'
				'.s("Ce message a été censuré.").'
			</div>';

		} else {

			$h = (new \editor\EditorUi())->value(
				$eText['value'],
				['acceptEmbed' => FALSE],
				['class' => 'spaced', 'id' => 'reador-'.$eText['id']]
			);

		}

		if($eMessage['automatic']) {
			$h = '<div class="util-info">'.$h.'</div>';

		}

		return $h;

	}


	/**
	 * Display the first message congratulation block
	 *
	 */
	public function getFirstPublishingBlock(Message $eMessage): string {
		return '
			<div class="front-message-first-publishing">
				<p class="util-info">'.
			s("C'est la première contribution de {userName}, vous pouvez en profiter pour lui souhaiter la bienvenue et découvrir son profil !",
				['userName' => \user\UserUi::link($eMessage['author'])]).
				'</p>
			</div>
		';
	}

	/**
	 * Return a text for deleted messages
	 *
	 */
	public function getDeletedMessage(): string {

		$h = '<div class="front-message-deleted">';
			$h .= \Asset::icon('x');
			$h .= ' '.s("Ce message est supprimé...");
		$h .= '</div>';

		return $h;

	}

	/**
	 * Returns the header of a message
	 *
	 */
	public function getHeaderMessage(Discussion $eDiscussion, Message $eMessage, bool $unread) {

		$eMessage->expects(['isAuthor']);

		$eText = $eMessage['text'];

		list($vignette, $user) = $this->getAuthorDisplay($eMessage, $eMessage, $eMessage['isAuthor']);

		$eMessage['text']->expects(['valueUpdatedAt']);
		$eMessage->expects(['isAuthor']);

		$h = '<div class="front-message-head">';

			$h .= '<div class="front-message-user">';

				if($eMessage['type'] === Message::FEEDBACK) {
					$h .= \Asset::icon('reply-fill', ['class' => 'front-message-reply']);
				} else {
					$h .= $vignette;
				}

				$h .= $user;

			$h .= '</div>';


			$h .= '<div id="front-message-info-'.$eMessage['id'].'" class="front-message-info">';

				$h .= '<div class="front-message-date'.($unread ? '-unread' : '').'">';

				$date = \util\DateUi::ago($eMessage['createdAt']);

				$h .= '<span title="'.\util\DateUi::numeric($eMessage['createdAt']).'">';
					if($eMessage['type'] === Message::FEEDBACK) {
						$h .= $date;
					} else {
						$h .= '<a href="'.self::url($eDiscussion, $eMessage).'">'.$date.'</a>';
					}

					if($eMessage['text']['valueUpdatedAt'] !== NULL) {
						$h .= ' - <u class="message-updated" title="'.s("Le {date}", ['date' => \util\DateUi::numeric($eText['valueUpdatedAt'])]).'">'.s("modifié").'</u>';
					}

					if($unread) {
						$h .= ' '.\Asset::icon('asterisk', ['class' => 'unread', 'title' => s("Vous n'avez pas encore lu ce message")]);
					}
				$h .= '</div>';

				$h .= '<div class="front-message-actions">';

					$actions = array_merge(
						(new ModerationUi())->getMessageActions($eMessage, $eMessage['isAuthor']),
						$this->getHeaderMessageActions($eMessage, $eDiscussion)
					);

					$h .= implode('&nbsp;&nbsp;', $actions);

				$h .= '</div>';

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	/**
	 * Actions for a message
	 */
	protected function getHeaderMessageActions(Message $eMessage, Discussion $eDiscussion): array {

		$eMessage->expects(['isAuthor']);

		if($eMessage['censored']) {
			return [];
		}

		$actions = [];

		if($eMessage['isAuthor']) {

			switch($eMessage['type']) {

				case Message::ANSWER :
					break;
					$actions[] = '<a href="/paper/answerChange:update?id='.$eMessage['id'].'" class="btn btn-sm btn-outline-primary" title="'.s("Éditer mon message").'">'.\Asset::icon('pencil-fill').'</a>';

				case Message::FEEDBACK :
					$actions[] = '<a data-ajax="/paper/feedbackChange:update" class="btn btn-sm btn-outline-primary" post-message="'.$eMessage['answerOf']['id'].'" post-id="'.$eMessage['id'].'" title="'.s("Éditer mon message").'">'.\Asset::icon('pencil-fill').'</a>';
					break;

			}

		}


		if(
			($eMessage['type'] === Message::ANSWER or $eMessage['type'] === Message::OPEN) and
			$eDiscussion['write'] === Discussion::OPEN
		) {

			$actions[] = '<a data-ajax="/paper/feedbackCreate:form" post-message="'.$eMessage['id'].'" class="btn btn-sm btn-outline-primary" title="'.s("Répondre à ce message").'">'.\Asset::icon('reply-fill').'</a>';

		}

		if(
			($eMessage['type'] === Message::ANSWER or $eMessage['type'] === Message::OPEN) and
			\Privilege::can('paper\abuseReport') and
			$eMessage['abuseStatus'] !== Message::CLOSED and
			$eMessage['abuseReported'] === FALSE and
			$eMessage['isAuthor'] === FALSE
		) {

			$actions[] = '<a href="/paper/message:abuse?message='.$eMessage['id'].'" id="message-report-abuse-'.$eMessage['id'].'" class="btn btn-sm btn-outline-primary" title="'.s("Reporter un abus").'">'.\Asset::icon('flag-fill').'</a>';

		}

		$eUser = \Setting::get('main\onlineUser');

		if(
			$eUser->notEmpty() and
			(
				$eUser['id'] === $eDiscussion['author']['id'] or
				$eMessage['isAuthor']
			)
		) {

			$deleteTitle = 'title="'.s("Supprimer ce message").'" data-confirm="'.s("Êtes-vous sûr de vouloir supprimer ce message ?").'"';

			switch($eMessage['type']) {

				case Message::ANSWER :
					$actions[] = '<a data-ajax="/paper/answerChange:doDelete" class="btn btn-sm btn-outline-primary" post-id="'.$eMessage['id'].'" '.$deleteTitle.'>'.\Asset::icon('trash-fill').'</a>';
					break;

				case Message::FEEDBACK :
					$actions[] = '<a data-ajax="/paper/feedbackChange:doDelete" class="btn btn-sm btn-outline-primary" post-message="'.$eMessage['answerOf']['id'].'" post-id="'.$eMessage['id'].'" '.$deleteTitle.'>'.\Asset::icon('trash-fill').'</a>';
					break;

			}

		}

		return $actions;

	}

	public function getAuthorDisplay(Message $eMessage, \Element $eElement, bool $isAuthor): array {

		$eUser = $eElement['author'];

		$vignette = \user\UserUi::getVignette($eUser, '4rem');

		$name = '<div>';

			$name .= '<div>';
				$name .= \user\UserUi::link($eUser);

				if(in_array($eMessage['author']['id'], \Setting::get('user\team'))) {
					$name .= '&nbsp;&nbsp;<span class="annotation color-muted">'.S("Équipe {siteName}").'</span>';
				}
			$name .= '</div>';

			$name .= '<div class="front-message-stats">';

				$name .= p("{value} message", "{value} messages", $eMessage['author']['cMessage']['number']);
				$name .= ' - ';
				$name .= s("Inscrit depuis {date}", ['date' => \util\DateUi::ago($eElement['author']['createdAt'], \util\DateUi::LONG, 'YODHM')]);

			$name .= '</div>';

		$name .= '</div>';

		return [
			$vignette,
			$name
		];
	}

	/**
	 * To display a message "X days after"
	 *
	 */
	public function getBetweenMessageBox(int $position, string $lastMessageCreationDate, Discussion $eDiscussion, Message $eMessage): string {

		$intervalText = $this->getIntervalAsText($eMessage['createdAt'], $lastMessageCreationDate);

		if($intervalText === '') {
			return '';
		}

		$h = '<div class="front-message-time-interval">';
			$h .= '<div class="message-time-content">';
				$h .= \Asset::icon('clock').' ';
				$h .= $intervalText;
			$h .= '</div>';
		$h .= '</div>';

		return $h;
	}

	/**
	 * Return the time elapsed between the two date in days if there's less
	 * than 2 month or in month (and month and a half) otherwise.
	 * Also return an empty string if there's less than 15 days elapsed.
	 *
	 */
	protected function getIntervalAsText(string $currentDate, string $previousDate): string {

		$text = '';
		$averageDayByMonth = 365 / 12;

		if($previousDate !== NULL) {

			$secInterval = \util\DateLib::interval($currentDate, $previousDate);
			$daysInterval = floor($secInterval / 86400);

			if($daysInterval >= 15) {

				if($daysInterval <= $averageDayByMonth * 2) {

					$text .= p("{days} jour plus tard", "{days} jours plus tard", $daysInterval, ['days' => $daysInterval]);

				} else if($daysInterval > $averageDayByMonth * 2) {

					$months = $daysInterval / $averageDayByMonth;
					$floorMonths = floor($months);

					// Determine the average number of month to display
					$comp = $months - $floorMonths;
					if($comp < 0.25) {
						$displayMonth = $floorMonths;
						$halfMonth = FALSE;
					} else if ($comp >= 0.25 and $comp < 0.75) {
						$displayMonth = $floorMonths;
						$halfMonth = TRUE;
					} else {
						$displayMonth = $floorMonths + 1;
						$halfMonth = FALSE;
					}

					// Create the text to display
					if($halfMonth === TRUE) {
						$text .= p("{months} mois et demi plus tard", "{months} mois et demi plus tard", $displayMonth, ['months' => $displayMonth]);
					} else {
						$text .= p("{months} mois plus tard", "{months} mois plus tard", $displayMonth, ['months' => $displayMonth]);
					}
				}
			}
		}

		return $text;
	}

}
?>
