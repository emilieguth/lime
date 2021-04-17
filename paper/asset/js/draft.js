// Handle Ctrl+S shortcut
document.delegateEventListener('keydown', 'form[data-draft]', function(e) {

	// Ctrl ou command must be pressed
	if(e.ctrlKey === false && e.metaKey === false) {
		return true;
	}

	if(e.key === 's') {

		e.preventDefault();

		Draft.save(this);

		return false;

	}

});

// Initializes the drafts
// Maybe it's useless because drafts are enabled in FormUi directly (?)
document.addEventListener('navigation.wakeup', () => {
	Draft.resetDrafts();
});

document.ready(() => {

	// Save drafts every 10s
	setInterval(() => qsa('form[data-draft]', form => Draft.save(form)), 10000);

});


class Draft {

	static drafts = {};
	static initialDrafts = {};

	static resetDrafts() {

		this.initialDrafts = {};

		qsa('form[data-draft]', form => this.initDrafts(form));

	};


	static getDraft(form, withMedias) {

		let values = [];

		form.qsa('.draft-content textarea, .draft-content select, .draft-content input', input => {
			values.push({
				name: input.getAttribute('name'),
				value: input.value,
				type: 'text'
			});
		});

		form.qsa('.draft-content div.editor', editor => {

			// Don't save if draggable is enabled
			if(editor.qsa('.editor-media-moving').length > 0) {
				return;
			}

			// Don't save if upload in progress, because it can lead to performance issues...
			if(editor.qsa('[data-type="progress"]').length > 0) {
				return;
			}

			let data = new DOMParser().parseFromString(editor.innerHTML, 'text/html').body;

			if(withMedias === false) {
				data.qsa('div.editor-media > *', node => node.remove());
			}

			values.push({
				name: editor.getAttribute('data-name'),
				value: data.innerHTML,
				type: 'editor'
			});

		});

		return JSON.stringify(values);

	};

	static initDrafts(form) {

		const id = form.id;

		const draftContent = this.getDraft(form, false);

		if(draftContent === null) {
			return;
		}

		this.drafts[id] = draftContent;
		this.initialDrafts[id] = this.getDraft(form, true);

	};

	static save(form) {

		const id = form.id;
		const draftContent = this.getDraft(form, false);

		if(draftContent === null) {
			return;
		}

		if(this.drafts[id] === draftContent) {
			return;
		}

		// Display a waiting message during 1s
		const draftText = qs('#draft-message-text');
		const draftSave = qs('#draft-message-save');

		draftText.style.display = 'none';
		draftSave.style.display = 'block';

		setTimeout(() => {

			draftText.style.display = 'block';
			draftSave.style.display = 'none';

		}, 1000);

		// Saver draft
		new Ajax.Query(form)
			.url('/paper/draft:doSave')
			.body({
				hash: form.getAttribute('data-draft'),
				content: this.getDraft(form, true),
				initialContent: this.initialDrafts[id],
				timestamp: form.qs('input[name="draft-timestamp"]').value,
			})
			.fetch();

		this.drafts[id] = draftContent;

	};

	static unset(form) {

		delete this.drafts[form.id];

	};

}