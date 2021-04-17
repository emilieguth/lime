<?php
namespace user;

/**
 * Roles handling
 */
class RoleLib {

	/**
	 * Get the list of supported roles
	 *
	 * @param int $minLevel Select roles according to a minimum level
	 */
	public static function getList(int $minLevel = NULL): \Collection {

		if($minLevel !== NULL) {
			Role::model()->where('level > '.$minLevel);
		}

		$cRole = Role::model()
			->select(['id', 'name', 'level'])
			->sort('level')
			->getCollection();

		return $cRole;

	}

}
?>
