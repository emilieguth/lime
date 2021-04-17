<?php
/*
Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Get stats on lang files
 *
 * @author Vincent Guth (modified by Claire COPET)
 */
(new Page(function($data) {

	$app = GET('app', 'string', LIME_APP);
	$package = GET('package');

	$package = new ReflectionPackage($package, $app);

	if($package->exists() === FALSE) {
		throw new FailAction("Package '".$package->getPackageName()."' does not exist.");
	}

	$data->package = $package;

}))
	->cli('index', function($data) {

		$stats = [];

		foreach($data->package->getLangs() as $lang) {

			list($nFiles, $nMessages, $nWords) = language\StatsLib::count($data->package, $lang);

			$stats[$lang] = [
				'files' => $nFiles,
				'messages' => $nMessages,
				'words' => $nWords
			];

		}

		throw new JsonAction($stats);

	});
?>
