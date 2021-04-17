<?php
namespace paper;

class Text extends \Element {

	use TextElement;
	use \FilterElement;

	private static ?TextModel $model = NULL;

	const OPEN = 'open';
	const ANSWER = 'answer';
	const FEEDBACK = 'feedback';

	public static function model(): TextModel {
		if(self::$model === NULL) {
			self::$model = new TextModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Text::'.$failName, $arguments, $wrapper);
	}

}


class TextModel extends \ModuleModel {

	protected string $module = 'paper\Text';
	protected string $package = 'paper';
	protected string $table = 'paperText';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'type' => ['enum', [\paper\Text::OPEN, \paper\Text::ANSWER, \paper\Text::FEEDBACK], 'cast' => 'enum'],
			'value' => ['editor16', 'cast' => 'string'],
			'valueUpdatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'valueAuthor' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'ip' => ['ipv4', 'cast' => 'string'],
			'sid' => ['sid', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'type', 'value', 'valueUpdatedAt', 'valueAuthor', 'ip', 'sid'
		]);

		$this->propertiesToModule += [
			'valueAuthor' => 'user\User',
		];

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'valueAuthor' :
				return \user\ConnectionLib::getOnline();

			case 'ip' :
				return getIp();

			case 'sid' :
				return session_id();

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'ip' :
				return $value === NULL ? NULL : (int)first(unpack('l', pack('l', ip2long($value))));

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'ip' :
				return $value === NULL ? NULL : long2ip($value);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): TextModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TextModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): TextModel {
		return $this->where('id', ...$data);
	}

	public function whereType(...$data): TextModel {
		return $this->where('type', ...$data);
	}

	public function whereValue(...$data): TextModel {
		return $this->where('value', ...$data);
	}

	public function whereValueUpdatedAt(...$data): TextModel {
		return $this->where('valueUpdatedAt', ...$data);
	}

	public function whereValueAuthor(...$data): TextModel {
		return $this->where('valueAuthor', ...$data);
	}

	public function whereIp(...$data): TextModel {
		return $this->where('ip', ...$data);
	}

	public function whereSid(...$data): TextModel {
		return $this->where('sid', ...$data);
	}


}


abstract class TextCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Text {

		$e = new Text();

		if($id === NULL) {
			Text::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Text::getSelection();
		}

		if(Text::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Text {

		return new Text(['id' => NULL]);

	}

	public static function create(Text $e): void {

		Text::model()->insert($e);

	}

	public static function update(Text $e, array $properties): void {

		$e->expects(['id']);

		Text::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Text $e, array $properties): void {

		Text::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Text $e): void {

		$e->expects(['id']);

		Text::model()->delete($e);

	}

}


class TextPage extends \ModulePage {

	protected string $module = 'paper\Text';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TextLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TextLib::getPropertiesUpdate()
		);
	}

}
?>