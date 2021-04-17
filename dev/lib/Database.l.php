<?php
namespace dev;

class DatabaseLib {

	/**
	 * File PHP Database file from INI files
	 *
	 * @param string $mode (prod, dev, preprod)
	 * @param bool $test Test connections?
	 */
	public static function build(string $mode = LIME_ENV, bool $test = TRUE): bool {

		$ini = self::parseIni($mode);

		$content = [];

		$content[] = '<?php';
		$content[] = '/**';
		$content[] = ' * Database configuration file automatically generated for '.LIME_APP.' on '.currentDatetime();
		$content[] = ' * Edit database.ini in order to change database configuration';
		$content[] = ' */';

		// Link packages to database name
		self::buildPackage($ini, $content);

		// Build database servers
		self::buildServers($ini, $content);

		// Enable/disable debug
		self::buildDebug($ini, $content);

		$content[] = '';
		$content[] = '?>';

		$stringContent = implode("\n", $content);

		// On teste la connexion à la base de données
		if($test) {

			$fileTest = tempnam('/tmp', 'database_');
			file_put_contents($fileTest, $stringContent);

			$testErrors = self::testDatabase($fileTest, $mode);

			// Remove tmp_database.c.php
			unlink($fileTest);

			if($testErrors) {

				foreach($testErrors as $package => $message) {

					//throw new \Exception('Connection test failed for package '.$package.' ('.$message.')');

				}

			}

		}

		// Replace current database.c.php file content by new tested one
		$file = \Lime::getPath()."/database-".$mode.".c.php";
		file_put_contents($file, $stringContent);

		return TRUE;

	}


	/**
	 * Adds debug status to $content
	 *
	 * @param array $ini Content of cache.ini file
	 * @param array $content Output
	 */
	protected static function buildDebug(array $ini, array &$content) {

		$hasDebug = strtolower($ini['global']['debug'] ?? '');

		if(
			$hasDebug !== '' or
			($hasDebug === 'dev' and LIME_ENV === 'dev') or
			($hasDebug === 'preprod' and in_array(LIME_ENV, ['dev', 'preprod']))
		) {

			$content[] = '';
			$content[] = 'Database::setDebug(get_exists(\'sql\'));';

		}


		$hasMon = strtolower($ini['global']['mon'] ?? '');

		if($hasMon === '1') {
			$content[] = 'Database::startMon(\'DevSqlPackage\');';
		}

	}


	/**
	 * Adds package and database relationship to $content
	 *
	 * @param array $ini Content of cache.ini file
	 * @param array $content Output
	 */
	protected static function buildPackage(array $ini, array &$content) {

		$packages = $ini['package'] ?? [];

		if($packages === []) {
			return;
		}


		$content[] = '';
		$content[] = 'Database::addPackages([';

		foreach($packages as $package => $base) {
			$content[] = '	\''.$package.'\' => \''.self::getBase($ini, $base).'\',';
		}

		$content[] = ']);';

	}


	/**
	 * Adds servers to $content
	 *
	 * @param array $ini Content of cache.ini file
	 * @param array $content Output
	 */
	protected static function buildServers(array $ini, array &$content) {

		foreach($ini as $server => $info) {

			// Un serveur est au format hôte:port
			if(strpos($server, ':') === FALSE) {
				continue;
			}

			// On récupère les informations du serveur
			list($host, $port) = explode(':', $server);

			// On récupère une liste des bases de données
			$bases = explode(',', $info['bases']);
			$bases = array_map(function($base) use($ini) {
				return self::getBase($ini, $base);
			}, $bases);

			// On fusionne les infos avec les infos globales
			$info += $ini['global'] ?? [];

			// On vérifie qu'on a les paramètres indispensables (type, login, password)
			if(
				isset($info['type']) === FALSE or
				isset($info['login']) === FALSE or
				isset($info['password']) === FALSE
			) {
				throw new \Exception("Missing a mandatory field (type, login, password)");
			}

			$content[] = 'Database::addServer([';

			// On construit les paramètres de connexion au serveur
			$content[] = '	\'type\' => \''.$info['type'].'\',';
			$content[] = '	\'host\' => \''.$host.'\',';
			$content[] = '	\'port\' => '.(int)$port.',';
			$content[] = '	\'login\' => \''.addcslashes($info['login'], '\'').'\',';
			$content[] = '	\'password\' => \''.addcslashes($info['password'], '\'').'\',';

			// On ajoute les bases
			if($bases) {
				$content[] = '	\'bases\' => [\''.implode('\', \'', $bases).'\'],';
			}

			// On ajoute les éventuels serveurs liés
			foreach($info as $subServer => $subHost) {

				if(strpos($subServer, '@') !== 0) {
					continue;
				}

				$content[] = '	\''.$subServer.'\' => [';
				$content[] = '		\'host\' => \''.$subHost.'\',';
				$content[] = '	],';

			}

			$content[] = ']);';

		}

	}

	/**
	 * Returns database name including prefixes
	 *
	 * @param type $ini
	 * @param type $base
	 * @return string
	 */
	private static function getBase(array $ini, string $base): string {

		$final = '';

		// On commence par le préfixe lié au mode (dev, preprod, prod)
		switch(LIME_ENV) {

			case 'dev' :
				$final .= $ini['prefix']['mode_dev'] ?? 'dev_';
				break;

			case 'preprod' :
				$final .= $ini['prefix']['mode_preprod'] ?? 'preprod_';
				break;

			default :
				$final .= $ini['prefix']['mode_'.LIME_ENV] ?? '';
				break;

		}

		// On ajoute ensuite le préfixe lié à l'hôte
		$final .= $ini['prefix'][\Lime::getHost()] ?? '';

		// On ajoute ensuite la base
		$final .= $base;

		return $final;

	}

	/**
	 * Open database.ini for current app
	 *
	 */
	protected static function parseIni(): array {

		$content = [];

		$files = [
			\Lime::getPath()."/database.ini",
			\Lime::getPath()."/database-servers-".LIME_ENV.".ini",
		];
		foreach($files as $file) {

			if(is_file($file)) {
				$content = array_merge_recursive($content, parse_ini_file($file, TRUE));
			} else {
				throw new \Exception("File '".$file."' does not exist");
			}

		}

		return $content;

	}

	protected static function getHosts(array $ini): array {

		$hosts = [];
		$hostsIni = $ini['hosts'] ?? [];

		foreach($hostsIni as $host => $bases) {

			$bases = preg_replace('/\s+/', '', $bases);
			$bases = explode(',', $bases);
			foreach($bases as $base) {

				$hosts[$host][] = trim($base);

			}

		}

		// If there is only one host on dbConnect() method get it
		if(empty($hosts)) {
			$hosts[] = $ini['connection']['host'] ?? NULL;
		}

		return $hosts;

	}

	/**
	 * Test if *.ini generated file is able to connect
	 * to each database before database.c.php file
	 * creation
	 */
	private static function testDatabase(string $fileTest, string $mode): array {

		$errors = [];

		if(is_file($fileTest)) {

			try {

				// Nettoyage des connexions actuelles
				\Database::resetPackages();
				\Database::resetServers();

				\ModuleModel::dbClean();

				// Inclusion du fichier de test
				require_once $fileTest;

				foreach(\Database::getPackages() as $package => $base) {

					$server = \Database::SERVER($package);

					$errors[$package] = 'Can\' connect to '.$server['host'].':'.$server['port'];

					$database = new \Database($package);
					$database->exec("USE ".$base);

					unset($errors[$package]);

				}

			} catch(\Exception $e) {

				$errors[$package] = $e->getMessage();

			}

		}

		return $errors;

	}

}
?>
