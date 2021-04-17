<?php
namespace notification;

class Event extends \Element {

	use EventElement;
	use \FilterElement;

	private static ?EventModel $model = NULL;

	public static function model(): EventModel {
		if(self::$model === NULL) {
			self::$model = new EventModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Event::'.$failName, $arguments, $wrapper);
	}

}


class EventModel extends \ModuleModel {

	protected string $module = 'notification\Event';
	protected string $package = 'notification';
	protected string $table = 'notificationEvent';

	protected ?int $split = NULL;
	protected ?string $splitOn = NULL;

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial64', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'type' => ['element32', 'notification\Type', 'cast' => 'element'],
			'reference' => ['int32', 'min' => 0, 'max' => NULL, 'null' => TRUE, 'cast' => 'int'],
			'read' => ['bool', 'null' => TRUE, 'cast' => 'bool'],
			'clicked' => ['bool', 'cast' => 'bool'],
			'date' => ['datetime', 'cast' => 'string'],
			'readAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'clickedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'type', 'reference', 'read', 'clicked', 'date', 'readAt', 'clickedAt'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'type' => 'notification\Type',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['user', 'date']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'type', 'reference', 'read']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'read' :
				return FALSE;

			case 'clicked' :
				return FALSE;

			case 'date' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): EventModel {
		return parent::select(...$fields);
	}

	public function where(...$data): EventModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): EventModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): EventModel {
		return $this->where('user', ...$data);
	}

	public function whereType(...$data): EventModel {
		return $this->where('type', ...$data);
	}

	public function whereReference(...$data): EventModel {
		return $this->where('reference', ...$data);
	}

	public function whereRead(...$data): EventModel {
		return $this->where('read', ...$data);
	}

	public function whereClicked(...$data): EventModel {
		return $this->where('clicked', ...$data);
	}

	public function whereDate(...$data): EventModel {
		return $this->where('date', ...$data);
	}

	public function whereReadAt(...$data): EventModel {
		return $this->where('readAt', ...$data);
	}

	public function whereClickedAt(...$data): EventModel {
		return $this->where('clickedAt', ...$data);
	}


}


abstract class EventCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Event {

		$e = new Event();

		if($id === NULL) {
			Event::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Event::getSelection();
		}

		if(Event::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Event {

		return new Event(['id' => NULL]);

	}

	public static function create(Event $e): void {

		Event::model()->insert($e);

	}

	public static function update(Event $e, array $properties): void {

		$e->expects(['id']);

		Event::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Event $e, array $properties): void {

		Event::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Event $e): void {

		$e->expects(['id']);

		Event::model()->delete($e);

	}

}


class EventPage extends \ModulePage {

	protected string $module = 'notification\Event';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? EventLib::getPropertiesCreate(),
		   $propertiesUpdate ?? EventLib::getPropertiesUpdate()
		);
	}

}
?>