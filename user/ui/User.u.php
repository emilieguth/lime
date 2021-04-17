<?php
namespace user;


class UserUi {
	use \Notifiable;

	public function __construct() {
		\Asset::css('user', 'user.css');
	}

	public static function link(User $eUser, bool $newTab = FALSE): string {

		if($eUser->empty() === FALSE) {
			return '<a href="'.self::url($eUser).'" '.($newTab ? 'target="_blank"' : '').'>'.self::name($eUser).'</a>';
		} else {
			return '-';
		}

	}

	public static function name(User $eUser): string {
		return encode($eUser['firstName']).' '.encode($eUser['lastName']);
	}

	public static function url(User $eUser): string {
		return '/membre/'.$eUser['id'];
	}

	public function query(\PropertyDescriber $d, bool $multiple = FALSE): void {

		$d->prepend = \Asset::icon('person-fill');
		$d->field = 'autocomplete';

		$d->placeholder = s("Tapez un nom ou une adresse e-mail...");
		$d->multiple = $multiple;

		$d->autocompleteUrl = '/user/search:query';
		$d->autocompleteResults = function(User $e) {
			return $this->getAutocomplete($e);
		};

	}

	public function getAutocomplete(User $eUser): array {

		$item = self::getVignette($eUser, '3rem');
		$item .= encode($eUser['firstName'].' '.$eUser['lastName']);
		$item .= '&nbsp;<a onclick="AutocompleteField.removeItem(this)" class="btn btn-muted">'.\Asset::icon('trash-fill').'</a>';

		$label = self::getVignette($eUser, '3rem');
		$label .= '<span>'.encode($eUser['firstName'].' '.$eUser['lastName']).'</span>';

		return [
			'label' => $label,
			'value' => $eUser['id'],
			'itemText' => $eUser['firstName'].' '.$eUser['lastName'],
			'itemHtml' => $item
		];

	}
	
	/**
	 * Get a login form
	 *
	 * @return string
	 */
	public function logInBasic(): string {

		$redirect = REQUEST('redirect');

		$form = new \util\FormUi([
			'style' => 'horizontal'
		]);

		$h = '<div class="login-form">';

		$h .= $form->openAjax('/user/log:in');

			$h .= $form->hidden('redirect', $redirect);

			$h .= $form->group(
				s("Adresse e-mail"),
				$form->email('login')
			);

			$h .= $form->group(
				s("Mot de passe"),
				$form->password('password')
			);


			$h .= $form->group(
				NULL,
				$form->checkbox('remember', 1, ['checked' => TRUE, 'display' => fn($input) => $input.' '.s("Se souvenir de moi")]),
				['class' => 'login-remember']
			);

			$submit = '<div class="login-submit">';
				$submit .= $form->submit(s("Se connecter"));
				$submit .= '<div class="login-forgotten-password">
					<a href="/user/log:forgottenPassword">'.s("Mot de passe oublié ?").'</a>
				</div>';
			$submit .= '</div>';

			$h .= $form->group(content: $submit);

		$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	/**
	 * Groups all the social signup forms
	 *
	 * @return string
	 */
	public function signUp(?string $email = NULL): string {

		$form = new \util\FormUi([
			'style' => 'horizontal',
			'horizontalSize' => 40
		]);

		$h = $form->openAjax('/user/signUp:create', ['autocomplete' => 'off']);

		$h .= implode('', self::notify('signUpForm', $form));

		$h .= $form->hidden('redirect', REQUEST('redirect'));

		$h .= $form->group(
			s("Votre prénom"),
			$form->text('firstName', NULL, ['placeholder' => s("Prénom")])
		);

		$h .= $form->group(
			s("Votre nom"),
			$form->text('lastName', NULL, ['placeholder' => s("Nom")])
		);

		$h .= $form->group(
			s("Votre adresse e-mail"),
			$form->email('email', $email, ['placeholder' => s("Adresse e-mail")])
		);

		$h .= $form->group(
			s("Votre mot de passe"),
			$form->password('password', NULL, ['placeholder' => s("Mot de passe")])
		);

		$h .= $form->group(
			s("Retapez le mot de passe"),
			$form->password('passwordBis')
		);

		$h .= $form->group(
			s("J'accepte les <link>conditions générales d'utilisation</link>", ['link' => '<a href="/legal/tos" target="_blank">']),
			$form->inputCheckbox('tos', 1, ['id' => 'tos'])
		);


		$h .= $form->group(
			content: $form->submit(s("S'inscrire"))
		);

		$h .= $form->close();

		return $h;

	}

	/**
	 * Get a forgotten password form
	 *
	 */
	public function forgottenPassword(): string {

		$form = new \util\FormUi(['style' => 'horizontal']);

		$h = '<div class="util-info">'.s("Si vous avez oublié votre mot de passe, indiquez l'adresse e-mail avec laquelle vous vous êtes inscrit sur {siteName}.
Vous recevrez alors un e-mail contenant un lien vous permettant d'en choisir un nouveau.").'</div>';

		$h .= $form->openAjax('/user/forgotten:do');

		$h .= $form->group(
			s("Votre adresse e-mail"),
			$form->email('email')
		);

		$h .= $form->group(
			content: $form->submit(s("Recevoir les instructions par e-mail"))
		);

		$h .= $form->close();

		return $h;
	}

	/**
	 * Change an email
	 */
	public function updateEmail(User $eUser): string {

		if($eUser['bounce'] === TRUE) {
			$h = '<div class="util-warning">'.s("Le dernier e-mail que nous avons tenté de vous adresser a été rejeté, car votre adresse e-mail semble incorrecte. Veuillez s'il vous plait la mettre à jour avant de continuer sur {siteName}.").'</div>';
		} else {
			$h = '';
		}
		$form = new \util\FormUi();

		$h .= $form->openAjax('/user/update:doEmail');

		$h .= $form->group(
			$form->email('email', $eUser['email'])
		);

		if($eUser['bounce']) {
			$h .= '<br/>';
			$h .= '<p class="color-danger">'.\Asset::icon('exclamation-triangle-fill').'&nbsp;'.s("Cette adresse e-mail ne fonctionne pas, merci de renseigner une nouvelle adresse e-mail.").'</p>';
		}

		$h .= $form->group(
			content: $form->submit(s("Modifier mon e-mail"), ['class' => 'btn btn-primary'])
		);

		$h .= $form->close();

		if($eUser['email'] !== NULL and $eUser['bounce'] === FALSE) {

			$isVerified = MailLib::isVerified($eUser);

			$h .= '<hr/>';

			$h .= '<div style="text-align: center">';

			if($isVerified) {
				$h .= '<p class="color-success">'.\Asset::icon('check').'&nbsp;'.s("Adresse e-mail validée").'</p>';
			} else {
				$h .= $this->sendConfirmationMail($eUser);
			}

			$h .= '</div>';

		}

		return $h;

	}

	/**
	 * Send my confirmation email again
	 */
	protected function sendConfirmationMail(User $eUser): string {

		$h = '<p class="color-warning">';
			$h .= \Asset::icon('exclamation-triangle-fill').'&nbsp;'.s("Vous n'avez pas encore vérifié votre adresse e-mail !");
		$h .= '</p>';

		$h .= '<a data-ajax="/mail/verify:doSend" class="btn btn-warning">'.s("Recevoir le mail de confirmation").'</a>';

		return $h;

	}

	/**
	 * Send my confirmation email again
	 */
	public function getSignUpType(User $eUser): string {

		$eUser->expects([
			'email',
			'auth' => ['type']
		]);

		switch($eUser['auth']['type']) {

			case UserAuth::IMAP :
				return s("Vous venez de vous inscrire sur {siteName} en utilisant un compte IMAP !");

			case UserAuth::BASIC :
				return s("Vous venez de vous inscrire sur {siteName} avec votre adresse e-mail {value}.", ['value' => encode($eUser['email'])]);

		}

	}


	/**
	 * Change a password
	 */
	public function updatePassword(User $eUser, string $hash = NULL, string $email = NULL): string {

		$form = new \util\FormUi([
			'style' => 'horizontal'
		]);

		if($hash === NULL) {
			$url = '/user/update:doPassword';
		} else {
			$url = '/user/forgotten:doReset';
		}

		$h = $form->openAjax($url);

		$passwordText1 = s("Nouveau mot de passe");
		$passwordText2 = s("Encore mon nouveau mot de passe");
		$textButton = s("Modifier mon mot de passe");

		if($hash !== NULL and $email !== NULL) {

			$h .= $form->hidden('hash', $hash);
			$h .= $form->hidden('email', $email);

		} else if($eUser['canUpdate']['hasPassword'] === FALSE) {

			$type = first($eUser['canUpdate']['cUserAuth'])['type'];
			$h .= '<div class="util-info">'.
				s("En créant un mot de passe vous pourrez vous connecter sur {siteName} sans passer par {social}, en utilisant directement directement l'e-mail et le mot de passe que vous aurez fournis.", ['social' => ucfirst($type)]).
			'</div>';
			$passwordText1 = s("Mon mot de passe");
			$passwordText2 = s("Encore mon mot de passe");

			$textButton = s("Créer mon mot de passe");

			if($eUser['email'] === NULL) {

				$h .= $form->group(
					s("Mon adresse e-mail"),
					$form->email('email')
				);

			} else {

				$h .= $form->group(
					s("Mon adresse e-mail"),
					'<u>'.encode($eUser['email']).'</u>'
				);
				$h .= $form->hidden('email', $eUser['email']);

			}

		} else {

			$h .= $form->group(
				s("Mot de passe actuel"),
				$form->password('passwordOld')
			);

		}

		$h .= $form->group(
			$passwordText1,
			$form->password('password')
		);

		$h .= $form->group(
			$passwordText2,
			$form->password('passwordBis')
		);

		$h .= $form->group(
			content: $form->submit($textButton, ['class' => 'btn btn-primary'])
		);

		$h .= $form->close();

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = User::model()->describer($property, [
			'email' => s("Adresse e-mail"),
			'lastName' => s("Nom"),
			'firstName' => s("Prénom"),
			'birthdate' => s("Date de naissance"),
		]);

		switch($property) {

			case 'birthdate' :
				$d->prepend = \Asset::icon('calendar-date');
				break;

		}

		return $d;

	}

	public function logOutExternal(User $eUser, User $eUserAction): string {

		if($eUser['email']) {
			$connected = s("Vous êtes connecté sur le compte de {user}", ['user' => encode($eUser['email'])]);
		} else {
			$connected = s("Vous êtes connecté sur le compte de l'utilisateur #{id}", ['id' => $eUser['id']]);
		}
		return '<div class="util-warning logout-external" style="position: fixed; bottom: 10px; right: 10px; max-width: 300px; z-index: 100000; font-size: 0.875rem">'.s(
			$connected."<br /><link>Revenir sur votre compte</link>",
			[
				'link' => '<a data-ajax="/user/log:doLogoutExternal" post-redirect="'.LIME_REQUEST.'">'
			]
		).'</div>';

	}

	public static function getVignette(User $eUser, string $size = '120px'): string {

		\Asset::css('media', 'media.css');

		$pixels = \media\MediaUi::getPixelSize($size);
		$class = 'media-vignette-view';
		$style = '';

		if($eUser->empty()) {

			$class .= ' media-vignette-default '.$class;
			$style .= 'background-color: #EEE;';
			$content = '@';

		} else {

			$eUser->expects(['id', 'vignette', 'firstName', 'lastName']);

			if($eUser['vignette'] !== NULL) {

				$format = (new \media\PlantVignetteUi())->convertToFormat($pixels);

				$style .= 'background-image: url('.(new \media\UserVignetteUi())->getUrlByElement($eUser, $format).');';
				$class .= ' media-vignette-image';

				$content = '';

			} else {

				list(
					$color,
					$content
				) = self::getDefault($eUser);

				$class .= ' media-vignette-default';
				$style .= 'background-color:'.$color.';';

			}

		}

		return '<div class="'.$class.'" style="width: '.$size.'; min-width: '.$size.'; height: '.$size.'; font-size: '.\media\MediaUi::getFactorSize($size, 0.45).'; '.$style.'">'.$content.'</div>';

	}

	protected static function getDefault(User $eUser): array {

		$colors = [
			'#606ec9',
			'#9dd53a',
			'#f0b7a1',
			'#c4c960',
			'#cc6163',
			'#cb60b3',
			'#b361cc',
			'#60c4c9',
			'#8829fb',
			'#c9a460',
			'#08c08c',
			'#3585de',
			'#b3a6de',
			'#cea17f'
		];

		return [
			$colors[crc32($eUser['id']) % count($colors)],
			mb_strtoupper(mb_substr($eUser['firstName'], 0, 1).mb_substr($eUser['lastName'], 0, 1))
		];
	}

}
?>
