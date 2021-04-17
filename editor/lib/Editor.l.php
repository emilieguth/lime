<?php
namespace editor;

/**
 * Editor handling
 */
class EditorLib {

	/**
	 * Gets the open graph data for a URL
	 * If it doesn't work, it gets it from facebook
	 *
	 * @param string $url
	 *
	 * @return array data extracted
	 */
	public static function getOpenGraphData(string $url): array {

		if(\Filter::check('url', $url) === FALSE) {
			throw new \Exception('Invalid link');
		}

		$parse = parse_url($url);

		$scheme = $parse['scheme'] ?? NULL;
		$host = $parse['host'] ?? NULL;

		// Check link
		if($scheme !== 'http' and $scheme !== 'https') {
			throw new \Exception('Invalid link');
		}

		$ouvretaferme = (
			$host === \Lime::getHost()
		);

		$urlTest = $url;

		if($ouvretaferme) {

			if(strpos($urlTest, '?') === FALSE) {
				$urlTest .= '?';
			} else {
				$urlTest .= '&';
			}

		}

		$doc = new \DOMDocument();

		\dev\ErrorPhpLib::createExceptionFromError(TRUE);

		try {
			$fileContent = file_get_contents($urlTest);
		} catch(\Exception $e) {
			$fileContent = FALSE;
		}

		\dev\ErrorPhpLib::createExceptionFromError(FALSE);

		$title = NULL;
		$description = NULL;
		$image = NULL;
		$generator = NULL;
		$ouvretaferme = [];

		if($fileContent !== FALSE) {

			libxml_use_internal_errors(true);

			@$doc->loadHTML(mb_convert_encoding($fileContent, 'HTML-ENTITIES', 'UTF-8'));
			libxml_clear_errors();

			$meta = $doc->getElementsByTagName('meta');

			foreach($meta as $item) { // DOMElement Object

				$property =  $item->getAttribute("property");
				$content =  $item->getAttribute("content");

				if(strpos($property, 'og') === 0) {

					if($property === 'og:title' and $title === NULL) {
						$title = $content;
					} else if($property === 'og:description' and $description === NULL) {
						$description = $content;
					} else if($property === 'og:image' and $image === NULL) {
						$image = $content;
					}

				} else if(strpos($property, 'ouvretaferme-') === 0) {
					$ouvretaferme[substr($property, 8)] = (int)$content;
				}

			}

		}

		return [
			'title' => $title,
			'description' => $description,
			'image' => $image,
			'link' => $url,
			'generator' => $generator,
			'ouvretaferme' => $ouvretaferme
		];

	}
}
?>
