class MoreNotification extends Ajax.Query {

	body(body) {

		body.set('offset', qsa("#notifications > a.notification").length);

		super.body(body);

	}

}