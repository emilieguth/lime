<?php
(new Page(fn() => Privilege::check('dev\admin')))
	->get('index', function($data) {

		$data->page = GET('page', 'int');
		$data->number = 100;

		if(get_exists('id')) {

			$eError = dev\ErrorMonitoringLib::getById(GET('id'));

			if($eError->notEmpty()) {
				$data->cError = new Collection([$eError]);
			} else {
				$data->cError = new Collection();
			}

			$data->search = TRUE;

		} else {

			$data->search = FALSE;

			if(get_exists('content')) {
				$message = GET('content');
				$data->search = TRUE;
			} else {
				$message = NULL;
			}

			if(get_exists('unexpected')) {
				$unexpected = GET('unexpected', 'bool');
				$data->search = TRUE;
			} else {
				$unexpected = FALSE;
			}

			if(get_exists('user')) {
				$eUser = GET('user', 'user\User');
				$data->search = TRUE;
			} else {
				$eUser = new \user\User();
			}

			if(get_exists('type')) {
				$type = GET('type', '?string');
				$data->search = TRUE;
			} else {
				$type = NULL;
			}

			[$data->cError, $data->nError] = dev\ErrorMonitoringLib::getLast($data->page * $data->number, $data->number, $message, $type, $unexpected, $eUser);

		}

		throw new ViewAction($data);


	})
	->post('doStatus', function($data){

		$data->eError  = POST('id', 'dev\Error');

		if($data->eError->notEmpty()) {

			\dev\ErrorMonitoringLib::close($data->eError);

			throw new ViewAction($data);

		}

	})
	->post('doStatusByMessage', function($data){

		$message = POST('message');

		if($message !== '') {

			\dev\ErrorMonitoringLib::closeByMessage($message);

			throw new RedirectAction('/dev/admin/?success=dev:Error.closedByMessage');

		}

	});
?>
