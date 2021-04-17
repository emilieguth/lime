<?php
namespace notification;

class NotificationUi {

	public function __construct() {

		\Asset::js('notification', 'notification.js');
		\Asset::css('notification', 'notification.css');

	}

	/**
	 * Display pagination for events
	 */
	public function pagination(int $unread): string {

		$h = '<div id="notifications-pagination">';
			$h .= '<a data-ajax="/notification/:get" data-ajax-class="MoreNotification" post-unread="'.$unread.'" class="btn btn-secondary">'.s("Voir plus de notifications").'</a>';
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display events
	 */
	public function display(\Collection $cEvent, int $position = 0, int $unread = 0): string {

		$h = '';

		foreach($cEvent as $eEvent) {

			list($image, $icon, $message, $cUser, $url) = $this->getEvent($eEvent);

			if(
				$eEvent['clicked'] === FALSE and
				strpos($url, 'mailto:') !== 0
			) {

				if(strpos($url, '?') === FALSE) {
					$url .= '?';
				} else {
					$url .= '&';
				}
				$url .= 'notificationClick='.$eEvent['id'];

			}
			$h .= '<a href="'.$url.'" class="notification';

			if($eEvent['read'] === FALSE or $position < $unread) {
				$h .= ' notification-unread';
			}

			if($eEvent['clicked'] === FALSE) {
				$h .= ' notification-unclicked';
			}

			$h .= '" data-id="'.$eEvent['id'].'">';
				$h .= '<div class="notification-image">';

					if($image !== NULL) {
						$h .= $this->getImage($image, $icon);
					} else if($icon !== NULL) {
						$h .= $this->getImageIcon($icon);
					}
				$h .= '</div>';
				$h .= '<div class="notification-text">';
					$h .= $this->getMessage($eEvent, $message);

					if($cUser !== NULL and $cUser->count() > 0) {

						$h .= '<div class="notification-bottom">';
							$h .= '<div class="notification-bottom-users">';
								foreach($cUser as $eUser) {
									$h .= \user\UserUi::getVignette($eUser, '3rem');
								}
							$h .= '</div>';
						$h .= '</div>';

					}

				$h .= '</div>';
			$h .= '</a>';

			$position++;

		}

		return $h;

	}

	protected function getImage(string $url, string $icon = NULL): string {

		$h = '';

		if($url !== NULL) {
			$h .= '<div class="notification-image" style="background-image: url('.$url.');"></div>';
		}

		$h .= $this->getImageIcon($icon);

		return $h;

	}

	protected function getImageIcon(string $icon = NULL): string {

		if(empty($icon)) {
			return '';
		}

		return '<div class="notification-icon">'.\Asset::icon($icon).'</div>';

	}

	protected function getMessage(Event $eEvent, string $message): string {

		$h = '<div class="notification-message">';

			$h .= '<div class="notification-message-value">';
				$h .= $message;
			$h .= '</div>';

			$h .= '<div class="notification-message-date">';
				$date = $eEvent['date'];
				$h .= \util\DateUi::ago($date, \util\DateUi::SHORT);
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	/**
	 * Builds the message to display for the notification
	 */
	public function getEvent(Event $eEvent): array {

		switch($eEvent['type']['fqn']) {

			case 'discussion-open':
				return $this->getDiscussionOpen($eEvent);

			case 'discussion-answer':
				return $this->getDiscussionAnswer($eEvent);

			case 'discussion-feedback':
				return $this->getDiscussionFeedback($eEvent);

			case 'discussion-feedback-sibling':
				return $this->getDiscussionFeedbackSibling($eEvent);

			case 'team':
				return $this->getTeam($eEvent);

		}

	}

	protected function getDiscussionOpen(Event $eEvent): array {

		return $this->getDiscussionMessage($eEvent, function($number, $arguments) {

			return p(
				"{user} a ouvert une discussion {title} dans le forum {name}.",
				"{value} nouvelles discussions ont été ouvertes dans le forum {name}.",
				$number,
				$arguments
			);

		}, function(int $number, \paper\Discussion $eDiscussion, \paper\Message $eMessage) {
			if($number === 1) {
				return \paper\DiscussionUi::url($eDiscussion);
			} else {
				return \paper\ForumUi::url($eMessage['forum']);
			}
		});

	}

	protected function getDiscussionAnswer(Event $eEvent): array {

		return $this->getDiscussionMessage($eEvent, function($number, $arguments) {

			return p(
				"{user} a posté un message dans la discussion {title} du forum {name}.",
				"{value} messages ont été postés dans la discussion {title} du forum {name}.",
				$number,
				$arguments
			);

		});

	}

	protected function getDiscussionFeedback(Event $eEvent): array {

		return $this->getDiscussionMessage($eEvent, function($number, $arguments) {

			return p(
				"{user} a répondu à un de vos messages dans la discussion {title} du forum {name}.",
				"{value} réponses à un de vos messages dans la discussion {title} du forum {name}.",
				$number,
				$arguments
			);

		});

	}

	protected function getDiscussionFeedbackSibling(Event $eEvent): array {

		return $this->getDiscussionMessage($eEvent, function($number, $arguments) {

			return p(
				"{user} a répondu dans une conversation à laquelle vous avez participé dans la discussion {title} du forum {name}.",
				"{value} réponses dans une conversation à laquelle vous avez participé dans la discussion {title} du forum {name}.",
				$number,
				$arguments
			);

		});

	}

	protected function getDiscussionMessage(Event $eEvent, \Closure $callbackMessage, \Closure $callbackUrl = NULL) {

		$cEventItem = $eEvent['item'];
		$number = $cEventItem->count();

		$eEventItem = $cEventItem->last(); // Older message
		$eText = $eEventItem['aboutText'];
		$eMessage = $eText['message'];
		$eDiscussion = $eMessage['discussion'];


		$message = $callbackMessage(
			$number,
			[
				'number' => '<b>'.$number.'</b>',
				'user' => $this->getUser($eEventItem),
				'title' => '<b>'.encode($eDiscussion['title']).'</b>',
				'name' => '<b>'.encode($eMessage['forum']['name']).'</b>'
			]
		);

		if($callbackUrl !== NULL) {
			$url = $callbackUrl($number, $eDiscussion, $eMessage);
		} else {
			$url = \paper\MessageUi::url($eDiscussion, $eMessage);
		}

		return [
			NULL,
			'pencil',
			$message,
			$cEventItem->getColumnCollection('user'),
			$url,
			[
				'publicationUrl' => \paper\DiscussionUi::url($eDiscussion),
				'publication' => $eDiscussion['id'],
				'message' => $eMessage['id']
			]
		];

	}

	protected function getUser(Event $eEventItem) {

		return '<b>'.encode($eEventItem['aboutUser']['firstName'].' '.$eEventItem['aboutUser']['lastName']).'</b>';

	}

	protected function getTeam(Event $eEvent): array {

		$data = (new TeamUi())->get($eEvent['reference']) + [
			'image' => \Asset::path('main', 'infinite-100.png')
		];

		return [
			$data['image'],
			NULL,
			$data['message'],
			NULL,
			$data['url'],
			[]
		];

	}

}
?>
