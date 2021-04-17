<?php
new AdaptativeView('index', function($data, MainTemplate $t) {

	echo (new \user\UserUi())->signUp();

});
?>
