<?php
namespace paper;

class Abuse extends \Element {

	use AbuseElement;
	use \FilterElement;

	private static ?AbuseModel $model = NULL;

	const OFFPUBLICATION = 'offpublication';
	const INAPPROPRIATE = 'inappropriate';
	const SPAM = 'spam';
	const OTHER = 'other';

	const ABUSIVE = 'abusive';
	const NOT_ABUSIVE = 'not-abusive';
	const UNKNOWN = 'unknown';

	const OPEN = 'open';
	const CLOSED = 'closed';

	public static function model(): AbuseModel {
		if(self::$model === NULL) {
			self::$model = new AbuseModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Abuse::'.$failName, $arguments, $wrapper);
	}

}


class AbuseModel extends \ModuleModel {

	protected string $module = 'paper\Abuse';
	protected string $package = 'paper';
	protected string $table = 'paperAbuse';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'discussion' => ['element32', 'paper\Discussion', 'cast' => 'element'],
			'message' => ['element32', 'paper\Message', 'cast' => 'element'],
			'for' => ['enum', [\paper\Abuse::OFFPUBLICATION, \paper\Abuse::INAPPROPRIATE, \paper\Abuse::SPAM, \paper\Abuse::OTHER], 'cast' => 'enum'],
			'why' => ['text16', 'min' => 0, 'max' => NULL, 'cast' => 'string'],
			'createdBy' => ['element32', 'user\User', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'resolution' => ['enum', [\paper\Abuse::ABUSIVE, \paper\Abuse::NOT_ABUSIVE, \paper\Abuse::UNKNOWN], 'null' => TRUE, 'cast' => 'enum'],
			'resolvedBy' => ['element32', 'user\User', 'null' => TRUE, 'cast' => 'element'],
			'resolvedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\paper\Abuse::OPEN, \paper\Abuse::CLOSED], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'discussion', 'message', 'for', 'why', 'createdBy', 'createdAt', 'resolution', 'resolvedBy', 'resolvedAt', 'status'
		]);

		$this->propertiesToModule += [
			'discussion' => 'paper\Discussion',
			'message' => 'paper\Message',
			'createdBy' => 'user\User',
			'resolvedBy' => 'user\User',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['message', 'status'],
			['status', 'id']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'createdBy' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Abuse::OPEN;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'for' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'resolution' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): AbuseModel {
		return parent::select(...$fields);
	}

	public function where(...$data): AbuseModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): AbuseModel {
		return $this->where('id', ...$data);
	}

	public function whereDiscussion(...$data): AbuseModel {
		return $this->where('discussion', ...$data);
	}

	public function whereMessage(...$data): AbuseModel {
		return $this->where('message', ...$data);
	}

	public function whereFor(...$data): AbuseModel {
		return $this->where('for', ...$data);
	}

	public function whereWhy(...$data): AbuseModel {
		return $this->where('why', ...$data);
	}

	public function whereCreatedBy(...$data): AbuseModel {
		return $this->where('createdBy', ...$data);
	}

	public function whereCreatedAt(...$data): AbuseModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereResolution(...$data): AbuseModel {
		return $this->where('resolution', ...$data);
	}

	public function whereResolvedBy(...$data): AbuseModel {
		return $this->where('resolvedBy', ...$data);
	}

	public function whereResolvedAt(...$data): AbuseModel {
		return $this->where('resolvedAt', ...$data);
	}

	public function whereStatus(...$data): AbuseModel {
		return $this->where('status', ...$data);
	}


}


abstract class AbuseCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Abuse {

		$e = new Abuse();

		if($id === NULL) {
			Abuse::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Abuse::getSelection();
		}

		if(Abuse::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Abuse {

		return new Abuse(['id' => NULL]);

	}

	public static function create(Abuse $e): void {

		Abuse::model()->insert($e);

	}

	public static function update(Abuse $e, array $properties): void {

		$e->expects(['id']);

		Abuse::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Abuse $e, array $properties): void {

		Abuse::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Abuse $e): void {

		$e->expects(['id']);

		Abuse::model()->delete($e);

	}

}


class AbusePage extends \ModulePage {

	protected string $module = 'paper\Abuse';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? AbuseLib::getPropertiesCreate(),
		   $propertiesUpdate ?? AbuseLib::getPropertiesUpdate()
		);
	}

}
?>