<?php
namespace notification;

class Type extends \Element {

	use TypeElement;
	use \FilterElement;

	private static ?TypeModel $model = NULL;

	public static function model(): TypeModel {
		if(self::$model === NULL) {
			self::$model = new TypeModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Type::'.$failName, $arguments, $wrapper);
	}

}


class TypeModel extends \ModuleModel {

	protected string $module = 'notification\Type';
	protected string $package = 'notification';
	protected string $table = 'notificationType';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'fqn' => ['fqn', 'unique' => TRUE, 'cast' => 'string'],
			'withReferences' => ['bool', 'cast' => 'bool'],
			'defaultSubscribed' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'fqn', 'withReferences', 'defaultSubscribed'
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['fqn']
		]);

	}

	public function select(...$fields): TypeModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TypeModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): TypeModel {
		return $this->where('id', ...$data);
	}

	public function whereFqn(...$data): TypeModel {
		return $this->where('fqn', ...$data);
	}

	public function whereWithReferences(...$data): TypeModel {
		return $this->where('withReferences', ...$data);
	}

	public function whereDefaultSubscribed(...$data): TypeModel {
		return $this->where('defaultSubscribed', ...$data);
	}


}


abstract class TypeCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Type {

		$e = new Type();

		if($id === NULL) {
			Type::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Type::getSelection();
		}

		if(Type::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getByFqn(string $fqn, array $properties = []): Type {

		$e = new Type();

		if(empty($fqn)) {
			Type::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Type::getSelection();
		}

		if(Type::model()
			->select($properties)
			->whereFqn($fqn)
			->get($e) === FALSE) {
				$e->setGhost($fqn);
		}

		return $e;

	}

	public static function getByFqns(array $fqns, array $properties = []): \Collection {

		if(empty($fqns)) {
			Type::model()->reset();
			return new \Collection();
		}

		if($properties === []) {
			$properties = Type::getSelection();
		}

		return Type::model()
			->select($properties)
			->whereFqn('IN', $fqns)
			->getCollection(NULL, NULL, 'fqn');

	}

	public static function getCreateElement(): Type {

		return new Type(['id' => NULL]);

	}

	public static function create(Type $e): void {

		Type::model()->insert($e);

	}

	public static function update(Type $e, array $properties): void {

		$e->expects(['id']);

		Type::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Type $e, array $properties): void {

		Type::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Type $e): void {

		$e->expects(['id']);

		Type::model()->delete($e);

	}

}


class TypePage extends \ModulePage {

	protected string $module = 'notification\Type';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TypeLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TypeLib::getPropertiesUpdate()
		);
	}

}
?>