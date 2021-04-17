<?php
namespace dev;

/**
 * Generate module files from DIA diagram
 *
 * @author Vincent Guth
 */
class DiaLib {

	protected $package;
	protected $xml;

	protected $classes = [];
	protected $generalizations = [];

	const DIA_NAMESPACE = 'http://www.lysator.liu.se/~alla/dia/';
	const BASE_CONTEXT = '/dia:diagram/dia:layer[position()=1]';

	private $buildDefaults = [];
	private $buildDynamicRanges = [];

	/**
	 * Get selected classes
	 *
	 * @return array
	 */
	public function getClasses() {
		return array_keys($this->classes);
	}

	/**
	 * Build modules
	 *
	 * @param string $className
	 */
	public function buildModule(string $className) {

		if(isset($this->classes[$className]) === FALSE) {
			throw new \Exception("Class '".$className."' does not exist");
		}

		list($package) = explode('\\', $className);
		$class = $this->classes[$className];

		$content = [];

		$content[] = '<?php';
		$content[] = 'namespace '.$package.';';
		$content[] = '';
		$content = array_merge($content, $this->buildElement($package, $class));
		$content[] = '';
		$content[] = '';

		if($this->hasGeneralization($package, $class['name'])) {
			$extends = 'extends \\'.$this->getGeneralization($package, $class['name']).'Model ';
		} else {
			$extends = 'extends \\ModuleModel ';
		}

		if($class['abstract']) {
			$abstract = 'abstract ';
		} else {
			$abstract = '';
		}

		if($package === lcfirst($class['name'])) {
			$table = $package;
		} else {
			$table = $package.$class['name'];
		}

		$content[] = $abstract.'class '.$class['name'].'Model '.$extends.'{';

		$content[] = '';
		$content[] = '	protected string $module = \''.$package.'\\'.$class['name'].'\';';
		$content[] = '	protected string $package = \''.$package.'\';';
		$content[] = '	protected string $table = \''.$table.'\';';
		$content = array_merge($content, $this->createSplit($class, $package));
		$content = array_merge($content, $this->createCache($class));
		$content = array_merge($content, $this->createCharset($class));
		$content = array_merge($content, $this->createStorage($class));
		$content[] = '';
		$content[] = '	public function __construct() {';
		$content[] = '';
		$content[] = '		parent::__construct();';
		$content[] = '';
		$content = array_merge($content, $this->createMeta($class));
		$content = array_merge($content, $this->createIndexes($class));
		$content = array_merge($content, $this->createSpatials($class));
		$content = array_merge($content, $this->createUniques($class));
		$content = array_merge($content, $this->createSearchs($class));
		$content[] = '	}';
		$content[] = '';

		$content = array_merge($content, $this->createDefaults());

		$splitSequence = $this->getField($class, 'split');
		$splitList = $this->getField($class, 'splitlist');

		if($splitSequence) {
			$content = array_merge($content, $this->createSplitSequence($package, $class, $splitSequence));
		} else if($splitList) {
			$content = array_merge($content, $this->createSplitList());
		}

		$encode = $this->getField($class, 'encode');

		if($encode) {
			$content = array_merge($content, $this->createEncodeOrDecode('encode', $encode));
		}

		$decode = $this->getField($class, 'decode');

		if($decode) {
			$content = array_merge($content, $this->createEncodeOrDecode('decode', $decode));
		}

		$content = array_merge($content, $this->createFunctions($class));

		$content[] = '';
		$content[] = '}';
		$content[] = '';
		$content[] = '';

		$content = array_merge($content, $this->buildLib($package, $class));
		$content[] = '';
		$content[] = '';
		$content = array_merge($content, $this->buildPage($package, $class));

		$content[] = '?>';

		$fileContent = implode("\n", $content);
		$file = $this->getDirectory($package, 'module').'/'.$class['name'].'.m.php';

		file_put_contents($file, $fileContent);

	}

	protected function buildElement(string $package, array $class): array {

		if($this->hasGeneralization($package, $class['name'])) {
			$extends = 'extends \\'.$this->getGeneralization($package, $class['name']).' ';
		} else {
			$extends = 'extends \\Element ';
		}

		if($class['abstract']) {
			$abstract = 'abstract ';
		} else {
			$abstract = '';
		}

		$content = [];

		$content[] = $abstract.'class '.$class['name'].' '.$extends.'{';
		$content[] = '';
		$content[] = '	use '.$class['name'].'Element;';
		$content[] = '	use \FilterElement;';
		$content[] = '';

		if($class['abstract'] === FALSE) {
			$content[] = '	private static ?'.$class['name'].'Model $model = NULL;';
			$content[] = '';
		}

		$content = array_merge($content, $this->createConstants($class));

		if($class['abstract'] === FALSE) {

			$content[] = '	public static function model(): '.$class['name'].'Model {';
			$content[] = '		if(self::$model === NULL) {';
			$content[] = '			self::$model = new '.$class['name'].'Model();';
			$content[] = '		}';
			$content[] = '		return self::$model;';
			$content[] = '	}';
			$content[] = '';

			$content[] = '	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {';
			$content[] = '		return \Fail::log(\''.$class['name'].'::\'.$failName, $arguments, $wrapper);';
			$content[] = '	}';
			$content[] = '';

		}

		$content[] = '}';

		return $content;

	}

	protected function buildLib(string $package, array $class): array {

		$content = [];

		$content[] = 'abstract class '.$class['name'].'Crud extends \ModuleCrud {';
		$content[] = '';
		$content[] = '	public static function getById($id, array $properties = []): '.$class['name'].' {';
		$content[] = '';
		$content[] = '		$e = new '.$class['name'].'();';
		$content[] = '';
		$content[] = '		if($id === NULL) {';
		$content[] = '			'.$class['name'].'::model()->reset();';
		$content[] = '			return $e;';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		if($properties === []) {';
		$content[] = '			$properties = '.$class['name'].'::getSelection();';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		if('.$class['name'].'::model()';
		$content[] = '			->select($properties)';
		$content[] = '			->whereId($id)';
		$content[] = '			->get($e) === FALSE) {';
		$content[] = '				$e->setGhost($id);';
		$content[] = '		}';
		$content[] = '';
		$content[] = '		return $e;';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';

		if($class['fields']['fqn']) {

			$content[] = '	public static function getByFqn(string $fqn, array $properties = []): '.$class['name'].' {';
			$content[] = '';
			$content[] = '		$e = new '.$class['name'].'();';
			$content[] = '';
			$content[] = '		if(empty($fqn)) {';
			$content[] = '			'.$class['name'].'::model()->reset();';
			$content[] = '			return $e;';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		if($properties === []) {';
			$content[] = '			$properties = '.$class['name'].'::getSelection();';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		if('.$class['name'].'::model()';
			$content[] = '			->select($properties)';
			$content[] = '			->whereFqn($fqn)';
			$content[] = '			->get($e) === FALSE) {';
			$content[] = '				$e->setGhost($fqn);';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		return $e;';
			$content[] = '';
			$content[] = '	}';
			$content[] = '';

			$content[] = '	public static function getByFqns(array $fqns, array $properties = []): \Collection {';
			$content[] = '';
			$content[] = '		if(empty($fqns)) {';
			$content[] = '			'.$class['name'].'::model()->reset();';
			$content[] = '			return new \Collection();';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		if($properties === []) {';
			$content[] = '			$properties = '.$class['name'].'::getSelection();';
			$content[] = '		}';
			$content[] = '';
			$content[] = '		return '.$class['name'].'::model()';
			$content[] = '			->select($properties)';
			$content[] = '			->whereFqn(\'IN\', $fqns)';
			$content[] = '			->getCollection(NULL, NULL, \'fqn\');';
			$content[] = '';
			$content[] = '	}';
			$content[] = '';

		}

		$content[] = '	public static function getCreateElement(): '.$class['name'].' {';
		$content[] = '';
		$content[] = '		return new '.$class['name'].'([\'id\' => NULL]);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function create('.$class['name'].' $e): void {';
		$content[] = '';
		$content[] = '		'.$class['name'].'::model()->insert($e);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function update('.$class['name'].' $e, array $properties): void {';
		$content[] = '';
		$content[] = '		$e->expects([\'id\']);';
		$content[] = '';
		$content[] = '		'.$class['name'].'::model()';
		$content[] = '			->select($properties)';
		$content[] = '			->update($e);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function updateCollection(\Collection $c, '.$class['name'].' $e, array $properties): void {';
		$content[] = '';
		$content[] = '		'.$class['name'].'::model()';
		$content[] = '			->select($properties)';
		$content[] = '			->whereId(\'IN\', $c)';
		$content[] = '			->update($e->extracts($properties));';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '	public static function delete('.$class['name'].' $e): void {';
		$content[] = '';
		$content[] = '		$e->expects([\'id\']);';
		$content[] = '';
		$content[] = '		'.$class['name'].'::model()->delete($e);';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';
		$content[] = '}';

		return $content;

	}

	protected function buildPage(string $package, array $class): array {

		if($this->hasGeneralization($package, $class['name'])) {
			$extends = 'extends \\'.$this->getGeneralization($package, $class['name']).'Page ';
		} else {
			$extends = 'extends \\ModulePage ';
		}

		if($class['abstract']) {
			$abstract = 'abstract ';
		} else {
			$abstract = '';
		}

		$content = [];

		$content[] = $abstract.'class '.$class['name'].'Page '.$extends.'{';
		$content[] = '';
		$content[] = '	protected string $module = \''.$package.'\\'.$class['name'].'\';';
		$content[] = '';

		if($class['abstract'] === FALSE) {

			$content[] = '	public function __construct(';
			$content[] = '	   ?\Closure $start = NULL,';
			$content[] = '	   \Closure|array|null $propertiesCreate = NULL,';
			$content[] = '	   \Closure|array|null $propertiesUpdate = NULL';
			$content[] = '	) {';
			$content[] = '		parent::__construct(';
			$content[] = '		   $start,';
			$content[] = '		   $propertiesCreate ?? '.$class['name'].'Lib::getPropertiesCreate(),';
			$content[] = '		   $propertiesUpdate ?? '.$class['name'].'Lib::getPropertiesUpdate()';
			$content[] = '		);';
			$content[] = '	}';
			$content[] = '';

		}

		$content[] = '}';

		return $content;

	}

	protected function createSplitSequence(string $package, array $class, array $split): array {

		$content = [];

		if(first($split['value']) === 'NULL') {
			return $content;
		}

		list($property, $number) = $split['value'];

		if($property === '') {

			$property = 'data';
			$type = NULL;

		} else if($split['value'][0] === 'id') {
			$max = Filter::MAX_INT32;
			$split['value'][2] = 'floor(($id - 1) / floor('.$max.' / $number))';
			$type = 'int';
		} else {

			// The property exists in the element
			if(isset($class['fields']['properties'][$property])) {
				$type = $class['fields']['properties'][$property]['type'];
			}
			// Check if the property is in parent classes
			else {

				if($this->hasGeneralization($package, $class['name'])) {

					// TODO !
					$parentClassName = $this->getGeneralization($package, $class['name']).'Model';

					// Parent class may be abstract
					// Create a new temporary class
					$tmpClassName = '_'.$parentClassName;

					if(class_exists($tmpClassName) === FALSE) {
						eval('class '.$tmpClassName.' extends '.$parentClassName.' { }');
					}

					$mParent = new $tmpClassName();
					$propertyType = $mParent->getPropertyType($property);

					if($mParent->getPropertyType($property) === 'element') {
						$type = $mParent->getPropertyToModule($property);
					} else {
						$type = $propertyType;
					}

				} else {
					trigger_error("Can not split class '".$class['name']."' on property '".$property."'", E_USER_ERROR);
				}

			}

			if(strpos($type, '(') !== FALSE) {
				$type = substr($type, 0, strpos($type, '('));
			}

		}

		if(count($split['value']) < 3) {
			if(
				in_array($type, [
					'int', 'int8', 'int16', 'int24', 'int32', 'int64',
					'float', 'float16', 'float24', 'float32', 'float64',
					'element8', 'element16', 'element24', 'element32', 'element64'
				]) or
				strpos($type, '\\') !== FALSE
			) {
				return $content;
			} else {
				$value = 'crc32($value) % '.$number;
			}
		} else {
			$value = $split['value'][2];
		}

		$content[] = '	public function split($'.$property.'): int {';

		// This is an element
		if(ucfirst($type) === $type or in_array($type, ['element8', 'element16', 'element24', 'element32', 'element64'])) {
			$content[] = '		$'.$property.'->expects([\'id\']);';
			$value = str_replace('$value', '$'.$property.'[\'id\']', $value);
		} else {
			$value = str_replace('$value', '$'.$property.'', $value);
		}


		$value = str_replace('$number', '$this->split', $value);

		$content[] = '		return '.$value.';';
		$content[] = '	}';
		$content[] = '';

		return $content;

	}

	protected function createSplitList(): array {

		$content = [];

		$content[] = '	public function split($value): int {';
		$content[] = '		throw new Exception("Can not use split() on lists");';
		$content[] = '	}';
		$content[] = '';

		return $content;

	}

	protected function createCache(array $class): array {

		$content = [];

		$cache = $this->getField($class, 'cache');

		if($cache) {

			$cacheType = strtolower($cache['value'][0]);

			if($cacheType === 'none' or $cacheType === 'storage') {
				$content[] = '';
				$content[] = '	protected $cache = \''.$cacheType.'\';';
			} else {

				if(count($cache['value']) >= 2) {

					if(count($cache['value']) > 2) {
						$propertiesArray = preg_split('/\s*\|\s*/', $cache['value'][2]);
						$properties = ', [\''.implode('\', \'', $propertiesArray).'\']';
					} else {
						$properties = '';
					}

					$content[] = '	protected $cache = [\''.$cache['value'][0].'\', '.((int)$cache['value'][1]).''.$properties.'];';

				}

			}

		}

		return $content;

	}

	protected function createStorage(array $class): array {

		$content = [];

		$storage = $this->getField($class, 'storage');

		if($storage) {

			$storageDetail = first($storage);

			$content[] = '	protected string $storage = \''.$storageDetail['name'].'\';';

		}

		return $content;


	}

	protected function createCharset(array $class): array {

		$content = [];

		$charset = $this->getField($class, 'charset');

		if($charset) {

			$content[] = '	protected string $charset = \''.$charset['name'].'\';';

		}

		return $content;

	}

	protected function createEncodeOrDecode(string $type, array $entries): array {

		$content = [];

		$content[] = '	public function '.$type.'(string $property, $value) {';
		$content[] = '';
		$content[] = '		switch($property) {';
		$content[] = '';

		foreach($entries as $property => $value) {

			$content[] = '			case \''.$property.'\' :';

			if(is_array($value)) {
				foreach($value as $codeLine) {
					$content[] = '				'.$codeLine;
				}
			} else {
				$content[] = '				return '.$value.';';
			}

			$content[] = '';

		}

		$content[] = '			default :';
		$content[] = '				return parent::'.$type.'($property, $value);';
		$content[] = '';
		$content[] = '		}';
		$content[] = '';
		$content[] = '	}';
		$content[] = '';

		return $content;

	}

	protected function createSplit(array $class, string $package): array {

		$splitSequence = $this->getField($class, 'split');

		if($splitSequence) {

			if($splitSequence['value'][0] === 'NULL') {
				return ["\n".'	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;'];
			} else {

				$property = $splitSequence['value'][0] ? '\''.$splitSequence['value'][0].'\'' : 'NULL';
				$number = $splitSequence['value'][1];

				if(preg_match("/SETTING\((.*)\)/si", $number, $result)) {

					$setting = $result[1];
					if(strpos($setting, '\\') === FALSE) {
						$setting = $package.'\\'.$setting;
					}

					$number = \Setting::get($setting);
				} else {
					$number = (int)$number;
				}

				if($number > 1) {

					return ["\n".'	protected ?string $splitMode = \'sequence\';
	protected ?int $split = '.$number.';
	protected ?string $splitOn = '.$property.';'];

				} else {

					return ["\n".'	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;'];

				}

			}

		}

		$splitList = $this->getField($class, 'splitlist');

		if($splitList) {

			$content = "\n".'	protected ?string $splitMode = \'list\';';
			$content .= "\n".'	protected ?int $split = [\''.implode('\', \'', $splitList['value']).'\'];';

			$splitProperties = $this->getField($class, 'splitproperties');

			if($splitProperties) {
				$content .= "\n".'	protected ?array $splitProperties = [\''.implode('\', \'', $splitProperties['value']).'\'];';
			}

			return [$content];

		}


		return [];


	}

	protected function createMeta(array &$class): array {

		$content = [];

		if(empty($class['fields']['properties'])) {
			return $content;
		}

		$this->buildDefaults = [];
		$this->buildDynamicRanges = [];

		$uniques = $class['fields']['uniques'] ?? [];

		$properties = [];
		$propertiesList = [];
		$propertiesToModule = [];

		foreach($class['fields']['properties'] as $property) {

			// Check type for ID field
			if(
				$property['name'] === 'id' and
				strpos($property['typeName'], 'serial') !== 0 and strpos($property['typeName'], 'int') !== 0
			) {
				throw new \Exception("Property 'id' must be a serial or int in '".$class['name']."'");
			}

			// Check use for serial types
			if(
				strpos($property['typeName'], 'serial') === 0 and
				$property['name'] !== 'id'
			) {
				throw new \Exception("Type '".$property['typeName']."' can only be used for 'id' property in '".$class['name']."'");
			}

			// Save element property
			if(strpos($property['typeName'], 'element') === 0) {
				$propertiesToModule[$property['name']] = strpos($property['typeParams'], 'element') === 0 ? NULL : $property['typeParams'];
			}

			// Check for special types
			$typeMore = [
				'charset' => NULL,
				'collate' => NULL,
			];

			switch($property['typeName']) {
				case 'textFixed' :
				case 'text' :
				case 'text8' :
				case 'text16' :
				case 'text24' :
				case 'text32' :
				case 'email' :
				case 'url' :

					if(preg_match("/CHARSET\((.*?)\)/si", $property['comment'], $result)) {
						$typeMore['charset'] = $result[1];
					}

					if(preg_match("/COLLATE\((.*?)\)/si", $property['comment'], $result)) {
						// if no charset has been specified => utf8
						$typeMore['collate'] = $result[1];
					}
					break;
			}

			$mask = $this->getTypeFilter($property['typeName'], $property['typeParams'], $typeMore, $class['fullName']);

			if($property['typeNull']) {
				$mask .= ', \'null\' => TRUE';
			}

			if(isset($uniques[$property['name']])) {
				$mask .= ', \'unique\' => TRUE';
			}

			$cast = \ModuleModel::getCast($property['name'], $property['typeName']);
			if($cast === NULL) {
				$cast = 'NULL';
			} else {
				$cast = '\''.$cast.'\'';
			}

			$mask .= ', \'cast\' => '.$cast.'';

			// Save default value
			if(empty($property['value']) === FALSE) {

				$default = $this->getDefault($property['typeName'], $property['typeParams'], $property['value'], $class['name']);

				if($default !== NULL) {
					$this->buildDefaults[$property['name']] = $default;
				}

			}

			$properties[$property['name']] = $mask;
			$propertiesList[] = $property['name'];

		}

		if($properties) {
			$content[] = '		$this->properties = array_merge($this->properties, [';
			foreach($properties as $name => $mask) {
				$content[] = '			\''.$name.'\' => ['.$mask.'],';
			}
			$content[] = '		]);';
			$content[] = '';
		}

		if($propertiesList) {
			$content[] = '		$this->propertiesList = array_merge($this->propertiesList, [';
			$content[] = '			\''.implode('\', \'', $propertiesList).'\'';
			$content[] = '		]);';
			$content[] = '';
		}

		if($propertiesToModule) {

			$content[] = '		$this->propertiesToModule += [';
			foreach($propertiesToModule as $name => $type) {
				$content[] = '			\''.$name.'\' => '.($type ? '\''.$type.'\'' : 'NULL').',';
			}
			$content[] = '		];';
			$content[] = '';

		}

		return $content;


	}

	protected function hasProperty(array $class, string $name): bool {

		$fields = $class['fields'];

		if(isset($fields['properties']) === FALSE) {
			return FALSE;
		}

		foreach($fields['properties'] as $property) {
			if($property['name'] === $name)  {
				return TRUE;
			}
		}

		return FALSE;

	}

	protected function createFunctions(array $class): array {

		$content = [];

		if(empty($class['fields']['properties'])) {
			return $content;
		}

		$content[] = '	public function select(...$fields): '.$class['name'].'Model {';
		$content[] = '		return parent::select(...$fields);';
		$content[] = '	}';
		$content[] = '';

		$content[] = '	public function where(...$data): '.$class['name'].'Model {';
		$content[] = '		return parent::where(...$data);';
		$content[] = '	}';
		$content[] = '';

		foreach($class['fields']['properties'] as $property) {

			$name = $property['name'];

			$content[] = '	public function where'.ucfirst($name).'(...$data): '.$class['name'].'Model {';
			$content[] = '		return $this->where(\''.$name.'\', ...$data);';
			$content[] = '	}';
			$content[] = '';

		}

		return $content;


	}

	protected function createSpatials(array $class): array {

		$fields = $class['fields'];

		if(isset($fields['spatials']) === FALSE) {
			return [];
		}

		return $this->createConstraint($fields['spatials'], 'spatial', FALSE);

	}

	protected function createIndexes(array $class): array {

		$fields = $class['fields'];

		if(isset($fields['indexes']) === FALSE) {
			return [];
		}

		return $this->createConstraint($fields['indexes'], 'index', FALSE);

	}

	protected function createUniques(array $class): array {

		$fields = $class['fields'];

		if(isset($fields['uniques']) === FALSE) {
			return [];
		}

		return $this->createConstraint($fields['uniques'], 'unique', FALSE);

	}

	protected function createSearchs(array $class): array {

		$fields = $class['fields'];

		if(isset($fields['searchs']) === FALSE) {
			return [];
		}

		return $this->createConstraint($fields['searchs'], 'search', FALSE);

	}

	protected function createConstraint(array $constraints, string $name, string $key): array {

		$content = [];
		$content[] = '		$this->'.$name.'Constraints = array_merge($this->'.$name.'Constraints, [';

		$position = 0;

		foreach($constraints as $constraint) {

			$comma = ($position === count($constraints) - 1) ? '' : ',';
			$properties = [];


			if($key) {

				$valueKey = $constraint['value'][0];

				foreach(array_slice($constraint['value'], 1) as $value) {
					$properties[] = "'".$value."'";
				}

				if(count($properties) > 1) {
					$content[] = '			\''.$valueKey.'\' => ['.implode(', ', $properties).']'.$comma;
				} else {
					$content[] = '			\''.$valueKey.'\' => '.$properties[0].$comma;
				}

			} else {
				foreach($constraint['value'] as $value) {
					if(is_array($value)) {
						$properties[] = '['.implode(', ', array_map(function($value) { return "'".$value."'"; }, $value)).']'.$comma;
					} else {
						$properties[] = "'".$value."'";
					}
				}
				$content[] = '			['.implode(', ', $properties).']'.$comma;
			}

			$position++;

		}

		$content[] = '		]);';
		$content[] = '';

		return $content;

	}

	protected function createConstants(array $class): array {

		$content = [];

		if(isset($class['fields']['properties']) === FALSE) {
			return $content;
		}

		$properties = $class['fields']['properties'];

		$constants = $this->getField($class, 'constant');
		$constantsUsed = [];

		foreach($constants as $constant) {

			list($name, $value) = $constant['value'];

			$constantsUsed[] = $name;

			if(is_numeric($value)) {
				$value = ((int)$value = (int)ceil($value)) ? (int)$value : (float)$value;
			} else if(is_string($value)) {
				$value = preg_replace("/[';\"]/", "", $value);
			}

			$content[] = '	const '.$name.' = '.var_export($value, TRUE).';';

		}

		if($constants) {
			$content[] = '	';
		}

		foreach($properties as $property) {

			$type = $property['type'];
			$position = 1;

			if(preg_match("/^enum\((.*)\)(\s*,.*)?$/si", $type, $result)) {

				if(preg_match("/PHP\((.*)\)/si", $result[1]) > 0) {
					continue;
				}

				$what = 'enum';

			} else if(preg_match("/^set\((.*)\)(\s*,.*)?$/si", $type, $result)) {
				$what = 'set';
			} else {
				continue;
			}

			$constantsEnum = preg_split("/\s*,\s*/si", $result[1]);
			$hasConstants = FALSE;

			foreach($constantsEnum as $constantEnum) {

				if(strpos($constantEnum, '::') === FALSE) {

					if(in_array($constantEnum, $constantsUsed) === TRUE) {
						continue;
					}

					$hasConstants = TRUE;

					if($what === 'enum') {
						$value = '\''.strtolower(str_replace('_', '-', $constantEnum)).'\'';
					} else if($what === 'set') {
						$value = $position;
						$position *= 2;
					}

					if($constantEnum !== '?') {
						$content[] = '	const '.$constantEnum.' = '.$value.';';
					}

					$constantsUsed[] = $constantEnum;

				}

			}

			if($hasConstants) {
				$content[] = '';
			}

		}

		$constantsSet = $this->getField($class, 'constants');

		if($constantsSet) {

			foreach($constantsSet as $constants) {

				foreach($constants['value'] as $constant) {
					$content[] = '	const '.$constant.' = \''.strtolower($constant).'\';';
				}

				$content[] = '';

			}

		}

		return $content;

	}

	protected function createDefaults(): array {

		$content = [];

		if($this->buildDefaults) {

			$content[] = '	public function getDefaultValue(string $property) {';
			$content[] = '';
			$content[] = '		switch($property) {';
			$content[] = '';

			foreach($this->buildDefaults as $name => $default) {

				$php = $this->getDefaultValue($default);

				$content[] = '			case \''.$name.'\' :';
				$content[] = '				return '.$php.';';
				$content[] = '';

			}

			$content[] = '			default :';
			$content[] = '				return parent::getDefaultValue($property);';
			$content[] = '';

			$content[] = '		}';
			$content[] = '';

			$content[] = '	}';
			$content[] = '';

		}

		return $content;

	}

	protected function getDefaultValue(array $default): string {

		list($mode, $type, $value) = $default;

		switch($mode) {

			case 'php' :
				return $value;

			case 'sql' :
				return "new \Sql('".$value."')";

			case 'special' :

				switch($value) {

					case 'ip' :
						return "getIp()";

					case 'host' :
						return "gethostbyaddr(getIp())";

					case 'sid' :
						return "session_id()";

					case 'user' :
						return "\user\ConnectionLib::getOnline()";

					case 'now' :

						switch($type) {

							case 'date' :
								return "new \Sql('CURDATE()')";

							case 'time' :
								return "new \Sql('CURTIME()')";

							case 'datetime' :
								return "new \Sql('NOW()')";

							case 'week' :
								return 'currentWeek()';

							case 'month' :
								return "new \Sql('DATE_FORMAT(CURDATE(), \\'%Y-%m\\')')";

						}

				}

		}

		return 'NULL';

	}

	protected function getType(string $content, string $name): array {

		$type = NULL;
		$params = NULL;
		$null = FALSE;

		$number = preg_match_all("/([a-z\\\\0-9]+)(\((.*)\))?(,|$)/si", $content, $result);

		if($number === 0) {
			throw new \Exception("Empty type detected for property '".$name."' (".$content.")");
		}

		for($i = 0; $i < $number; $i++) {

			if($result[1][$i] === 'null') {
				$null = TRUE;
				continue;
			} else {
				$type = $result[1][$i];
				$params = $result[3][$i];
			}

			if($params === '') {
				$params = NULL;
			} else {

				$convert = function(&$params) {

					$params = preg_split("/\s*,\s*/", $params);

					foreach($params as $key => $value) {
						if(strtolower($value) === 'null') {
							$params[$key] = NULL;
						}
					}

				};

				switch($type) {

					case 'textFixed' :
					case 'text8' :
					case 'text16' :
					case 'text24' :
					case 'text32' :
					case 'text' :
					case 'editor8' :
					case 'editor16' :
					case 'editor24' :
					case 'editor32' :
					case 'editor' :
					case 'binary8' :
					case 'binary16' :
					case 'binary24' :
					case 'binary32' :
					case 'binary' :
					case 'json' :
					case 'int8' :
					case 'int16' :
					case 'int24' :
					case 'int32' :
					case 'int64' :
					case 'int' :
					case 'float16' :
					case 'float24' :
					case 'float32' :
					case 'float64' :
					case 'float' :
					case 'binaryFixed' :
					case 'date' :
					case 'datetime' :
					case 'week' :
					case 'month' :
					case 'set' :
					case 'point' :
					case 'polygon' :

						$convert($params);

						break;

					case 'enum' :

						if(preg_match("/PHP\((.*)\)/si", $params, $typeResult) > 0) {
							$params = $typeResult[1];
						} else {
							$convert($params);
						}

						break;

					case 'collection' :

						if(preg_match("/^([a-z\\\\0-9]+)(\((.*)\))?,\s*([0-9]+)$/si", $params, $typeResult) === 0) {
							throw new \Exception("Invalid syntax for property '".$name."' (".$content.")");
						}

						$possibleListTypes = ['serial8', 'serial16', 'serial32', 'serial64'];

						if(in_array($typeResult[1], $possibleListTypes) === FALSE) {
							throw new \Exception("Invalid type for property '".$name."' (".$content."), expected ".implode(', ', $possibleListTypes)."");
						}

						$params = [
							$typeResult[1], // type such as int32, user\User
							$typeResult[4], // list size
						];

						$params[] = NULL; // min
						$params[] = NULL; // max

						break;

				}

			}

			// Transform generic types (float, int, serial...)
			switch($type) {

				case 'text' :
				case 'editor' :
				case 'binary' :
					$type = $type.'24';
					break;

				case 'float' :
					$type = $type.'32';
					break;

				case 'int' :
				case 'serial' :

					if(is_array($params) === FALSE or count($params) !== 2) {
						$type = $type.'32';
					} else {

						list($min, $max) = $params;

						$min = is_numeric($min) ? (int)$min : NULL;
						$max = is_numeric($max) ? (int)$max : NULL;

						if($min < 0 or $min === NULL) {
							if($min === NULL) {
								$type = $type.'32';
							} else if($min >= -128 and $max <= 127) {
								$type = $type.'8';
							} else if($min >= -32768 and $max <= 32767) {
								$type = $type.'16';
							} else {
								$type = $type.'32';
							}
						} else { // $min >= 0
							if($max === NULL) {
								$type = $type.'32';
							} else if($max <= 255) {
								$type = $type.'8';
							} else if($max <= 65535) {
								$type = $type.'16';
							} else {
								$type = $type.'32';
							}
						}

					}

					break;

			}

		}

		return [$type, $params, $null];

	}

	protected function getTypeFilter(string $typeName, $typeParams, $typeMore, string $className): string {

		$typeFilter = '\''.$typeName.'\'';

		switch($typeName) {

			case 'element8' :
			case 'element16' :
			case 'element24' :
			case 'element32' :
			case 'element64' :
				if($typeParams) {
					$type = $typeFilter.', \''.$typeParams.'\'';
				} else {
					$type = $typeFilter;
				}
				break;

			case 'enum' :
			case 'set' :

				if(is_array($typeParams)) {

					$list = [];

					foreach($typeParams as $param) {
						if(strpos($param, '::') === FALSE) {
							if($param !== '?') {
								$list[] = '\\'.$className.'::'.$param;
							}
						} else {
							$list[] = $param;
						}
					}

					$typeParams = '['.implode(', ', $list).']';

				}

				$type = $typeFilter.', '.$typeParams;
				break;

			case 'textFixed' :
			case 'text8' :
			case 'text16' :
			case 'text24' :
			case 'text32' :


				if($typeParams === NULL) {
					$type = $typeFilter;
				} else {

					$countParams = count($typeParams);

					switch($countParams) {
						case 1 : // Only charset
							$type = $typeFilter.', \'charset\' => \''.(string)$typeParams[0].'\'';
							break;

						case 2 : // min and max values only
							list($min, $max) = $typeParams;

							$type = $typeFilter;
							$type .= $this->buildRange($min, $max, $className);
							break;

						default : // Nothing
							$type = $typeFilter;
							break;
					}

				}

				break;

			case 'editor8' :
			case 'editor16' :
			case 'editor24' :
			case 'editor32' :
			case 'binaryFixed' :
			case 'binary8' :
			case 'binary16' :
			case 'binary24' :
			case 'binary32' :
			case 'int8' :
			case 'int16' :
			case 'int24' :
			case 'int32' :
			case 'int64' :
			case 'float16' :
			case 'float24' :
			case 'float32' :
			case 'float64' :

				if($typeParams !== NULL and count($typeParams) === 2) {
					list($min, $max) = $typeParams;
					$type = $typeFilter;
					$type .= $this->buildRange($min, $max, $className);
				} else {
					$type = $typeFilter;
				}
				break;

			case 'collection' :
				list($typeList, $size) = $typeParams;
				$type = $typeFilter.', \''.$typeList.'\', '.$size;
				break;

			case 'date' :
			case 'datetime' :
			case 'week' :
			case 'month' :
				if($typeParams !== NULL and count($typeParams) === 2) {
					list($min, $max) = $typeParams;
					$type = $typeFilter;
					$type .= $this->buildRange($min, $max, $className);
				} else {
					$type = $typeFilter;
				}
				break;

			default :
				$type = $typeFilter;
				break;

		}

		if($typeMore['charset'] !== NULL) {
			$type .= ', \'charset\' => \''.$typeMore['charset'].'\'';
		}

		if($typeMore['collate'] !== NULL) {
			$type .= ', \'collate\' => \''.$typeMore['collate'].'\'';
		}

		return $type;

	}

	protected function buildRange($min, $max, string $className): string {

		$range = '';

		foreach(['min' => $min, 'max' => $max] as $type => $value) {

			if(preg_match("/SETTING\((.*)\)/si", $value, $result)) {

				if(strpos($result[1], '\\') === FALSE) {
					list($package) = explode('\\', $className);
					$setting = $package.'\\'.$result[1];
				} else {
					$setting = $result[1];
				}

				$value = '\Setting::get(\''.$setting.'\')';

			} else if(preg_match("/PHP\((.*)\)/si", $value, $result)) {
				$value = $result[1];
			} else if($value === NULL) {
				$value = 'NULL';
			} else if(is_numeric($value)) {
				// Numeric value
			} else {
				$value = '\''.addcslashes($value, '\'').'\'';
			}

			$range .= ', \''.$type.'\' => '.$value;

		}

		return $range;

	}

	protected function getDefault(string $type, $typeParams, array $value, string $className): array {

		$value = first($value);
		if(preg_match("/SPECIAL\((.*)\)/si", $value, $result)) {
			if($result[1] === 'empty') {

				switch($type) {

					case 'int8' :
					case 'int16' :
					case 'int24' :
					case 'int32' :
					case 'int64' :
						return ['php', $type, "0"];
					case 'set' :
						return ['php', $type, "new \Set(0)"];

					case 'float16' :
					case 'float24' :
					case 'float32' :
					case 'float64' :
						return ['php', $type, "0.0"];

					case 'bool' :
						return ['php', $type, "FALSE"];

					case 'date' :
						return ['php', $type, "'0000-00-00'"];

					case 'datetime' :
						return ['php', $type, "'0000-00-00 00:00:00'"];

					case 'week' :
						return ['php', $type, "'0000-W00'"];

					case 'month' :
						return ['php', $type, "'0000-00'"];

					case 'json':
					case 'polygon':
						return ['php', $type, "[]"];

					case 'point':
						return ['php', $type, "[0, 0]"];

					default :
						return ['php', $type, "''"];

				}

			} else if($result[1] === 'null') {

				return ['php', $type, "NULL"];

			} else {
				return ['special', $type, $result[1]];
			}
		} else if(preg_match("/ID\((.*)\)/si", $value, $result)) {
			return ['php', $type, '[\'id\' => \''.$result[1].'\']'];
		} else if(preg_match("/PHP\((.*)\)/si", $value, $result)) {
			return ['php', $type, $result[1]];
		} else if(preg_match("/SQL\((.*)\)/si", $value, $result)) {
			return ['sql', $type, $result[1]];
		} else {

			switch($type) {

				case 'int8' :
				case 'int16' :
				case 'int24' :
				case 'int32' :
				case 'int64' :
					$default = strpos($value, '::') !== FALSE ? $value : (int)$value;
					break;

				case 'float16' :
				case 'float24' :
				case 'float32' :
				case 'float64' :
					$default = strpos($value, '::') !== FALSE ? $value : (float)$value;
					break;

				case 'point' :
				case 'polygon' :
					$default = strpos($value, '::') !== FALSE ? $value : '['.implode(', ', preg_split('/\s*,\s*/si', $value)).']';
					break;

				case 'textFixed' :
				case 'binaryFixed' :
				case 'binary8' :
				case 'binary16' :
				case 'binary24' :
				case 'binary32' :
				case 'text8' :
				case 'text16' :
				case 'text24' :
				case 'text32' :
				case 'editor8' :
				case 'editor16' :
				case 'editor24' :
				case 'editor32' :
				case 'pcre' :
				case 'email' :
				case 'url' :
				case 'pcre' :
				case 'fqn' :
					$default = "\"".addcslashes($value, '\\"')."\"";
					break;

				case 'enum' :
					$default = $this->getConstant($value, $className);
					break;

				case 'set' :
					if($value === '*') {

						$defaultValues = [];
						foreach($typeParams as $typeParam) {
							$defaultValues[] = $this->getConstant($typeParam, $className);
						}

					} else if($value === '0') {
						$defaultValues = ['0'];
					} else {

						$defaultValues = preg_split("/\s*\|\s*/si", $value);
						foreach($defaultValues as $key => $defaultValue) {
							$defaultValues[$key] = $this->getConstant($defaultValue, $className);
						}

					}

					$default = 'new \Set('.implode(' | ', $defaultValues).')';
					break;

				case 'bool' :
					$default = ($value === 'TRUE' ? 'TRUE' : 'FALSE');
					break;

				case 'date' :
				case 'datetime' :
				case 'week' :
				case 'month' :
					$default = "\"".addcslashes($value, '\\"')."\"";
					break;

				default :
					return ['php', $type, $value];

			}

			return ['php', $type, $default];

		}

	}


	/**
	 * Build environment for the specified DIA file
	 *
	 */
	public function load() {

		foreach(\Package::getList() as $package => $app) {

			foreach(glob(\Package::getPath($package).'/*.dia') as $file) {

				$this->xml = @simplexml_load_file($file);

				if($this->xml === FALSE) {
					throw new \Exception("File '".$file."' is not a XML file");
				}

				$elements = $this->xml->xpath(self::BASE_CONTEXT);

				if(empty($elements)) {
					throw new \Exception("The XML file '".$file."' is not a DIA file");
				}

				$this->initGeneralization($package, self::BASE_CONTEXT);
				$this->initClasses($package, self::BASE_CONTEXT);

			}

		}

		$this->initElements();
		$this->initLists();

	}

	protected function initElements() {

		foreach($this->classes as $className => $class) {

			if(empty($class['fields']['properties'])) {
				continue;
			}

			foreach($class['fields']['properties'] as $propertyName => $property) {

				if(strpos($property['typeName'], '\\') !== FALSE) {

					if(isset($this->classes[$property['typeName']]) === FALSE) {
						throw new \Exception('Could not find element '.$property['typeName'].' for property \''.$propertyName.'\' in class \''.$className.'\'');
					} else {

						// Search for ID in the given class
						$typeId = $this->getTypeFromClass($property['typeName'], 'id');

						if($typeId === NULL) {
							throw new \Exception('Missing ID field in class '.$property['typeName'].' for property \''.$className.'::'.$propertyName.'\'');
						}

						$this->classes[$className]['fields']['properties'][$propertyName]['typeParams'] = $property['typeName']; // Element name is a param
						$this->classes[$className]['fields']['properties'][$propertyName]['typeName'] = str_replace(['serial', 'int'], ['element', 'element'], $typeId); // Type if elementX

					}

				}

			}

		}

	}

	protected function initLists() {

		foreach($this->classes as $className => $class) {

			if(empty($class['fields']['properties'])) {
				continue;
			}

			foreach($class['fields']['properties'] as $propertyName => $property) {

				if($property['typeName'] === 'enum') {

					$this->classes[$className]['fields']['encode'][$property['name']] =  [
						'return ($value === NULL) ? NULL : (string)$value;'
					];

				} else if($property['typeName'] === 'collection') {

					list($typeList) = $property['typeParams'];

					switch($typeList) {

						case 'serial8' :
							$format = 'C';
							break;
						case 'serial16' :
							$format = 'S';
							break;
						case 'serial32' :
							$format = 'L';
							break;
						case 'serial64' :
							$format = 'Q';
							break;

					}

					switch($typeList) {

						case 'serial8' :
						case 'serial16' :
						case 'serial32' :
						case 'serial64' :

							$this->classes[$className]['fields']['encode'][$property['name']] =  [
								'if($value instanceof \Collection) {',
								'	return pack(\''.$format.'*\', ...$value->getColumn(\'id\'));',
								'} else {',
								'	return NULL;',
								'}'
							];

							$this->classes[$className]['fields']['decode'][$property['name']] =  [
								'if($value !== NULL) {',
								'	$c = new \Collection;',
								'	foreach(unpack(\''.$format.'*\', $value) as $id) {',
								'		$c[] = new $this->module([\'id\' => $id]);',
								'	}',
								'	return $c;',
								'} else {',
								'	return NULL;',
								'}'
							];

							break;

					}

				}

			}

		}

	}

	protected function initGeneralization(string $package, string $context) {

		$generalizations = $this->xml->xpath($context."/dia:object[@type='UML - Generalization']/dia:connections");

		if(empty($generalizations)) {
			return;
		}

		foreach($generalizations as $generalization) {

			$key = NULL;
			$value = NULL;

			foreach($generalization->children(self::DIA_NAMESPACE) as $entry) {

				$attributes = $entry->attributes();

				$id = $attributes['to'];

				$class = $this->getClassNameFromId($context, $id);

				switch($attributes['handle']) {

					case "0" :
						$value = $class;
						break;

					case "1" :
						$key = $class;
						break;

				}

			}

			if($key !== NULL and $value !== NULL) {
				$this->generalizations[$package.'\\'.$key] = strpos($value, '\\') === FALSE ? $package.'\\'.$value : $value;
			} else {
				trigger_error("Invalid generalization in diagram", E_USER_ERROR);
				exit;
			}

		}

	}

	protected function initClasses(string $package, string $context) {

		$classes = $this->xml->xpath($context."/dia:object[@type='UML - Class']");
		$classesTable = [];
		$classesList = [];

		foreach($classes as $class) {

			$classTable = $this->initClass($class);

			if(isset($classTable['fields'])) {

				if(isset($classTable['fields']['properties']) or $classTable['visible_attributes']) {
					if($this->hasGeneralization($package, $classTable['name']) === FALSE) {
						$fullName = $package.'\\'.$classTable['name'];
						$this->classes[$fullName] = $classTable + ['fullName' => $fullName];
					} else {
						$classesTable[$classTable['name']] = $classTable;
					}
					$classesList[] = $classTable['name'];
				}

			}

		}

		foreach($classesTable as $className => $classTable) {

			$parentClassName = $this->getGeneralization($package, $className);

			if(in_array($parentClassName, $classesList) === FALSE) {
				$fullName = $package.'\\'.$classTable['name'];
				$this->classes[$package.'\\'.$className] = $classTable + ['fullName' => $fullName];
				unset($classesTable[$className]);
			}

		}

		// Sort classes
		while($classesTable) {

			foreach($classesTable as $className => $classTable) {

				$parentClassName = $this->getGeneralization($package, $className);

				if(isset($this->classes[$package.'\\'.$parentClassName])) {
					$fullName = $package.'\\'.$classTable['name'];
					$this->classes[$package.'\\'.$className] = $classTable + ['fullName' => $fullName];
					unset($classesTable[$className]);
				}

			}

		}

	}

	protected function initClass(\SimpleXMLElement $class): array {

		$classTable = [];

		foreach($class->children(self::DIA_NAMESPACE) as $entry) {

			$attributes = $entry->attributes();
			$name = (string)$attributes['name'];

			switch($name) {

				case 'name' :
				case 'comment' :
					$children = $entry->children(self::DIA_NAMESPACE);
					$content = (string)current($children);
					$classTable[$name] = trim($content, '#');
					break;

				case 'abstract' :
				case 'visible_attributes' :
					$children = $entry->children(self::DIA_NAMESPACE);
					$boolean = current($children);
					$classTable[$name] = ((string)$boolean->attributes()->val === 'true') ? TRUE : FALSE;
					break;

				case 'attributes' :

					$classTable['fields'] = $this->initProperties($entry);
					break;

			}

		}

		return $classTable;

	}

	protected function initProperties(\SimpleXMLElement $class): array {

		$propertiesTable = [
			'fqn' => FALSE
		];

		foreach($class->children(self::DIA_NAMESPACE) as $properties) {

			$propertyTable = [];

			foreach($properties as $property) {

				$attributes = $property->attributes();
				$name = (string)$attributes['name'];

				$children = $property->children(self::DIA_NAMESPACE);
				$content = (string)current($children);
				$value = trim($content, '#');

				switch($name) {

					case 'name' :
					case 'type' :
					case 'comment' :
						$propertyTable[$name] = trim($content, '#');
						break;
					case 'value' :
						if($value === '') {
							$propertyTable[$name] = [];
						} else {
							$propertyTable[$name] = [$value];
						}
						break;

				}

			}

			if(preg_match("/^SPATIAL\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'spatials';
				$limit = -1;
			} else if(preg_match("/^INDEX\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'indexes';
				$limit = -1;
			} else if(preg_match("/^UNIQUE\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'uniques';
				$limit = -1;
			} else if(preg_match("/^SEARCH\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'searchs';
				$limit = -1;
			} else if(preg_match("/^PRIVILEGE\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'privilege';
				$limit = -1;
			} else if(preg_match("/^CHARSET\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'charset';
				$limit = 1;
			} else if(preg_match("/^CONSTANT\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'constant';
				$limit = 2;
			} else if(preg_match("/^CONSTANTS\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'constants';
				$limit = -1;
			} else if(preg_match("/^STORAGE\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'storage';
				$limit = 1;
			} else if(preg_match("/^CONNECTION\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'connection';
				$limit = 1;
			} else if(preg_match("/^CACHE\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'cache';
				$limit = 3;
			} else if(preg_match("/^SPLIT\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'split';
				$limit = 3;
			} else if(preg_match("/^SPLITLIST\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'splitlist';
				$limit = -1;
			} else if(preg_match("/^SPLITPROPERTIES\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'splitproperties';
				$limit = -1;
			} else if(preg_match("/^DECODE\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'decode';
				$limit = 2;
			} else if(preg_match("/^ENCODE\((.*)\)/msi", $propertyTable['name'], $result)) {
				$type = 'encode';
				$limit = 2;
			} else {
				$type = 'properties';
			}

			if($type === 'properties') {

				[
					$propertyTable['typeName'],
					$propertyTable['typeParams'],
					$propertyTable['typeNull']
				] = $this->getType($propertyTable['type'], $propertyTable['name']);

				if($propertyTable['typeName'] === 'fqn') {

					if($propertyTable['name'] !== 'fqn') {
						throw new \Exception('Type \'fqn\' must be \'fqn\' property name');
					}

					$propertiesTable['uniques'][$propertyTable['name']] = [
						'name' => $propertyTable['name'],
						'value' => [$propertyTable['name']]
					];

					$propertiesTable['fqn'] = TRUE;

				}

			} else {

					$propertyTable['typeName'] = NULL;
					$propertyTable['typeParams'] = NULL;
					$propertyTable['typeNull'] = NULL;

					if($type === 'indexes' or $type === 'uniques') {
						$names = [];
						$values = preg_split("/\s*,\s*/", $result[1], $limit);
						foreach($values as $value) {
							if(preg_match('#^(.+?)\((\d+)\)#', $value, $match)) {
								$names[] = $match[1].'_'.$match[2];
								$propertyTable['value'][] = [$match[1], $match[2]];
							} else {
								$names[] = $value;
								$propertyTable['value'][] = $value;
							}
						}
						$propertyTable['name'] = implode('_', $names);
					} else {
						$propertyTable['value'] = preg_split("/\s*,\s*/", $result[1], $limit);
						$propertyTable['name'] = implode('_', $propertyTable['value']);
					}

			}

			switch($type) {

				case 'privilege' :
				case 'charset' :
				case 'split' :
				case 'splitlist' :
				case 'cache' :
				case 'connection' :

					if(count($propertyTable['value']) < 1) {
						throw new \Exception("Type '".$type."' expects at least one property");
					}

					$propertiesTable[$type] = $propertyTable;
					break;

				case 'splitproperties' :

					if(count($propertyTable['value']) < 1) {
						throw new \Exception("Type '".$type."' expects at least one property");
					}

					$propertiesTable[$type] = $propertyTable;
					break;

				case 'storage' :
				default :
					$propertiesTable[$type][$propertyTable['name']] = $propertyTable;
					break;

			}

			// Special cases
			$encode = NULL;
			$decode = NULL;

			$propertyComment = $propertyTable['comment'];
			$isJsonType = str_starts_with($propertyTable['type'], 'json');

			if(
				preg_match("/OPTION\((.*?)\)/si", $propertyComment, $result) or
				$isJsonType === TRUE // check if json type
			) {
				$options = [];
				if(isset($result[1])) {
					$options = preg_split("/\s*\,\s*/", $result[1]);
				}

				if($isJsonType === TRUE and !in_array('json', $options)) {
					$options[] = 'json';
				}

				$encode = '$value';
				$decode = '$value';

				foreach($options as $option) {

					switch($option) {
						case 'compress' :
							$encode = 'gzcompress('.$encode.', 9)';
							break;
						case 'serialize' :
							$encode = 'serialize('.$encode.')';
							break;
						case 'json' :
							$encode = 'json_encode('.$encode.', JSON_UNESCAPED_UNICODE)';
							break;
					}

				}

				foreach(array_reverse($options) as $option) {

					switch($option) {
						case 'compress' :
							$decode = 'gzuncompress('.$decode.')';
							break;
						case 'serialize' :
							$decode = 'unserialize('.$decode.')';
							break;
						case 'json' :
							$decode = 'json_decode('.$decode.', TRUE)';
							break;
					}

				}

				$encode = '$value === NULL ? NULL : '.$encode;
				$decode = '$value === NULL ? NULL : '.$decode;

			} else if($propertyTable['typeName'] === 'ipv4') {
				$encode = '$value === NULL ? NULL : (int)first(unpack(\'l\', pack(\'l\', ip2long($value))))';
				$decode = '$value === NULL ? NULL : long2ip($value)';
			} else if($propertyTable['typeName'] === 'password' or $propertyTable['typeName'] === 'md5') {
				$encode = '$value === NULL ? NULL : hex2bin($value)';
				$decode = '$value === NULL ? NULL : bin2hex($value)';
			} else if($propertyTable['typeName'] === 'float16') {
				$encode = '$value === NULL ? NULL : $this->recast($value, 100)';
				$decode = '$value === NULL ? NULL : ($value / 100)';
			} else if($propertyTable['typeName'] === 'float24') {
				$encode = '$value === NULL ? NULL : $this->recast($value, 1000)';
				$decode = '$value === NULL ? NULL : ($value / 1000)';
			} else if($propertyTable['typeName'] === 'point') {
				$encode = '$value === NULL ? NULL : new \Sql($this->pdo()->api->getPoint($value))';
				$decode = '$value === NULL ? NULL : json_encode(json_decode($value, TRUE)[\'coordinates\'])';
			} else if($propertyTable['typeName'] === 'polygon') {
				$encode = '$value === NULL ? NULL : new \Sql($this->pdo()->api->getPolygon($value))';
				$decode = '$value === NULL ? NULL : json_encode(json_decode($value, TRUE)[\'coordinates\'][0])';
			}

			if($encode !== NULL) {
				$propertiesTable['encode'][$propertyTable['name']] = $encode;
			}

			if($decode !== NULL) {
				$propertiesTable['decode'][$propertyTable['name']] = $decode;
			}

		}

		return $propertiesTable;

	}

	protected function hasGeneralization(string $package, string $class): bool {
		return isset($this->generalizations[$package.'\\'.$class]);
	}

	protected function getGeneralization(string $package, string $class) {
		return $this->generalizations[$package.'\\'.$class];
	}

	protected function getField(array $class, string $field) {
		return $class['fields'][$field] ?? [];
	}

	protected function getClassNameFromId(string $context, string $id): string {
		$class = $this->xml->xpath($context."/dia:object[@id='".$id."']/dia:attribute[@name='name']/dia:string[position() = 1]");
		return trim((string)$class[0], '#');
	}

	protected function getTypeFromClass(string $className, string $propertyName) {

		$typeId = NULL;

		if(isset($this->classes[$className]['fields']['properties'][$propertyName])) {

			$typeId = $this->classes[$className]['fields']['properties'][$propertyName]['typeName'];

		} else {

			$classGeneralization = $className;

			while(isset($this->generalizations[$classGeneralization])) {

				$classGeneralization = $this->generalizations[$classGeneralization];

				if(isset($this->classes[$classGeneralization]['fields']['properties'][$propertyName])) {
					$typeId = $this->classes[$classGeneralization]['fields']['properties'][$propertyName]['typeName'];
					break;
				}

			}

		}

		return $typeId;

	}

	protected function getDirectory(string $package, string $type): string {

		$directory = \Package::getPath($package).'/'.$type;

		if(is_dir($directory) === FALSE) {
			mkdir($directory, 0755, TRUE);
		}

		return $directory;

	}

	protected function getConstant(string $value, string $className): string {

		if(strpos($value, '::') === FALSE) {
			return $className."::".$value;
		} else {
			return $value;
		}

	}

}
?>
