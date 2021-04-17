<?php
(new Page())
	->post('configureVideo', function($data) {

		throw new ViewAction($data);

	})
	->post('configureMedia', function($data) {

		$data->instanceId = POST('instanceId');
		$data->url = POST('url');
		$data->xyz = POST('xyz', '?string');
		$data->title = POST('title');
		$data->license = POST('license');
		$data->source = POST('source');
		$data->figureSize = POST('figureSize', 'int');

		throw new ViewAction($data);

	})
	->post('getExternalLink', function($data) {

		$url = POST('url');

		try {
			$og = editor\EditorLib::getOpenGraphData($url);
		} catch(Exception $e) {
			throw new VoidAction();
		}

		$title = $og['title'];

		if(strlen($title) > 80) {
			$title = mb_substr($title, 0, 77).'...';
		}

		$description = (string)$og['description'];
		$description = strip_tags($description);

		if(strlen($description) > 200) {
			$description = mb_substr($description, 0, 197).'...';
		}

		$data->title = $title;
		$data->description = $description;
		$data->image = $og['image'];
		$data->link = $og['link'];

		$data->url = $url;

		throw new ViewAction($data, path: ':getExternalLink');

	});
?>
