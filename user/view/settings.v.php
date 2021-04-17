<?php
new AdaptativeView('index', function($data, PanelTemplate $t) {

	\Asset::css('user', 'user.css');

	$h = '<div class="user-settings-list">';

		if($data->canUpdate['email']) {

			$h .= '<a href="/user/settings:updateEmail" class="btn-secondary user-settings-element">';

				$h .= '<span>'.\Asset::icon('envelope-fill').' '.encode($data->eUser['email']).'</span>';
				$h .= '<h4>'.s("Changer mon adresse e-mail").'</h4>';

			$h .= '</a>';

		}

		if($data->eUser['canUpdate']['cUserAuth']->offsetExists(\user\UserAuth::BASIC)) {
			$text = s("Changer mon mot de passe");
			$span = NULL;
		} else {
			$social = $data->eUser['canUpdate']['cUserAuth']->first()['type'];
			$span = \Asset::icon('circle-fill').' '.s("Créez un mot de passe pour vous connecter à {siteName} sans passer par {social}", ['social' => ucfirst($social)]);
			$text = s("Créer un mot de passe");
		}

		$h .= '<a href="/user/settings:updatePassword" class="btn-secondary user-settings-element">';

			$h .= '<span>'.$span.'</span>';
			$h .= '<h4>'.$text.'</h4>';

		$h .= '</a>';

		if($data->canUpdate['drop']) {

			$h .= '<a href="/user/settings:dropAccount" class="btn-secondary user-settings-element">';

				$h .= '<span></span>';
				$h .= '<h4>';
					if($data->userDeletedAt) {
						$h .= s("Annuler la fermeture de mon compte");
					} else {
						$h .= s("Fermer mon compte");
					}
				$h .= '</h4>';

			$h .= '</a>';

		}

	$h .= '</div>';

	return new Panel(
		id: 'update-settings',
		title: s("Mes paramètres"),
		body: $h
	);

});

new AdaptativeView('updateEmail', function($data, PanelTemplate $t) {

	return new Panel(
		title: s("Changer mon adresse e-mail"),
		body: (new user\UserUi())->updateEmail($data->eUser)
	);

});

new AdaptativeView('updatePassword', function($data, PanelTemplate $t) {

	if($data->eUser['canUpdate']['hasPassword'] === FALSE) {
		$title = s("Créer un mot de passe");
	} else {
		$title = s("Changer mon mot de passe");
	}

	return new Panel(
		title: $title,
		body: (new user\UserUi())->updatePassword($data->eUser)
	);

});

new AdaptativeView('dropAccount', function($data, PanelTemplate $t) {

	return new Panel(
		title: s("Fermer mon compte"),
		body: (new user\DropUi())->close($data->eUser, $data->canCloseDelay)
	);

});
?>
