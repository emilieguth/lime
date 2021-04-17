<?php
namespace paper;

class Read extends \Element {

	use ReadElement;
	use \FilterElement;

	private static ?ReadModel $model = NULL;

	public static function model(): ReadModel {
		if(self::$model === NULL) {
			self::$model = new ReadModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Read::'.$failName, $arguments, $wrapper);
	}

}


class ReadModel extends \ModuleModel {

	protected string $module = 'paper\Read';
	protected string $package = 'paper';
	protected string $table = 'paperRead';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'discussion' => ['element32', 'paper\Discussion', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'messagesRead' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'lastMessageRead' => ['element32', 'paper\Message', 'cast' => 'element'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'discussion', 'user', 'messagesRead', 'lastMessageRead'
		]);

		$this->propertiesToModule += [
			'discussion' => 'paper\Discussion',
			'user' => 'user\User',
			'lastMessageRead' => 'paper\Message',
		];

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'discussion']
		]);

	}

	public function select(...$fields): ReadModel {
		return parent::select(...$fields);
	}

	public function where(...$data): ReadModel {
		return parent::where(...$data);
	}

	public function whereDiscussion(...$data): ReadModel {
		return $this->where('discussion', ...$data);
	}

	public function whereUser(...$data): ReadModel {
		return $this->where('user', ...$data);
	}

	public function whereMessagesRead(...$data): ReadModel {
		return $this->where('messagesRead', ...$data);
	}

	public function whereLastMessageRead(...$data): ReadModel {
		return $this->where('lastMessageRead', ...$data);
	}


}


abstract class ReadCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Read {

		$e = new Read();

		if($id === NULL) {
			Read::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Read::getSelection();
		}

		if(Read::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Read {

		return new Read(['id' => NULL]);

	}

	public static function create(Read $e): void {

		Read::model()->insert($e);

	}

	public static function update(Read $e, array $properties): void {

		$e->expects(['id']);

		Read::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Read $e, array $properties): void {

		Read::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Read $e): void {

		$e->expects(['id']);

		Read::model()->delete($e);

	}

}


class ReadPage extends \ModulePage {

	protected string $module = 'paper\Read';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? ReadLib::getPropertiesCreate(),
		   $propertiesUpdate ?? ReadLib::getPropertiesUpdate()
		);
	}

}
?>