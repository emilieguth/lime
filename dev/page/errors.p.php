<?php
/*
Copyright (C) 2006 Vincent Guth

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
- Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
- Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/**
 * Import dev errors for Android devices
 *
 * @author Vincent Guth
 */
(new Page())
	->post('importAndroid', function($data) {

		$appVersion = POST('APP_VERSION_NAME', 'string', '?');
		$appCode = POST('APP_VERSION_CODE', '?int');
		$stackTrace = POST('STACK_TRACE');
		$customData = POST('CUSTOM_DATA');

		$match = NULL;

		if(preg_match('/user\s*\=\s*([0-9]+)/', $customData, $match) > 0) {

			$eUser = new \user\User([
				'id' => (int)$match[1]
			]);

			\dev\ErrorPhpLib::overrideUser($eUser);

		}

		$match = NULL;

		if(post_exists('code')) {
			$codeText = POST('code');
		} else if(preg_match('/code\s*\=\s*([a-zA-Z]+)/', $customData, $match) > 0) {
			$codeText = $match[1];
		} else {
			$codeText = NULL;
		}

		$errorMessage = $stackTrace ? strstr($stackTrace, "\n", TRUE) : 'No stack trace provided';

		$message = '[Android app '.$appVersion.'] '.$errorMessage;

		switch($codeText) {

			case 'Fatal' :
				$codePhp = E_ERROR;
				break;

			case 'Warning' :
				$codePhp = E_WARNING;
				break;

			case 'Notice' :
				$codePhp = E_NOTICE;
				break;

			default :
				$codePhp = (POST('IS_SILENT') === 'true') ? E_WARNING : E_ERROR;
				break;

		}

		$deprecated = (
			$appCode !== NULL and
			$appCode < \Setting::get('main\androidRequiredVersion')
		); // N'a rien à faire là

		\dev\ErrorPhpLib::handleError(\dev\Error::ANDROID, $codePhp, $message, NULL, NULL, [], $deprecated);

	});
?>
