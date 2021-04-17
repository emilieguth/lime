<?php
new AdaptativeView('email', function($data, MainTemplate $t) {

	$t->title = s("Changer mon adresse e-mail");

	echo (new user\UserUi())->updateEmail($data->eUser);

});

new JsonView('email.api', function($data, JsonTemplate $t) {

	$t->push('email', $data->eUser['email']);
	$t->push('verified', $data->eUser['verified']);

});

new AdaptativeView('emailVerified', function($data, MainTemplate $t) {

	\Asset::css('user', 'user.css');

	echo '<div class="user-light">';
		echo '<h1>'.s("Votre adresse e-mail est validée !").'</h1>';
		echo '<h4>'.encode($data->eUser['email']).'</h4>';
	echo '</div>';

});

new AdaptativeView('password', function($data, MainTemplate $t) {

	if($data->eUser['canUpdate']['hasPassword']) {
		$t->title = s("Changer mon mot de passe");
	} else {
		$t->title = s("Créer mon mot de passe");
	}

	echo (new user\UserUi())->updatePassword($data->eUser);

});
?>
