<?php
/*
Copyright (C) 2006-2007 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

(new Page())
	->cli('init', function($data) {

		$package = GET('package');
		$module = GET('module');

		try {

			(new ModuleAdministration($package.'\\'.$module))->init();

		} catch(Exception $e) {
			throw new FailAction("Processing error for '".$module."' (".str_replace("\n", " ", $e->getMessage()).")");
		}

		throw new DataAction('Init '.$module.': OK'."\n");

	})
	->cli('finalize', function($data) {

		$package = GET('package');
		$module = GET('module');

		try {

			(new ModuleAdministration($package.'\\'.$module))->finalize();

		} catch(Exception $e) {
			throw new FailAction("Processing error for '".$module."' (".str_replace("\n", " ", $e->getMessage()).")");
		}

		throw new DataAction('Finalize '.$module.': OK'."\n");

	})
	->cli('rebuild', function($data) {

		$package = GET('package');
		$module = GET('module');
		$default = GET('default', 'array');

		try {

			(new ModuleAdministration($package.'\\'.$module))->rebuild($default);

		} catch(Exception $e) {
			throw new FailAction("Processing error for '".$module."' (".str_replace("\n", " ", $e->getMessage()).")");
		}

		throw new DataAction('Rebuild '.$module.': OK'."\n");

	});
?>
