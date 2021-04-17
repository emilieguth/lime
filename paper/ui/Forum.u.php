<?php
namespace paper;

/**
 * Forum
 *
 */
class ForumUi {

	/**
	 * Build  class
	 */
	public function __construct() {

		\Asset::css('paper', 'forum.css');
		\Asset::css('paper', 'paper.css');

	}


	/**
	 * Display a list of forums
	 *
	 */
	public function getList(\Collection $cForum, array $options = []): string {

		if($cForum->count() === 0) {
			return '';
		}

		\Asset::js('util', 'column.js');
		\Asset::css('util', 'column.css');

		$h = '<div class="forum-items columns" onrender="ColumnLime.reorganize(this)">';

		$position = 0;

		foreach($cForum as $eForum) {
			$h .= $this->getItem($eForum, $options + ['position' => $position]);
			$position++;
		}

		$h .= '</div>';

		return $h;

	}


	/**
	 * Get forum box
	 *
	 * Options:
	 * - position : position du forum
	 * - withLast : avec ou sans les dernières publications ?
	 */
	public function getItem(Forum $eForum, array $options = []): string {

		// Options
		$options += [
			'withLast' => TRUE,
			'position' => ($eForum['position'] + 1)
		];

		$properties = ['id'];

		if($options['withLast']) {
			$properties[] = 'lastPublications';
		}

		$eForum->expects($properties);

		// we use the forum cover
		$url = (new \media\ForumCoverUi())->getUrlByElement($eForum, 'm');

		$h = '<div class="util-block forum-item column-item" data-n="'.$options['position'].'">';

		$h .= '<div class="forum-item-header" style="'.($url ? 'background-image: url('.$url.');' : '').'">';

			$h .= '<div class="forum-item-header-black"></div>';

			$h .= '<h2>';
				$h .= '<a href="'.$this->url($eForum).'">';
					$h .= encode($eForum['name']);
				$h .= '</a>';
			$h .= '</h2>';

		$h .= '</div>';

		$h .= '<div class="forum-item-body">';

		$h .= '<p class="forum-item-description">'.$eForum['description'].'</p>';

		$h .= $this->getStats($eForum);

		if(
			$options['withLast'] and
			$eForum['lastPublications']->notEmpty()
		) {

			$h .= '<div class="forum-item-users">';

			foreach($eForum['lastPublications'] as $position => $eDiscussion) {

				$url = DiscussionUi::url($eDiscussion);

				if($eDiscussion['messages'] > 1) {
					$eUser = $eDiscussion['lastMessage']['author'];
					$date = $eDiscussion['lastMessage']['createdAt'];
					$text = s("{author} a répondu", ['author' => \user\UserUi::link($eUser)]);
					$urlUser = MessageUi::url($eDiscussion, $eDiscussion['lastMessage']);
				} else {
					$eUser = $eDiscussion['author'];
					$date = $eDiscussion['createdAt'];
					$text = s("Posté par {author}", ['author' => \user\UserUi::link($eUser)]);
					$urlUser = $url;
				}

				$h .= '<div class="forum-item-user">
					<div class="forum-item-user-info">
						<div class="forum-item-user-title">
							<div class="forum-item-user-value">
								<a href="'.$url.'">'.encode($eDiscussion['title']).'</a>
							</div>
						</div>
						<div class="forum-item-user-data">
							'.$text.'<br/>
							'.\util\DateUi::ago($date).'
						</div>
						<div class="forum-item-line">
						</div>
						<div class="forum-item-caret" style="left: '.($position * 50 + 15).'px">'.\Asset::image('paper', 'caret.png').'</div>
					</div>
					<div class="forum-item-user-image">
						<a href="'.$urlUser.'">'.\user\UserUi::getVignette($eUser, '3rem').'</a>
					</div>
				</div>';

			}

			$h .= '</div>';

		}

		$h .= '</div>';

		$h .= '</div>';

		return $h;

	}



	/**
	 * Returns a header with a forum link
	 *
	 */
	public function getHeader(Forum $eForum, bool $isCreate, bool $hasNotifications = NULL): string {

		$background = (new \media\ForumCoverUi())->getBackgroundByElement($eForum, 'l');

		$h = '<div id="paper-header" style="'.$background.'">';

			$h .= '<div id="paper-header-content">';

				$h .= '<h4>';
				$h .= '<a href="/forums">'.s("Forums").'</a>';
				$h .= '</h4>';

				$h .= '<h1>';
				$h .= '<a href="'.ForumUi::url($eForum).'">'.encode($eForum['name']).'</a>';
				$h .= '</h1>';

				$h .= '<div class="paper-header-description">';

				$h .= '<p>'.encode($eForum['description']).'</p>';

				if(\Privilege::can('paper\moderation') and $hasNotifications !== NULL) {
					$h .= $this->getForumSubscription($eForum, $hasNotifications);
				}

				$h .= '</div>';

			if($isCreate === FALSE) {

				$h .= '<div>
							<a href="/paper/open:create?forum='.$eForum['id'].'" class="btn btn-primary">'.\Asset::icon('pencil-fill').' '.s("Créer une discussion").'</a>
						</div>';

			}

			$h .= '</div>';

			if(\Privilege::can('paper\admin')) {

				$h .= '<div class="paper-header-upload hide-sm-down" title="'.s("Modifier la photo de couverture du forum").'">';
				$h .= (new \media\ForumCoverUi())->getCamera($eForum);
				$h .= '</div>';

			}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Get notification status for a discussion
	 *
	 */
	public function getSubscription(Forum $eForum, bool $hasNotifications) {

		$h = '';

		if($hasNotifications) {

			$h .= '<div class="forum-notification-bell">';
			$h .= '<a data-ajax="/paper/forum:doSubscribe" post-id="'.$eForum['id'].'" post-subscribe="0" title="'.s("Désactiver les notifications pour ce forum, sans rétroactivité pour les sujets déjà créés").'">';
			$h .= \Asset::icon('bell-fill');
			$h .= '<span>'.s("Abonné").'</span>';
			$h .= \Asset::icon('check');
			$h .= '</a>';
			$h .= '</div>';

		} else {

			$h .= '<div class="forum-notification-bell">';
			$h .= '<a data-ajax="/paper/forum:doSubscribe" post-id="'.$eForum['id'].'" post-subscribe="1" title="'.s("Recevoir des notifications quand il y a du nouveau sur ce forum").'">';
			$h .= \Asset::icon('bell-fill');
			$h .= '</a>';
			$h .= '</div>';

		}

		return $h;

	}

	protected function getStats(Forum $eForum): string {

		$h = '<div class="forum-item-statistics">';

		$h .= '<div class="forum-item-discussions">'.p("{value} discussion", "{value} discussions", $eForum['publications']).'</div>';
		$h .= '<div>//</div>';
		$h .= '<div class="forum-item-messages">'.p("{value} message", "{value} messages", $eForum['messages']).'</div>';

		$h .= '</div>';

		return $h;
	}

	/**
	 * Url to a forum
	 */
	public static function url(Forum $eForum, $page = NULL): string {

		$eForum->expects(['id', 'cleanName']);

		if($page !== NULL) {
			$page = '/'.$page;
		}

		return \Lime::getUrl().'/forum/'.$eForum['cleanName'].'/'.$eForum['id'].$page;

	}

}
?>
