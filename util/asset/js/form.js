/**
 * Form submission
 */
document.delegateEventListener('submit', 'form[data-ajax]', function(e) {

	e.preventDefault();
	e.stopImmediatePropagation();

	const method = (this.getAttribute('method') ?? 'POST').toUpperCase();

	let object = this.hasAttribute('data-ajax-class') ?
		eval(this.getAttribute('data-ajax-class')) :
		Ajax.Navigation;

	const body = this.form();
	let url = this.getAttribute('data-ajax');

	switch(method) {

		case 'GET' :

			url += url.includes('?') ? '&' : '?';
			url += new URLSearchParams(body).toString();

			new object(this)
				.method(method)
				.url(url)
				.fetch();

			break;

		case 'POST' :

			new object(this)
				.method(method)
				.url(url)
				.skipHistory()
				.body(body)
				.fetch();

			break;

		default :
			throw 'Method should be GET or POST';

	}

});

document.delegateEventListener('click', '[data-ajax-submit]', function(e) {

	e.preventDefault();
	e.stopImmediatePropagation();

	const url = this.getAttribute('data-ajax-submit');
	const form = this.hasAttribute('data-ajax-form') ? qs(this.getAttribute('data-ajax-form')) : this.firstParentMatches('form');

	if(this.hasAttribute('data-ajax-method')) {
		form.setAttribute('method', this.getAttribute('data-ajax-method'));
	}

	if(form === null) {
		throw "No form found for data-ajax-submit";
	}

	form.qsa('[data-form-zombie]', node => node.remove());

	this.post().forEach((value, key) => {
		form.insertAdjacentHTML('afterbegin', '<input type="hidden" name="' + key.encode() + '" value="' + value.encode() + '" data-form-zombie="1"/>');
	});

	form.setAttribute('data-ajax', url);

	form.dispatchEvent(new CustomEvent("submit"));

});

/**
 * Alternative for multi-select field
 */

document.delegateEventListener('click', 'a[data-action="form-selects-delete"]', function() {

	const item = this.parentElement;
	const root = item.parentElement;

	if(root.childNodes.filter('.form-selects-item').length > 1) {
		item.remove();
	} else {
		item.childNodes.filter('select').forEach(node => {
			node.value = ''
		});
	}

});


document.delegateEventListener('click', 'a[data-action="form-selects-add"]', function() {

	const root = this.parentElement.parentElement;

	let newItem = root.childNodes.filter('.form-selects-item')[0].cloneNode(true);
	newItem.qsa('option', node => node.removeAttribute('selected'));

	root.insertAdjacentElement('beforeend', newItem);

});

document.delegateEventListener('input', 'input[data-type="fqn"]', function(e) {

	if(this.value.match(/^[a-z0-9\-]*$/) === null) {
		this.parentNode.classList.add('form-error-field');
	} else {
		this.parentNode.classList.remove('form-error-field');
	}

});

document.delegateEventListener('input', 'div.form-range input[type="range"][data-label]', function(e) {

	const wrapper = this.firstParentMatches('div.form-range');
	const label = wrapper.qs('div.form-range-label');

	label.innerHTML = this.getAttribute('data-label').replace('VALUE', this.value);

});

/**
 * Manipulates dates
 */

document.delegateEventListener('input', '[data-week-number]', function(e) {
	DateField.updateWeeks(this);
});

document.delegateEventListener('input', '[data-week-selector]', function(e) {
	DateField.updateWeeks(qs(this.dataset.weekSelector));
});

class DateField {

	static weeks = {};

	/* Handle fallback <input type="week"/> */
	static startFieldWeek(selector) {

		const wrapper = qs(selector);

		if(wrapper.length === 0) {
			return;
		}

		wrapper.qsa('div.field-week-fields input',function(node) {

			node.addEventListener('input', e => {

				const weekNumber = wrapper.qs('[data-field-fallback="weekNumber"]').value.padStart(2, '0');
				const year = wrapper.qs('[data-field-fallback="year"]').value;

				wrapper.qs('input.field-week-value').value = year +'-W'+ weekNumber;

			});

		});

	};

	static changeFieldWeek(selector, target) {

		const wrapper = qs(selector);
		const week = target.dataset.week;

		wrapper.qs('[data-field-fallback="year"]').value = week.substring(0, 4);
		wrapper.qs('[data-field-fallback="weekNumber"]').value = parseInt(week.substring(6, 8));

		wrapper.qs('input.field-week-value').value = week;

	};

	static blurFieldWeek(target) {

		if(document.body.dataset.touch === 'yes') {
			target.blur();
		}

	};

	/* Handle fallback <input type="month"/> */
	static startFieldMonth(selector) {

		const wrapper = qs(selector);

		if(wrapper.length === 0) {
			return;
		}

		const test = document.createElement('input');
		test.type = 'month'; // Vérification de la disponibilité du type sur le navigateur du client

		if(test.type === 'month') {
			wrapper.qs('div.field-month-native').style.display = 'block';
		} else {
			wrapper.qs('div.field-month-fallback').style.display = 'grid';
		}

		wrapper.qsa('div.field-month-fallback input, select', node => {

			node.addEventListener('input', e => {

				let monthNumber = wrapper.querySelector('[data-field-fallback="monthNumber"]').value.padStart(2, '0');
				let year = wrapper.querySelector('[data-field-fallback="year"]').value;

				wrapper.querySelector('div.field-month-native input').value = year +'-'+ monthNumber;

			});

		});

	};

	static updateWeeks(selector) {

		const label = qs(selector.dataset.weekNumber);
		const weekNumber = parseInt(selector.value);

		if(weekNumber) {

			let year;

			if(selector.dataset.yearSelector) {
				qs(selector.dataset.yearSelector, node => year = node.value, () => year = new Date().getFullYear());
			} else {
				year = new Date().getFullYear();
			}

			const startDate = this.getDateOfISOWeek(1, weekNumber, year);
			const endDate = this.getDateOfISOWeek(7, weekNumber, year);

			label.innerHTML = this.formatDateElement(startDate.getDate()) +'/'+ this.formatDateElement(1 + startDate.getMonth()) +' → '+ this.formatDateElement(endDate.getDate()) +'/'+ this.formatDateElement(1 + endDate.getMonth());

		}

	};

	static formatDateElement(value) {
		return (value < 10 ? '0' : '') + value;
	}

	static getDateOfISOWeek(day, week, year) {
		const simple = new Date(year, 0, 1 + (week - 1) * 7);
		const dow = simple.getDay();
		if(dow <= 4) {
			simple.setDate(simple.getDate() - simple.getDay() + day);
		} else {
			simple.setDate(simple.getDate() + 7 + day - simple.getDay());
		}
		return simple;
	}

};

/**
 * Manipulates field with a limit
 */
const selector = 'div.form-character-count-wrapper input[type=text], div.form-character-count-wrapper textarea';

document.delegateEventListener('focus', selector, function(e) {
	CounterField.refresh(this);
	CounterField.show(this);
});

document.delegateEventListener('blur', selector, function(e) {
	CounterField.hide(this);
});

document.delegateEventListener('input', selector, function(e) {
	CounterField.refresh(this);
});

class CounterField {

	static show(input, visible) {

		const counter = qs(".form-character-count[data-limit-for="+ input.getAttribute('data-field') +"]");

		counter.style.display = 'flex';

		if(input.tagName == "TEXTAREA") {

			const originalHeight = input.offsetHeight;
			const newHeight = originalHeight - counter.offsetHeight;

			input.style.height = newHeight +'px';
			input.setAttribute('data-field-height', originalHeight);

		}
	};

	static hide(input, visible) {

		const counter = qs(".form-character-count[data-limit-for="+ input.getAttribute('data-field') +"]");

		counter.style.display = '';

		if(input.tagName == "TEXTAREA") {

			const originalHeight = input.getAttribute('data-field-height');

			input.style.height = originalHeight +'px';
			input.removeAttribute('data-field-height');

		}
	};

	static refresh(input) {

		const counter = qs(".form-character-count[data-limit-for="+ input.getAttribute('data-field') +"]");
		const wrapper = input.firstParentMatches('.form-character-count-wrapper');

		const limit = parseInt(input.getAttribute("data-limit"));

		const count = input.value.length;
		const countLeft = limit - count;

		counter.classList.remove("error");
		counter.classList.add("focus");
		wrapper.classList.remove("form-error-field");

		if(countLeft < 0) {
			counter.classList.add("error");
			counter.classList.remove("focus");
			wrapper.classList.add("form-error-field");
		}

		if(input.tagName === 'TEXTAREA') {
			counter.innerHTML = count +'/'+ limit;
		} else {
			counter.innerHTML = countLeft;
		}
	}


};

/**
 * Backend form features
 */

document.delegateEventListener('submit', '#form-backend form', function (e) {

	e.preventDefault();

	BackendForm.search();

});

document.delegateEventListener('change', '#form-backend select', function (e) {

	e.preventDefault();

	BackendForm.search();

});

document.delegateEventListener('click', '#form-backend input[type="checkbox"]', function (e) {

	e.preventDefault();

	BackendForm.search();

});

class BackendForm {

	static search() {

		let request = document.location.href;

		qsa('#form-backend select', node => {

			const value = node.value;
			const field = node.getAttribute('data-field');

			if(value.length === 0) {
				request = request.removeArgument(field);
			} else {
				request = request.setArgument(field, value);
			}

		});

		qsa('#form-backend input', node => {

			const type = node.getAttribute('type');
			const field = node.getAttribute('data-field');

			let value;

			if(type === 'checkbox') {
				value = node.checked ? '1' : '0';
			} else {
				value = node.value;
			}

			if(value.length === 0) {
				request = request.removeArgument(field);
			} else {
				request = request.setArgument(field, value);
			}

		});

		window.location = request;

	};

};

/**
 *  Autocomplete features
 */
class AutocompleteField {

	static queryTimeout = null;
	static ignoreNextFocus = false;
	static clickListener = {};

	static getDropdown(input) {

		const dropdownId = input.id +'-autocomplete';

		if(this.hasDropdown(input) === false) {
			input.insertAdjacentHTML('afterend', '<div id="'+ dropdownId +'" class="autocomplete-dropdown"></div>');
		}

		return qs('#'+ dropdownId);

	}

	static hasDropdown(input) {
		return (qs('#'+ input.id +'-autocomplete') !== null);
	}

	static start(input) {

		input.addEventListener('focusin', () => {

			if(AutocompleteField.ignoreNextFocus) {
				AutocompleteField.ignoreNextFocus = false;
				return;
			}

			if(input.id === null) {
				throw 'Missing ID for input';
			}

			this.init(input); // Init autocomplete field once
			this.query(input);

			input.setSelectionRange(0, input.value.length);

			if(this.isFullscreen() === false) {

				this.clickListener[input.id] = (e) => {

					let target;
					for(target = e.target; target && target !== input; target = target.parentNode);

					if(target !== input) {
						this.internalRemove(input);
					} else {
						watch();
					}

				};

				const watch = () => setTimeout(() => document.addEventListener('click', this.clickListener[input.id], {once: true}), 0);

				watch();

			}

		});

	};

	static init(input) {

		if(input.hasAttribute('data-autocomplete')) {
			return;
		}

		input.setAttribute('data-autocomplete', 'on');
		input.setAttribute('autocomplete', 'off');

		input.addEventListener('focusin', () => {
			input.setSelectionRange(0, input.value.length);
		});

		input.addEventListener('input', () => {
			this.change(input);
		});

		input.addEventListener('keydown', e => {

			switch(e.key) {

				case 'Enter' : // Enter

					e.preventDefault();

					if(this.hasDropdown(input)) {

						this.getDropdown(input).qs('li.selected', input => {
							input.dispatchEvent(new CustomEvent('autocompleteEnter'));
						});

					}

					break;

				case 'ArrowUp' :
					this.hover(input, 'up', e);
					break;

				case 'ArrowDown' :
					this.hover(input, 'down', e);
					break;
			}


		});

	};

	static change(input) {

		this.onUpdate(input, null);

		if(this.queryTimeout !== null) {
			clearTimeout(this.queryTimeout);
		}

		this.queryTimeout = setTimeout(() => this.query(input), 250);

	};

	static query(input) {

		const url = input.dataset.autocompleteUrl;
		const body = new URLSearchParams(input.dataset.autocompleteBody ? JSON.parse(input.dataset.autocompleteBody) : {});
		const items = input.dataset.autocompleteItems;
		const value = input.value;

		const field = input.getAttribute('data-autocomplete-field');
		const multiple = (field.indexOf('[]') !== -1);

		if(multiple === false) {
			qs('#'+ items).innerHTML = '';
		}

		this.onUpdate(input, null);

		AutocompleteField.move(input);

		body.set('query', value);

		new Ajax.Query(input)
			.url(url)
			.body(body)
			.fetch()
			.then((json) => {

				// Le focus est perdu suite au déplacement précédent
				if(document.activeElement !== input) {
					AutocompleteField.ignoreNextFocus = true;
					input.focus();
				}

				AutocompleteField.source(input, json.results)

			});

	};

	static onUpdate(input, value) {

		if(value !== null) {
			document.activeElement.blur();
		}


		const event = new CustomEvent('autocompleteUpdate', {detail: value});

		if(input.dataset.autocompleteDispatch) {
			qs(input.dataset.autocompleteDispatch, node => node.dispatchEvent(event));
		} else {
			input.dispatchEvent(event);
		}

	};

	static move(input) {

		if(input.classList.contains('autocomplete-open')) {
			return;
		}

		input.dataset.scroll = window.scrollY;
		input.classList.add('autocomplete-open');

		const dropdown = this.getDropdown(input);

		if(this.isFullscreen()) {

			input.insertAdjacentHTML('beforebegin', '<div id="'+ input.id +'-placeholder" class="autocomplete-placeholder"></div>');

			document.body.insertAdjacentHTML('beforeend', '<div id="'+ input.id +'-wrapper" class="autocomplete-wrapper" style="z-index: '+ Lime.getZIndex() +'"></div>');

			const wrapper = qs('#'+ input.id +'-wrapper');
			wrapper.insertAdjacentElement('beforeend', input);
			wrapper.insertAdjacentElement('beforeend', dropdown);

			Lime.History.pushLayer(dropdown, () => this.internalRemove(input), true);

			document.body.classList.add('autocomplete-fullscreen-open');

		} else {
			input.style.zIndex = Lime.getZIndex();
			dropdown.style.zIndex = Lime.getZIndex();
		}

	};

	static source(input, values) {

		const dropdown = this.getDropdown(input);

		let html = '<ul class="autocomplete-list">';

		values.forEach((value, key) => {

			if(value['separator'] !== undefined) {
				html += '<li class="not-selectable">';
					html += value['separator'];
				html += '</li>';
			}

			html += '<li data-n="'+ key +'">';
				html += value['label'];
			html += '</li>';

		});

		html += '</ul>';

		dropdown.innerHTML = html;

		dropdown.qs('ul.autocomplete-list', list => {

			const listBounds = list.getBoundingClientRect();
			const inputBounds = input.getBoundingClientRect();

			list.scroll(0, 0);
			list.style.width = inputBounds.width +'px';

			const translateX = inputBounds.left - listBounds.left;
			const translateY = inputBounds.bottom + 1 - listBounds.top;

			list.style.transform = 'translate('+ translateX +'px, '+ translateY +'px)';

		});

		dropdown.qsa('li', node => node.addEventListener('click', e => AutocompleteField.onSelect(input, values, node)));
		dropdown.qsa('li', node => node.addEventListener('autocompleteEnter', e => AutocompleteField.onSelect(input, values, node)));

	};

	static onSelect(input, values, selected) {

		if(selected.hasAttribute('data-n') === false) {
			return;
		}

		const position = parseInt(selected.getAttribute('data-n'));
		const value = values[position];

		// On applique et appelle les callbacks
		this.apply(input, value);

		// On remet l'élément à sa place
		this.remove(input);

	};

	static apply(input, value) {

		if(input.dataset.autocompleteItems) {

				let itemSelector = '#'+ input.dataset.autocompleteItems;

				const field = input.getAttribute('data-autocomplete-field');
				const multiple = (field.indexOf('[]') !== -1);

				if(multiple === false) {

					qs(itemSelector).innerHTML ='<input type="hidden" name="'+ field +'" value="'+ value.value +'"/>';

					input.value = value.itemText;

					this.onUpdate(input, value);

				} else {

					if(qsa(itemSelector +' > [data-value="'+ value.value +'"]').length === 0) {

						let item = '<div class="autocomplete-item" data-value="'+ value.value +'">';
							item += value.itemHtml;
							item += '<input type="hidden" name="'+ field +'" value="'+ value.value +'"/>';
						item += '</div>';

						qs(itemSelector).insertAdjacentHTML('beforeend', item);

					}

					input.value = '';
					this.onUpdate(input, null);

				}

		} else {

			input.value = value.value;
			this.onUpdate(input, value);

		}

	};

	static hover(input, direction, e) {

		const list = this.getDropdown(input).qsa('li');
		const length = list.length;

		if(length === 0) {
			return;
		}

		e.preventDefault();

		let position = 0;
		let currentPosition = null;

		list.forEach(entry => {

			if(entry.classList.contains('selected')) {

				entry.classList.remove('selected');

				currentPosition = position;

			}

			position++;

		});

		let newPosition;

		if(direction === 'up') {

			if(currentPosition === 0) {
				newPosition = length - 1;
			} else {
				newPosition = currentPosition - 1;
			}

		} else {

			if(currentPosition === null || currentPosition === length - 1) {
				newPosition = 0;
			} else {
				newPosition = currentPosition + 1;
			}

		}

		this.getDropdown(input).qs('li[data-n="' + newPosition + '"]').classList.add('selected');


	};

	static removeItem(item) {
		item.firstParentMatches('.autocomplete-item').remove();
	}

	static remove(input) {

		const dropdown = this.getDropdown(input);

		if(dropdown !== null) {

			if(this.isFullscreen()) {
				Lime.History.removeLayer(dropdown);
			} else {
				this.internalRemove(input);
			}

		}

	};

	static internalRemove(input) {

		const dropdown = this.getDropdown(input);

		input.classList.remove('autocomplete-open');

		if(this.isFullscreen()) {

			qs('#'+ input.id +'-placeholder', placeholder => {

				placeholder.insertAdjacentElement('afterend', dropdown);
				placeholder.insertAdjacentElement('afterend', input);
				placeholder.remove();

			}/*, () => {
				input.remove();
			}*/);

			qs('#'+ input.id +'-wrapper', wrapper => wrapper.remove());

			document.body.classList.remove('autocomplete-fullscreen-open');

			setTimeout(() => {
				window.scrollTo(0, input.dataset.scroll);
				input.dataset.scroll = null;
			}, 0);

		}

		if(this.isFullscreen() === false) {
			document.removeEventListener('click', this.clickListener[input.id], {once: true})
			delete this.clickListener[input.id];
		}

		dropdown.remove();

		return true;

	}

	static isFullscreen() {
		return document.body.matches('[data-touch="yes"]');
	};

};