<?php
new JsonView('doSave', function($data, AjaxTemplate $t) {

	if($data->online) {

		if($data->eDraft->notEmpty() and $data->eDraft['content'] !== $data->eDraft['initialContent']) {

			$t->qs('.draft-message-delete')->setAttribute('post-hash', $data->eDraft['hash']);

			$t->qs('.draft-message-container')->style([
				'display' => 'flex'
			]);

		} else {

			$t->qs('.draft-message-container')->style([
				'display' => 'none'
			]);

		}

	}
});

?>