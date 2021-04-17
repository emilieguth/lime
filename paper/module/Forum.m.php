<?php
namespace paper;

class Forum extends \Element {

	use ForumElement;
	use \FilterElement;

	private static ?ForumModel $model = NULL;

	public static function model(): ForumModel {
		if(self::$model === NULL) {
			self::$model = new ForumModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Forum::'.$failName, $arguments, $wrapper);
	}

}


class ForumModel extends \ModuleModel {

	protected string $module = 'paper\Forum';
	protected string $package = 'paper';
	protected string $table = 'paperForum';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'name' => ['text8', 'min' => 1, 'max' => \Setting::get('paper\nameSizeMax'), 'cast' => 'string'],
			'cleanName' => ['text8', 'cast' => 'string'],
			'description' => ['text24', 'min' => 0, 'max' => \Setting::get('paper\descriptionSizeMax'), 'cast' => 'string'],
			'active' => ['bool', 'cast' => 'bool'],
			'publications' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'position' => ['int24', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'messages' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'deletedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'lastMessage' => ['element32', 'paper\Message', 'null' => TRUE, 'cast' => 'element'],
			'lastMessageAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'cover' => ['text8', 'min' => 30, 'max' => 30, 'charset' => 'ascii', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'name', 'cleanName', 'description', 'active', 'publications', 'position', 'messages', 'createdAt', 'deletedAt', 'lastMessage', 'lastMessageAt', 'cover'
		]);

		$this->propertiesToModule += [
			'lastMessage' => 'paper\Message',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['active']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'active' :
				return FALSE;

			case 'publications' :
				return 0;

			case 'position' :
				return 9999999;

			case 'messages' :
				return 0;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): ForumModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ForumModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): ForumModel {
		return $this->where('id', ...$data);
	}

	public function whereName(...$data): ForumModel {
		return $this->where('name', ...$data);
	}

	public function whereCleanName(...$data): ForumModel {
		return $this->where('cleanName', ...$data);
	}

	public function whereDescription(...$data): ForumModel {
		return $this->where('description', ...$data);
	}

	public function whereActive(...$data): ForumModel {
		return $this->where('active', ...$data);
	}

	public function wherePublications(...$data): ForumModel {
		return $this->where('publications', ...$data);
	}

	public function wherePosition(...$data): ForumModel {
		return $this->where('position', ...$data);
	}

	public function whereMessages(...$data): ForumModel {
		return $this->where('messages', ...$data);
	}

	public function whereCreatedAt(...$data): ForumModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereDeletedAt(...$data): ForumModel {
		return $this->where('deletedAt', ...$data);
	}

	public function whereLastMessage(...$data): ForumModel {
		return $this->where('lastMessage', ...$data);
	}

	public function whereLastMessageAt(...$data): ForumModel {
		return $this->where('lastMessageAt', ...$data);
	}

	public function whereCover(...$data): ForumModel {
		return $this->where('cover', ...$data);
	}


}


abstract class ForumCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Forum {

		$e = new Forum();

		if($id === NULL) {
			Forum::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Forum::getSelection();
		}

		if(Forum::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Forum {

		return new Forum(['id' => NULL]);

	}

	public static function create(Forum $e): void {

		Forum::model()->insert($e);

	}

	public static function update(Forum $e, array $properties): void {

		$e->expects(['id']);

		Forum::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Forum $e, array $properties): void {

		Forum::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Forum $e): void {

		$e->expects(['id']);

		Forum::model()->delete($e);

	}

}


class ForumPage extends \ModulePage {

	protected string $module = 'paper\Forum';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ForumLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ForumLib::getPropertiesUpdate()
		);
	}

}
?>