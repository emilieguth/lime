<?php
namespace paper;

/**
 * Write content
 *
 */
class WriteUi {

	public function __construct() {

		\Asset::js('paper', 'write.js');

		\Asset::js('paper', 'draft.js');
		\Asset::css('paper', 'draft.css');

	}

	/**
	 * Form for a new publication
	 */
	public function createOpen(
		Forum $eForum,
		Draft $eDraft,
		bool $isFirstPost,
		\user\User $eUser
	): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/paper/open:doCreate', ['data-ajax-class' => 'WriteOpenPaper', 'data-draft' => $eDraft['hash'], 'data-form-lock' => TRUE]);

		$defaultValues = [
			'user' => $eUser,
			'isFirstPost' => $isFirstPost
		];

		$h .= $this->writeOpen($form, $eForum, $eDraft, $defaultValues, new Discussion());

		$h .= $form->close();

		return $h;

	}

	/**
	 * Form to update a publication
	 */
	public function updateOpen(
		Forum $eForum,
		Draft $eDraft,
		\user\User $eUser,
		Discussion $eDiscussion
	): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/paper/open:doUpdate', ['data-ajax-class' => 'WriteOpenPaper', 'data-draft' => $eDraft['hash']]);

		$h .= $form->hidden('publication', $eDiscussion['id']);

		$defaultValues = [
			'user' => $eUser,
			'isFirstPost' => FALSE
		];

		$h .= $this->writeOpen($form, $eForum, $eDraft, $defaultValues, $eDiscussion);

		$h .= $form->close();

		return $h;

	}

	protected function writeOpen(
		\util\FormUi $form,
		Forum $eForum,
		Draft $eDraft,
		array $defaultValues,
		Discussion $eDiscussion
	): string {

		$defaultValues += $eDraft['contentFields'];

		if(empty($eDraft['contentFields'])) {
			$defaultValues['hash'] = NULL;
		} else {
			$defaultValues['hash'] = $eDraft['hash'];
		}

		$h = '';

		if($eForum->notEmpty()) {
			$h .= $form->hidden('forum', $eForum['id']);
		}

		$h .= $form->hidden('draft-timestamp', time());

		$h .= '<div class="draft-content">';
			$h .= $this->formOpen($form, $defaultValues, $eDiscussion);
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display form to create a new publication
	 */
	protected function formOpen(\util\FormUi $form, array $defaultValues, Discussion $eDiscussion): string {

		$h = '<h3>';

			if($eDiscussion->empty()) {
				$h .= s("Créer une discussion");
			} else {
				$h .= s("Modifier une discussion");
			}

		$h .= '</h3>';

		$h .= $form->group(
			s("Titre"),
			$form->text('title', $eDiscussion['title'] ?? $defaultValues['title'] ?? '', ['data-limit' => \Setting::get('paper\publicationSizeMax')])
		);
		$h .= $this->displayDraftInfo(Message::OPEN, ($eDiscussion->notEmpty()), $defaultValues);

		$h .= $form->group(
			s("Message"),
			$this->messageField('value', $eDiscussion['openMessage']['text']['value'] ?? $defaultValues['value'] ?? '', [
				'acceptFigure' => TRUE
			]),
			['wrapper' => 'message']
		);

		// We can pin only discussions
		if(\Privilege::can('pin')) {

			$h .= $form->group(
				s("Accroché ?"),
				$form->inputCheckbox('pinned', TRUE, ['checked' => $eDiscussion['pinned'] ?? FALSE])
			);

		}

		if($defaultValues['isFirstPost']) {
			$h .= $this->completeProfileBlock($form, $defaultValues['user']);
		}

		if($eDiscussion->empty()) {
			$submitText = s("Créer la discussion");
		} else {
			$submitText = s("Modifier la discussion");
		}

		$h .= $form->submit($submitText);

		return $h;

	}

	/**
	 * Form for answering a publication
	 */
	public function createAnswer(
		Discussion $eDiscussion,
		Draft $eDraft,
		bool $isFirstPost,
		\user\User $eUser
	): string {

		$eDiscussion->expects(['id']);

		$content = '';

		if($eDiscussion['isOutdated']) {

			$content = '<div class="util-info">';
				$content .= s("Il n'est plus possible de répondre dans cette discussion car il n'y a pas eu d'activité récente.");
			$content .= '</div>';

		}

		if($eDiscussion['write'] !== Discussion::OPEN) {

			$content .= '<br/>';
			$content .= '<div class="util-info">'.\Asset::icon('lock-fill').' '.AlertUi::getError('Discussion::locked').'</div>';
			$content .= '<br/>';

		} else {

			$form = new \util\FormUi();

			$content .= $form->openAjax('/paper/answerCreate:do', ['id' => 'create-answer-'.$eDiscussion['id'], '', 'data-ajax-class' => 'DoCreateAnswerPaper', 'data-draft' => $eDraft['hash']]);

			$content .= $form->hidden('publication', $eDiscussion['id']);

			$content .= $form->hidden('draft-timestamp', time());

			$defaultValues = [
				'isFirstPost' => $isFirstPost,
				'user' => $eUser
			];

			$content .= $this->writeAnswer($form, $eDraft, $defaultValues, new Message());

			$content .= $form->group(
				content: $form->submit(s("Envoyer"))
			);

			$content .= $form->close();

		}

		return $this->formatCreateAnswer($eDiscussion, $content);

	}

	protected function formatCreateAnswer(Discussion $eDiscussion, $content) {

		return '<div class="front-message-discussion front-message-answer front-create-answer">'.$content.'</div>';

	}

	/**
	 * Form to update an existing message
	 */
	public function updateAnswer(
		Discussion $eDiscussion,
		Message $eMessage,
		Draft $eDraft
	): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/paper/answerChange:doUpdate', ['id' => 'update-answer', 'data-draft' => $eDraft['hash']]);
		$h .= $form->hidden('id', $eMessage['id']);

		$defaultValues = [
			'isFirstPost' => FALSE
		];

		$h .= $this->writeAnswer($form, $eDraft, $defaultValues, $eMessage);

		$h .= $form->group(
			content: $form->submit(s("Modifier"))
		);

		$h .= $form->close();

		return $this->formatCreateAnswer($eDiscussion, $h);

	}

	protected function writeAnswer(
		\util\FormUi $form,
		Draft $eDraft,
		array $defaultValues,
		Message $eMessage
	): string {

		if(empty($eDraft['contentFields'])) {
			if($eMessage->notEmpty()) {
				$defaultValues['value'] = $eMessage['text']['value'];
			} else {
				$defaultValues['value'] = '';
			}
			$defaultValues['hash'] = NULL;
		} else {
			$defaultValues += $eDraft['contentFields'];
			$defaultValues['hash'] = $eDraft['hash'];
		}

		$h = '';

		if($eMessage->empty()) {
			$h .= '<h3>'.s("Poster un message").'</h3>';
		} else {
			$h .= '<h3>'.s("Modifier un message").'</h3>';
		}

		$h .= $this->displayDraftInfo(Message::OPEN, ($eMessage->notEmpty()), $defaultValues);

		$h .= '<div class="draft-content">';
			$h .= $this->formAnswer($form, $defaultValues);
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display form to answer an existing publication
	 */
	protected function formAnswer(\util\FormUi $form, array $defaultValues): string {

		$h = $this->messageField('value', $defaultValues['value'], [
			'acceptFigure' => TRUE
		]);

		if($defaultValues['isFirstPost']) {
			$h .= $this->completeProfileBlock($form, $defaultValues['user']);
		}

		return $h;

	}

	/**
	 * Form for giving a feedback
	 */
	public function createFeedback(Message $eMessageParent): string {

		$eMessageParent->expects(['id']);

		$form = new \util\FormUi();

		$h = $form->openAjax('/paper/feedbackCreate:do', ['id' => 'create-feedback']);

		$h .= '<p class="write-feedback-title">'.s("Répondre à ce message").'</p>';

		$h .= $form->hidden('message', $eMessageParent['id']);

		$h .= $this->formFeedback($form, '');

		$h .= '<div class="write-feedback-submit">';

			$h .= $form->submit(s("Répondre"));
			$h .= '<a data-action="cancel-feedback" class="btn btn-outline-secondary">'.s("Annuler").'</a>';

		$h .= '</div >';

		$h .= $form->close();

		return $h;

	}

	/**
	 * Form to update an existing feedback
	 */
	public function updateFeedback(Message $eMessage): string {

		$form = new \util\FormUi();

		$h = $form->openAjax('/paper/feedbackChange:doUpdate');
		$h .= $form->hidden('message', $eMessage['answerOf']['id']);
		$h .= $form->hidden('id', $eMessage['id']);

		$h .= $this->formFeedback($form, $eMessage['text']['value']);

		$h .= '<div class="write-feedback-submit">';
			$h .= $form->submit(s("Modifier"));
			$h .= '<a data-ajax="/paper/feedbackChange:doUpdate" post-message="'.$eMessage['answerOf']['id'].'" post-id="'.$eMessage['id'].'" post-cancel="1" class="btn btn-outline-secondary">'.s("Annuler").'</a>';
		$h .= '</div >';

		$h .= $form->close();

		return $h;

	}

	/**
	 * Display form to feedback an existing publication
	 */
	protected function formFeedback(\util\FormUi $form, string $default): string {

		$h = $this->messageField('value', $default, [
			'acceptFigure' => FALSE,
			'placeholder' => s("Écrivez votre réponse...")
		]);

		return $h;

	}

	/**
	 * Display a message field
	 */
	protected function messageField(string $name, ?string $defaultValue, array $options = []): string {

		$options += [
			'acceptEmbed' => FALSE,
		];

		if($defaultValue) {
			$defaultValue = (new \editor\EditorFormatterUi())->getFromXml($defaultValue, $options);
		} else {
			$defaultValue = '';
		}

		return (new \editor\EditorUi())->field($name, $options, $defaultValue);

	}

	/**
	 * Displays the message to delete the draft
	 */
	public function displayDraftInfo(string $type, bool $isUpdate, array $content): string {

		return '<div id="draft-message-fixed" class="draft-message-container" style="'.($this->isDraftVisible($content) ? '' : 'display: none').'">'.
			$this->getDraftContent($type, $isUpdate, $content).
		'</div>';

	}

	public function isDraftVisible(array $content): bool {

		$hash = ($content['hash'] ?? NULL);

		if(empty($content) or $hash === NULL) {
			return FALSE;
		} else {
			return TRUE;
		}

	}

	public function getDraftContent(string $type, bool $isUpdate, array $content): string {

		$hash = ($content['hash'] ?? NULL);

		$link = '<a data-ajax="/paper/draft:doDelete" class="draft-message-delete" data-confirm="'.$this->getDraftResetConfirm().'" post-hash="'.$hash.'">';

		$textLink = $this->getDraftReset($isUpdate);

		return '<div id="draft-message-text">'.
			\Asset::icon('check').
			'<span class="hide-sm-up">'.s("Brouillon enregistré").'</span>'.
		'</div>'.
		'<div id="draft-message-save">'.
			\Asset::icon('cloud-upload').' '.s("Enregistrement...").
		'</div>'.
		'<div class="draft-message-info">'.
			$link.$textLink.'</a>'.
		'</div>';

	}

	protected function getDraftReset($isUpdate) {
		return s("Effacer le brouillon");
	}

	protected function getDraftResetConfirm() {
		return s("Toutes les modifications que vous avez réalisées seront perdues. Souhaitez-vous continuer ?");
	}

	/**
	 * Display a block asking for the unprovided user info.
	 */
	protected function completeProfileBlock(\util\FormUi $form, \user\User $eUser): string {

		if($eUser['bio'] !== NULL) {
			return '';
		}

		$h = '';

		$h .= '<div class="util-block">';

			$h .= '<h4>'.s("Bonjour {firstName},</h4>", ['firstName' => encode($eUser['firstName'])]).'</h4>';
			$h .= '<p>'.s("C'est la première fois que vous participez sur le forum. Pour aider les autres utilisateurs à mieux vous connaitre, n'hésitez pas à compléter votre profil !").'</p>';

			$h .= $this->getBioField($eUser, FALSE, $form);

		$h .= '</div>';


		return $h;
	}

	/**
	 * Return the Bio field.
	 */
	protected function getBioField($eUser, $title, \util\FormUi $form): string {

		$textarea = $form->textarea('bio', $eUser['bio'], ['placeholder' => s("Décrivez-vous en quelques mots ..."), 'data-limit' => \Setting::get('user\bioSizeMax')]);

		if($title) {
			return $form->group(s("Ma description :"), $textarea);
		} else {
			return $form->group(content: $textarea);
		}

	}

}
?>
