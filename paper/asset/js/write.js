class WriteOpenPaper extends Ajax.Navigation {

	done(json) {
		Draft.unset(this.context);
	};

}


class DoCreateAnswerPaper extends Ajax.Navigation {

	done(json) {
		Draft.unset(this.context);
	};

}

document.delegateEventListener('click', 'a[data-action="cancel-feedback"]', function(e) {

	qs('#create-feedback').slideUp({
		done: function() {
			this.remove();
		}
	});

});