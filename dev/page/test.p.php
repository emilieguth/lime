<?php
/*
Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Run test pages
 *
 */
(new Page())
	->cli('index', function($data) {

		$selectedFile = GET('file', 'string', '*');
		$selectedPackage = GET('package', 'string', '*');

		if($selectedPackage === '*') {

			$packages = [];

			foreach(Package::getList() as $package => $app) {
				$packages[] = new ReflectionPackage($package, $app);
			}

		} else {
			$packages = [new ReflectionPackage($selectedPackage, Package::getApp($selectedPackage))];
		}

		foreach($packages as $package) {

			if($package->exists()) {

				\dev\TestLib::run($package, $selectedFile);

			} else {
				throw new FailAction("Package '".$package->getPackageName()."' does not exist");
			}

		}

	})
	/**
	 * Display help
	 */
	->cli('help', function($data) {

		echo "Usage: dev/test package=[package name] file=[file name] ...\n";
		echo "\n";
		echo "DESCRIPTION\n";
		echo "	Run test pages.\n";
		echo "\n";
		echo "OPTIONS\n";
		echo "	package=[package name]\n";
		echo "		The name of the package (use * for all packages).\n";
		echo "	file=[file name]\n";
		echo "		Limit the test to the specified file.\n";

	});
?>
