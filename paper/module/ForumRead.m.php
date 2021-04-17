<?php
namespace paper;

class ForumRead extends \Element {

	use ForumReadElement;
	use \FilterElement;

	private static ?ForumReadModel $model = NULL;

	public static function model(): ForumReadModel {
		if(self::$model === NULL) {
			self::$model = new ForumReadModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('ForumRead::'.$failName, $arguments, $wrapper);
	}

}


class ForumReadModel extends \ModuleModel {

	protected string $module = 'paper\ForumRead';
	protected string $package = 'paper';
	protected string $table = 'paperForumRead';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'forum' => ['element32', 'paper\Forum', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'discoveredAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'forum', 'user', 'discoveredAt'
		]);

		$this->propertiesToModule += [
			'forum' => 'paper\Forum',
			'user' => 'user\User',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'forum']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'discoveredAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): ForumReadModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ForumReadModel {
		return parent::where(...$data);
	}

	public function whereForum(...$data): ForumReadModel {
		return $this->where('forum', ...$data);
	}

	public function whereUser(...$data): ForumReadModel {
		return $this->where('user', ...$data);
	}

	public function whereDiscoveredAt(...$data): ForumReadModel {
		return $this->where('discoveredAt', ...$data);
	}


}


abstract class ForumReadCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): ForumRead {

		$e = new ForumRead();

		if($id === NULL) {
			ForumRead::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = ForumRead::getSelection();
		}

		if(ForumRead::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): ForumRead {

		return new ForumRead(['id' => NULL]);

	}

	public static function create(ForumRead $e): void {

		ForumRead::model()->insert($e);

	}

	public static function update(ForumRead $e, array $properties): void {

		$e->expects(['id']);

		ForumRead::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, ForumRead $e, array $properties): void {

		ForumRead::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(ForumRead $e): void {

		$e->expects(['id']);

		ForumRead::model()->delete($e);

	}

}


class ForumReadPage extends \ModulePage {

	protected string $module = 'paper\ForumRead';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ForumReadLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ForumReadLib::getPropertiesUpdate()
		);
	}

}
?>