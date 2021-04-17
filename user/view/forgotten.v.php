<?php
new AdaptativeView('set', function($data, MainTemplate $t) {

	$t->title = s("Réinitialiser mon mot de passe");

	echo '<h1>'.$t->title.'</h1>';

	echo (new user\UserUi())->updatePassword(new \user\User(), $data->hash, $data->email);

});
?>
