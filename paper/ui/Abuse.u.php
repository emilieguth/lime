<?php
namespace paper;

/**
 * Display abuses
 *
 */
class AbuseUi {

	public function __construct() {

		\Asset::js('paper', 'moderation.js');
		\Asset::css('paper', 'moderation.css');

	}

	/**
	 * Display the list of opened abuses
	 *
	 */
	public function getOpen(\Collection $cMessage): string {

		$h = '<div id="abuses">';

		if($cMessage->count() === 0) {
			return '<div class="util-info">'.s("Il n'y a pas d'abus à traiter pour le moment.").'</div>';
		}

		foreach($cMessage as $eMessage) {

			$h .= '<div id="message-abuse-'.$eMessage['id'].'">';
				$h .= $this->get($eMessage);
			$h .= '</div>';

		}

		$h .= '</div>';

		return $h;

	}

	/**
	 * Display a single abuse
	 *
	 */
	public function get(Message $eMessage): string {

		$cAbuse = $eMessage['cAbuse'];

		$eDiscussion = $eMessage['discussion'];

		if($eMessage->empty()) {
			$title = s("Abus sur une discussion");
			$is = 'publication';
		} else {
			$title = s("Abus sur un message");
			$is = 'message';
		}

		$fors = array_count_values($cAbuse->getColumn('for'));

		foreach($fors as $for => $number) {

			$title .= ' <small><b>'.$this->p('for')->values[$for].' ('.$number.')</b></small>';

		}

		$h = '<div class="util-card">';
			$h .= '<div class="util-card-body">';

				$h .= '<h4>'.$title.'</h4>';

				$h .= '<div class="abuse-item-display">';

					$h .= '<div>';
						$h .= $this->getSimple($eDiscussion, ($is === 'publication') ? new Message() : $eMessage);
						$h .= '<a href="'.MessageUi::url($eDiscussion, $eMessage).'" class="btn btn-primary my-1" target="_blank">'.s("Voir en contexte").'</a>';
					$h .= '</div>';
					$h .= '<div>';

						foreach($cAbuse as $eAbuse) {

							if($eAbuse['why'] === '') {
								continue;
							}

							$h .= '<div class="util-card">';
								$h .= '<div class="util-card-header">';
									$h .= '<b>'.\util\DateUi::ago($eAbuse['createdAt']).'</b>';
								$h .= '</div>';
								$h .= '<div class="util-card-body">';

									$h .= '<div>'.$this->p('for')->values[$eAbuse['for']].'</div>';

									if($eAbuse['why']) {
										$h .= '<div>'.encode($eAbuse['why']).'</div>';
									} else {
										$h .= '<div class="color-warning">'.s("Pas de commentaire").'</div>';
									}

								$h .= '</div>';
							$h .= '</div>';

						}

					$h .= '</div>';
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div class="util-card-footer abuse-item-actions">';

				$h .= '<div>';
					$h .= '<div class="input-group">';

					if($is === 'publication') {

						if($eDiscussion['write'] === Discussion::OPEN) {
							$h .= '<a data-ajax="/paper/moderation/abuse:doLock" post-message="'.$eMessage['id'].'" class="btn btn-secondary" data-confirm="'.s("Vous avez choisi de fermer cette discussion. Continuer ?").'">'.s("Fermer la discussion").'</a> ';
						}

					} else {

						if($eMessage['type'] !== Message::OPEN) {
							$h .= '<a data-ajax="/paper/moderation/abuse:doHide" post-message="'.$eMessage['id'].'" class="btn btn-secondary" data-confirm="'.s("Vous avez choisi de supprimer ce message. Continuer ?").'">'.s("Supprimer le message").'</a>';
						}
					}

					if($eMessage['censored'] === FALSE) {
						$h .= '<a data-ajax="/paper/moderation/abuse:doCensor" post-message="'.$eMessage['id'].'" class="btn btn-secondary" data-confirm="'.s("Vous avez choisi de censurer ce message. Continuer ?").'">'.s("Censurer le message").'</a> ';
					}

					$h .= '</div>';
				$h .= '</div>';
				$h .= '<div>';
					$h .= '<strong>'.s("Marquer comme").' </strong>';
					$h .= '<div class="input-group">';
						$h .= '<a data-ajax="/paper/moderation/abuse:doYes" post-message="'.$eMessage['id'].'" class="btn btn-danger">'.s("Abusif").'</a>';
						$h .= '<a data-ajax="/paper/moderation/abuse:doNo" post-message="'.$eMessage['id'].'" class="btn btn-success">'.s("Non abusif").'</a>';
					$h .= '</div>';
				$h .= '</div>';

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}


	/**
	 * Display a publication/message without any moderation/update options
	 *
	 */
	protected function getSimple(Discussion $eDiscussion, Message $eMessage = NULL): string {

		if($eMessage->notEmpty()) {
			$eElement = $eMessage;
		} else {
			$eElement = $eDiscussion;
		}

		$h = '<div class="front-message-meta">';
			$h .= s("Par {user} le {date}", ['user' => \user\UserUi::link($eElement['author']), 'date' => \util\DateUi::numeric($eElement['createdAt'])]);
		$h .= '</div>';

		$h .= '<div class="front-message-text border-highlight">';
			$h .= (new MessageUi())->getText($eMessage, $eMessage['text']);
		$h .= '</div>';

		return $h;

	}

	/**
	 * Report an abuse
	 *
	 */
	public function reportAbuse(Message $eMessage): \Panel {

		$h = '<p class="util-info">'.s("Vous reporterez cet abus anonymement directement auprès des équipes de modération.").'</p>';

		$form = new \util\FormUi();

		$h .= $form->openAjax('/paper/message:doAbuse');
		$h .= $form->hidden('message', $eMessage['id'], ['id' => 'report-abuse-message-id']);

		$h .= $form->group(
			$this->p('for'),
			'<br/>'.
			$form->radio('for', Abuse::OFFPUBLICATION, $this->p('for')->values[Abuse::OFFPUBLICATION]).
			$form->help(s("Ce message n'est pas pertinent par rapport au contexte de la discussion.")).
			'<hr/>'.
			$form->radio('for', Abuse::INAPPROPRIATE, $this->p('for')->values[Abuse::INAPPROPRIATE]).
			$form->help(s("Ce message contrevient à la charte de la communauté, aux lois en vigueur ou peut-être jugé offensant.")).
			'<hr/>'.
			$form->radio('for', Abuse::SPAM, $this->p('for')->values[Abuse::SPAM]).
			$form->help(s("Ce message est une publicité qui n'est pas utile dans le contexte de la discussion.")).
			'<hr/>'.
			$form->radio('for', Abuse::OTHER, $this->p('for')->values[Abuse::OTHER]).
			'<br/>'
		);

		$h .= $form->group(
			s("Ajoutez un commentaire pour nous aider à comprendre le contexte..."),
			$form->textarea('why', '')
		);

		$h .= $form->group(
			content: $form->submit(s("Reporter l'abus"))
		);

		$h .= $form->close();

		return new \Panel(
			title: s("Reporter un abus"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Abuse::model()->describer($property, [
			'for' => s("Que s'est-il passé ?"),
		]);

		switch($property) {

			case 'for' :
				$d->values = [
					Abuse::OFFPUBLICATION => s("Hors-sujet"),
					Abuse::INAPPROPRIATE => s("Inapproprié"),
					Abuse::SPAM => s("Spam"),
					Abuse::OTHER => s("Autre"),
				];
				break;

		}

		return $d;

	}

}
?>
