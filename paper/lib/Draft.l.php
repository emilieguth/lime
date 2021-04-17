<?php
namespace paper;

/**
 * Message handling
 */
class DraftLib {

	/**
	 * Get a hash for drafts using $values
	 *
	 * @param array $values
	 */
	public static function getHash(...$values): string {

		$string = '';

		foreach($values as $key => $value) {
			$string .= 'key:'.$key.';value:';
			if($value instanceof \Element) {
				$string .= $value['id'];
			} else if(is_scalar($value)) {
				$string .= $value;
			} else {
				throw new \Exception("Hash must be created from scalar/element values");
			}
			$string .= ';';
		}

		return md5($string);

	}

	/**
	 * Invalidates a draft
	 *
	 * @param string $hash
	 * @return type
	 */
	public static function invalidate(string $hash): int {

		return Draft::model()
			->whereHash($hash)
			->whereAuthor(\user\ConnectionLib::getOnline())
			->update([
				'invalidatedAt' => new \Sql('NOW()')
			]);

	}

	/**
	 * Deletes an old draft
	 *
	 * @param string $hash
	 * @return type
	 */
	public static function delete(string $hash): int {

		return Draft::model()
			->whereHash($hash)
			->whereAuthor(\user\ConnectionLib::getOnline())
			->delete();

	}

	/**
	 * Gets a draft for the current user
	 *
	 * @param string $hash
	 *
	 * @return array
	 */
	public static function get(string $hash): Draft {

		$eUser = \user\ConnectionLib::getOnline();

		if($eUser->empty()) {

			$eDraft = new Draft([
				'hash' => $hash,
				'content' => [],
				'initialContent' => [],
			]);

		} else {

			$eDraft = Draft::model()
				->select('id', 'content', 'hash', 'savedAt', 'invalidatedAt', 'initialContent')
				->whereHash($hash)
				->whereAuthor(\user\ConnectionLib::getOnline())
				->get();

			if(
				$eDraft->empty() or
				$eDraft['invalidatedAt'] !== NULL
			) {
				$eDraft = self::add($hash, [], []);

			// There is no real draft we undo it manually
			} else if($eDraft['content'] === $eDraft['initialContent']) {
				$eDraft['content'] = [];
				$eDraft['initialContent'] = [];
			}

		}

		$eDraft['contentFields'] = self::getFields($eDraft['content']);

		return $eDraft;
	}

	/**
	 * Get empty cache
	 * @return array
	 */
	public static function getEmpty(): array {
		return new Draft([
			'hash' => NULL,
			'content' => [],
			'contentFields' => []
		]);
	}

	/**
	 * Creates an empty draft (and replace an old existing one)
	 *
	 * @param string $hash
	 * @param mixed $content
	 * @param mixed $initialContent reference content.
	 *
	 * @return array
	 */
	protected static function add(string $hash, $content, $initialContent = NULL): Draft {

		$eDraft = new Draft([
			'hash' => $hash,
			'content' => $content,
		]);

		if($initialContent !== NULL) {
			$eDraft['initialContent'] = $initialContent;
		}

		Draft::model()
			->option('add-replace')
			->insert($eDraft);

		return $eDraft;

	}

	/**
	 * Get form fields from a draft
	 *
	 * @param array $content
	 * @return array
	 */
	protected static function getFields(array $content): array {

		// it's a publication draft
		$fields = [];

		foreach($content as $entry) {
			$fields[$entry['name']] = $entry['value'];
		}

		return $fields;

	}

	/**
	 * Tries to update a draft
	 * If needed, creates it
	 *
	 * We check if the message or publication has already been sent between last save and now
	 *
	 * @param string $hash
	 * @param mixed $content
	 * @param mixed $initialContent
	 * @param string $initializedAt Initialization date of the draft
	 */
	public static function save(string $hash, $content, $initialContent, string $initializedAt): Draft {

		// If a draft with the same hash already exists, some stuff to do...
		$eDraft = Draft::model()
			->select('id', 'createdAt', 'invalidatedAt', 'hash', 'initialContent')
			->whereAuthor(\user\ConnectionLib::getOnline())
			->whereHash($hash)
			->get();

		if($eDraft->notEmpty()) {

			// The draft has been initialized BEFORE its creation date
			// This is a CONFLICT
			if(\util\DateLib::compare($initializedAt, $eDraft['createdAt']) < 0) {
				return new Draft(); // Nothing to do, we can't save this old draft
			}
			// Mmm, user already published draft content
			else if($eDraft['invalidatedAt'] !== NULL) {

				// The draft has been initialized BEFORE it has been published
				// This is a CONFLICT
				if(\util\DateLib::compare($initializedAt, $eDraft['invalidatedAt']) < 0) {
					return new Draft(); // Nothing to do, we can't save this old draft
				} else {
					self::delete($hash); // OK, new draft, deletes old one
				}

			}
			// OK, draft not published yet, we can update it
			else {

				$properties = [
					'savedAt' => new \Sql('NOW()'),
					'content' => $content,
				];
				if($eDraft['initialContent'] === []) {
					$properties['initialContent'] = $initialContent;
					$eDraft['initialContent'] = $initialContent;
				}

				Draft::model()->update($eDraft, $properties);

				$eDraft['content'] = $content;

				return $eDraft;

			}

		}

		$eDraft = self::add($hash, $content, $initialContent);

		return $eDraft;

	}

	/**
	 * Convert and check a list of value in a valid format before saving it in the database
	 *
	 * @param array $values
	 *
	 * @return array
	 */
	public static function convertSave(array $values): array {

		foreach($values as $key => $value) {

			try {

				array_expects($value, ['name', 'value', 'type']);

				switch($value['type']) {

					case 'text' :
						break;

					case 'editor' :
						$values[$key]['value'] = (new \editor\XmlLib())->fromHtml($value['value'], [
							'acceptFigure' => TRUE,
							'draft' => TRUE
						]);
						break;

					default :
						unset($values[$key]);

				}

			} catch(Exception $e) {
				unset($values[$key]);
			}

		}

		return $values;

	}

	/**
	 * Cleans the drafts that were created more than 1 day ago
	 *
	 */
	public static function clean() {

		Draft::model()
			->where('savedAt < NOW() - INTERVAL 60 DAY')
			->delete();

	}

}

?>
