<?php
namespace notification;

class Subscription extends \Element {

	use SubscriptionElement;
	use \FilterElement;

	private static ?SubscriptionModel $model = NULL;

	const WEB = 'web';
	const APP = 'app';
	const MOBILE_WEB = 'mobile-web';
	const TABLET_WEB = 'tablet-web';
	const CRAWLER = 'crawler';

	public static function model(): SubscriptionModel {
		if(self::$model === NULL) {
			self::$model = new SubscriptionModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Subscription::'.$failName, $arguments, $wrapper);
	}

}


class SubscriptionModel extends \ModuleModel {

	protected string $module = 'notification\Subscription';
	protected string $package = 'notification';
	protected string $table = 'notificationSubscription';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'user' => ['element32', 'user\User', 'cast' => 'element'],
			'type' => ['element32', 'notification\Type', 'cast' => 'element'],
			'reference' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'subscribed' => ['bool', 'cast' => 'bool'],
			'blocked' => ['bool', 'cast' => 'bool'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'device' => ['enum', [\notification\Subscription::WEB, \notification\Subscription::APP, \notification\Subscription::MOBILE_WEB, \notification\Subscription::TABLET_WEB, \notification\Subscription::CRAWLER], 'cast' => 'enum'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'user', 'type', 'reference', 'subscribed', 'blocked', 'createdAt', 'device'
		]);

		$this->propertiesToModule += [
			'user' => 'user\User',
			'type' => 'notification\Type',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['type', 'reference']
		]);

		$this->uniqueConstraints = array_merge($this->uniqueConstraints, [
			['user', 'type', 'reference']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'reference' :
				return 0;

			case 'blocked' :
				return FALSE;

			case 'createdAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'device' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): SubscriptionModel {
		return parent::select(...$fields);
	}

	public function where(...$data): SubscriptionModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): SubscriptionModel {
		return $this->where('id', ...$data);
	}

	public function whereUser(...$data): SubscriptionModel {
		return $this->where('user', ...$data);
	}

	public function whereType(...$data): SubscriptionModel {
		return $this->where('type', ...$data);
	}

	public function whereReference(...$data): SubscriptionModel {
		return $this->where('reference', ...$data);
	}

	public function whereSubscribed(...$data): SubscriptionModel {
		return $this->where('subscribed', ...$data);
	}

	public function whereBlocked(...$data): SubscriptionModel {
		return $this->where('blocked', ...$data);
	}

	public function whereCreatedAt(...$data): SubscriptionModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereDevice(...$data): SubscriptionModel {
		return $this->where('device', ...$data);
	}


}


abstract class SubscriptionCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Subscription {

		$e = new Subscription();

		if($id === NULL) {
			Subscription::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Subscription::getSelection();
		}

		if(Subscription::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Subscription {

		return new Subscription(['id' => NULL]);

	}

	public static function create(Subscription $e): void {

		Subscription::model()->insert($e);

	}

	public static function update(Subscription $e, array $properties): void {

		$e->expects(['id']);

		Subscription::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Subscription $e, array $properties): void {

		Subscription::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Subscription $e): void {

		$e->expects(['id']);

		Subscription::model()->delete($e);

	}

}


class SubscriptionPage extends \ModulePage {

	protected string $module = 'notification\Subscription';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? SubscriptionLib::getPropertiesCreate(),
		   $propertiesUpdate ?? SubscriptionLib::getPropertiesUpdate()
		);
	}

}
?>