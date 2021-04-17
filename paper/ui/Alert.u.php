<?php
namespace paper;

/**
 * Alert messages
 *
 */
class AlertUi {

	public static function getError(string $fqn): ?string {

		return match($fqn) {

			'Forum::name.check' => p("Le nom du forum doit faire entre 1 et {value} caractères", "Le nom du forum doit faire entre 1 et {value} caractères", \Setting::get('nameSizeMax')),
			'Forum::description.check'=> p("La description doit faire moins de {value} caractères", "La description doit faire moins de {value} caractères", \Setting::get('descriptionSizeMax')),
			'Forum::deleteActive' => s("Il n'est pas possible de supprimer un forum encore actif..."),
			'Discussion::title.check' => p("Le titre doit faire entre 1 et {value} caractères",  "Le titre doit faire entre 1 et {value} caractères", \Setting::get('publicationSizeMax')),
			'Discussion::locked' => s("Cette discussion est verrouillée, il n'est plus possible d'y contribuer."),
			'Discussion::outdated' => ''.\Asset::icon('lock-fill').' '.s("Cette discussion est trop ancienne, il n'est plus possible d'y contribuer."),
			'Discussion::noMessages'=> s("La discussion n'a pas été créée car il n'y avait aucun message à déplacer"),
			'Message::value.check' => s("Vous n'avez pas écrit de message"),
			'Message::value.length' => p("Le message doit faire moins de {value} caractères", "Le message doit faire moins de {value} caractères", \Setting::get('paper\messageSizeMax')),
			'Message::value.flood' => s("Veuillez patienter un instant avant de poster votre message."),
			default => NULL

		};

	}

	public static function getSuccess(string $fqn): ?string {

		return match($fqn) {

			'Forum::created' => s("La forum a bien été créé !"),
			'Forum::updated' => s("La forum a bien été mis à jour !"),
			'Forum::deleted' => s("La forum a bien été supprimé !"),
			'Discussion::locked' => s("La discussion a été verrouillée."),
			'Discussion::unlocked' => s("La discussion a été déverrouillée."),
			'Discussion::moved' => s("La discussion a été déplacée dans un autre forum."),
			'Discussion::hidden' => s("La discussion a été supprimée."),
			'Discussion::lockedCollection' => s("Les discussions ont été verrouillées."),
			'Discussion::unlockedCollection' => s("Les discussions ont été déverrouillées."),
			'Discussion::movedCollection' => s("Les discussions ont été déplacées dans un autre forum."),
			'Discussion::hiddenCollection' => s("Les discussions ont été supprimées."),
			'Message.censored' => s("Le message a été censuré."),
			'Message.uncensored' => s("Le message n'est plus censuré."),
			'Message.hidden' => s("Le message été supprimé."),
			'Message.censoredCollection' => s("Les messages ont été censurés."),
			'Message.uncensoredCollection' => s("Les messages ne sont plus censurés."),
			'Message.hiddenCollection' => s("Les messages ont bien été supprimés."),
			'Abuse.reported' => s("Votre abus a bien été reporté, il sera traité par l'équipe de modération."),
			default => NULL

		};

	}

}
?>
