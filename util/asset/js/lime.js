function evalScope(node, script) {

	(function() {
		eval(script);
	}).call(node);

}

function d(...arguments) {
	console.log(...arguments);
}

if('scrollRestoration' in history) { // what an amazing feature!
	history.scrollRestoration = 'manual';
}

document.addEventListener("keydown", e => {

    if(e.key === 'Backspace' && e.target.matches('input, textarea, [contenteditable="true"]') === false) {
        e.preventDefault();
    }

});

document.addEventListener('DOMContentLoaded', () => {

	/**
	 * Save touch state
	 */
	if(
		("ontouchstart" in window) ||
		navigator.MaxTouchPoints ||
		navigator.msMaxTouchPoints
	) {
		document.body.setAttribute('data-touch', 'yes');
	} else {
		document.body.setAttribute('data-touch', 'no');
	}

	Lime.History.init();

	document.body.parseRender();

});

/**
 * Load browser information
 */
let browser = {

	get: function() {

		return browser.isOpera ? 'Opera' :
			browser.isFirefox ? 'Firefox' :
				browser.isSafari ? 'Safari' :
					browser.isChrome ? 'Chrome' :
						browser.isIE ? 'IE' :
							browser.isEdge ? 'Edge' :
								browser.isBlink ? 'Blink' :
									'';
	}

};

// Opera 8.0+
browser.isOpera = (!!window.opr && !!opr.addons) || !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;

// Firefox 1.0+
browser.isFirefox = typeof InstallTrigger !== 'undefined';

// Safari 3.0+ "[object HTMLElementConstructor]"
browser.isSafari = /constructor/i.test(window.HTMLElement) || (function (p) { return p.toString() === "[object SafariRemoteNotification]"; })(!window['safari'] || (typeof safari !== 'undefined' && window['safari'].pushNotification));

// Internet Explorer 6-11
browser.isIE = /*@cc_on!@*/false || !!document.documentMode;

// Edge 20+
browser.isEdge = !browser.isIE && !!window.StyleMedia;

// Chrome 1 - 79
browser.isChrome = !!window.chrome && (!!window.chrome.webstore || !!window.chrome.runtime);

// Blink engine detection
browser.isBlink = (browser.isChrome || browser.isOpera) && !!window.CS;

/**
 * Convert current rem unit in pixels
 */
function rem() {
	return parseFloat(getComputedStyle(document.documentElement).fontSize);
}

/**
 * Shortcut for document.qs()
 *
 * @return Element
 */
function qs(selector, found, empty) {
	return document.qs(selector, found, empty);
}

/**
 * Shortcut for querySelector()
 *
 * @return Element
 */
Node.prototype.qs = function(selector, found, empty) {

	const node = this.querySelector(selector);

	if(node !== null) {
		if(found) {
			found.call(this, node);
		}
	} else {
		if(empty) {
			empty.call(this);
		}
	}

	return node;

}

/**
 * Shortcut for document.qsa()
 *
 * @return Element
 */
function qsa(selector, each, empty) {
	return document.qsa(selector, each, empty);
}

/**
 * Shortcut for querySelectorAll()
 *
 * @return NodeList
 */
Node.prototype.qsa = function(selector, each, empty) {

	let nodes = this.querySelectorAll(selector);

	if(nodes.length > 0) {
		if(each) {
			nodes.forEach(each);
		}
	} else {
		if(empty) {
			empty.call(this);
		}
	}

	return nodes;

}

/**
 * Shortcut for document.ref()
 *
 * @return Element
 */
function ref(ref, each, empty, cssSelector) {
	return document.ref(ref, each, empty, cssSelector);
}

/**
 * Lookup for a ref
 *
 * @return NodeList
 */
Node.prototype.ref = function(ref, each, empty, cssSelector) {

	if(cssSelector === undefined) {
		cssSelector = '~=';
	}

	if(['~=', '=', '|=', '*='].includes(cssSelector) === false) {
		throw 'Invalid selector';
	}

	return this.qsa('[data-ref'+ cssSelector +'"'+ ref +'"]', each, empty);

}

/**
 * Search for first parent node that matches the given selector
 */
Element.prototype.firstParentMatches = function(selector) {

	let currentNode = this;

	while((currentNode = currentNode.parentNode) && currentNode !== document) {

		if(currentNode.matches(selector)) {
			return currentNode;
		}

	}

	return null;

};

/**
 * Search for siblings node that matches the given selector
 */
Element.prototype.firstPreviousSiblingMatches = function(selector) {

	let currentNode = this;

	while(currentNode = currentNode.previousElementSibling) {

		if(currentNode.matches(selector)) {
			return currentNode;
		}

	}

	return null;

};

Element.prototype.firstNextSiblingMatches = function(selector) {

	let currentNode = this;

	while(currentNode = currentNode.nextElementSibling) {

		if(currentNode.matches(selector)) {
			return currentNode;
		}

	}

	return null;

};

Element.prototype.parseScripts = function() {

	if(this.tagName === 'SCRIPT') {
		evalScope(document.body, this.innerHTML);
		return;
	}

	const scripts = this.getElementsByTagName('script');
	let currentLength = scripts.length;

	for(let i = 0; i < currentLength; i++) {

		evalScope(document.body, scripts[i].innerHTML);

		const newLength = scripts.length;

		if(newLength !== currentLength) {
			i = i + newLength - currentLength;
			currentLength = newLength;
		}

	}

};

Element.prototype.parseRender = function() {

	if(this.hasAttribute('onrender')) {
		evalScope(this, this.getAttribute('onrender'));
	}

	this.qsa('[onrender]', function(node) {
		evalScope(node, node.getAttribute('onrender'));
	});

};

Element.prototype.slideUp = function(options) {

	const duration = options.duration || 0.5;
	const done = options.done || function() {};

	this.style.overflowY = 'hidden';
	this.style.maxHeight = this.offsetHeight +'px';
	this.style.transition = 'all '+ duration +'s';

	setTimeout(() => this.style.maxHeight = '0px', 10);
	setTimeout(() => done.call(this), 10 + duration * 1000);

};

Element.prototype.isVisible = function() {
	return (this.offsetWidth > 0 || this.offsetHeight > 0);
};

Element.prototype.isHidden = function() {
	return !this.isVisible();
};

Element.prototype.renderInner = function(html) {

	this.innerHTML = html;

	this.childNodes.forEach(node => {
		if(node instanceof Element) {
			node.parseScripts();
			node.parseRender();
		}
	});

}

Element.prototype.renderOuter = function(html) {

	const node = new DOMParser().parseFromString(html, 'text/html').body.firstChild;

	if(node === null) {
		this.remove();
	} else {
		this.parentNode.replaceChild(node, this);
		node.parseScripts();
		node.parseRender();
	}

}

Element.prototype.renderAdjacentHTML = function(where, html) {

	if(html === '') {
		return;
	}

	const node = new DOMParser().parseFromString(html, 'text/html').body.firstChild;

	this.insertAdjacentElement(where, node);

	node.parseScripts();
	node.parseRender();

}

/**
 * Get all post-* attributes as an object key=>value
 *
 * @returns Object
 */
Element.prototype.post = function(formData) {

	if(formData === undefined) {
		formData = new URLSearchParams();
	}

	for(let i = 0; i < this.attributes.length; i++) {

		let attribute = this.attributes.item(i);

		if(attribute.name.indexOf('post-') === 0) {

			let key = attribute.name.substring(5).replace(/\-([a-z])/gi, function(match, letter) {
				return letter.toUpperCase();
			});

			formData.set(key, attribute.value);
		}

	};

	return formData;

};

Element.prototype.form = function() {

	let data = new FormData(this);

	this.qsa('.editor', node => {

		// Can't save if loaded
		if(node.querySelectorAll('[data-type="progress"]').length > 0) {
			throw node.getAttribute('data-progress');
		}

		data.set(
			node.getAttribute('data-name'),
			node.innerHTML
		);

		Editor.resetInstance('#'+ node.id);

	});

	return data;

};

NodeList.prototype.filter = function(selector) {

	let newList = [];

	this.forEach(node => {
		if(node.matches(selector)) {
			newList.push(node);
		}
	});

	return newList;

};

/**
 * Create a delegated event listener
 */
Node.prototype.delegateEventListener = function(type, selector, listener, options) {

	options = options || {capture: true};

	const delegateTarget = (e) => {

		const target = e.target;

		if(target !== document && target.matches(selector)) {
			e.delegateTarget = target;
			listener.call(target, e);
		}

	}

	const delegateTargetAndParents = (e) => {

		for(let target = e.target; target && target != this; target = target.parentNode) {

			if(target.matches(selector)) {
				e.delegateTarget = target;
				listener.call(target, e);
				break;
			}

		}

	}

	if(type === 'mouseenter' || type === 'mouseleave' || type === 'mouseover' || type === 'mouseout') {
		return this.addEventListener(type, delegateTarget, options);
	} else {
		return this.addEventListener(type, delegateTargetAndParents, options);
	}

};

/**
 * Event dispatched on a new page
 */
Document.prototype.ready = function(listener) {
	if(document.readyState === 'interactive' || document.readyState === 'complete') {
		listener();
	} else {
		this.addEventListener("DOMContentLoaded", listener, {once: true});
	}
};

document.delegateEventListener('click', '[data-confirm]', function(e) {

	if(confirm(this.getAttribute('data-confirm')) === false) {

		e.preventDefault();
		e.stopImmediatePropagation();

	}


});

document.delegateEventListener('click', '[data-alert]', function(e) {

	alert(this.getAttribute('data-alert'));


});

URLSearchParams.prototype.importObject = function(start) {

	const browse = (object, prefix) => {

		Object.entries(object).forEach(([key, value]) => {
			const newKey = prefix + (prefix === '' ? key : '['+ key +']');
			if(value instanceof Object) {
				browse(value, newKey)
			} else {
				this.append(newKey, value);
			}
		});

	}
	browse(start, '');

}

/*
 * String handling
 */

String.prototype.encode = function() {

	const map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};

	return this.replace(/[&<>"]/g, function(m) {
		return map[m];
	});

}

String.prototype.attr = function(value) {
	return this +'="'+ value.encode() +'"';
}

String.prototype.removeArgument = function(name) {

	let location = this;

	const regex = new RegExp('([\&\?])'+ name +'(=[a-z0-9/\.\%\:\\-\\\\+]*)*', 'i');
	location = location.replace(regex, '$1');
	location = location.replace('?&', '?');
	location = location.replace('&&', '&');

	if(
		location.charAt(location.length - 1) === '?' ||
		location.charAt(location.length - 1) === '&'
	) {
		location = location.substring(0, location.length - 1);
	}

	return location;

};

String.prototype.setArgument = function(name, value) {

	let location = this;

	const regex = new RegExp('([\&\?])'+ name +'=([^\&]*)', 'i');

	if(location.match(regex)) {

		location = location.replace(regex, '$1'+ name +'='+ encodeURIComponent(value));

	} else {

		location = location + (location .indexOf('?') === -1 ? '?' : '&');

		if(typeof value !== 'undefined') {
			location = location + name +'='+ encodeURIComponent(value);
		} else {
			location = location + name;
		}

	}

	return location;

};

/*
 * History handling
 */
history.removeArgument = function(name, replace) {

	const location = document.location.href.removeArgument(name);

	if(replace === false) {
		Lime.History.pushState(location);
	} else {
		Lime.History.replaceState(location);
	}

}

let isMouseDown = false;

document.addEventListener('mousedown', function() {
	isMouseDown = true;
})

document.addEventListener('mouseup', function() {
	isMouseDown = false;
});

let isTouchDown = false;

document.addEventListener('touchstart', function() {
	isTouchDown = true;
})

document.addEventListener('touchend', function() {
	isTouchDown = false;
});

const Lime = {

	counter: 1,
	zIndex: 1100,

	getZIndex: function() {
		Lime.zIndex += 10;
		return Lime.zIndex;
	}

};

Lime.Alert = class {

	static showStaticErrors(errors) {

		let message = Lime.Alert.getInlineErrors(errors);

		if(message !== '') {
			Lime.Alert.showStaticError(message);
		}

	};

	static getInlineErrors(errors) {

		let messages = [];

		errors.forEach(([, message]) => {

			if(message !== null) {
				messages[messages.length] = message;
			}

		});

		switch(messages.length) {

			case 0 :
				return '';

			case 1 :
				return messages[0];

			case 2 :

				let h = '<ul>';

				messages.forEach(message => {
					h += '<li>'+ message +'</li>';
				});

				h += '</ul>';

				return h;

		}

	};

	static showStaticError(message) {

		if(message === '') {
			return false;
		}

		if(document.getElementById('alert-danger') === null) {
			document.body.insertAdjacentHTML('beforeend', '<div id="alert-danger" class="util-box-danger util-box-sticked"></div>');
		}

		document.getElementById('alert-danger').innerHTML = message + '<a onclick="Lime.Alert.hideStaticErrors()" class="util-box-close">'+ Lime.Asset.icon('x-circle') +'</a>';
		document.getElementById('alert-danger').style.display = 'block';

		setTimeout(() => history.removeArgument('error'), 2500);
		setTimeout(() => this.hideStaticErrors(), 5000);

	};

	static hideStaticErrors() {
		this.hideStaticBox('#alert-danger');
	};

	static showStaticSuccess(message) {

		if(message === '') {
			return false;
		}

		if(document.getElementById('alert-success') === null) {
			document.body.insertAdjacentHTML('beforeend', '<div id="alert-success" class="util-box-success util-box-sticked"></div>');
		}

		document.getElementById('alert-success').innerHTML = message + '<a onclick="Lime.Alert.hideStaticSuccess()" class="util-box-close">'+ Lime.Asset.icon('x-circle') +'</a>';
		document.getElementById('alert-success').style.display = 'block';

		setTimeout(() => history.removeArgument('success'), 2500);
		setTimeout(() => this.hideStaticSuccess(), 5000);

	};

	static hideStaticSuccess() {
		this.hideStaticBox('#alert-success');
	};

	static hideStaticBox(id) {

		qs(id, node => {

			node.classList.add('util-box-remove');
			setTimeout(() => node.remove(), 500);

		});

	};

	static hideErrors(selector) {

		// Hide previous errors
		selector.qsa('.util-box-danger', node => node.remove());

		selector.qsa('.form-error', node => node.classList.remove('form-error'));
		selector.qsa('.form-error-wrapper', node => node.classList.remove('form-error-wrapper'));
		selector.qsa('p.form-error-message', node => node.remove());

	};

	static showErrors(context, errors) {

		Lime.Alert.hideErrors(context);

		if(
			typeof errors === 'undefined' ||
			errors.length === 0
		) {
			return;
		}

		let remainingErrors = [];

		errors.forEach(error => {

			let message, errorName, wrapper;

			[errorName, message, wrapper] = error;

			// No custom message
			if(errorName === message) {
				message = null;
			}

			// No wrapper specified
			if(wrapper === null) {

				// If the error is linked to a field, we take the first part of error name
				if(errorName.indexOf('.') !== -1) {
					wrapper = errorName.substring(0, errorName.indexOf('.'));
				}
				// This is a generic error (not linked to a field)
				else {
					wrapper = null;
				}

			}

			if(wrapper === null) {

				remainingErrors.push(error);

			} else {

				context.qsa('[data-wrapper~="'+ wrapper +'"]', node => {

					node.classList.add('form-error-wrapper');

					if(message !== null) {
						node.qs('.form-control-label:first-child', nodeLabel => nodeLabel.insertAdjacentHTML('beforeend', '<p class="form-error-message">'+ message +'</p>'));
					}


				}, () => {
					remainingErrors.push(error);
				});

			}

		});

		if(remainingErrors.length > 0) {
			Lime.Alert.showStaticErrors(remainingErrors);
		}

		context.classList.add('form-error');

	};

}

Lime.Panel = class {

	/*
	 * Create a panel on-the-fly
	 */
	static create(id, options) {

		if(document.getElementById(id) === null) {

			const attributes = options?.attributes || {};
			attributes.class = 'panel' + (attributes.class ? ' '+ attributes.class : '');

			let h = '';

			h += '<div class="panel" id="'+ id +'">';
				h += '<div class="panel-backdrop"></div>';
				h += options.dialogOpen ?? '<div class="panel-dialog container">';
					h += '<div class="panel-header"></div>';
					h += '<div class="panel-body"></div>';
					h += '<div class="panel-footer"></div>';
				h += options.dialogClose ?? '</div>';
			h += '</div>';

			document.querySelector('body').insertAdjacentHTML('beforeend', h);

			const node = document.getElementById(id);

			Object.entries(attributes).forEach(([name, value]) => node.setAttribute(name, value));

		}

		return document.getElementById(id);

	};

	static render(data) {

		if(data.id === undefined) {
			throw 'data.id is missing';
		}

		const panelId = data.id;
		let panel;

		if(document.getElementById(panelId) === null) {

			panel = this.create(panelId, data);

			panel.dataset.new = 'true';

		} else {

			panel = document.getElementById(panelId);
			panel.classList.remove('closing');
			panel.classList.remove('closing-last');
			panel.dataset.new = 'false';

		}

		// Affiche la panel en premier afin que les éléments affichés ultérieurement aient immédiatement une taille
		Lime.Panel.show(panel);
		Lime.Panel.paint(panel, data);

		return panel;

	};

	static paint(panel, data) {

		const title = data.title || '';
		const body = data.body || '';
		const header = data.header || '';
		const footer = data.footer || '';

		Lime.Panel.header(panel, title, header);
		Lime.Panel.body(panel, body);
		Lime.Panel.footer(panel, footer);

	};

	static layer(panel, requestedWith) {

		if(requestedWith === 'html') {
			panel.dataset.context = 'new';
		} else {
			panel.dataset.context = 'layer';

			Lime.History.pushLayer(panel, () => this.internalClose(panel), false /* No new entry in the history */);

		}

	};

	static show(panel) {

		panel.dispatchEvent(new CustomEvent('panelBeforeShow'));

		if(document.body.classList.contains('panel-open') === false) {
			document.body.classList.add('panel-open');
		}

		panel.classList.add('open');

		panel.style.zIndex = Lime.getZIndex();

		panel.dispatchEvent(new CustomEvent('panelAfterShow'));

	};

	static purge() {

		qsa('.panel', panel => panel.remove());
		document.body.classList.remove('panel-open');

	}

	static close(panel) {

		switch(panel.dataset.context) {

			case 'new' :
				Lime.History.go(-1);
				break;

			case 'layer' :
				Lime.History.removeLayer(panel);
				break;

			default :
				this.internalClose(panel);
				break;


		}

	};

	static closeLast() {

		const nodes = qsa('.panel.open:not(.closing)');

		if(nodes.length > 0) {

			const node = nodes[nodes.length - 1];
			this.close(node);

			return true;

		} else {
			return false;
		}

	};

	static closeEscape(e) {

		if(e.key === 'Escape') {
			this.closeLast();
		}

	};

	static internalClose(panel) {

		if(panel.classList.contains('closing')) {
			return false;
		}

		// Reload panel
		if(panel.dataset.close === 'reload') {

			new Ajax.Navigation(panel)
				.skipHistory()
				.waiter(panel.style.zIndex - 1, 333)
				.url(location.href)
				.fetch();

		}

		panel.classList.add('closing');

		// No more panel open
		if(qsa('.panel.open:not(.closing)').length === 0) {
			panel.classList.add('closing-last');
			document.body.classList.remove('panel-open');
		}

		setTimeout(() => {

			if(panel.classList.contains('closing')) { // May have been reopen
				panel.remove();
			}

		}, 500);

		return true;
			
	};

	static header(panel, title, header) {

		let h = '';
		h += '<div class="panel-header-content">';
			if(title) {
				h += '<h2 class="panel-title">';
					h += title;
				h += '</h2>';
			}
			h += header;
		h += '</div>';
		h += this.removeButton();

		panel.qs('.panel-header').renderInner(h);
		return this;

	};

	static removeButton() {

		let h = '<a class="panel-close-desktop" onclick="Lime.Panel.closeLast()">'+ Lime.Asset.icon('x') +'</a>';
		h += '<a class="panel-close-mobile" onclick="Lime.Panel.closeLast()">'+ Lime.Asset.icon('arrow-left-short') +'</a>';

		return h;

	};

	static body(panel, body) {
		panel.qs('.panel-body').renderInner(body);
		return this;
	};

	static footer(panel, footer) {
		panel.qs('.panel-footer').renderInner(footer);
		return this;
	};

}

Lime.Dropdown = class {

	static mutationObserver = {};
	static clickListener = {};

	static toggle(button, position) {

		if(button.hasAttribute('data-dropdown-display')) {
			this.close(button);
		} else {
			this.open(button, position);
		}

	};

	static open(button, position) {

		if(button.id === '') {
			button.id = 'dropdown-'+ ++Lime.counter;
		}

		const list = Lime.Dropdown.getListFromButton(button);

		this.closeAll();

		list.classList.add('dropdown-list-open');

		list.insertAdjacentHTML('beforebegin', '<div data-dropdown-id="'+ button.id +'-placeholder" class="dropdown-placeholder"></div>');
		document.body.insertAdjacentElement('beforeend', list);

		button.dispatchEvent(new CustomEvent('dropdownBeforeShow', {
			detail: {button: button, list: list}
		}));

		if(this.isFullscreen(list)) {
			this.openFullscreen(button, list);
		} else {
			this.openAround(button, position, list);
		}

		button.dispatchEvent(new CustomEvent('dropdownAfterShow', {
			detail: {button: button, list: list}
		}));

		this.startCloseListener(button);

	};

	static isFullscreen(list) {

		if(window.matchMedia('(max-width: 575px)').matches) { // Mobile
			return true;
		} else {

			// Fullscreen if height is too high
			const listBounding = list.getBoundingClientRect();
			const listHeight = listBounding.height
			const windowHeight = window.innerHeight;

			return (listHeight > windowHeight / 2);

		}

	};

	static openFullscreen(button, list) {

		Lime.History.pushLayer(button, () => this.internalClose(button), true);

		document.body.classList.add('dropdown-fullscreen-open');

		button.setAttribute('data-dropdown-display', 'fullscreen');

		const windowWidth = window.innerWidth;
		const windowHeight = window.innerHeight;

		list.style.position = 'absolute';

		const listBounding = list.getBoundingClientRect();

		const positionX = (windowWidth - listBounding.width) / 2;
		const positionY = (windowHeight - listBounding.height) / 2;

		const translateX = positionX - listBounding.left;
		const translateY = positionY - listBounding.top;

		list.style.transform = 'translate('+ translateX +'px, '+ translateY +'px)';
		list.insertAdjacentHTML('afterend', '<div data-dropdown-id="'+ button.id +'-backdrop" class="dropdown-backdrop" style="z-index: '+ Lime.getZIndex() +'"></div>');
		list.style.zIndex = Lime.getZIndex();

	};

	static openAround(button, position, list) {

		button.setAttribute('data-dropdown-display', 'around');

		const isTop = position.startsWith('top');

		button.stick = new Lime.Stick(
			button,
			list,
			position,
			{x: 0, y: isTop ? -5 : 5}
		);

		list.style.zIndex = Lime.getZIndex();
		list.classList.add(isTop ? 'dropdown-list-top' : 'dropdown-list-bottom')

	};

	static startCloseListener(button) {

		// Si on clique quelque part sauf sur le dropdown
		// Eviter que l'événement soit pris dans le clic en cours
		this.clickListener[button.id] = (e) => {

			function keep(target) {
				return target.getAttribute('data-dropdown-keep') === 'true';
			}

			let target;
			for(target = e.target; target instanceof Element && keep(target) === false; target = target.parentNode) ;

			if(
				target instanceof Element === false ||
				keep(target) === false
			) {
				this.close(button);
			} else {
				watch();
			}

		};

		const watch = () => setTimeout(() => document.addEventListener('click', this.clickListener[button.id], {once: true}), 0);

		watch();

		// Si le bouton est supprimé en dur
		this.mutationObserver[button.id] = new MutationObserver(mutations => {

			if(document.body.contains(button)) {
				return;
			}

			if(window.matchMedia('(max-width: 575px)').matches) { // Mobile

				Lime.History.getElementsOfLayers()
					.filter(button => button.hasAttribute('data-dropdown-display'))
					.forEach(button => {

						if(document.body.contains(button) === false) {
							this.close(button);
						}

					});

			} else {

				this.close(button);

			}

		});

		// pass in the target node, as well as the observer options
		this.mutationObserver[button.id].observe(document.body, {
			childList: true,
			subtree: true
		});

	};

	static closeAll() {
		qsa('[data-dropdown-display]', button => this.close(button));
	};

	static close(button) {

		switch(button.getAttribute('data-dropdown-display')) {

			case 'fullscreen' :
				Lime.History.removeLayer(button);
				break;

			case 'around' :
				this.internalClose(button);
				break;

		}

	};

	static internalClose(button) {

		if(this.mutationObserver[button.id] === undefined) { // Already closed
			return false;
		}

		if(document.body.contains(button) === false) {

			qs('[data-dropdown-id="'+ button.id +'-list"]', list => {

				list.remove();
				document.body.classList.remove('dropdown-fullscreen-open');

			})

			qs('[data-dropdown-id="'+ button.id +'-backdrop"]', backdrop => backdrop.remove())

		} else {

			const list = Lime.Dropdown.getListFromButton(button);

			switch(button.getAttribute('data-dropdown-display')) {

				case 'fullscreen' :
					this.internalCloseFullscreen(button, list);
					break;

				case 'around' :
					this.internalCloseAround(button, list);
					break;

			}

			button.removeAttribute('data-dropdown-display');

			list.classList.remove('dropdown-list-open');

		}

		this.mutationObserver[button.id].disconnect();
		delete this.mutationObserver[button.id];

		document.removeEventListener('click', this.clickListener[button.id], {once: true})
		delete this.clickListener[button.id];

		return true;

	};

	static internalCloseFullscreen(button, list) {

		list.style.position = '';
		list.style.transform = '';

		list.classList.add('fullscreen-closing');
		qs('[data-dropdown-id="'+ button.id +'-backdrop"]', node => node.classList.add('fullscreen-closing'));

		setTimeout(() => {

			list.classList.remove('fullscreen-closing');
			list.style.zIndex = '';

			this.closePlaceholder(button, list)

		}, 250);

		document.body.classList.remove('dropdown-fullscreen-open');

	};

	static internalCloseAround(button, list) {

		list.style.zIndex = '';

		this.closePlaceholder(button, list);

		button.stick.destroy();

	};

	static closePlaceholder(button, list) {

		qs('[data-dropdown-id="'+ button.id +'-placeholder"]', placeholder => {

			placeholder.insertAdjacentElement('afterend', list);
			placeholder.remove();

		}, () => {
			list.remove();
		});

		qs('[data-dropdown-id="'+ button.id +'-backdrop"]', node => node.remove());

	}

	static getListFromButton(button) {

		let list = qs('[data-dropdown-id="'+ button.id +'-list"]');

		if(list === null) {

			list = button.nextElementSibling;

			if(list === null || list.classList.contains('dropdown-list') === false) {
				throw "Missing dropdown target for button #"+ button.id;
			} else {
				list.setAttribute('data-dropdown-id', button.id +'-list');
			}

		}

		if(list.button === undefined) {
			list.button = button;
		}

		return list;

	};

	static filter(item) {

		const filter = item.dataset.filter;
		const value = item.dataset.value;
		const select = !item.classList.contains('selected');

		const list = item.firstParentMatches('.dropdown-list');
		const button = list.button;

		list.qsa('[data-filter="'+ filter +'"]', node => {
			node.classList.remove('selected');
		});

		if(select) {
			item.classList.add('selected');
		}

		qs('main', wrapper => {

			if(select) {

				// On cache
				wrapper.qsa('[data-filter-'+ filter +']', node => node.classList.add('filter-not-'+ filter));

				// On affiche
				wrapper.qsa('[data-filter-'+ filter +'~="'+ value +'"]', node => node.classList.remove('filter-not-'+ filter));

			} else {
				wrapper.qsa('[data-filter-'+ filter +']', node => node.classList.remove('filter-not-'+ filter));
			}

		});

		const filters = list.qsa('a.selected').length;

		if(filters > 0) {
			button.innerHTML = Lime.Asset.icon('funnel') +' x '+ filters;
			button.classList.remove('btn-outline-primary');
			button.classList.add('btn-danger');
		} else {
			button.innerHTML = Lime.Asset.icon('funnel');
			button.classList.remove('btn-danger');
			button.classList.add('btn-outline-primary');
		}

	}

}

Lime.Stick = class {

	element;
	free;
	placement;
	offset;

	events;

	constructor(element, free, placement, offset) {

		this.element = element;
		this.free = free;
		this.placement = placement;
		this.offset = offset;

		this.free.style.position = 'absolute';

		this.events = () => {
			this.move();
		};

		window.addEventListener('resize', this.events);
		window.addEventListener('scroll', this.events);

		this.move();

	}
	
	move() {

		const elementBounds = this.element.getBoundingClientRect();
		const freeBounds = this.free.getBoundingClientRect();

		let translateX = 0;
		let translateY = 0;

		if(this.free.style.transform) {

			const transform = this.free.style.transform
				.slice(10, -1)
				.split(', ')
				.map(parseFloat);

			translateX += transform[0];
			translateY += transform[1];

		}

		if(this.placement.endsWith('-start')) {
			translateX += elementBounds.left - freeBounds.left;
		} else if(this.placement.endsWith('-center')) {
			translateX += (elementBounds.left + elementBounds.width / 2 - freeBounds.width / 2) - freeBounds.left;
		} else if(this.placement.endsWith('-end')) {
			translateX += (elementBounds.right - freeBounds.width) - freeBounds.left;
		}

		if(this.placement.startsWith('top-')) {
			translateY += (elementBounds.bottom - freeBounds.height - elementBounds.height) - freeBounds.top;
		} else if(this.placement.startsWith('bottom-')) {
			translateY += elementBounds.bottom - freeBounds.top;
		}

		translateX += this.offset.x ?? 0;
		translateY += this.offset.y ?? 0;

		this.free.style.transform = 'translate('+ translateX +'px, '+ translateY +'px)';

	}

	destroy() {

		this.free.style.transform = '';
		this.free.style.position = '';

		window.removeEventListener('resize', this.events);
		window.removeEventListener('scroll', this.events);

	}

}

Lime.Asset = class {

	static icon(name) {

		return '<svg class="asset-icon asset-icon-'+ name +'" fill="currentColor">'+
			'<use xlink:href="/asset/framework/util/lib/bootstrap-icons-1.4.0/bootstrap-icons.svg#'+ name +'"/>'+
			'</svg>';

	}

};

Lime.History = class {

	static popped = null;
	static ignoreNextPopstate = 0;
	static popstatePromises = [];

	static currentTimestamp = 0;
	static previousTimestamp = null;
	static initialTimestamp = new Date().getTime();

	static scrolls = [window.scrollY];

	static layers = [];

	static purgingLayers = false;
	static purgingResolve = null;


	static init() {

		// Handles the navigation buttons in the browser
		this.popped = (
			'state' in history &&
			history.state !== null
		);

		const poppedInitialURL = location.href;

		window.onpopstate = e => {

			// Handle promise
			if(this.popstatePromises.length > 0) {
				this.popstatePromises.pop()();
			}

			// Ignore inital popstate that some browsers fire on page load
			const initial = (this.popped === false && location.href === poppedInitialURL);

			this.popped = true;

			if(initial) {
				return;
			}

			if(this.ignoreNextPopstate > 0) {
				this.ignoreNextPopstate--;
				return;
			}

			this.previousTimestamp = this.currentTimestamp;
			this.currentTimestamp = e.state ?? this.initialTimestamp;

			const move = (this.currentTimestamp < this.previousTimestamp ? 'back' : 'forward');

			if(move === 'back' && this.popLayerOnPopstate()) {

				if(this.purgingLayers) {
					this.purgeNextLayer();
				}

			} else {

				if(this.previousTimestamp !== null) {
					this.saveScroll(this.previousTimestamp);
				}

				const query = new Ajax.Navigation(document.body);

				query.headers.set('x-requested-history', this.currentTimestamp);

				query
					.url(location.href)
					.skipHistory()
					.fetch();

			}


		};

	};

	static go(number) {

		return new Promise((resolve) => {

			this.popstatePromises.push(resolve);

			history.go(-1);

		});

	}

	static pushState(url) {

		this.previousTimestamp = this.currentTimestamp;
		this.currentTimestamp = new Date().getTime();

		this.saveScroll(this.previousTimestamp);

		history.pushState(this.currentTimestamp, '', url);

	}

	static replaceState(url) {
		history.replaceState(history.state, '', url);
	}

	static saveScroll(state) {
	//	console.log('saveScroll #'+state+':'+ window.scrollY);
		this.scrolls[state] = window.scrollY;
	}

	static restoreScroll(state) {
	//	console.log('restoreScroll #'+ state +':'+this.scrolls[state]);
		window.scrollTo(0, this.scrolls[state]);
	}

	static getElementsOfLayers() {
		return this.layers.map(layer => layer.element);
	}

	static pushLayer(element, onPop, isPushHistory) {

		// Element already included in layers, remove it
		this.layers = this.layers.filter(layer => layer.element !== element);

		this.layers.push({
			element: element,
			onPop: onPop,
			scrollY: window.scrollY
		});

		if(isPushHistory) {
			this.push(location.href);
		}

	};

	static removeLayer(element, isOnPop = true) {

		for(let i = this.layers.length - 1; i >= 0; i--) {

			if(this.layers[i].element === element) {

				this.ignoreNextPopstate++; // Simulate an history change

				return this.go(-1).then(() => {

					const layer = this.layers[i];

					if(isOnPop) {
						layer.onPop.call(this);
					}

					window.scrollTo(0, layer.scrollY);

					this.layers.splice(i, 1);

					return true;

				});

			}

		}

		return false;

	};

	static popLayerOnPopstate() {

		if(this.layers.length === 0) {
			return false;
		} else {

			const layer = this.layers.pop();
			layer.onPop.call(this);
			window.scrollTo(0, layer.scrollY);

			return true;

		}

	};

	static purgeLayers() {

		this.purgingLayers = true;

		return new Promise(resolve => {

			this.purgingResolve = resolve;
			this.purgeNextLayer();

		});

	};

	static purgeNextLayer() {

		if(this.layers.length > 0) {

			this.go(-1);

		} else {

			this.purgingLayers = false;
			this.purgingResolve();

		}

	};

	static push(url) {
		Lime.History.pushState(url);
		this.pop();
	};

	static pop() {
		this.popped = true;
	}

};

Lime.Instruction = class {

	package;

	static methods = {};

	constructor(packageName) {

		this.package = packageName;

		if(Lime.Instruction.methods[this.package] === undefined) {
			Lime.Instruction.methods[this.package] = {};
		}

	}

	register(methodName, callback) {
		Lime.Instruction.methods[this.package][methodName] = callback;
		return this;
	}

	call(context, methodName, data) {

		const callback = Lime.Instruction.methods[this.package][methodName];

		if(callback === undefined) {
			throw "Method '"+ methodName +"' does not exist in package '"+ this.package +"'";
		}

		callback.call(context, ...data);

	}

}

document.delegateEventListener('click', '[data-dropdown]', function(e) {

	e.preventDefault();

	Lime.Dropdown.toggle(this, this.dataset.dropdown);

});

document.addEventListener('keyup', (e) => {
	Lime.Panel.closeEscape(e);
});