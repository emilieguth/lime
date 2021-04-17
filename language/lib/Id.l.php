<?php
namespace language;

/*

Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Handle ID for language files
 *
 * @author Vincent Guth
 */
class IdLib {

	/**
	 * Check ID on each view to find duplicates
	 *
	 * @param ReflectionPackage $package Package
	 * @return array $duplicates Return array with message and number of duplicate ID found
	 */
	public static function checkIntegrity(\ReflectionPackage $package): array {

		$duplicates = [];

		$package->browse(['view/', 'ui/'], ['.v.php', '.u.php'], function($file) use(&$duplicates) {

			$path = $file->getPathname();

			$messages = ParserLib::extractFromView($path, FALSE);

			foreach($messages as list($id, $texts)) {

				if(isset($duplicates[$id]) === FALSE) {

					$duplicates[$id] = [
						'texts' => $texts,
						'count' => 1,
						'paths' => [$path]
					];

				} else {

					$duplicates[$id]['count']++;
					$duplicates[$id]['paths'][] = $path;

				}


			}

		});

		$duplicates = array_filter($duplicates, function($duplicate) {
			return ($duplicate['count'] > 1);
		});

		return $duplicates;

	}


	/**
	 * Get a new translation ID for a new message
	 * The counter is stored in /lang/lastId
	 *
	 * @param ReflectionPackage $package Package
	 * @return int
	 */
	public static function getNewId(\ReflectionPackage $package): int {

		$lastIdFile = $package->getPath().'/lang/lastId';

		if(is_file($lastIdFile)) {

			$lastId = (int)file_get_contents($lastIdFile) + 1;

		} else {

			$lastId = 1;

		}

		file_put_contents($lastIdFile, $lastId);

		return $lastId;

	}

}
?>
