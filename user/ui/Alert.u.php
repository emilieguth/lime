<?php
namespace user;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn, array $arguments = NULL): ?string {

		return match($fqn) {

			'User::connectionBanned' => (new BanUi())->getConnectionBanned($arguments['eBan']),
			'User::signUpBanned' => (new BanUi())->getSignUpBanned($arguments['eBan']),
			'User::tos.accepted' => s("Veuillez accepter les conditions générales d'utilisation."),
			'User::connectionInvalid' => s("Impossible de se connecter, car vous avez sans doute saisi un mauvais identifiant ou mot de passe."),
			'User::firstName.check' => s("Veuillez indiquer votre prénom"),
			'User::lastName.check' => s("Veuillez indiquer votre nom"),
			'User::bio.check' => p("Votre présentation ne doit pas dépasser {value} caractère", "Votre présentation ne doit pas dépasser {value} caractères", \Setting::get('bioSizeMax')),
			'User::country.check' => s("Veuillez indiquer votre pays"),
			'User::birthdate.check' => s("La date de naissance n'est pas correcte"),
			'User::gender.check' => s("Votre sexe n'existe pas"),
			'User::hashConnectedWrongAccount'=> s("Veuillez vous déconnecter de ce compte pour pouvoir confirmer votre adresse e-mail."),
			'User::email.check' => s("L'adresse e-mail n'est pas correcte"),
			'User::email.auth' => s("Vous ne pouvez pas changer d'adresse e-mail car vous n'êtes pas en authentification standard."),
			'User::email.empty' => s("Saisissez votre adresse e-mail ici !"),
			'User::email.duplicate' => s("Vous avez déjà ouvert un compte avec cette adresse e-mail, vous pouvez vous connecter directement en saisissant votre mot de passe (<link>mot de passe oublié ?</link>).", ['link' => '<a href="/user/log:forgottenPassword">']),
			'User::invalidHash' => s("Désolé, ce code de confirmation n'est pas valide."),
			'User::internal' => s("Une erreur interne est survenue."),

			'UserAuth::password.match' => s("Vous avez entré deux mots de passe différents"),
			'UserAuth::password.check' => p("Votre mot de passe doit contenir au minimum {value} caractère", "Votre mot de passe doit contenir au minimum {value} caractères", \Setting::get('passwordSizeMin')),
			'UserAuth::passwordOld.invalid' => s("Votre mot de passe actuel n'est pas correct"),


			default => NULL

		};


	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'User::welcomeCreate'=> s("Bienvenue sur {siteName} !"),
			'User::welcome' => s("Vous êtes maintenant connecté sur {siteName} !"),
			'User::bye' => s("Vous êtes maintenant déconnecté de {siteName}."),
			'User::updated' => s("L'utilisateur a bien bien été mis à jour."),
			'User::emailUpdated' => s("Votre adresse e-mail a bien été modifié."),
			'User::profileUpdated' => s("Votre profil a bien été mis à jour."),
			'User::passwordUpdated' => s("Votre mot de passe a bien été modifié."),
			'User::passwordReset' => s("Votre mot de passe a bien été réinitialisé."),
			'User::forgottenPasswordSend' => s("Un e-mail avec un lien vient de vous être envoyé."),
			'User::invalidLinkForgot'=> s("Le lien pour réinitialiser le mot de passe n'est plus valide."),
			default => NULL

		};


	}

}
?>
