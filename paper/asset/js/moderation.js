document.delegateEventListener('input', '#publication-moderation-select-all', function(e) {

	qsa('#publication-moderation-selection [data-field="ids[]"]', field => field.checked = this.checked);
	ModerationPaper.togglePublicationsActions();

});

document.delegateEventListener('input', '#publication-moderation-selection input[data-field="ids[]"]', function() {
	ModerationPaper.togglePublicationsActions();
});

document.delegateEventListener('input', '#message-moderation-select input[data-field="ids[]"]', function() {
	ModerationPaper.toggleMessagesActions();
});

class ModerationPaper {

	static togglePublicationsActions() {

		const selected = qsa('#publication-moderation-selection input[data-field="ids[]"]:checked').length;

		qs('#moderation-actions-number').innerHTML = selected;

		if(selected > 0) {
			qs('#moderation-actions').style.display = 'block';
		} else {
			qs('#moderation-actions').style.display = '';
		}

	}

	static toggleMessagesActions() {

		// Update selected ids
		const list = qs('#message-moderation-ids');

		list.innerHTML = '';

		qsa('#message-moderation-select input[data-field="ids[]"]:checked', field => {
			list.insertAdjacentHTML('beforeend', '<input type="hidden" name="ids[]" value="'+ field.value +'"/>');
		});

		const selected = qsa('#message-moderation-ids input').length;

		qs('#moderation-actions-number').innerHTML = selected;

		if(selected > 0) {
			qs('#moderation-actions').style.display = 'block';
		} else {
			qs('#moderation-actions').style.display = '';
		}

		// Enable or disable duplicate link
		const duplicate = qs("#message-moderation-duplicate-link");

		if(
			// Copied messages
			qsa('#message-moderation-select .front-message-discussion.copied input[data-field="ids[]"]:checked').length > 0 ||
			// Censored messages
			qsa('#message-moderation-select .front-message-discussion.censored input[data-field="ids[]"]:checked').length > 0
		) {
			duplicate.classList.add('disabled');
		} else {
			duplicate.classList.remove('disabled');
		}

	}

}