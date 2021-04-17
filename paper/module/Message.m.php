<?php
namespace paper;

class Message extends \Element {

	use MessageElement;
	use \FilterElement;

	private static ?MessageModel $model = NULL;

	const OPEN = 'open';
	const ANSWER = 'answer';
	const FEEDBACK = 'feedback';

	const PREVIEW = 'preview';
	const PUBLISHED = 'published';

	const CLEAN = 'clean';
	const REPORTED = 'reported';
	const CLOSED = 'closed';

	public static function model(): MessageModel {
		if(self::$model === NULL) {
			self::$model = new MessageModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Message::'.$failName, $arguments, $wrapper);
	}

}


class MessageModel extends \ModuleModel {

	protected string $module = 'paper\Message';
	protected string $package = 'paper';
	protected string $table = 'paperMessage';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'discussion' => ['element32', 'paper\Discussion', 'cast' => 'element'],
			'forum' => ['element32', 'paper\Forum', 'null' => TRUE, 'cast' => 'element'],
			'author' => ['element32', 'user\User', 'cast' => 'element'],
			'type' => ['enum', [\paper\Message::OPEN, \paper\Message::ANSWER, \paper\Message::FEEDBACK], 'cast' => 'enum'],
			'text' => ['element32', 'paper\Text', 'null' => TRUE, 'cast' => 'element'],
			'copied' => ['element32', 'paper\Discussion', 'null' => TRUE, 'cast' => 'element'],
			'createdAt' => ['datetime', 'cast' => 'string'],
			'publishedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'notifiedAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'status' => ['enum', [\paper\Message::PREVIEW, \paper\Message::PUBLISHED], 'cast' => 'enum'],
			'abuseStatus' => ['enum', [\paper\Message::CLEAN, \paper\Message::REPORTED, \paper\Message::CLOSED], 'cast' => 'enum'],
			'abuseNumber' => ['int16', 'min' => 0, 'max' => NULL, 'cast' => 'int'],
			'censored' => ['bool', 'cast' => 'bool'],
			'censoredAt' => ['datetime', 'null' => TRUE, 'cast' => 'string'],
			'automatic' => ['bool', 'cast' => 'bool'],
			'answerOf' => ['element32', 'paper\Message', 'null' => TRUE, 'cast' => 'element'],
			'first' => ['bool', 'cast' => 'bool'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'discussion', 'forum', 'author', 'type', 'text', 'copied', 'createdAt', 'publishedAt', 'notifiedAt', 'status', 'abuseStatus', 'abuseNumber', 'censored', 'censoredAt', 'automatic', 'answerOf', 'first'
		]);

		$this->propertiesToModule += [
			'discussion' => 'paper\Discussion',
			'forum' => 'paper\Forum',
			'author' => 'user\User',
			'text' => 'paper\Text',
			'copied' => 'paper\Discussion',
			'answerOf' => 'paper\Message',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['author'],
			['createdAt'],
			['answerOf'],
			['text'],
			['discussion']
		]);

	}

	public function getDefaultValue(string $property) {

		switch($property) {

			case 'author' :
				return \user\ConnectionLib::getOnline();

			case 'createdAt' :
				return new \Sql('NOW()');

			case 'publishedAt' :
				return new \Sql('NOW()');

			case 'status' :
				return Message::PUBLISHED;

			case 'abuseStatus' :
				return Message::CLEAN;

			case 'abuseNumber' :
				return 0;

			case 'censored' :
				return FALSE;

			case 'automatic' :
				return FALSE;

			case 'first' :
				return FALSE;

			default :
				return parent::getDefaultValue($property);

		}

	}

	public function encode(string $property, $value) {

		switch($property) {

			case 'type' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'status' :
				return ($value === NULL) ? NULL : (string)$value;

			case 'abuseStatus' :
				return ($value === NULL) ? NULL : (string)$value;

			default :
				return parent::encode($property, $value);

		}

	}

	public function select(...$fields): MessageModel {
		return parent::select(...$fields);
	}

	public function where(...$data): MessageModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): MessageModel {
		return $this->where('id', ...$data);
	}

	public function whereDiscussion(...$data): MessageModel {
		return $this->where('discussion', ...$data);
	}

	public function whereForum(...$data): MessageModel {
		return $this->where('forum', ...$data);
	}

	public function whereAuthor(...$data): MessageModel {
		return $this->where('author', ...$data);
	}

	public function whereType(...$data): MessageModel {
		return $this->where('type', ...$data);
	}

	public function whereText(...$data): MessageModel {
		return $this->where('text', ...$data);
	}

	public function whereCopied(...$data): MessageModel {
		return $this->where('copied', ...$data);
	}

	public function whereCreatedAt(...$data): MessageModel {
		return $this->where('createdAt', ...$data);
	}

	public function wherePublishedAt(...$data): MessageModel {
		return $this->where('publishedAt', ...$data);
	}

	public function whereNotifiedAt(...$data): MessageModel {
		return $this->where('notifiedAt', ...$data);
	}

	public function whereStatus(...$data): MessageModel {
		return $this->where('status', ...$data);
	}

	public function whereAbuseStatus(...$data): MessageModel {
		return $this->where('abuseStatus', ...$data);
	}

	public function whereAbuseNumber(...$data): MessageModel {
		return $this->where('abuseNumber', ...$data);
	}

	public function whereCensored(...$data): MessageModel {
		return $this->where('censored', ...$data);
	}

	public function whereCensoredAt(...$data): MessageModel {
		return $this->where('censoredAt', ...$data);
	}

	public function whereAutomatic(...$data): MessageModel {
		return $this->where('automatic', ...$data);
	}

	public function whereAnswerOf(...$data): MessageModel {
		return $this->where('answerOf', ...$data);
	}

	public function whereFirst(...$data): MessageModel {
		return $this->where('first', ...$data);
	}


}


abstract class MessageCrud extends \ModuleCrud {

	public static function getById($id, array $properties = []): Message {

		$e = new Message();

		if($id === NULL) {
			Message::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Message::getSelection();
		}

		if(Message::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getCreateElement(): Message {

		return new Message(['id' => NULL]);

	}

	public static function create(Message $e): void {

		Message::model()->insert($e);

	}

	public static function update(Message $e, array $properties): void {

		$e->expects(['id']);

		Message::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Message $e, array $properties): void {

		Message::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Message $e): void {

		$e->expects(['id']);

		Message::model()->delete($e);

	}

}


class MessagePage extends \ModulePage {

	protected string $module = 'paper\Message';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? MessageLib::getPropertiesCreate(),
		   $propertiesUpdate ?? MessageLib::getPropertiesUpdate()
		);
	}

}
?>