<?php
new AdaptativeView('form', function($data, PanelTemplate $t) {

	return new Panel(
		title: s("Connectez-vous !"),
		body: (new \user\UserUi())->logInBasic()
	);

});

new AdaptativeView('forgottenPassword', function($data, MainTemplate $t) {

	$t->title = s("RĂ©initialiser mon mot de passe");
	$t->metaNoindex = TRUE;

	echo (new \user\UserUi())->forgottenPassword();


});
?>
