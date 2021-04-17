<?php
namespace editor;

class LicenseUi {

	protected static array $licenses = [];

	private static function getLicenses() {

		if(self::$licenses === []) {

			$baseUrl = 'https://creativecommons.org/licenses/';
			$version = '/4.0/';

			self::$licenses = [
				'copyright' => [
					'text' => s("Tous droits réservés"),
					'shortText' => s("Tous droits réservés"),
					'icon' => '',
				],
				'cc-by' => [
					'text' => s("Attribution (CC BY)"),
					'shortText' => s("Creative Commons BY"),
					'icon' => 'badge-cc-fill',
				],
				'cc-by-sa' => [
					'text' => s("Attribution - Partage dans les Mêmes Conditions (CC BY-SA)"),
					'shortText' => s("Creative Commons BY-SA"),
					'icon' => 'badge-cc-fill',
				],
				'cc-by-nd' => [
					'text' => s("Attribution - Pas de Modification (CC BY-ND)"),
					'shortText' => s("Creative Commons BY-ND"),
					'icon' => 'badge-cc-fill',
				],
				'cc-by-nc' => [
					'text' => s("Attribution - Pas d’Utilisation Commerciale (CC BY-NC)"),
					'shortText' => s("Creative Commons BY-NC"),
					'icon' => 'badge-cc-fill',
				],
				'cc-by-nc-sa' => [
					'text' => s("Attribution - Pas d’Utilisation Commerciale - Partage dans les Mêmes Conditions (CC BY-NC-SA)"),
					'shortText' => s("Creative Commons BY-NC-SA"),
					'icon' => 'badge-cc-fill',
				],
				'cc-by-nc-nd' => [
					'text' => s("Attribution - Pas d’Utilisation Commerciale - Pas de Modification (CC BY-NC-ND)"),
					'shortText' => s("Creative Commons BY-NC-ND"),
					'icon' => 'badge-cc-fill',
				],
				'public' => [
					'text' => s("Domaine public"),
					'shortText' => s("Domaine public"),
					'icon' => ''
				]
			];

			foreach(self::$licenses as $license => $data) {

				if($license === 'copyright') {

					self::$licenses[$license]['icon'] = '';
					self::$licenses[$license]['acronym'] = '';
					self::$licenses[$license]['url'] = NULL;
					self::$licenses[$license]['source'] = s("Tous droits réservés");

				} else if($license === 'public') {

					self::$licenses[$license]['icon'] = '';
					self::$licenses[$license]['acronym'] = '';
					self::$licenses[$license]['url'] = NULL;
					self::$licenses[$license]['source'] = '';

				} else {

					self::$licenses[$license]['acronym'] = strtoupper(str_replace('-', ' ', substr($license, strlen('license-cc-'))));
					self::$licenses[$license]['url'] = $baseUrl.substr($license, strlen('license-cc-')).$version;
					self::$licenses[$license]['icon'] = 'badge-cc-fill';
					self::$licenses[$license]['source'] = '';

				}
			}

		}

		return self::$licenses;

	}

	public static function getSelect() {

		$select = [];
		foreach(self::getLicenses() as $license => $infos) {
			$select[$license] = $infos['text'];
		}

		return $select;

	}

	public static function getInfo(string $license, string $type = NULL) {

		if($type === NULL) {
			return self::getLicenses()[$license];
		}

		return self::getLicenses()[$license][$type];
	}

	public static function link(string $license): string {

		$infos = self::getLicenses()[$license];
		
		if($infos['url']) {
			return '<a href="'.$infos['url'].'" target="_blank">'.$infos['shortText'].'</a>';
		} else {
			return $infos['shortText'];
		}
		
		return $link;
		
	}

}

?>
