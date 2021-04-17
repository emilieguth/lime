<?php
namespace paper;

class Discussion extends \Element {

	use DiscussionElement;
	use \FilterElement;

	private static ?DiscussionModel $model = NULL;

	const OPEN = 'open';
	const LOCKED = 'locked';

	public static function model(): DiscussionModel {
		if(self::$model === NULL) {
			self::$model = new DiscussionModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Discussion::'.$failName, $arguments, $wrapper);
	}

}


class DiscussionModel extends \ModuleModel {

	protected string $module = 'paper\Discussion';
	protected string $package = 'paper';
	protected string $table = 'paperDiscussion';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'forum' => ['element32', 'paper\Forum', 'null' => TRUE, 'cast' => 'element'],
			'title' => ['text8', 'min' => 1, 'max' => \Setting::get('paper\publicationSizeMax'), 'collate' => 'general', 'cast' => 'string'],
			'search' => ['text8', 'min' => 1, 'max' => NULL, 'cast' => 'string'],
			'cleanTitle' => ['text8', 'cast' => 'string'],
			'messages' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'views' => ['int32', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'write' => ['enum', [\paper\Discussion::OPEN, \paper\Discussion::LOCKED], 'cast' => 'enum'],
			'writeUpdatedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'author' => ['element32', 'user\User', 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'openMessage' => ['element32', 'paper\Message', 'null' => TRUE, 'cast' => 'element'],
			'lastMessage' => ['element32', 'paper\Message', 'null' => TRUE, 'cast' => 'element'],
			'lastMessageAt' => ['datetime', 'cast' => 'string'],
			'pinned' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'forum', 'title', 'search', 'cleanTitle', 'messages', 'views', 'write', 'writeUpdatedAt', 'author', 'createdAt', 'openMessage', 'lastMessage', 'lastMessageAt', 'pinned'
		]);

		$this->propertiesToModule += [
			'forum' => 'paper\Forum',
			'author' => 'user\User',
			'openMessage' => 'paper\Message',
			'lastMessage' => 'paper\Message',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['createdAt']
		]);

		$this->searchConstraints = array_merge($this->searchConstraints, [
			['search']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'messages' :
				return 0;

			case 'views' :
				return 0;

			case 'write' :
				return Discussion::OPEN;

			case 'author' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'lastMessageAt' :
				return new \Sql('NOW()');

			case 'pinned' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'write' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): DiscussionModel {
		return parent::select(...$fields);
	}

	public function where(...$data): DiscussionModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): DiscussionModel {
		return $this->where('id', ...$data);
	}

	public function whereForum(...$data): DiscussionModel {
		return $this->where('forum', ...$data);
	}

	public function whereTitle(...$data): DiscussionModel {
		return $this->where('title', ...$data);
	}

	public function whereSearch(...$data): DiscussionModel {
		return $this->where('search', ...$data);
	}

	public function whereCleanTitle(...$data): DiscussionModel {
		return $this->where('cleanTitle', ...$data);
	}

	public function whereMessages(...$data): DiscussionModel {
		return $this->where('messages', ...$data);
	}

	public function whereViews(...$data): DiscussionModel {
		return $this->where('views', ...$data);
	}

	public function whereWrite(...$data): DiscussionModel {
		return $this->where('write', ...$data);
	}

	public function whereWriteUpdatedAt(...$data): DiscussionModel {
		return $this->where('writeUpdatedAt', ...$data);
	}

	public function whereAuthor(...$data): DiscussionModel {
		return $this->where('author', ...$data);
	}

	public function whereCreatedAt(...$data): DiscussionModel {
		return $this->where('createdAt', ...$data);
	}

	public function whereOpenMessage(...$data): DiscussionModel {
		return $this->where('openMessage', ...$data);
	}

	public function whereLastMessage(...$data): DiscussionModel {
		return $this->where('lastMessage', ...$data);
	}

	public function whereLastMessageAt(...$data): DiscussionModel {
		return $this->where('lastMessageAt', ...$data);
	}

	public function wherePinned(...$data): DiscussionModel {
		return $this->where('pinned', ...$data);
	}


}


abstract class DiscussionCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Discussion {

		$e = new Discussion();

		if($id === NULL) {
			Discussion::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Discussion::getSelection();
		}

		if(Discussion::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Discussion {

		return new Discussion(['id' => NULL]);

	}

	public static function create(Discussion $e): void {

		Discussion::model()->insert($e);

	}

	public static function update(Discussion $e, array $properties): void {

		$e->expects(['id']);

		Discussion::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Discussion $e, array $properties): void {

		Discussion::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Discussion $e): void {

		$e->expects(['id']);

		Discussion::model()->delete($e);

	}

}


class DiscussionPage extends \ModulePage {

	protected string $module = 'paper\Discussion';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? DiscussionLib::getPropertiesCreate(),
		   $propertiesUpdate ?? DiscussionLib::getPropertiesUpdate()
		);
	}

}
?>