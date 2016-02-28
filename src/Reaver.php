<?php namespace Crawler;

use Crawler\Rank;

class Reaver 
{

	public $url;

	public function __construct()
	{
		set_time_limit(1000); 
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		//error_reporting(E_ALL & ~E_NOTICE);
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";
	}

	public function __destruct()
	{
		print 'Stats: '."\n";
		print '----------------------------------------------------------------'."\n";
		print 'Crawled....'. count($this->url) . ' Pages'. "\n";
		print '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'."\n";
	}

	/**
	 * Method that implements two functions to
	 * snag header information from each url.
	 * @return [array] [headers]
	 */
	public function headers()
	{
		return [ 
			'code' => (int) get_http_response_code($this->url),
			'status' => get_http_headers($this->url)
		];
	}

	public function get()
	{
		return fetch($this->url);
	}

	public function links($site)
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $site,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$dom->normalizeDocument();
		$dom->formtOutput=true;
		$dom->encoding = 'UTF-8';
		$dom->preserveWhiteSpace = false;

		$a = $dom->getElementsByTagName('a');
		$links = [];

		foreach($a as $link) {
			$a = url_to_absolute($this->url, $link->getAttribute('href'));
			$a = rtrim($a, '#');
			$a = rtrim($a, '/');
			if(checkUrl($a) && !checkImage($a) && $a !== $this->url) {
				$links[] = $a;
			} 
		}

		var_dump($links);

		return $links;
	}

	public function init()
	{	
		$headers = $this->headers();
		$response = $this->get();
		$rank = new Rank;

		$result = [
			'url' => $this->url,
			'links' => $this->links($response['html']),
			'rank' => $rank->getRank($this->url),
			'headers' => $headers, 
			'site' => $response
		];

		$result = json_encode($result);

		file_put_contents('response.json', indent($result));//, FILE_APPEND | LOCK_EX);

		return indent($result);
	}

	public function crawl($uri)
	{
		if(count($uri) > 2)
			die('Too many arguments');

		$this->url = $uri[1];
		return $this->init();
	}
}