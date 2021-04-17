<?php
/*
Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

(new Page())
	/**
	 * Build a DIA environment in the specified package
	 *
	 */
	->cli('index', function($data) {

		$flags = strtoupper(GET('flags'));
		$selectedModule = GET('module');
		$selectedPackage = GET('package');


		if($flags === '') {
			throw new FailAction("Argument flags=? is missing (type ".LIME_REQUEST_PATH.":help)");
		}

		if(strpos($flags, 'B') or strpos($flags, 'T') or strpos($flags, 'D')) {

			if(
				$selectedPackage === '' or
				(ctype_alnum($selectedPackage) === FALSE and $selectedPackage !== '*')
			) {
				throw new FailAction("Argument package=? is mandatory with flags B, T, D (type ".LIME_REQUEST_PATH.":help)");
			}

			if(
				$selectedModule === '' or
				(ctype_alnum($selectedModule) === FALSE and $selectedModule !== '*')
			) {
				throw new FailAction("Argument module=? is mandatory with flags B, T, D (type ".LIME_REQUEST_PATH.":help)");
			}

		} else if($flags === 'E') {

			if($selectedModule === '') {
				$selectedModule = '*';
			}

			if($selectedPackage === '') {
				$selectedPackage = '*';
			}

		}

		$libDia = new dev\DiaLib();
		$libDia->load();

		$classes = $libDia->getClasses();

		if($classes === []) {
			throw new FailAction("No valid class selected");
		}

		$found = 0;

		if(strpos($flags, 'E') !== FALSE) {

			foreach($classes as $class) {

				list($package, $module) = explode('\\', $class);

				if($flags === 'E') {

					if($selectedPackage !== '*' and $selectedPackage !== $package) {
						continue;
					}

					if($selectedModule !== '*' and $selectedModule !== $module) {
						continue;
					}

				}

				echo $class.": ";

				try {
					$libDia->buildModule($class);
					echo 'OK';
					$found++;
				}
				catch(Exception $e) {
					dev\ErrorPhpLib::handle($e);
				}

				echo "\n";

			}

		}

		$command = '';

		foreach(getConstants() as $name => $value) {
			$command .= " -c ".$name."=".$value."";
		}

		$actions = [
			'D' => 'finalize',
			'T' => 'init',
			'B' => 'rebuild'
		];

		foreach($actions as $flag => $action) {

			if(strpos($flags, $flag) !== FALSE) {

				foreach($classes as $class) {

					list($package, $module) = explode('\\', $class);

					if($selectedPackage !== '*' and $selectedPackage !== $package) {
						continue;
					}

					if($selectedModule !== '*' and $selectedModule !== $module) {
						continue;
					}

					$output = dev\SystemLib::command(LIME_APP, '-e '.LIME_ENV.' dev/module:'.$action.' package='.$package.' module='.$module);

					echo implode("\n", $output)."\n";
					$found++;

				}

			}
		}

		if($found === 0) {
			throw new FailAction("No class found");
		}

	})
	/**
	 * Display help
	 */
	->cli('help', function($data) {

		echo "Usage: dev/module package=[package name] module=[module name] ...\n";
		echo "\n";
		echo "DESCRIPTION\n";
		echo "	Build modules from a Dia module.\n";
		echo "\n";
		echo "OPTIONS\n";
		echo "	package=[package name]\n";
		echo "		The name of the package (use * for all packages).\n";
		echo "		This option is MANDATORY for DTB flags.\n";
		echo "	module=[module name]\n";
		echo "		Limit the build to the specified module (use * for all modules).\n";
		echo "		This option is MANDATORY for DTB flags.\n";
		echo "	flags=MDTB\n";
		echo "		E : build element / query files (automatically build files for all packages and all modules).\n";
		echo "		D : drop module table.\n";
		echo "		T : create module table.\n";
		echo "		B : rebuild module table.\n";

	});
?>
