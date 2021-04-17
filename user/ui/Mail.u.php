<?php

namespace user;

/**
 * Handle user mail templates.
 *
 * @author renaud@ouvretaferme.org
 */
class MailUi {

	/**
	 * Get the subject the text and html body of the email to reset the password mail.
	 *
	 * @param string $hash
	 * @param string $email
	 *
	 * @return [string, string, string]
	 */
	public static function getForgottenPasswordMail(string $hash, string $email): array {

		$urlHash = \Lime::getUrl().'/user/forgotten:set?hash='.$hash.'&email='.urlencode($email);

		$title = s("Réinitialisez votre mot de passe sur {siteName} !");

		// Text version
		$text = s("Vous recevez ce message parce que vous avez utilisé cette adresse e-mail pour vous inscrire sur {siteName} et que vous souhaitez réinitialiser votre mot de passe.

Copiez / collez le lien suivant dans votre navigateur pour réinitialiser votre mot de passe :
{url}

Attention, ce lien n'est valable que pendant 3 heures.", ['url' => $urlHash]);

		// HTML version
		$h = \mail\ComponentUi::getParagraph(s("Suite à votre demande, voici un lien pour réinitialiser votre mot de passe et recommencer à profiter de toutes les fonctionnalités de {siteName}."));
		$h .= '<br>';
		$h .= \mail\ComponentUi::getButton($urlHash, s("Réinitialiser mon mot de passe"));
		$h .= '<br>';
		$h .= \mail\ComponentUi::getParagraph(s("Attention, ce lien n'est valable que 3 heures."));

		$options = [
			'footer' => ['user', 'ignore-user'],
			'header' => $title
		];

		return [
			$title,
			\mail\ComponentUi::getTextEnvelop($text, $options),
			\mail\ComponentUi::getHtmlEnvelop($h, $options)
		];
	}

	public static function getCloseMail(): array {

		$title = s("Fermeture de votre compte {siteName}");

		$textSorry = s("Nous avons bien enregistré votre demande.");
		$textChange = s("Vous avez encore 10 jours pour changer d'avis. Passé ce délai, vos données seront définitivement supprimées. Pensez à faire une sauvegarde si vous souhaitez les conserver.");

		$text = s("Vous recevez ce message parce que vous avez décidé de fermer votre compte sur {siteName}.")."\n\n";
		$text .= $textSorry."\n";
		$text .= $textChange;

		// HTML version
		$h = \mail\ComponentUi::getParagraph($textSorry);
		$h .= \mail\ComponentUi::getParagraph($textChange);
		$h .= '<br>';
		$h .= \mail\ComponentUi::getButton(\Lime::getUrl(), s("Retourner sur {siteName}"));

		$options = [
			'footer' => ['one-shot-self'],
			'header' => s("Votre compte est en cours de fermeture.")
		];

		return [
			$title,
			\mail\ComponentUi::getTextEnvelop($text ,$options),
			\mail\ComponentUi::getHtmlEnvelop($h, $options)
		];
	}

	/**
	 * Get the subject the text and html body of the email confirmation mail.
	 *
	 * @param string $hash
	 * @return array
	 */
	public static function getSignUpMail(User $eUser): array {

		$title = s("Bienvenue sur {siteName} !");

		// HTML version
		$h = (new \user\UserUi())->getSignUpType($eUser);
		$h .= '<br/>'.s("Vous avez maintenant accès à toutes les fonctionnalités !");

		$text = (new \user\UserUi())->getSignUpType($eUser)."\n";
		$text .= s("Vous avez maintenant accès à toutes les fonctionnalités !")."\n\n";

		$text .= \Lime::getUrl();

		$options = [
			'footer' => ['user-first', 'ignore-anonymous'],
			'header' => s("Bienvenue sur le réseau de partage de connaissances pour maraîchers et arboriculteurs en agriculture biologique !")
		];

		return [
			$title,
			\mail\ComponentUi::getTextEnvelop($text, $options),
			\mail\ComponentUi::getHtmlEnvelop($h, $options)
		];

	}

	/**
	 * Mail to verify the email address after
	 * - the user asked to receive the confirmation email
	 * - the user changed his email address
	 *
	 * @param string $email
	 * @param string $hash
	 * @param bool $change
	 * @return array
	 */
	public static function getVerifyMail(User $eUser, string $hash, bool $change): array {

		$urlHash = \Lime::getUrl().'/mail/verify:check?hash='.$hash;

		if($change === TRUE) {

			$title = s("Confirmez votre nouvelle adresse e-mail sur {siteName} !");

			$text = s("Vous recevez ce message parce que vous avez choisi l'adresse {email} sur {siteName}.

Copiez / collez l'url suivante dans votre navigateur pour confirmer votre e-mail :
{url}", ['email' => encode($eUser['email']), 'url' => $urlHash]);

			$message = s("Vous avez choisi l'adresse {email} sur {siteName}. Merci de confirmer cette adresse en cliquant sur le lien ci-dessous.", ['email' => encode($eUser['email'])]);

		} else {

			$title = s("Confirmez votre adresse e-mail sur {siteName} !");

			$text = s("Vous recevez ce message parce que vous avez demandé à confirmer votre adresse e-mail sur {siteName}.

Copiez / collez l'url suivante dans votre navigateur pour le faire :
{url}", ['url' => $urlHash]);

			$message = s("Vous pouvez confirmer votre adresse e-mail utilisée sur {siteName} en cliquant sur le bouton ci-dessous :");
		}


		// HTML version
		$h = \mail\ComponentUi::getParagraph($message);
		$h .= '<br>';
		$h .= \mail\ComponentUi::getButton($urlHash, s("Confirmer mon adresse e-mail maintenant"));

		$options = [
			'footer' => ['user'],
			'header' => s("En attente de confirmation..."),
		];

		return [
			$title,
			\mail\ComponentUi::getTextEnvelop($text, $options),
			\mail\ComponentUi::getHtmlEnvelop($h, $options)
		];

	}

	public static function test() {


		// USER

		// Create account
		//$content = \user\MailUi::getSignUpMail(['email' => 'vincent.guth@gmail.com', 'id' => 1] + ['auth' => ['type' => \user\UserAuth::BASIC]]);

		// Verify an email
		//$content = \user\MailUi::getVerifyMail(['email' => 'vincent.guth@gmail.com', 'id' => 1] + ['auth' => ['type' => \user\UserAuth::BASIC]], 'abc', TRUE);

		// Close account
		//$content = \user\MailUi::getCloseMail();

		// Forgotten password
		$content = \user\MailUi::getForgottenPasswordMail('123', 'vincent.guth@gmail.com');

		echo '<pre>'.$content[1].'</pre>';
		echo $content[2];

	}


}
?>
