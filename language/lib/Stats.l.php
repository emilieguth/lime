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
 * Get stats for language files
 *
 * @author Vincent Guth
 */
class StatsLib {

	/**
	 * Count number of files, messages and words for the given package and lang
	 *
	 * @param \ReflectionPackage $package
	 * @param string $lang
	 */
	public static function count(\ReflectionPackage $package, string $lang): array {

		$nFiles = 0;
		$nMessages = 0;
		$nWords = 0;

		$libPattern = new PatternLib($package);

		$package->browse('lang/'.$lang, '.m.php', function($file) use($libPattern, $lang, &$nFiles, &$nMessages, &$nWords) {

			$path = $file->getPathname();

			// Check if path match pattern.cfg file for this lang
			if($libPattern->match($path, $lang) === FALSE) {
				return;
			}

			// Get messages
			$messages = ParserLib::extractFromLang($path);

			// Compute stats for messages
			$nFiles++;
			$nMessages += count($messages);

			foreach($messages as $texts) {

				$texts = (array)$texts;

				$nWords += array_reduce($texts, function($words, $text) {
					return $words + count(preg_split("/\s+/s", $text));
				}, 0);

			}

		});

		return [$nFiles, $nMessages, $nWords];

	}

}
?>
