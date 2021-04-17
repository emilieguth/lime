<?php
namespace paper;

class Draft extends \Element {

	use DraftElement;
	use \FilterElement;

	private static ?DraftModel $model = NULL;

	public static function model(): DraftModel {
		if(self::$model === NULL) {
			self::$model = new DraftModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Draft::'.$failName, $arguments, $wrapper);
	}

}


class DraftModel extends \ModuleModel {

	protected string $module = 'paper\Draft';
	protected string $package = 'paper';
	protected string $table = 'paperDraft';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'content' => ['json', 'cast' => 'array'],
			'initialContent' => ['json', 'cast' => 'array'],
			'hash' => ['text8', 'min' => 32, 'max' => 32, 'charset' => 'ascii', 'cast' => 'string'],
			'author' => ['element32', 'user\User', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'savedAt' => ['datetime', 'cast' => 'string'],
			'invalidatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'content', 'initialContent', 'hash', 'author', 'createdAt', 'savedAt', 'invalidatedAt'
		]);

		$this->propertiesToModule += [
			'author' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['author', 'hash']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'author' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'savedAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'content' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			case 'initialContent' :
				return $value === NULL ? NULL : json_encode($value, JSON_UNESCAPED_UNICODE);

			default :
				return parent::encode($property, $value);

		}

	}

	public function decode(string $property, $value) {

		switch($property) {

			case 'content' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			case 'initialContent' :
				return $value === NULL ? NULL : json_decode($value, TRUE);

			default :
				return parent::decode($property, $value);

		}

	}

	public function select(...$fields): DraftModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DraftModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DraftModel {
		return $this->where('id', ...$data);
	}

	public function whereContent(...$data): DraftModel {
		return $this->where('content', ...$data);
	}

	public function whereInitialContent(...$data): DraftModel {
		return $this->where('initialContent', ...$data);
	}

	public function whereHash(...$data): DraftModel {
		return $this->where('hash', ...$data);
	}

	public function whereAuthor(...$data): DraftModel {
		return $this->where('author', ...$data);
	}

	public function whereCreatedAt(...$data): DraftModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereSavedAt(...$data): DraftModel {
		return $this->where('savedAt', ...$data);
	}

	public function whereInvalidatedAt(...$data): DraftModel {
		return $this->where('invalidatedAt', ...$data);
	}


}


abstract class DraftCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Draft {

		$e = new Draft();

		if($id === NULL) {
			Draft::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Draft::getSelection();
		}

		if(Draft::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Draft {

		return new Draft(['id' => NULL]);

	}

	public static function create(Draft $e): void {

		Draft::model()->insert($e);

	}

	public static function update(Draft $e, array $properties): void {

		$e->expects(['id']);

		Draft::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Draft $e, array $properties): void {

		Draft::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Draft $e): void {

		$e->expects(['id']);

		Draft::model()->delete($e);

	}

}


class DraftPage extends \ModulePage {

	protected string $module = 'paper\Draft';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DraftLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DraftLib::getPropertiesUpdate()
		);
	}

}
?>