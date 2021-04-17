<?php
namespace paper;

/**
 * Discussions
 *
 */
class DiscussionUi {

	/**
	 * Build  class
	 */
	public function __construct() {

		\Asset::js('paper', 'draft.js');
		\Asset::css('paper', 'draft.css');

		\Asset::css('paper', 'discussion.css');
		\Asset::css('paper', 'paper.css');

	}


	/**
	 * Link to a publication
	 *
	 */
	public static function link(Discussion $eDiscussion, array $attributes = []): string {

		$eDiscussion->expects(['id', 'title', 'cleanTitle', 'messages']);

		return '<a href="'.self::url($eDiscussion).'" '.attrs($attributes).'>'.encode($eDiscussion['title']).'</a>';

	}

	public static function url(Discussion $eDiscussion): string {

		$eDiscussion->expects(['id', 'cleanTitle']);

		return '/topic/'.$eDiscussion['cleanTitle'].'/'.$eDiscussion['id'];

	}


	/**
	 * Gets the metadatas of a publication (title, description)
	 *
	 */
	public function getMeta(Discussion $eDiscussion): array {

		$eText = $eDiscussion['openMessage']['text'];

		$title = s('{title} - Forum {forum}', ['title' => $eDiscussion['title'], 'forum' => $eDiscussion['forum']['name']]);

		$metaDescription = s("Rejoignez la discussion sur le forum !");

		$text = mb_substr(strip_tags($eText['value']), 0, 100);

		if(mb_strlen($text) === 100) {
			$text .= '...';
		}

		if($text) {
			$metaDescription .= ' '.encode($text);
		}

		return [$title, $metaDescription];

	}


	/**
	 * Display a list of publications in the forums
	 *
	 */
	public function getList(\Collection $cDiscussion, \Collection $cForum, array $newPublications): string {

		if($cDiscussion->count() === 0) {

			return '<p class="util-info">'.s("Il n'y a pas encore de discussion dans ce forum...").'</p>';

		}

		$h = '';

		$moderator = \Privilege::can('paper\moderation');

		if($moderator) {
			$form = new \util\FormUi();
			$h .= $form->open('publication-moderation-selection');
		}

		$h .= '<div class="publications-grid '.($moderator ? 'publications-grid-moderation' : '').'">';

		if($moderator) {
			$h .= '<div class="util-grid-header publication-grid-select">'.(new ModerationUi())->getPublicationSelectAll().'</div>';
		}

		$h .= '<div class="util-grid-header" style="grid-column: span 2">'.s("Titre").'</div>';
		$h .= '<div class="util-grid-header publication-grid-author">'.s("Auteur").'</div>';
		$h .= '<div class="util-grid-header text-center">'.s("Réponses").'</div>';
		$h .= '<div class="util-grid-header">'.s("Activité").'</div>';

		foreach($cDiscussion as $eDiscussion) {

			if($moderator) {
				$h .= '<div class="publication-grid-select">'.(new ModerationUi())->getPublicationSelection($form, $eDiscussion).'</div>';
			}

			$urlPublication = DiscussionUi::url($eDiscussion);

			if($eDiscussion['new'] or in_array($eDiscussion['id'], $newPublications)) {
				$new = ' '.\Asset::icon('asterisk', ['class' => 'unread', 'title' => s("Vous n'avez pas encore lu cette discussion")]).' ';
			} else {

				if($eDiscussion['unread'] > 0) {
					$new = ' <span data-notification="forum-publication-'.$eDiscussion['id'].'" class="tag-default" title="'.AlertUi::getMessage('unread').'">'.$eDiscussion['unread'].'</span>';
					$urlPublication = MessageUi::url($eDiscussion, $eDiscussion['notification']['lastMessageRead']);
				} else {
					$new = '';
				}

			}

			$pinned = ($eDiscussion['pinned'] ? \Asset::icon('paperclip') : '');

			$link = '<a data-notification="forum-publication-link-'.$eDiscussion['id'].'" href="'.$urlPublication.'" class="neutral">'.$pinned.' <span class="highlight">'.encode($eDiscussion['title']);

			if($eDiscussion['write'] !== Discussion::OPEN) {
				$link .= ' '.\Asset::icon('lock-fill').'';
			}

			$link .= '</span></a>';

			$link .= $new;

			if($eDiscussion['messages'] > \Setting::get('messagesPerPage')) {


				$h .= '<div>
					'.$link.'
				</div>';
				$h .= '<div>
					<div class="publication-grid-begin-end">
						<a href="'.$urlPublication.'" class="neutral front-discussions-link-begin">'.s("début").'</a>
						<a href="'.MessageUi::url($eDiscussion, $eDiscussion['lastMessage']).'" class="neutral front-discussions-link-end">'.s("fin").'</a>
					</div>
				</div>';

			} else {

				$h .= '<div style="grid-column: span 2">
					'.$link.'
				</div>';

			}

			$h .= '<div class="publication-grid-author">'.
				\user\UserUi::getVignette($eDiscussion['author'], '2rem').
				\user\UserUi::link($eDiscussion['author']).
				'</div>';
			$h .= '<div class="text-center">
				<span >'.($eDiscussion['messages'] - 1).'</span>
			</div>';
			$h .= '<div title="'.\util\DateUi::numeric($eDiscussion['lastMessageAt']).'">
				'.\util\DateUi::ago($eDiscussion['lastMessageAt'], \util\DateUi::SHORT).'
			</div>';

		}

		$h .= '</div>';

		if($moderator) {

			$h .= (new ModerationUi())->getPublicationsActions($cForum);
			$h .= $form->close();
		}

		return $h;

	}

	public function getSimpleHeader(Discussion $eDiscussion): string {

		$background = (new \media\ForumCoverUi())->getBackgroundByElement($eDiscussion['forum'], 'l');

		$h = '<div id="paper-header" style="'.$background.'">';

		$h .= '<div id="paper-header-content">';

		$h .= '<h4>';
		$h .= '<a href="/forums">'.s("Forums").'</a>';
		$h .= ' '.\Asset::icon('chevron-right').' ';
		$h .= '<a href="'.ForumUi::url($eDiscussion['forum']).'">'.encode($eDiscussion['forum']['name']).'</a>';
		$h .= '</h4>';

		$h .= '<h1>';
		$h .= '<a href="'.$this->url($eDiscussion).'">'.encode($eDiscussion['title']).'</a>';
		$h .= '</h1>';

		$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getHeader(Discussion $eDiscussion, \Collection $cForumMove = NULL, bool $hasNotifications): string {

		$background = (new \media\ForumCoverUi())->getBackgroundByElement($eDiscussion['forum'], 'l');

		$h = '<div id="paper-header" style="'.$background.'">';

		$h .= '<div id="paper-header-content">';

		$h .= '<h4>';
		$h .= '<a href="/forums">'.s("Forums").'</a>';
		$h .= ' '.\Asset::icon('chevron-right').' ';
		$h .= '<a href="'.ForumUi::url($eDiscussion['forum']).'">'.encode($eDiscussion['forum']['name']).'</a>';
		$h .= '</h4>';

		$h .= '<h1>';
		$h .= '<a href="'.$this->url($eDiscussion).'">'.encode($eDiscussion['title']).'</a>';
		$h .= '</h1>';

		$h .= $this->getStats($eDiscussion, $hasNotifications);

		$actions = [];

		if(\Privilege::can('paper\moderation')) {

			$actions[] = (new ModerationUi())->getPublicationActions($eDiscussion, $cForumMove);

		}

		if($eDiscussion['isAuthor']) {

			$link = '/paper/open:update?publication='.$eDiscussion['id'].'&forum='.$eDiscussion['forum']['id'];
			$actions[] = '<a href="'.$link.'" class="btn btn-primary" title="'.s("Paramétrer cette publication").'">'.\Asset::icon('gear-fill').'</a>';

		}

		if($actions) {

			$h .= '<div class="paper-header-actions">
						'.implode('', $actions).'
					</div>';

		}

		$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getStats(Discussion $eDiscussion, bool $hasNotifications = FALSE): string {

		$h = '<div class="discussion-stats">';

		$h .= '<div class="discussion-stats-answer">'.p(
				'{value} message',
				'{value} messages',
				$eDiscussion['messages'] - 1,
				['number' => '<span >'.($eDiscussion['messages'] - 1).'</span>']
			).'</div>';

		$h .= $this->getSubscription($eDiscussion, $hasNotifications);

		$h .= '</div>';

		return $h;
	}

	/**
	 * Get notification status for a discussion
	 *
	 */
	public function getSubscription(Discussion $eDiscussion, bool $hasNotifications) {

		$h = '';

		if($hasNotifications) {

			$h .= '<div class="discussion-notification-bell">';
			$h .= \Asset::icon('bell-fill').' ';
			$h .= '<a data-ajax="/paper/discussion:doSubscribe" post-publication="'.$eDiscussion['id'].'" post-subscribe="0" title="'.s("Désactiver les notifications pour ce sujet").'">';
			$h .= s("Abonné");
			$h .= '</a>';
			$h .= ' '.\Asset::icon('check');
			$h .= '</div>';

		} else {

			$h .= '<div class="discussion-notification-bell">';
			$h .= \Asset::icon('bell-fill').' ';
			$h .= '<a data-ajax="/paper/discussion:doSubscribe" post-publication="'.$eDiscussion['id'].'" post-subscribe="1" title="'.s("Recevoir des notifications quand il y a du nouveau sur ce sujet").'">';
			$h .= s("S'abonner");
			$h .= '</a>';
			$h .= '</div>';

		}

		return $h;

	}

}
?>
