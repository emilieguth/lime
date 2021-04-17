class AdminPaper {

	static makeForumsSortable() {

		const list = qs('div.forum-admin-table');

		if(list === null) {
			return;
		}

		new Sortable(list, {
			emptyInsertThreshold: 10,
			animation: 300,
			onEnd: function(e, ui) {

				// Get new positions
				const body = new URLSearchParams();

				qsa("div.forum-admin-box", forum => {
					body.append('positions[]', forum.getAttribute('data-id'));
				});

				new Ajax.Query(null)
					.url('/paper/admin/forum:doPosition')
					.body(body)
					.fetch();

			}

		});

	};

}