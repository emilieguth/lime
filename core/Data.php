<?php
/**
 * Groups of elements
 */
class Collection extends ArrayIterator {

	private int $depth = 1;

	private array $properties = [];

	/**
	 * Checks if the collection is not empty
	 *
	 * @return bool
	 */
	public function notEmpty(): bool {
		return ($this->count() > 0);
	}

	/**
	 * Checks if the collection is empty
	 *
	 * @return bool
	 */
	public function empty(): bool {
		return ($this->count() === 0);
	}

	/**
	 * Set the depth of the group
	 */
	public function setDepth(int $depth): Collection {
		$this->depth = $depth;
		return $this;
	}

	/**
	 * Returns the depth of the group
	 */
	public function getDepth(): int {
		return $this->depth;
	}

	/**
	 * Set the property of the group
	 */
	public function setProperty(string $name, $value) {
		$this->properties[$name] = $value;
	}

	/**
	 * Returns the property of the group
	 */
	public function getProperty(string $name) {
		if(isset($this->properties[$name])) {
			return $this->properties[$name];
		} else {
			throw new Exception('Property \''.$name.'\' does not exist');
		}
	}

	/**
	 * Convert element group as a string
	 *
	 * @return string
	 */
	public function __toString(): string {

		$output = 'Collection ('."\n";

		foreach($this as $key => $value) {

			if($value instanceof Element === FALSE and $value instanceof Collection === FALSE) {
				throw new Exception('Expected only Element or Collection in a collection');
			}

			$output .= '   ['.$key.'] => '.str_replace("\n", "\n   ", trim((string)$value))."\n";

		}

		$output .= ')'."\n";

		return $output;

	}

	/**
	 * Push a new key
	 *
	 */
	public function push($keys, Element $e) {

		$keys = (array)$keys;

		if(count($keys) !== $this->depth) {
			throw new Exception('Number of $keys ('.count($keys).') should be equal to Collection depth ('.$this->depth.')');
		}

		$key = array_shift($keys);

		if($this->depth === 1) {

			if($key === NULL) {
				$this->append($e);
			} else {
				$this->offsetSet($key, $e);
			}

		} else {

			if($key === NULL) {
				throw new Exception('Key must not be NULL when Collection depth is greater than 1');
			}

			if($this->offsetExists($key) === FALSE) {
				$this->offsetSet($key, (new Collection())->setDepth($this->depth - 1));
			}

			$this[$key]->push($keys, $e);

		}

	}

	/**
	 * Get value for a key
	 *
	 * @param mixed $index
	 * @param mixed $default
	 */
	public function get($index, $default = NULL) {

		if($index === NULL) {
			$index = '';
		}

		if($this->offsetExists($index)) {
			return $this->offsetGet($index);
		} else {
			return $default;
		}

	}

	/**
	 * Get an array copy of the element group
	 *
	 * @return array
	 */
	public function getArrayCopy(): array {

		if($this->depth > 1) {

			$values = [];

			foreach($this as $key => $entry) {
				$values[$key] = $entry->getArrayCopy();
			}

			return $values;

		} else {
			return parent::getArrayCopy();
		}

	}

	/**
	 * Format a Collection as a array using the given rules
	 * $rules is an associative array. Each value can be either:
	 * - 'propertyName' (put as is in the returned array)
	 * - 'propertyName' => 'newPropertyName' (put using the new namle is the returned array)
	 * - 'propertyName' => function($eElement) { ... } (custom property)
	 *
	 * @return array $rules
	 */
	public function format(array $rules): array {

		if($this->depth > 1) {

			$values = [];

			foreach($this as $key => $entry) {
				$values[$key] = $entry->format($rules);
			}

			return $values;

		} else {

			$values = [];

			foreach($this as $eElement) {

				$value = [];

				foreach($rules as $destination => $source) {

					if(is_closure($source)) {
						$value[$destination] = $source($eElement);
					} else {

						if(is_string($destination)) {
							$value[$destination] = $eElement[$source];
						} else {
							$value[$source] = $eElement[$source];
						}

					}

				}

				$values[] = $value;

			}

			return $values;

		}

	}

	/**
	 * Add a property with the given value for all elements present in the element group
	 *
	 * @param string $property Property name
	 * @param mixed $value Property value (may be a scalar value or a callack function taking the element in parameter)
	 */
	public function setColumn(string $property, $value): \Collection {

		foreach($this as $key => $entry) {

			if($this->depth > 1) {
				$entry->addColumn($property, $value);
			} else {

				if(is_closure($value)) {
					$this[$key][$property] = $value($entry);
				} else {
					$this[$key][$property] = $value;
				}

			}

		}

		return $this;

	}

	/**
	 * Drop a property
	 *
	 * @param string $property Property name
	 */
	public function removeColumn(string $property): \Collection {

		foreach($this as $key => $entry) {

			if($this->depth > 1) {
				$entry->removeColumn($property);
			} else {
				unset($this[$key][$property]);
			}

		}

		return $this;

	}

	/*
	 * Get IDs in the element group
	 *
	 * @return array
	 */
	public function getIds(): array {
		return $this->getColumn('id');
	}

	/*
	 * Get values of a property in the element group
	 * Option can be a boolean (returns references) or an int (array level)
	 *
	 * @return array
	 */
	public function getColumn($property, $index = NULL): array {

		return $this->getRecursiveColumn($property, $index);

	}

	/**
	 * Idéalement :
	 * $cUser = user\User::newCollection();
	 * Produit un objet du type UserCollection extends Collection
	 * Du coup on peut supprimer cette méthode et rendre getColumn() plus intelligent pour fabriquer automatiquement une collection pertinente
	 */
	public function getColumnCollection($property, $index = NULL, int $depth = 1): \Collection {

		$values = $this->getRecursiveColumn($property, $index);

		$collection = new Collection($values);

		if($depth > 1) {
			$collection->setDepth($depth);
		}

		return $collection;

	}

	private function getRecursiveColumn($property, $index): array {

		if($this->depth > 1) {

			$values = [];

			foreach($this as $entry) {

				if($index !== NULL) {

					$values += $entry->getRecursiveColumn($property, $index);

				} else {

					$values = array_merge(
						$values,
						$entry->getRecursiveColumn($property, $index)
					);

				}

			}

			return $values;

		} else {

			if($index !== NULL) {

				$values = [];

				foreach($this as $value) {

					if(isset($value[$property])) {

						if(isset($value[$index]))  {
							if($value[$index] instanceof Element) {
								$key = $value[$index]['id'];
							} else {
								$key = $value[$index];
							}
						} else {
							$key = NULL;
						}

						$values[$key] = $value[$property];

					}

				}

				return $values;

			} else {

				$values = [];

				foreach($this as $e) {
					if(isset($e[$property])) {
						$values[] = $e[$property];
					}
				}

				return $values;

			}

		}

	}

	/**
	 * Applies the diff between two Collections
	 * according to IDs from the elements
	 *
	 * @param Collection $c
	 * @param bool $preserveKeys Keep the keys if asked. Otherwise, keys are reset
	 * @return \Collection
	 */
	public function diff(Collection $c, $preserveKeys = TRUE): Collection {

		$keys1 = $this->getIds();
		$keys2 = $c->getIds();

		$diff = array_diff($keys1, $keys2);

		$newC = new Collection();

		foreach($this as $key => $eElement) {
			if(in_array($eElement['id'], $diff)) {
				if($preserveKeys) {
					$newC[$key] = $eElement;
				} else {
					$newC->append($eElement);
				}
			}
		}

		return $newC;

	}

	/**
	 * Keep only $number elements in the collection
	 * DO NOT USE WITH LARGE COLLECTIONS
	 *
	 * @return bool TRUE if elements have been removed, FALSE otherwise
	 */
	public function cut(int $number, int $depth = 1): bool {

		$cut = FALSE;
		$this->recursiveCut($this, $number, $depth, $cut);

		return $cut;

	}

	protected function recursiveCut(\Collection $c, int &$number, int $depth, bool &$cut): void {

		if($depth > 1) {
			foreach($c as $offset => $newC) {
				if($number === 0) {
					$c->offsetUnset($offset);
					$cut = TRUE;
				} else {
					$this->recursiveCut($newC, $number, $depth - 1, $cut);
				}
			}
			return;
		}

		if($c->count() <= $number) {
			$number .= $c->count();
			return;
		}

		for($c->rewind(); $c->valid(); ) {

			if($number === 0) {
				$c->offsetUnset($c->key());
				$cut = TRUE;
			} else {
				$number--;
				$c->next();
			}

		}

	}

	/**
	 * Applies the diff between two Collections
	 * according to their key values
	 *
	 * @param Collection $c
	 * @return \Collection
	 */
	public function keyDiff(Collection $c): Collection {
		return new Collection(array_diff_key($this->getArrayCopy(), $c->getArrayCopy()));
	}

	/**
	 * Apply a closure to each element of the collection
	 *
	 * @param callable $closure
	 */
	public function map(callable $closure): Collection {

		foreach($this as $value) {
			$closure($value);
		}

		return $this;

	}

	/**
	 * Apply a closure to each element of the collection
	 *
	 * @param callable $closure
	 */
	public function expects(array $keys, ?callable $callback = NULL): Collection {

		return $this->map(function(Element $e) use ($keys, $callback) {
			$e->expects($keys, $callback);
		});

	}

	/**
	 * Filter collection
	 *
	 */
	public function filter(Closure $filter): Collection {

		for($this->rewind(); $this->valid(); ) {

			if($filter($this->current()) === FALSE) {
				$this->offsetUnset($this->key());
			} else {
				$this->next();
			}

		}

		return $this;

	}

	/**
	 * Filter collection
	 *
	 * @param mixed $filter Filter can be a callable function (works just as array_filter do) or a property name (delete entries which do not define the given property)
	 */
	public function copy(?Closure $filter = NULL, bool $preserveKeys = TRUE): Collection {

		$c = new Collection();

		foreach($this as $key => $e) {

			if($filter === NULL or $filter($e)) {

				if($preserveKeys) {
					$c[$key] = clone $e;
				} else {
					$c[] = clone $e;
				}

			}

		}

		return $c;

	}

	/**
	 * Reduce collection
	 *
	 */
	public function reduce(Closure $callback, mixed $value): mixed {

		for($this->rewind(); $this->valid(); ) {
			$value = $callback($this->current(), $value);
			$this->next();
		}

		return $value;

	}

	/**
	 * Transform to string
	 *
	 */
	public function makeString(Closure $callback): string {

		$output = '';

		for($this->rewind(); $this->valid(); ) {
			$output .= $callback($this->current());
			$this->next();
		}

		return $output;

	}

	/**
	 * Transform to array
	 *
	 */
	public function makeArray(Closure $callback): array {

		$output = [];

		for($this->rewind(); $this->valid(); ) {
			$key = NULL;
			$value = $callback($this->current(), $key);
			if($key === NULL) {
				$output[] = $value;
			} else {
				$output[$key] = $value;
			}
			$this->next();
		}

		return $output;

	}

	/**
	 * Returns first element of a collection
	 */
	public function first(): ?Element {

		$this->rewind();
		return $this->current();

	}

	/**
	 * Returns last element of a collection
	 */
	public function last(): ?Element {

		$this->seek($this->count() - 1);
		return $this->current();

	}

	/*
	 * Sort the collection using a property
	 *
	 */
	public function sort(Closure|array|string $properties, bool $naturalSort = FALSE): Collection {

		if(is_closure($properties) === FALSE) {

			$properties = (array)$properties;

			$callback = function($eElement1, $eElement2) use ($properties, $naturalSort) {

				foreach($properties as $key => $value) {

					if(is_string($key)) {
						$list = (array)$key;
						$sort = $value;
					} else {
						$list = (array)$value;
						$sort = SORT_ASC;
					}

					$value1 = $eElement1;
					$value2 = $eElement2;

					foreach($list as $property) {
						$value1 = $value1[$property];
						$value2 = $value2[$property];
					}

					$mul = ($sort === SORT_ASC) ? 1 : -1;

					if(is_string($value1)) {
						if($naturalSort) {
							return strnatcmp(mb_strtolower($value1), mb_strtolower($value2)) * $mul;
						} else {
							return strcmp($value1, $value2) * $mul;
						}
					} else if(is_scalar($value1)) {
						return ($value1 < $value2 ? -1 : 1) * $mul;
					} else if($value1 instanceof Element) {
						return ($value1['id'] < $value2['id'] ? -1 : 1) * $mul;
					}

				}

				return 0;

			};

		} else {
			$callback = $properties;
		}

		$this->uasort($callback);

		return $this;

	}

	/**
	 * Validate tests
	 */
	public function validate(array $tests): \Collection {

		if($this->empty()) {
			throw new NotExistsAction('Empty collection');
		}

		$this->map(function(Element $e) use ($tests) {
			$e->validate($tests);
		});

		return $this;

	}

	/**
	 * Merge collections into one
	 */
	public static function merge(...$cc): Collection {

		$merge = array_map(fn($c) => $c->getArrayCopy(), $cc);
		return new Collection(array_merge(...$merge));

	}

	/**
	 * Same as array + operator
	 * @return Collection the union of the current Collection with the Collection sent as parameter
	 */
	public function appendCollection(Collection $cElement): Collection {

		foreach($cElement as $key => $eElement) {
			if($this->offsetExists($key) === FALSE) {
				$this[$key] = $eElement;
			}
		}

		return $this;

	}

	/**
	 * Chunk group
	 *
	 * @param int $size
	 */
	public function chunk($size): array {

		$splits = [];

		for($i = 0; $i < $size; $i++) {
			$splits[$i] = new Collection();
		}

		$position = 0;

		foreach($this as $eElement) {

			$splits[$position % $size][] = $eElement;
			$position++;

		}

		return $splits;

	}

	/**
	 * Creates a new collection from an array of values
	 *
	 * @param array $values Array of IDs
	 * @param string $element Element name
	 */
	public static function fromArray(array $values, string $element): Collection {

		$cElement = new Collection();

		foreach($values as $key => $value) {

			$eElement = cast($value, $element);

			if($eElement->notEmpty()) {
				$cElement[$key] = $eElement;
			}

		}

		return $cElement;

	}

	/**
	 * Creates an array from a collection
	 *
	 */
	public function toArray(\Closure $callback, $keys = FALSE): array {

		$values = [];

		foreach($this as $eElement) {

			$output = $callback($eElement);

			if($keys) {
				[$key, $value] = $output;
				$values[$key] = $value;
			} else {
				$values[] = $output;
			}

		}

		return $values;

	}

}

/**
 * Handle set fields
 */
class Set {

	protected int $values;

	/**
	 * Build Set with initial values
	 *
	 */
	public function __construct($values = 0) {

		if(is_array($values)) {
			$this->values = 0;
			foreach($values as $value) {
				$this->values |= (int)$value;
			}
		} else {
			$this->values = (int)$values;
		}

	}

	/**
	 * Manipulate content of Set using bit value
	 *
	 * @param int $bit Bit value (1, 2, 4, 8, 16, ...)
	 * @param bool $newValue New value (TRUE/FALSE) or NULL to get current value
	 * @return \Set
	 */
	public function value(int $bit, bool $newValue = NULL) {

		if($newValue === TRUE) {

			$this->values = $this->values | $bit;
			return $this;

		} else if($newValue === FALSE) {

			$this->values = $this->values & ~$bit;
			return $this;

		} else {
			return (bool)($this->values & $bit);
		}

	}

	/**
	 * Manipulate content of Set using bit position
	 *
	 * @param int $position Bit position (0, 1, 2, 3, 4, ...)
	 * @param bool $newValue New value (TRUE/FALSE) or NULL to get current value
	 * @return \Set
	 */
	public function position(int $position, bool $newValue = NULL) {

		$bit = pow(2, $position);

		return $this->value($bit, $newValue);

	}

	/**
	 * Set all Set values to 0
	 */
	public function reset(): void {
		$this->values = 0;
	}

	/**
	 * Get Set full value
	 *
	 * @return int
	 */
	public function get(): int {
		return $this->values;
	}

	/**
	 * Get all values
	 *
	 * @return int
	 */
	public function values(): array {

		if($this->values === 0) {
			return [];
		}

		$values = [];
		$bits = log($this->values, 2);

		for($i = 0; $i <= $bits; $i++) {
			if($this->position($i)) {
				$values[] = pow(2, $i);
			}
		}

		return $values;
	}

	public function __toString(): string {
		return (string)$this->values;
	}

	public function __sleep(): array {
		return ['values'];
	}

}


/**
 * Describe Element
 */
class Element extends ArrayObject {

	private $ghost = NULL;

	public function setGhost($value) {
		$this->ghost = $value;
	}

	public function add(array $properties): Element {

		foreach($properties as $key => $value) {
			if($this->offsetExists($key) === FALSE) {
				$this->offsetSet($key, $value);
			}
		}

		return $this;

	}

	public function merge(array|ArrayObject $properties): Element {

		foreach($properties as $key => $value) {
			$this->offsetSet($key, $value);
		}

		return $this;

	}

	public function getModule(): string {
		return get_class($this);
	}

	public function notEmpty(): bool {
		return ($this->count() > 0);
	}

	public function empty(): bool {
		return ($this->count() === 0);
	}

	/**
	 * Check validity of an element
	 */
	public function validate(array $tests = []): Element {

		if($this->empty()) {

			if($this->ghost !== NULL) {
				throw new NotExistsAction($this->getModule().' #'.$this->ghost);
			} else {
				throw new NotExistsAction($this->getModule());
			}

		}

		foreach($tests as $test) {

			if(method_exists($this, $test) === FALSE) {
				throw new Exception('Invalid test \''.$test.'\'');
			}

			if($this->$test() === FALSE) {

				if(strpos($test, 'can') === 0) {
					throw new NotAllowedAction($this);
				} else {
					throw new NotExpectedAction($this);
				}

			}

		}

		return $this;

	}

	/**
	 * Get properties for further selection
	 */
	public static function getSelection(): array {
		return ['id'];
	}

	public function expects($keys, callable $callback = NULL): Element {

		$lacks = $this->checkExpected($this, (array)$keys);

		if($lacks) {

			if($callback !== NULL) {
				$callback($this);
			} else {
				throw new ElementException(p(
					'Property '.implode(', ', $lacks).' of Element '.get_class($this).' is not set',
					'Properties '.implode(', ', $lacks).' of Element '.get_class($this).' are not set',
					count($lacks)
				));
			}
		}

		return $this;

	}

	public function extracts($keys): array {

		$output = [];

		foreach($keys as $key) {
			if($this->offsetExists($key)) {
				$output[$key] = $this->offsetGet($key);
			}
		}

		return $output;

	}

	private function checkExpected(Element $e, array $keys): array {

		$lacks = [];

		foreach($keys as $key => $value) {

			if(is_string($key)) {
				$property = $key;
				$value = (array)$value;
			} else if(is_string($value)) {
				$property = $value;
			} else {
				throw new Exception('Invalid keys');
			}

			if($e->offsetExists($property) === FALSE) {
				$lacks[] = $property;
			} else if(is_array($value)) {

				if($e[$key] instanceof Element) {
					$result = $this->checkExpected($e[$key], $value);
				} else if(is_array($e[$key])) {
					$result = array_expects($e[$key], $value, function(array $result) {
						return $result;
					});
				} else {
					$result = ['Invalid type'];
				}

				if($result) {
					$lacks[] = $property.'['.implode(', ', $result).']';
				}

			}

		}

		return $lacks;

	}

	public static function fail(string|FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		throw new Exception('Invalid call of Element::fail()');
	}

	/**
	 * Create an element with properties $properties using $input (ie: $_POST)
	 *
	 * @param array $properties List of properties (ie: ['email', 'sex' (in $input) => 'gender' (element property)]
	 * @param array $input List of values indexed by property name
	 * @param array $callbacks Callback function for additional checks
	 * @param string $for create, update
	 */
	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		$model = $this->model();

		$callbackWrapper = $callbacks['wrapper'] ?? fn($property) => $property;
		$newProperties = [];

		foreach($properties as $key => $property) {

			$value = $input[$property] ?? NULL;
			$newProperties[] = $property;

			$callbacksProperty = [];

			foreach($callbacks as $name => $callback) {

				if(str_contains($name, '.') and $property === explode('.', $name)[0]) {
					$callbacksProperty[$name] = $callback;
				}

			}

			if($model->hasProperty($property)) {

				$callbackCast = $callbacksProperty[$property.'.cast'] ?? function(&$value, $newProperties) use ($model, $property): bool {
					$model->cast($property, $value);
					return TRUE;

				};

				$callbackPrepare = $callbacksProperty[$property.'.prepare'] ?? function(&$value, $newProperties) use ($model, $property): bool {

					if(strpos($model->getPropertyType($property), 'editor') === 0) {
						$value = (new \editor\XmlLib())->fromHtml($value);
					}

					return TRUE;

				};

				$callbackCheck = $callbacksProperty[$property.'.check'] ?? function(&$value, $newProperties) use ($model, $property): bool {

					if(
						$model->isPropertyNull($property) and
						$value === ''
					) {
						$value = NULL;
					}

					return $model->check($property, $value);

				};

				$callbackSet = $callbacksProperty[$property.'.set'] ?? function($value, $newProperties) use ($property) {
					$this[$property] = $value;
				};

				unset($callbacksProperty[$property.'.prepare'], $callbacksProperty[$property.'.check'], $callbacksProperty[$property.'.set']);

				$callbacksSelected = [];
				$callbacksSelected[$property.'.cast'] = $callbackCast;
				$callbacksSelected[$property.'.prepare'] = $callbackPrepare;
				$callbacksSelected[$property.'.check'] = $callbackCheck;
				$callbacksSelected += $callbacksProperty;
				$callbacksSelected[$property.'.set'] = $callbackSet;

			} else {
				$callbacksSelected = $callbacksProperty;
			}

			// Check callback function
			$wrapper = $callbackWrapper($property);
			$success = TRUE;

			foreach($callbacksSelected as $name => $callback) {

				$onError = function() use ($name, $property, $wrapper, &$success) {

					$class = $this->getModule($this);
					$error = explode('.', $name)[1];

					$class::fail($property.'.'.$error, wrapper: $wrapper);

					$success = FALSE;

				};

				try {

					if($callback($value, $newProperties) === FALSE) {
						throw new BuildPropertyError();
					}

				} catch(BuildPropertySkip) {
					break;
				} catch(BuildPropertySuccess) {
				} catch(FailException $e) {
					Fail::log($e, wrapper: $wrapper);
					$success = FALSE;
					break;
				} catch(BuildPropertyError) {
					$onError();
					break;
				} catch(BuildElementError) {
					$onError();
					return $newProperties;
				}

			}

		}

		return $newProperties;

	}

	public function buildIndex(array $properties, array $input, $index, array $callbacks = []): array {

		$callbacks['wrapper'] = function(string $property) use ($index) {
			return $property.'['.$index.']';
		};

		$values = [];

		foreach($input as $inputProperty => $inputValues) {
			if(is_array($inputValues) and array_key_exists($index, $inputValues)) {
				$values[$inputProperty] = $inputValues[$index];
			}
		}

		return $this->build(
			$properties,
			$values,
			$callbacks
		);

	}

	public function buildProperty(string $property, $value, array $callbacks = []): array {

		return $this->build(
			[$property],
			[$property => $value],
			$callbacks
		);

	}

	public function format(string $property, array $options = []): ?string {
		return '';
	}

	public function __toString(): string {

		$output = 'Element::'.get_class($this).' ('."\n";

		foreach($this as $key => $value) {

			if($value instanceof Element or $value instanceof Collection) {
				$output .= '   ['.$key.'] => '.str_replace("\n", "\n   ", trim((string)$value))."\n";
			} else {

				switch(gettype($value)) {

					case 'integer' :
					case 'float' :
						$displayValue = $value."\n";
						break;

					case 'bool' :
						$displayValue = ($value ? 'TRUE' : 'FALSE')."\n";
						break;

					case 'NULL' :
						$displayValue = 'NULL'."\n";
						break;

					case 'string' :
						$displayValue = '"'.$value.'"'."\n";
						break;

					default :
					ob_start();
					var_dump($value);
					$displayValue = ob_get_clean();

				}
				$output .= '   ['.$key.'] => '.$displayValue;
			}

		}

		$output .= ')'."\n";

		return $output;

	}

}

class BuildPropertySkip extends Exception {

}

class BuildPropertySuccess extends Exception {

}

class BuildPropertyError extends Exception {

}

class BuildElementError extends Exception {

}

trait FilterElement {

	public static function POST(mixed $value, string $property, $default = NULL): mixed {
		return self::filter($_POST[$value] ?? NULL, $property, $default);
	}

	public static function GET(mixed $value, string $property, $default = NULL): mixed {
		return self::filter($_GET[$value] ?? NULL, $property, $default);
	}

	public static function REQUEST(mixed $value, string $property, $default = NULL): mixed {
		return self::filter($_REQUEST[$value] ?? NULL, $property, $default);
	}

	public static function INPUT(mixed $value, string $property, $default = NULL): mixed {

		return match(Route::getRequestMethod()) {
			'GET' => self::GET($value, $property, $default),
			'POST' => self::POST($value, $property, $default),
			default => throw new Exception('Unhandled method '.Route::getRequestMethod())
		};

	}

	private static function filter(mixed $value, string $property, $default = NULL): mixed {

		if(str_starts_with($property, '?')) {
			$allowNull = TRUE;
			$property = substr($property, 1);
		} else {
			$allowNull = FALSE;
		}

		self::model()->cast($property, $value);

		if(
			($allowNull or $value !== NULL) and
			self::model()->check($property, $value)
		) {
			return $value;
		} else {
			return is_closure($default) ? $default($value) : $default;
		}

	}

}


class ElementException extends Exception {

}

/**
 * Db value
 *
 * @author Vincent Guth
 */
class Sql {

	protected string $value;
	protected string $type;

	public function __construct(string $value, string $type = 'text') {
		$this->value = $value;
		$this->type = $type;
	}

	public function __toString(): string {
		return (string)$this->value;
	}

	public function getType(): string {
		return $this->type;
	}

}

class PropertyDescriber {

	/**
	 * Property name
	 */
	public ?string $property = NULL;

	/**
	 * Property label
	 */
	public ?string $label = NULL;

	/**
	 * Property type
	 */
	public ?string $type = NULL;

	/**
	 * Property range
	 */
	public ?array $range = NULL;

	/**
	 * Property enum
	 */
	public ?array $enum = NULL;

	/**
	 * Property attributes
	 */
	public array|Closure $attributes = [];

	/**
	 * Property set
	 */
	public ?array $set = NULL;

	/**
	 * Property module
	 */
	public ?string $module = NULL;

	/**
	 * Property default value
	 */
	public $default = NULL;

	/**
	 * List of values for the type
	 */
	public array|Collection $values = [];

	/**
	 * User-defined values
	 */
	private array $magic = [];

	/**
	 * Attributes for dynamic groups
	 */
	public array|Closure $group = [];

	/**
	 * Override field type
	 */
	public $field = 'default';

	public function __construct(string $property, array $values = []) {

		$this->property = $property;

		foreach($values as $key => $value) {
			$this->$key = $value;
		}

	}

	public function __get(string $key) {
		return $this->magic[$key] ?? NULL;
	}

	public function __set(string $key, $value): void {
		$this->magic[$key] = $value;
	}

	public function __toString(): string {
		return (string)$this->label;
	}

}
?>