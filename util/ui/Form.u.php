<?php
namespace util;

/**
 * Handle forms
 *
 * @author Vincent Guth
 */
class FormUi {

	protected ?string $lastFieldId = NULL;
	protected ?string $lastFieldName = NULL;
	protected ?string $formId = NULL;
	protected ?bool $formDraft = NULL;

	/**
	 * Fields and button size
	 *
	 * @var string Can be 'sm', 'lg', ...
	 */
	protected ?string $size = NULL;

	protected array $options = [];

	/**
	 * Build a new form
	 *
	 * Options are:
	 * - style: form style from options (basic, inline, horizontal)
	 * -
	 *
	 * @param array $options Boostrap options
	 */
	public function __construct(array $options = []) {

		$this->options = $options + [
			'style' => 'basic',
		];

		if($this->options['style'] === 'horizontal') {

			$this->options = $options + [
				'horizontalSize' => 33,
				'horizontalBreak' => 'sm',
			];

		}

		\Asset::css('util', 'form.css');
		\Asset::js('util', 'form.js');

	}

	/**
	 * Open a new form with POST method
	 *
	 * @param string $id Id
	 * @param string $attributes Additional attributes for <form> tag (+ groupLabelClass, groupDivClass)
	 */
	public function open(string $id = NULL, array $attributes = []): string {

		if(isset($attributes['binary'])) {
			$attributes['enctype'] = 'multipart/form-data';
			unset($attributes['binary']);
		}

		$attributes['action'] ??= 'javascript:;';
		$attributes['method'] ??= 'post';

		if($id) {
			$attributes['id'] = (string)$id;
			$this->formId = (string)$id;
			$this->formDraft = isset($attributes['data-draft']);
		} else {
			$this->formId = NULL;
			$this->formDraft = NULL;
		}

		if($this->options['style'] === 'inline') {
			$attributes['class'] = 'form-'.$this->options['style'].' '.($attributes['class'] ?? '');
		}
		if($this->options['style'] === 'horizontal') {
			$attributes['class'] = 'form-horizontal form-horizontal-'.$this->options['horizontalSize'].' form-horizontal-'.$this->options['horizontalBreak'].' '.($attributes['class'] ?? '');
		}

		if(in_array('novalidate', $attributes, TRUE)) {
			$attribute = ' novalidate';
			unset($attributes[array_search('novalidate', $attributes)]);
		} else {
			$attribute = '';
		}

		return '<form '.attrs($attributes).$attribute.'>';

	}

	public function openUrl(string $url, array $attributes = []): string {

		$attributes['action'] = $url;

		return $this->open(NULL, $attributes);

	}

	public function openAjax(string $url, array $attributes = []): string {

		$attributes['data-ajax'] = $url;

		return $this->open(NULL, $attributes);

	}

	/**
	 * Close a form
	 *
	 */
	public function close(): string {

		$h = '</form>';

		if($this->formDraft) {

			$h .= '<script type="text/javascript">';
				$h .= 'Draft.initDrafts(qs("#'.$this->formId.'"));';
			$h .= '</script>';

		}

		return $h;

	}

	/**
	 * Change fields size in the form
	 *
	 * @param string $size
	 */
	public function setSize(string $size): string {
		$this->size = $size;
	}

	/**
	 * Get size class for a field
	 *
	 * @param string $field 'btn' or 'input'
	 * @return string
	 */
	protected function getSize(string $field): string {
		if($this->size) {
			return $field.'-'.$this->size;
		} else {
			return '';
		}
	}

	/**
	 * Create a form group (according to options definition) from a label and form fields
	 *
	 * @param string $label
	 * @param string $fields
	 * @param array $attributes
	 *
	 * @return string
	 */
	public function group(?string $label = NULL, ?string $content = NULL, array $attributes = []): string {

		$wrapper = $attributes['wrapper'] ?? $this->lastFieldName;
		unset($attributes['wrapper']);

		if(substr($wrapper, -2) === '[]') {
			$wrapper = substr($wrapper, 0, -2);
		}

		$class = $attributes['class'] ?? '';
		unset($attributes['class']);

		$h = '<div data-wrapper="'.$wrapper.'" class="form-group '.$class.'" '.attrs($attributes).'>';

		if($attributes['for'] ?? TRUE) {
			$for = 'for="'.$this->lastFieldId.'"';
		} else {
			$for = '';
		}

		switch($this->options['style']) {

			case 'horizontal' :
				$h .= '<label '.$for.' class="form-control-label">'.$label.'</label>';
				$h .= '<div class="form-control-field">'.$content.'</div>';
				break;

			default :
				if($label !== NULL) {
					$h .= '<label '.$for.' class="form-control-label">'.$label.'</label>';
				}
				$h .= ' <div>'.$content.'</div>';
				break;


		}
		$h .= '</div>';

		return $h;

	}

	/**
	 * Create group from an element and given properties
	 */
	public function dynamicGroups(\Element $e, array $properties, array $dsCallback = []): string {

		$h = '';

		foreach($properties as $property) {
			$h .= $this->dynamicGroup($e, $property, $dsCallback[$property] ?? NULL);
		}

		return $h;

	}

	/**
	 * Create group from an element and given property
	 */
	public function dynamicGroup(\Element $e, string $property, ?\Closure $dCallback = NULL): string {

		if($e instanceof \Element === FALSE) {
			throw new \Exception('No element given');
		}

		$d = NULL;
		$h = $this->dynamicField($e, $property, $dCallback, $d);

		$field = $attributes['field'] ?? $d->field;

		switch($field) {

			case NULL :
				return '';

			case 'hidden' :
				return $h;

			default :

				if($d->groupLabel === FALSE) {
					$label = '';
				} else {
					$label = $d->label ?? '<i>'.$property.'</i>';
				}

				if($d->group) {
					$groupAttributes = is_closure($d->group) ? call_user_func($d->group, $e) : $d->group;
				} else {
					$groupAttributes = [];
				}

				return $this->group($label, $h, $groupAttributes);


		}

	}

	/**
	 * Create a dynamic field
	 */
	public function dynamicField(\Element $e, string $property, ?\Closure $dCallback = NULL, \PropertyDescriber &$d = NULL): string {

		$classUi = $e->getModule().'Ui';
		$ui = new $classUi();

		$name = $property;

		if(preg_match('/^([a-z0-9]+)\[([^\]]+)\]/si', $property, $matches) > 0) {
			[, $property] = $matches;
		}

		$d = $ui->p($property);

		if($dCallback !== NULL) {
			$dCallback($d);
		}

		$field = $d->field;

		if(is_closure($d->attributes)) {
			$d->attributes = call_user_func($d->attributes, $this, $e, $property, $field);
		}

		$d->attributes += [
			'name' => $name
		];

		if($field === NULL) {
			return '';
		}

		$d->last = '';

		$dynamicField = $this->createDynamicField($d, $e, $property);

		if($d->load) {
			$load = $d->load;
			$load();
		}

		$h = '';

		if($d->before) {
			$h .= is_closure($d->before) ? call_user_func($d->before, $this, $e, $property, $field) : $d->before;
		}

		if($d->prepend !== NULL or $d->append !== NULL) {

			$input = '';

			if($d->prepend) {
				$input .= $this->addon(is_closure($d->prepend) ? call_user_func($d->prepend, $this, $e, $property, $field, $d->attributes) : $d->prepend);
			}

			$input .= $dynamicField;

			if($d->append) {
				$input .= $this->addon(is_closure($d->append) ? call_user_func($d->append, $this, $e, $property, $field, $d->attributes) : $d->append);
			}

			$h .= $this->inputGroup($input);
		} else {
			$h .= $dynamicField;
		}

		if($d->after) {
			$h .= is_closure($d->after) ? call_user_func($d->after, $this, $e, $property, $field, $d->attributes) : $d->after;
		}

		$h .= $d->last;

		return $h;

	}

	protected function createDynamicField(\PropertyDescriber $d, \Element $e, string $property): string {

		$field = $d->field;
		$attributes = $d->attributes;

		if(is_closure($field)) {
			return call_user_func($field, $this, $e, $property, $attributes);
		}

		$type = $d->type;

		if(is_closure($d->default)) {
			$default = ($d->default)($e, $property);
		} else {
			$default = $e[$property] ?? $d->default;
		}

		if($d->placeholder) {
			$attributes += [
				'placeholder' => is_closure($d->placeholder) ? call_user_func($d->placeholder, $this, $e, $property, $attributes) : $d->placeholder
			];
		}

		switch($field) {

			case 'select' :
				return $this->select($property, $d->values, $default, $attributes);

			case 'range' :
				return $this->range($property, $d->from, $d->to, $d->step ?? 1, $default, $attributes);

			case 'rangeSelect' :
				return $this->rangeSelect($property, $d->from, $d->to, $d->step ?? 1, $default, $attributes);

			case 'radio' :
				return $this->radios($property, $d->values ?? $attributes['values'], $default, $attributes);

			case 'weekNumber' :
				return $this->weekNumber($property, $default, $attributes);

			case 'week' :
				return $this->week($property, $default, $attributes);

			case 'month' :
				return $this->month($property, $default, $attributes);

			case 'time' :
				return $this->time($property, $default, $attributes);

			case 'hidden' :
				return $this->hidden($property, $default, $attributes);

			case 'autocomplete' :

				$url = $d->autocompleteUrl ?? throw new \Exception('Missing $d->autocompleteUrl for autocomplete field');

				if(is_closure($d->autocompleteBody)) {
					$body = $d->autocompleteBody->call($this, $this, $e);
				} else {
					$body = $d->autocompleteBody ?? [];
				}

				if($e->offsetExists($property)) {
					if(
						$e[$property] === NULL or
						($e[$property] instanceof \Element and $e[$property]->empty())
					) {
						$default = NULL;
					} else {
						$default = ($d->autocompleteResults)($e[$property]);
					}
				} else {
					$default = NULL;
				}

				if(isset($attributes['name'])) {
					$name = $attributes['name'];
					unset($attributes['name']);
				} else {
					$name = $property;
				}

				if($d->multiple ?? FALSE) {
					$name .= '[]';
				}

				$dispatch = $d->autocompleteDispatch ?? NULL;

				[
					'query' => $query,
					'results' => $results
				] = $this->autocomplete($name, $url, $body, $dispatch, $default, $attributes);

				$d->last = $results;

				return $query;


		}

		if($type === 'bool') {
			return $this->checkbox($property, 1, ['checked' => $default] + $attributes);
		} else if(strpos($type, 'element') !== FALSE and $d->module !== NULL) {

			if($d->values instanceof \Collection or is_array($d->values)) {
				return $this->select($property, $d->values, ($e[$property] ?? new \Element()), $attributes);
			} else {
				throw new \Exception('Missing collection or array for property \''.$property.'\'');
			}

		} else if($type === 'text8' or $type === 'textFixed') {

			return $this->text($property, $default, $attributes);

		} else if($type === 'fqn') {

			return $this->fqn($property, $default, $attributes);

		} else if(strpos($type, 'text') === 0) {

			return $this->textarea($property, $default, $attributes);

		} else if(strpos($type, 'editor') === 0) {

			return $this->editor($property, ($default), $d->options ?? [], $attributes);

		} else if($type === 'email') {

			return $this->email($property, $default, $attributes);

		} else if($type === 'url') {

			return $this->url($property, $default, $attributes);

		} else if(strpos($type, 'int') === 0) {

			if($e->model()->hasProperty($property)) {
				[$min, $max] = $e->model()->getPropertyRange($property);
				$attributes += [
					'min' => $min,
					'max' => $max
				];
			}

			return $this->number($property, $default, $attributes);

		} else if(strpos($type, 'float') === 0) {

			if($e->model()->hasProperty($property)) {
				[$min, $max] = $e->model()->getPropertyRange($property);
				$attributes += [
					'min' => $min,
					'max' => $max
				];
			}

			$attributes += [
				'step' => '0.01'
			];

			return $this->number($property, $default, $attributes);

		} else if($type === 'date') {

			if($e->model()->hasProperty($property)) {
				[$min, $max] = $e->model()->getPropertyRange($property);
				$attributes += [
					'min' => $min,
					'max' => $max
				];
			}

			return $this->date($property, $default, $attributes);

		} else if($type === 'time') {
			return $this->time($property, $default, $attributes);
		} else if($type === 'week') {
			return $this->week($property, $default, $attributes);
		} else if($type === 'month') {
			return $this->month($property, $default, $attributes);
		} else if($type === 'set') {

			$h = '';

			foreach($d->set as $set) {

				$checked = (isset($e[$property]) and $e[$property]->value($set));
				$label = $d->values[$set] ?? '<i>'.$set.'</i>';

				$h .= $this->checkbox($property.'[]', $set, [
					'checked' => $checked,
					'display' => fn($input) => $input.' '.$label
				]);

			}

			return $h;

		} else if(strpos($type, 'enum') === 0) {

			$values = [];

			foreach($d->enum as $enum) {
				$values[$enum] = $d->values[$enum] ?? '<i>'.$enum.'</i>';
			}

			return $this->radios($property, $values, $default, $attributes);

		} else if($type === 'color') {
			return $this->color($property, $default, $attributes);
		} else {
			throw new \Exception('Type \''.$type.'\' of property \''.$property.'\' not handled');
		}

	}

	/**
	 * Create a input group (according to options definition)
	 * It is usefull for inline checkbox/radio and for input-group-addon style
	 *
	 * @param string $fields
	 * @param array $attributes
	 * @return string
	 */
	public function inputGroup(string $fields, array $attributes = []): string {

		$h = '<div class="input-group';

		if(isset($attributes['class'])) {
			$h .= ' '.$attributes['class'];
		}

		$h .= '"';

		if(isset($attributes['id'])) {
			$h .= ' id="'.$attributes['id'].'"';
		}

		$h .= '>'.$fields.'</div>';

		return $h;

	}

	/**
	 * Create a input text
	 *
	 */
	public function addon(string $content): string {
		$h = '<span class="input-group-addon">'.$content.'</span>';
		return $h;
	}

	/**
	 * Display help for a field
	 *
	 * @param string $content
	 * @return string
	 */
	public function help(string $content): string {
		return '<span class="color-muted">'.$content.'</span>';
	}

	/**
	 * Create a label for the last field
	 *
	 * @param string $text Label text
	 */
	public function label(string $text): string {

		if($this->lastFieldId === NULL) {
			return $text;
		} else {
			return '<label for="'.$this->lastFieldId.'">'.$text.'</label>';
		}

	}

	/**
	 * Display a checkbox
	 *
	 */
	public function checkbox(string $name, mixed $value = '1', array $attributes = []): string {

		if(isset($attributes['display'])) {
			$addon = $attributes['display'];
			unset($attributes['display']);
		} else {
			$addon = fn($input) => $input;
		}

		$input = $this->inputCheckbox($name, $value, $attributes);

		$h = '<div class="field-checkbox">';
			$h .= '<label>'.$addon($input).'</label>';
		$h .= '</div>';

		return $h;

	}

	public function inputCheckbox(string $name, mixed $value = '1', array $attributes = []): string {

		if(isset($attributes['checked'])) {

			if($attributes['checked']) {
				$attributes['checked'] = 'checked';
			} else {
				unset($attributes['checked']);
			}
		}

		return $this->input('checkbox', $name, $value, $attributes);

	}

	/**
	 * Display a radio button
	 *
	 * @param string $name
	 * @param string $value
	 * @param string $label
	 * @param string $selectedValue Selected value
	 * @param array $attributes Additional attributes
	 */
	public function radio(string $name, $value, string $label, mixed $selectedValue = NULL, array $attributes = []): string {
		return '<div class="radio"><label>'.$this->inputRadio($name, $value, $label, $selectedValue, $attributes).'</label></div>';
	}

	/**
	 * Display several radio fields
	 *
	 * @param string $name
	 * @param array $values Possible values
	 * @param mixed $selection Default selection
	 * @param array $attributes Additional attributes ('multiple' for multiple select, with callback for content, default encode)
	 */
	public function radios(?string $name, array|\ArrayIterator $values, mixed $selectedValue = NULL, array $attributes = []): string {

		// Default attributes
		$attributes += [
			'columns' => 1,
			'callbackRadioLabel' => fn(string $label) => $label,
			'callbackRadioAttributes' => function() {
				return [];
			}
		];

		$columns = $attributes['columns'];
		unset($attributes['columns']);

		$callbackRadioLabel = $attributes['callbackRadioLabel'];
		unset($attributes['callbackRadioLabel']);

		$callbackRadioAttributes = $attributes['callbackRadioAttributes'];
		unset($attributes['callbackRadioAttributes']);

		$this->setDefaultAttributes($attributes, $name);

		$h = '<div class="field-radio-group field-radio-group-'.$columns.'" '.attrs($attributes).'>';

		foreach($values as $key => $option) {

			[$optionValue, $optionContent, $optionAttributes] = $this->getOptionValue($key, $option);

			$h .= '<label>'.$this->inputRadio($name, $optionValue, call_user_func($callbackRadioLabel, $optionContent), $selectedValue, call_user_func($callbackRadioAttributes, $option)).'</label>';

		}

		$h .= '</div>';

		return $h;

	}

	public function yesNo(?string $name, mixed $selectedValue = NULL, array $attributes = []): string {

		$attributes += ['columns' => 2];

		$values = [
			[
				'value' => 1,
				'label' => s("oui")
			],
			[
				'value' => 0,
				'label' => s("non")
			],
		];

		return $this->radios($name, $values, $selectedValue, $attributes);

	}

	protected function inputRadio(?string $name, $value, string $label = NULL, $selectedValue = NULL, array $attributes = []): string {

		if(array_key_exists('id', $attributes) === FALSE) {
			$attributes['id'] = $name;
			$attributes['id'] .= ctype_alnum($value) ? ucfirst($value) : crc32($value);
		}

		$selectedValue = $this->getInputValue($selectedValue);

		if(is_bool($selectedValue)) {
			$selectedValue = (int)$selectedValue;
		}

		$selectedValue = (string)$selectedValue;

		if((string)$value === $selectedValue) {
			$attributes['checked'] = 'checked';
		} else if(isset($attributes['checked'])) {

			if($attributes['checked']) {
				$attributes['checked'] = 'checked';
			} else {
				unset($attributes['checked']);
			}
		}

		$h = $this->input('radio', $name, $value, $attributes);

		if($label !== NULL) {
			$h .= ' <span>'.$label.'</span>';
		}

		return $h;

	}

	/**
	 * Display a date field
	 *
	 * @param string $name
	 * @param string $selection Default date
	 * @param array $attributes => 'callback' method called for each changement
	 * @return string
	 */
	public function date(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('date', $name, $value, $attributes);

	}

	/**
	 * Display a date field
	 *
	 * @param string $name
	 * @param string $selection Default date
	 * @param array $attributes => 'callback' method called for each changement
	 * @return string
	 */
	public function month(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$wrapperId = uniqid('field-month-wrapper-');

		$h = '<div class="field-month-wrapper" id="'.$wrapperId.'">';

			$h .= '<div class="field-month-fallback">';
				$h .= $this->select($name.'Month', DateUi::months(), $value ? date_month($value) : NULL, [
					'placeholder' => s("Mois"),
					'data-field-fallback' => 'monthNumber',
				]).' ';
				$h .= $this->number($name.'Year', $value ? date_year($value) : NULL, [
					'data-field-fallback' => 'year',
					'prepend' => s("Année")
				]);
			$h .= '</div>';

			$h .= '<div class="field-month-native">';
				$h .= $this->input('month', $name, $value, $attributes);
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<script>';
			$h .= 'DateField.startFieldMonth(\'#'.$wrapperId.'\')';
		$h .= '</script>';

		return $h;

	}

	/**
	 * Display a week field
	 */
	public function week(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$wrapperId = uniqid('field-week-wrapper-');

		$h = '<div class="field-week-wrapper" id="'.$wrapperId.'">';

			$h .= $this->hidden($name, $value, ['class' => 'field-week-value']);

			$h .= '<div class="field-week-fields">';
				$h .= $this->weekNumber($name.'Week', $value ? week_number($value) : NULL, [
					'id' => $wrapperId.'-week',
					'onfocus' => 'DateField.blurFieldWeek(this)',
					'onclick' => 'Lime.Dropdown.open(this, "bottom-start")',
					'data-field-fallback' => 'weekNumber',
					'data-year-selector' => '#'.$wrapperId.' [data-field="'.$name.'Year"]',
					'prepend' => s("Semaine"),
					'min' => 1,
					'max' => 53,
				]);
				$h .= $this->number($name.'Year', $value ? week_year($value) : NULL, [
					'data-field-fallback' => 'year',
					'data-week-selector' => '#'.$wrapperId.' [data-field="'.$name.'Week"]',
					'prepend' => s("Année")
				]);
			$h .= '</div>';

			$h .= '<div data-dropdown-id="'.$wrapperId.'-week-list" class="dropdown-list dropdown-list-minimalist">';
				$h .= \util\FormUi::weekSelector($value ? week_year($value) : date('Y'), 'DateField.changeFieldWeek(\'#'.$wrapperId.'\', this)', $value);
			$h .= '</div>';

		$h .= '</div>';

		$h .= '<script>';
			$h .= 'DateField.startFieldWeek(\'#'.$wrapperId.'\')';
		$h .= '</script>';

		return $h;

	}

	/**
	 * Display text field for weeks
	 */
	public function weekNumber(string $name, int $value = NULL, array $attributes = []): string {

		$id = uniqid('field-week-');

		$week = function($weekNumber) {
			return DateUi::weekToDays(date('Y').'-W'.sprintf('%02d', $weekNumber), withYear: FALSE);
		};

		$attributes['class'] = 'form-control url '.($attributes['class'] ?? '');
		$attributes += [
			'placeholder' => s("n°"),
			'prepend' => s("Semaine"),
			'append' => '<span id="'.$id.'" class="field-week-number-label">'.(is_numeric($value) ? $week($value) : '-').'</span>',
			'min' => 1,
			'max' => 53,
			'data-week-number' => '#'.$id,
			'onrender' => 'DateField.updateWeeks(this)'
		];

		$h = $this->input('number', $name, $value, $attributes);

		return $h;
	}

	public static function weekSelector(int $year, string $onclick, ?string $defaultWeek = NULL): string {

		\Asset::css('util', 'form.css');

		$currentWeek = currentWeek();
		$defaultWeek ??= $currentWeek;

		$weeks = date('W', strtotime($year.'-12-31')) === '53' ? 53 : 52;
		$list = [];

		for($weekNumber = 1; $weekNumber <= $weeks; $weekNumber++) {

			$week = $year.'-W'.sprintf('%02d', $weekNumber);
			$monday = strtotime($week);
			$sunday = strtotime($week.' + 6 DAY');

			$sundayMonth = (int)date('n', $sunday);
			$sundayDay = (int)date('j', $sunday);

			if($sundayDay >= 4) {

				$list[$sundayMonth][] = [
					'week' => $week,
					'weekNumber' => $weekNumber,
					'sunday' => $sundayDay,
				];

			} else {

				$mondayMonth = (int)date('n', $monday);
				$mondayDay = (int)date('j', $monday);

				$list[$mondayMonth][] = [
					'week' => $week,
					'weekNumber' => $weekNumber,
					'sunday' => $mondayDay + 6,
				];

			}

		}

		$id = uniqid('field-week-selector-');

		$h = '<div id="'.$id.'" class="field-week-selector">';
		$h .= '<h4 class="field-week-selector-title">'.s("Semaines").'</h4>';
			$h .= '<div class="field-week-selector-year">';
				$h .= '<a data-ajax="util/form:weekChange" post-id="'.$id.'" post-year="'.($year - 1).'" post-onclick="'.encode($onclick).'" post-default="'.encode($defaultWeek).'" data-dropdown-keep="true" class="field-week-selector-navigation">'.\Asset::icon('chevron-left').'</a>';
				$h .= '<h4>'.$year.'</h4>';
				$h .= '<a data-ajax="util/form:weekChange" post-id="'.$id.'" post-year="'.($year + 1).'" post-onclick="'.encode($onclick).'" post-default="'.encode($defaultWeek).'" data-dropdown-keep="true" class="field-week-selector-navigation">'.\Asset::icon('chevron-right').'</a>';
			$h .= '</div>';
			$h .= '<div class="field-week-selector-weeks">';

				$h .= '<div></div>';

				$h .= '<div class="field-week-selector-ticks">';

					foreach([1, 5, 10, 15, 20, 25, 31] as $day) {
						$h .= '<div class="field-week-selector-label" style="grid-column-start: '.($day + 3).'; grid-column-end: '.($day + 5).'">'.$day.'</div>';
						$h .= '<div class="field-week-selector-tick" style="grid-column-start: '.($day + 4).'">';
						$h .= '</div>';
					}

				$h .= '</div>';

				foreach($list as $month => $weeks) {

					$h .= '<h5 class="field-week-selector-month">';
						$h .= DateUi::getMonthName($month, TRUE);
					$h .= '</h5>';

					$h .= '<div class="field-week-selector-bubbles">';

					foreach($weeks as ['week' => $week, 'weekNumber' => $weekNumber, 'sunday' => $sunday]) {

						$color = ($defaultWeek === $week) ? 'btn-secondary' : ($currentWeek === $week ? 'btn-outline-primary field-week-selector-bubble-current' : 'btn-primary');

						$attrs = [
							'onclick' => $onclick,
							'data-week' => $week,
							'class' => 'btn btn-sm '.$color.' field-week-selector-bubble',
							'style' => 'grid-column-start: '.($sunday - 6 + 4).'; grid-column-end: '.($sunday + 1 + 4).'',
							'title' => \util\DateUi::weekToDays($week)
						];

						$h .= '<a '.attrs($attrs).'>'.$weekNumber.'</a>';

					}

					$h .= '</div>';

				}

			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	/**
	 * Display a time field
	 *
	 * @param string $name
	 * @param string $value Default time
	 * @param array $attributes => 'callback' method called for each changement
	 * @return strings
	 */
	public function time(string $name, mixed $value = NULL, array $attributes = []): string {

		$attributes['placeholder'] = '--:--';
		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('time', $name, $value, $attributes);

	}

	/**
	 * Display a select field with range
	 *
	 * @param string $name
	 * @param int $min
	 * @param int $max
	 * @param int $step
	 * @param mixed $selection Default selection (int or array for multiple selected values)
	 * @param array $attributes Additional attributes (with callback for content, default encode)
	 */
	public function range(string $name, int $min, int $max, int $step, int $value, array $attributes = []): string {

		$attributes['min'] = $min;
		$attributes['max'] = $max;
		$attributes['step'] = $step;

		if(isset($attributes['data-label'])) {

			$h = '<div class="form-range">';
				$h .=  $this->input('range', $name, $value, $attributes);
				$h .= '<div class="form-range-label">'.str_replace('VALUE', $value, $attributes['data-label']).'</div>';
			$h .= '</div>';

		} else {
			$h =  $this->input('range', $name, $value, $attributes);
		}

		return $h;

	}

	/**
	 * Display a select field with range
	 *
	 * @param string $name
	 * @param int $from
	 * @param int $to
	 * @param int $step
	 * @param mixed $selection Default selection (int or array for multiple selected values)
	 * @param array $attributes Additional attributes (with callback for content, default encode)
	 */
	public function rangeSelect(string $name, int $from, int $to, int $step, mixed $selection = NULL, array $attributes = []): string {

		$array = [];

		if(
			($from <= $to and $step > 0) or
			($from > $to and $step < 0)
		) {

			for($i = $from; ($from > $to) ? ($i >= $to) : ($i <= $to); $i += $step) {
				$array[$i] = $i;
			}

		}

		return $this->select($name, $array, $selection, $attributes);

	}

	/**
	 * Display a select field for elements
	 *
	 * @param string $name
	 * @param array $values Possible values
	 * @param mixed $selection Default selection
	 * @param array $attributes Additional attributes ('multiple' for multiple select, with callback for content, default encode)
	 */
	public function select(?string $name, $values, mixed $selection = NULL, array $attributes = []): string {

		// Selection can be an element
		$selection = $this->getSelectedValue($selection, !empty($attributes['multiple']));

		if(isset($attributes['callback'])) {
			$callback = $attributes['callback'];
			unset($attributes['callback']);
		} else {
			$callback = 'encode';
		}

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$size = $this->getSize('form-control');

		if($size) {
			$attributes['class'] .= ' '.$size;
		}

		$this->setDefaultAttributes($attributes, $name);

		$select = "";

		if(empty($attributes['mandatory']) and empty($attributes['multiple'])) {

			$select .= "<option value=''";

			if(in_array(NULL, $selection, TRUE)) {
				$select .= " selected='selected'";
			}

			$select .= ' class="field-radio-select">';
			$select .= $attributes['placeholder'] ?? s("< Choisir >");
			$select .= "</option>";

			unset($attributes['placeholder']);

		}
		unset($attributes['mandatory']);

		$select = "<select ".attrs($attributes).">".$select;

		foreach($values as $key => $value) {

			[$optionValue, $optionContent, $optionAttributes] = $this->getOptionValue($key, $value);

			$select .= "<option value=\"".encode($optionValue)."\"";

			if(isset($optionAttributes['disabled']) === FALSE) {

				foreach($selection as $valueCheck) {
					if((string)$valueCheck === (string)$optionValue) {
						$select .= ' selected="selected"';
						break;
					}
				}

			}

			if($optionAttributes) {
				$select .= ' '.attrs($optionAttributes);
			}

			$select .= ">".call_user_func($callback, $optionContent)."</option>";

		}

		$select .= "</select>";

		return $select;

	}

	/**
	 * Display a list of selects field for multiple choice
	 * This is a user-friendly alternative to <select multiple="multiple">
	 *
	 * @param string $name
	 * @param array $values Possible values
	 * @param mixed $selection Default selection
	 * @param array $attributes Additional attributes ('multiple' for multiple select, with callback for content, default encode)
	 */
	public function selects(string $name, $values, mixed $selection = NULL, array $attributes = []): string {

		$selection = $this->getSelectedValue($selection, TRUE);

		$h = '<div class="form-selects">';

			if($selection) {

				foreach($selection as $value) {
					$h .= '<div class="form-selects-item input-group">';
						$h .= $this->select($name, $values, $value, $attributes);
						$h .= '<a data-action="form-selects-delete" class="input-group-addon">';
							$h .= \Asset::icon('trash-fill');
						$h .= '</a>';
					$h .= '</div>';
				}

			} else {

				$h .= '<div class="form-selects-item input-group">';
					$h .= $this->select($name, $values, NULL, $attributes);
					$h .= '<a data-action="form-selects-delete" class="input-group-addon">';
						$h .= \Asset::icon('trash-fill');
					$h .= '</a>';
				$h .= '</div>';

			}

			$h .= '<div class="form-selects-add">';
				$h .= \Asset::icon('plus-circle').' ';
				$h .= '<a data-action="form-selects-add">'.($attributes['labelAdd'] ?? s("Ajouter")).'</a>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	private function getSelectedValue($selection, bool $multiple): array {

		if($selection instanceof \Collection) {
			if($multiple) {
				return $selection->getIds();
			} else {
				return $selection->get('id');
			}
		} else if($selection instanceof \Element) {
			if($selection->empty()) {
				return [];
			} else {
				return [$selection['id']];
			}
		} else if(is_array($selection) === FALSE) {
			return [$selection];
		} else {
			return $selection;
		}

	}

	private function getInputValue($selection) {

		if($selection instanceof \Element) {
			if($selection->empty()) {
				return NULL;
			} else {
				return $selection['id'];
			}
		} else if(is_bool($selection)) {
			return $selection ? '1' : '0';
		} else {
			return $selection;
		}

	}

	private function getOptionValue(string $key, $option): array {

		if($option instanceof \Element) {

			$value = NULL;
			$label = NULL;

			foreach($option as $field => $valueElement) {

				if(isset($value) === FALSE) {
					$value = $valueElement;
				} else if(isset($content) === FALSE) {
					$label = $valueElement;
					break;
				}
			}

			return [$value, $label, []];

		} else if(is_array($option)) {

			return [
				$option['value'] ?? NULL,
				$option['label'],
				$option['attributes'] ?? []
			];

		} else {
			return [$key, $option, []];
		}

	}

	/**
	 * Display text field
	 */
	public function text(?string $name, $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('text', $name, $value, $attributes);
	}

	/**
	 * Display text field for numbers
	 */
	public function number(?string $name, $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control form-number '.($attributes['class'] ?? '');

		return $this->input('number', $name, $value, $attributes);
	}

	/**
	 * Display text field with color picker
	 */
	public function color(string $name, $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control form-color '.($attributes['class'] ?? '');

		return $this->input('color', $name, $value, $attributes);
	}

	/**
	 * Display text field for emails
	 */
	public function email(string $name, string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control email '.($attributes['class'] ?? '');

		return $this->input('email', $name, $value, $attributes);
	}

	/**
	 * Display text field for fully qualified names
	 */
	public function fqn(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$attributes += [
			'data-type' => 'fqn',
			'append' => '<small>'.\Asset::icon('info-circle').' '.s("Uniquement a-z et -").'</small>'
		];

		return $this->input('text', $name, $value, $attributes);
	}

	/**
	 * Display text field for urls
	 */
	public function url(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control url '.($attributes['class'] ?? '');
		$attributes += [
			'placeholder' => 'https://'
		];

		return $this->input('url', $name, $value, $attributes);
	}

	/**
	 * Display text area field
	 */
	public function textarea(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		$this->setDefaultAttributes($attributes, $name);

		$textarea = "<textarea ".attrs($attributes).">".encode($value)."</textarea>";

		$count = $this->getCharacterCount($attributes);
		if($count) {
			return '<div class="form-character-count-wrapper for-textarea">
						'.$textarea.'
						'.$count.'
					</div>';

		}

		return $textarea;
	}

	/**
	 * Display editor field
	 */
	public function editor(string $name, $value = '', array $values = [], array $attributes = []): string {

		if($value) {
			$convertedValue = (new \editor\EditorFormatterUi())->getFromXml($value, $values);
		} else {
			$convertedValue = '';
		}

		$this->setDefaultAttributes($attributes);

		return (new \editor\EditorUi())->field($name, $values, $convertedValue, $attributes);

	}

	/**
	 * Display password field
	 */
	public function password(string $name, ?string $value = NULL, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');
		$attributes['autocapitalize'] = 'off';
		$attributes['autocorrect'] = 'off';

		return $this->input('password', $name, $value, $attributes);
	}

	/**
	 * Display hidden field
	 */
	public function hidden(string $name, $value = NULL, array $attributes = []): string {

		return $this->input('hidden', $name, $value, $attributes);

	}

	/**
	 * Display several hidden field
	 */
	public function hiddens(array $values, array $attributes = []): string {

		$h = '';

		foreach($values as $name => $value) {
			$h .= $this->hidden($name, $value, $attributes);
		}

		return $h;

	}

	/**
	 * Relay some information
	 */
	public function relay(string $source, string|array $names, array $attributes = []): string {

		str_is($source, ['GET', 'POST', 'REQUEST']);

		$h = '';

		foreach((array)$names as $name) {
			$h .= $this->hidden($name, $source($name), $attributes);
		}

		return $h;

	}

	/**
	 * Display a file field
	 */
	public function file(string $name, array $attributes = []): string {

		$attributes['class'] = 'form-control '.($attributes['class'] ?? '');

		return $this->input('file', $name, NULL, $attributes);
	}

	/**
	 * Display a button
	 */
	public function button($value = NULL, array $attributes = []): string {

		$attributes['class'] = ($attributes['class'] ?? 'btn '.$this->getSize('btn').' btn-primary');
		$attributes['type'] = ($attributes['type'] ?? 'button');

		$this->setDefaultAttributes($attributes);

		return "<button ".attrs($attributes).">".$value."</button>";

	}

	/**
	 * Display submit field
	 */
	public function submit($value = NULL, array $attributes = []): string {

		$attributes['type'] = 'submit';

		return $this->button($value, $attributes);

	}

	/**
	 * Create an autocomplete field
	 */
	public function autocomplete(string $name, string $url, array $body, ?string $dispatch, ?array $value, array $attributes): array {

		$id = uniqid('autocomplete-');
		$multiple = (strpos($name, '[]') !== FALSE);

		$attributes += [
			'data-autocomplete-url' => $url,
			'data-autocomplete-body' => json_encode($body),
			'data-autocomplete-items' => $id,
			'data-autocomplete-field' => $name,
			'onrender' => 'AutocompleteField.start(this);'
		];

		if($dispatch) {
			$attributes['data-autocomplete-dispatch'] = $dispatch;
		}

		if($multiple or $value === NULL) {
			$defaultQuery = NULL;
			$defaultResults = '';
		} else {
			$defaultQuery = $value['itemText'];
			$defaultResults = $this->hidden($name, $value['value']);
		}

		$query = $this->text($id.'-label', $defaultQuery, $attributes);

		return [
			'query' => $query,
			'results' => '<div class="autocomplete-items '.($multiple ? 'autocomplete-items-multiple' : '').'" id="'.$id.'">'.$defaultResults.'</div>'
		];

	}

	protected function input(string $type, ?string $name, $value, array $attributes): string {

		$this->setDefaultAttributes($attributes, $name);

		$attributes['type'] = $type;
		$attributes['value'] = $this->getInputValue($value);

		$size = $this->getSize('form-control');

		if($size) {
			$attributes['class'] = ($attributes['class'] ?? NULL).' '.$size;
		}

		$inputAttributes = $attributes;

		if(isset($attributes['prepend'])) {

			unset($inputAttributes['prepend']);
			$prepend = $this->addon($attributes['prepend']);

		} else {
			$prepend = '';
		}

		if(isset($attributes['append'])) {

			unset($inputAttributes['append']);
			$append = $this->addon($attributes['append']);

		} else {
			$append = '';
		}

		$input = '<input '.attrs($inputAttributes).'/>';

		if($prepend or $append) {
			$input = $this->inputGroup($prepend.$input.$append);
		}

		$count = $this->getCharacterCount($attributes);

		if($count) {
			return '<div class="form-character-count-wrapper for-input input-alert">
				'.$input.$count.'
			</div>';
		}

		return $input;
	}

	private function getCharacterCount(array $attributes): string {
		$limit = $attributes['data-limit'] ?? NULL;
		if($limit) {
			return '<span class="form-character-count" data-limit-for="'.$attributes['data-field'].'"></span>';
		}
		return '';
	}

	protected function setDefaultAttributes(array &$attributes, ?string $name = NULL) {

		$attributes['name'] ??= $name;
		$attributes['id'] ??= uniqid('field-');

		if(isset($attributes['name']) and isset($attributes['data-field']) === FALSE) {
			$attributes['data-field'] = $attributes['name'];
		}

		$this->lastFieldId = $attributes['id'] ?? NULL;
		$this->lastFieldName = $attributes['name'];


	}

}
?>
