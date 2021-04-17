<?php
namespace paper;

class MessageHidden extends \paper\Message {

	use MessageHiddenElement;
	use \FilterElement;

	private static ?MessageHiddenModel $model = NULL;

	public static function model(): MessageHiddenModel {
		if(self::$model === NULL) {
			self::$model = new MessageHiddenModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('MessageHidden::'.$failName, $arguments, $wrapper);
	}

}


class MessageHiddenModel extends \paper\MessageModel {

	protected string $module = 'paper\MessageHidden';
	protected string $package = 'paper';
	protected string $table = 'paperMessageHidden';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'hiddenAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'hiddenAt'
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'hiddenAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): MessageHiddenModel {
		return parent::select(...$fields);
	}

	public function where(...$data): MessageHiddenModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): MessageHiddenModel {
		return $this->where('id', ...$data);
	}

	public function whereHiddenAt(...$data): MessageHiddenModel {
		return $this->where('hiddenAt', ...$data);
	}


}


abstract class MessageHiddenCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): MessageHidden {

		$e = new MessageHidden();

		if($id === NULL) {
			MessageHidden::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = MessageHidden::getSelection();
		}

		if(MessageHidden::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): MessageHidden {

		return new MessageHidden(['id' => NULL]);

	}

	public static function create(MessageHidden $e): void {

		MessageHidden::model()->insert($e);

	}

	public static function update(MessageHidden $e, array $properties): void {

		$e->expects(['id']);

		MessageHidden::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, MessageHidden $e, array $properties): void {

		MessageHidden::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(MessageHidden $e): void {

		$e->expects(['id']);

		MessageHidden::model()->delete($e);

	}

}


class MessageHiddenPage extends \paper\MessagePage {

	protected string $module = 'paper\MessageHidden';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? MessageHiddenLib::getPropertiesCreate(),
		   $propertiesUpdate ?? MessageHiddenLib::getPropertiesUpdate()
		);
	}

}
?>