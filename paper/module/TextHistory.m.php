<?php
namespace paper;

class TextHistory extends \paper\Text {

	use TextHistoryElement;
	use \FilterElement;

	private static ?TextHistoryModel $model = NULL;

	public static function model(): TextHistoryModel {
		if(self::$model === NULL) {
			self::$model = new TextHistoryModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('TextHistory::'.$failName, $arguments, $wrapper);
	}

}


class TextHistoryModel extends \paper\TextModel {

	protected string $module = 'paper\TextHistory';
	protected string $package = 'paper';
	protected string $table = 'paperTextHistory';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'text' => ['element32', 'paper\Text', 'cast' => 'element'],
			'deletedAt' => ['datetime', 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'text', 'deletedAt'
		]);

		$this->propertiesToModule += [
			'text' => 'paper\Text',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['text']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'deletedAt' :
				return new \Sql('NOW()');

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function select(...$fields): TextHistoryModel {
		return parent::select(...$fields);
	}

	public function where(...$data): TextHistoryModel {
		return parent::where(...$data);
	}

	public function whereText(...$data): TextHistoryModel {
		return $this->where('text', ...$data);
	}

	public function whereDeletedAt(...$data): TextHistoryModel {
		return $this->where('deletedAt', ...$data);
	}


}


abstract class TextHistoryCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): TextHistory {

		$e = new TextHistory();

		if($id === NULL) {
			TextHistory::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = TextHistory::getSelection();
		}

		if(TextHistory::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): TextHistory {

		return new TextHistory(['id' => NULL]);

	}

	public static function create(TextHistory $e): void {

		TextHistory::model()->insert($e);

	}

	public static function update(TextHistory $e, array $properties): void {

		$e->expects(['id']);

		TextHistory::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, TextHistory $e, array $properties): void {

		TextHistory::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(TextHistory $e): void {

		$e->expects(['id']);

		TextHistory::model()->delete($e);

	}

}


class TextHistoryPage extends \paper\TextPage {

	protected string $module = 'paper\TextHistory';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? TextHistoryLib::getPropertiesCreate(),
		   $propertiesUpdate ?? TextHistoryLib::getPropertiesUpdate()
		);
	}

}
?>