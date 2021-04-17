<?php
namespace paper;

/**
 * Forums moderation
 *
 */
class ModerationUi {

	public function __construct() {

		\Asset::js('paper', 'moderation.js');

		if(\Privilege::can('paper\moderation')) {
			\Asset::css('paper', 'moderation.css');
		}

	}

	/**
	 * Change moderation display
	 */
	public function getNavigation(int $nAbuse): string {

		$h = '';

		$h .= '<li class="nav-action-optional">';
			$h .= '<a href="/paper/admin/abuses" class="nav-item">'.\Asset::icon('bug-fill').'&nbsp;&nbsp;'.p("{value} abus", "{value} abus", $nAbuse).'</a>';
		$h .= '</li>';

		return $h;

	}

	/**
	 * Get actions about a publication
	 */
	public function getPublicationActions(Discussion $eDiscussion, \Collection $cForumMove): string {

		if(\Privilege::can('paper\moderation') === FALSE) {
			return '';
		}

		$h = '<div>';

		$h .= '<button type="button" class="btn btn-secondary dropdown-toggle" data-dropdown="bottom-start">
			'.s("Modération").'
		</button>';

		$h .= '<div class="dropdown-list">';

		switch($eDiscussion['write']) {

			case Discussion::OPEN :
				$h .= '<a data-ajax="/paper/moderation/publication:doLock" class="dropdown-item" post-publication="'.$eDiscussion['id'].'" post-lock="1">'.\Asset::icon('lock-fill').' '.s("Fermer cette discussion").'</a>';
				break;

			case Discussion::LOCKED :
				$h .= '<a data-ajax="/paper/moderation/publication:doLock" class="dropdown-item" post-publication="'.$eDiscussion['id'].'" post-lock="0">'.\Asset::icon('unlock-fill').' '.s("Réouvrir la discussion").'</a>';
				break;

		}


		$h .= '<a data-ajax="/paper/moderation/publication:doHide" class="dropdown-item" data-confirm="'.s("Voulez-vous vraiment supprimer cette discussion ?").'" post-publication="'.$eDiscussion['id'].'">'.\Asset::icon('trash-fill').' '.s("Supprimer cette discussion").'</a>';

		if($cForumMove->count() > 0) {

			$h .= '<div class="dropdown-title">'.\Asset::icon('arrows-move').' '.s("Déplacer la discussion vers...").'</div>';

			foreach($cForumMove as $eForum) {
				$h .= '<a data-ajax="/paper/moderation/publication:doMove" class="dropdown-item" post-publication="'.$eDiscussion['id'].'" post-to="'.$eForum['id'].'">';
					$h .= \Asset::icon('chevron-right', ['style' => 'margin-left: 1rem']).' '.$eForum['name'];
				$h .= '</a>';
			}


		}

		$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getPublicationSelectAll(): string {

		$h = '<label class="publication-moderation-select" title="'.s("Tout cocher / Tout décocher").'">';
			$h .= '<input type="checkbox" id="publication-moderation-select-all"/>';
		$h .= '</label>';

		return $h;

	}

	public function getPublicationSelection(\util\FormUi $form, $eDiscussion): string {

		$h = '<label class="publication-moderation-select">';
			$h .= $form->inputCheckbox('ids[]', $eDiscussion['id']);
		$h .= '</label>';

		return $h;

	}

	public function getPublicationsActions(\Collection $cForum): string {

		$h = '<div id="moderation-actions">
			<div class="util-block">
				<h3>'.s("Modérer des discussions {value}", '(<span id="moderation-actions-number"></span>)').'</h3>
				<div id="moderation-actions-list">
					<div>
						<button class="btn btn-primary dropdown-toggle" data-dropdown="top-start">'.s("Déplacer").'</button>
						<div class="dropdown-list">
						'.$cForum->makeString(function(Forum $eForum) {
							return '<a data-ajax-submit="/paper/moderation/publications:doMove" class="dropdown-item" post-to="'.$eForum['id'].'">'.encode($eForum['name']).'</a>';
						}).'
						</div>
					</div>
					<a data-ajax-submit="/paper/moderation/publications:doLock" post-lock="1" class="btn btn-primary">'.\Asset::icon('lock-fill').' '.s("Fermer").'</a>
					<a data-ajax-submit="/paper/moderation/publications:doLock" post-lock="0" class="btn btn-primary">'.\Asset::icon('unlock-fill').' '.s("Réouvrir").'</a>
					<a data-ajax-submit="/paper/moderation/publications:doHide" class="btn btn-danger" data-confirm="'.s("Voulez-vous vraiment supprimer ces discussions ?").'">'.\Asset::icon('trash-fill').' '.s("Supprimer").'</a>
				</div>
			</div>
		</div>';

		return $h;
	}

	/**
	 * Let user to update a message
	 */
	public function getMessageActions(Message $eMessage, bool $isAuthor): array {

		if(\Privilege::can('paper\moderation') === FALSE) {
			return [];
		}

		$actions = '<div class="moderation-message dropdown">';

			$actions .= '<a class="dropdown-toggle color-secondary" data-dropdown="bottom-end">
				'.s("Modération").'
			</a>';

			$actions .= '<div class="dropdown-list">';

			if($eMessage['censored']) {
				$actions .= '<a data-ajax="/paper/moderation/message:doCensor" class="dropdown-item" data-confirm="'.s("Voulez-vous vraiment annuler la censure de ce message ?").'" post-message="'.$eMessage['id'].'" post-censor="0">'.\Asset::icon('eye-fill').' '.s("Ne plus censurer ce message").'</a>';
			} else {
				$actions .= '<a data-ajax="/paper/moderation/message:doCensor" class="dropdown-item" data-confirm="'.s("Voulez-vous vraiment censurer ce message ?").'" post-message="'.$eMessage['id'].'" post-censor="1">'.\Asset::icon('eye-slash-fill').' '.s("Censurer ce message").'</a>';
			}

			switch($eMessage['type']) {

				case Message::ANSWER :
					$actions .= '<a href="/paper/answerChange:update?id='.$eMessage['id'].'" class="dropdown-item">'.\Asset::icon('pencil').' '.s("Éditer ce message").'</a>';
					break;

				case Message::FEEDBACK :
					$actions .= '<a href="/paper/feedbackChange:update?id='.$eMessage['id'].'" class="dropdown-item">'.\Asset::icon('pencil').' '.s("Éditer ce message").'</a>';
					break;

				case Message::OPEN :
					$actions .= '<a href="/paper/open:update?forum='.$eMessage['forum']['id'].'&publication='.$eMessage['discussion']['id'].'" class="dropdown-item">'.\Asset::icon('pencil').' '.s("Éditer ce message").'</a>';
					break;

			}

			//it's not a publication message
			if($eMessage['type'] !== Message::OPEN and isset($eElement["title"]) === FALSE) {

				$actions .= '<a data-ajax="/paper/moderation/message:doHide" class="dropdown-item" data-confirm="'.s("Voulez-vous vraiment supprimer définitivement ce message de la discussion ?").'" post-message="'.$eMessage['id'].'">'.\Asset::icon('trash-fill').' '.s("Supprimer ce message").'</a>';

			}

			$actions .= '</div>';

		$actions .= '</div>';

		$checkbox = '<input type="checkbox" data-field="ids[]" value="'.$eMessage['id'].'" title="'.s("Modérer ce message").'"/>';

		return [
			$actions,
			$checkbox
		];

	}

	public function getMessagesActions(Discussion $eDiscussion): string {

		if(\Privilege::can('paper\moderation') === FALSE) {
			return '';
		}

		$form = new \util\FormUi();

		$h = $form->open('message-moderation-selection');

		$h .= '<div id="message-moderation-ids"></div>';

		$h .= '<div id="moderation-actions">';
			$h .= '<div class="util-block">';
				$h .= '<h3>'.s("Modérer des messages {value}", '(<span id="moderation-actions-number"></span>)').'</h3>';
				$h .= '<div id="moderation-actions-list">';
					$h .= '<a data-ajax-submit="/paper/moderation/messages:doCensor" post-censor="1" class="btn btn-primary">'.\Asset::icon('eye-fill').' '.s("Censurer").'</a> ';
					$h .= '<a data-ajax-submit="/paper/moderation/messages:doCensor" post-censor="0" class="btn btn-primary">'.\Asset::icon('eye-slash-fill').' '.s("Ne plus censurer").'</a> ';
					$h .= '<a data-ajax-submit="/paper/moderation/messages:duplicate" data-ajax-method="get" id="message-moderation-duplicate-link" class="btn btn-primary">'.\Asset::icon('files').' '.s("Dupliquer en une nouvelle discussion").'</a> ';
					$h .= '<a data-ajax-submit="/paper/moderation/messages:doHide" data-confirm="'.s("Voulez-vous vraiment supprimer définitivement ces messages de la discussion ?").'" class="btn btn-danger">'.\Asset::icon('trash-fill').' '.s("Supprimer").'</a>';
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		$h .= $form->close();

		return $h;
	}

	public function getDuplicate(\Collection $cMessage): \Panel {

		$form = new \util\FormUi(['style' => 'horizontal']);

		$h = $form->openAjax('/paper/moderation/messages:doDuplicate');
			$h .= $cMessage->makeString(fn($eMessage) => $form->hidden('ids[]', $eMessage['id']));
			$h .= $form->group(s("Titre"), $form->text('title'));
			$h .= $form->group(
				content: $form->submit("Créer la discussion")
			);
		$h .= $form->close();

		return new \Panel(
			title: s("Créer une nouvelle discussion avec ces messages"),
			body: $h
		);

	}

	public function getDuplicateIntroduction(Discussion $eDiscussion): string {

		return s("Bonjour,
Je viens de créer cette discussion à partir de messages qui viennent de la discussion {publication}.
Continuez à réagir à partir d'ici !", [
			'publication' => '<a href="'.DiscussionUi::url($eDiscussion).'">'.encode($eDiscussion['title']).'</a>'
		]);
	}

}
?>
