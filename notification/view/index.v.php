<?php
new AdaptativeView('list', function($data, MainTemplate $t) {

	if($data->hasUnclicked > 0) {
		$more = '<a data-ajax="/notification/:readAll" id="notification-read-all">'.s("Tout marquer comme lu").'</a>';
	} else {
		$more = '';
	}

	echo '<div class="notifications-title"><h1>'.s("Mes notifications").'</h1>'.$more.'</div>';

	echo '<div id="notifications">';

		echo (new \notification\NotificationUi())->display($data->cEvent);

		if($data->nEvent > $data->cEvent->count()) {
			echo (new \notification\NotificationUi())->pagination($data->unread);
		}

	echo '</div>';

});

new JsonView('get', function($data, AjaxTemplate $t) {

	$t->qs('#notifications-pagination')->remove();

	$notifications = (new \notification\NotificationUi())->display($data->cEvent, $data->offset, $data->unread);

	if($data->nEvent > $data->cEvent->count() + $data->offset) {
		$notifications .= (new \notification\NotificationUi())->pagination($data->unread);
	}

	$t->qs('#notifications')->insertAdjacentHtml('beforeend', $notifications);

});

new JsonView('readAll', function($data, AjaxTemplate $t) {

	$t->qs('a.notification-unclicked')->removeClass('notification-unclicked');
	$t->qs('#notification-read-all')->remove();

});
?>
