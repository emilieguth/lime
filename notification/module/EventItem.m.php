<?php
namespace notification;

class EventItem extends \Element {

	use EventItemElement;
	use \FilterElement;

	private static ?EventItemModel $model = NULL;

	public static function model(): EventItemModel {
		if(self::$model === NULL) {
			self::$model = new EventItemModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('EventItem::'.$failName, $arguments, $wrapper);
	}

}


class EventItemModel extends \ModuleModel {

	protected string $module = 'notification\EventItem';
	protected string $package = 'notification';
	protected string $table = 'notificationEventItem';

	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial64', 'cast' => 'int'],
			'event' => ['element64', 'notification\Event', 'cast' => 'element'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'type' => ['element32', 'notification\Type', 'cast' => 'element'],
			'aboutUser' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'aboutText' => ['element32', 'paper\Text', 'null' => TRUE, 'cast' => 'element'],
			'aboutValue' => ['int32', 'null' => TRUE, 'cast' => 'int'],
			'read' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'date' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'event', 'user', 'type', 'aboutUser', 'aboutText', 'aboutValue', 'read', 'date'
		]);

		$this->propertiesToModule += [
			'event' => 'notification\Event',
			'user' => 'user\User',
			'type' => 'notification\Type',
			'aboutUser' => 'user\User',
			'aboutText' => 'paper\Text',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['event'],
			['user']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['aboutText', 'type', 'user']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'read' :
				return FALSE;

			case 'date' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): EventItemModel {
		return parent::select(...$fields);
	}

	public function where(...$data): EventItemModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): EventItemModel {
		return $this->where('id', ...$data);
	}

	public function whereEvent(...$data): EventItemModel {
		return $this->where('event', ...$data);
	}

	public function whereUser(...$data): EventItemModel {
		return $this->where('user', ...$data);
	}

	public function whereType(...$data): EventItemModel {
		return $this->where('type', ...$data);
	}

	public function whereAboutUser(...$data): EventItemModel {
		return $this->where('aboutUser', ...$data);
	}

	public function whereAboutText(...$data): EventItemModel {
		return $this->where('aboutText', ...$data);
	}

	public function whereAboutValue(...$data): EventItemModel {
		return $this->where('aboutValue', ...$data);
	}

	public function whereRead(...$data): EventItemModel {
		return $this->where('read', ...$data);
	}

	public function whereDate(...$data): EventItemModel {
		return $this->where('date', ...$data);
	}


}


abstract class EventItemCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): EventItem {

		$e = new EventItem();

		if($id === NULL) {
			EventItem::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = EventItem::getSelection();
		}

		if(EventItem::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): EventItem {

		return new EventItem(['id' => NULL]);

	}

	public static function create(EventItem $e): void {

		EventItem::model()->insert($e);

	}

	public static function update(EventItem $e, array $properties): void {

		$e->expects(['id']);

		EventItem::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, EventItem $e, array $properties): void {

		EventItem::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(EventItem $e): void {

		$e->expects(['id']);

		EventItem::model()->delete($e);

	}

}


class EventItemPage extends \ModulePage {

	protected string $module = 'notification\EventItem';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? EventItemLib::getPropertiesCreate(),
		   $propertiesUpdate ?? EventItemLib::getPropertiesUpdate()
		);
	}

}
?>