<?php
Feature::register('paper', [

	'admin' => FALSE,

]);

Privilege::register('paper', [

	'moderation' => FALSE,
	'admin' => FALSE,
	'pin' => FALSE,

	'abuseReport' => function() {

		$eUser = \user\ConnectionLib::getOnline();
		return ($eUser->notEmpty() and $eUser['seniority'] >= Setting::get('paper\abuseSeniorityMin'));

	}

]);

Setting::register('paper', [

	// Is moderation visible for the user? (defined on-the-fly)
	'moderation' => FALSE,

	// Max size of a paper/category name
	'nameSizeMax' => 30,

	// Max size of a forum description
	'descriptionSizeMax' => 150,

	// Max size of a publication title
	'publicationSizeMax' => 50,

	// Max duration
	'durationMax' => 1000,

	// Max size of a feedback
	'feedbackSizeMax' => 200,

	// Max size of a message
	'messageSizeMax' => 5000000,

	// Number of publications displayed per page in a forum
	'publicationsPerPage' => 100,

	// Number of messages displayed per page in a publication
	'messagesPerPage' => 50,

	// Required seniority to report an abuse
	'abuseSeniorityMin' => LIME_ENV === 'dev' ? 0 : 4,

	// Number of days after which a publication is considered as dead
	'publicationOutdatedDelay' => 180,

	// Number of seconds during which a user cannot post a second message
	'messageFloodDelay' => 5,

	// Number of days to remove deleted forums from the database
	'deleteTimeout' => 2,

	// Number of personal info asked at first post
	'maxInfoAtFirstPost' => 2,

	// Number of days to display in views stats
	'publicationViewsDays' => 60,

	// Twitter account
	'twitter' => '@ouvretafermeteam',

]);
?>
