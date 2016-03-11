<?php namespace Crawler;

use Crawler\Rank;
use \DOMDocument;
use Crawler\Request;
use Crawler\Curl;


class Reaver extends Curl 
{
	public $url;
	public $links;
	public $followed = [];
	public $crawling;

	public function __construct()
	{
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		echo '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'. PHP_EOL;
		$this->crawling = true;
	}

	public function __destruct()
	{
		echo "\n\n".'Stats: '. PHP_EOL;
		echo '----------------------------------------------------------------'. PHP_EOL;
		echo 'Crawled....'. count($this->url) . ' Pages'.  PHP_EOL;
		echo 'Found....'. count($this->links) . ' Links'.  PHP_EOL;
		echo 'Indexed....'. count($this->followed) . ' Pages'.  PHP_EOL;
		echo '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'. PHP_EOL;
	}

	public function setUrl($url = '')
	{
		$this->url = is_array($url) ? $url[1] : $url;
		$this->links[] = $this->url;
	}

	public function followLinks($html)
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$a = $dom->getElementsByTagName('a');
		foreach($a as $link) {
			$a = url_to_absolute($this->url, $link->getAttribute('href'));
			$a = rtrim($a, '#');
			$a = rtrim($a, '/');
			// Load the links
			if(checkUrl($a) && !checkImage($a)) $this->links[] = $a; 
		}

		$this->links = is_array($this->links) ? array_unique($this->links) : [$this->links];
		$this->links = array_values($this->links);
	}

	public function index($html, $headers, $url)
	{
		$indexed = [
			'url' => $url,
			'headers' => $headers,
			'site' => $html
		];
	}

	public function fetch()
	{
		for($i = 0; $i < count($this->links); $i++) {
			if(in_array($this->links[$i], $this->followed)) {
				unset($this->links[$i]);
				$this->links = array_values($this->links);
				continue;
			}
			$this->get($this->links[$i]);
		}

		$results = array();

		$start = microtime(true);

		echo '['.date('Y-m-d h:i:s a').'] Fetching Seed url...'. PHP_EOL;

		$this->setCallback(function(Request $request, Curl $rollingCurl) use (&$results) {

		    $this->followLinks($request->responseText);   

		    $this->index($request->getUrl(), $request->responseText, $request->responseInfo);
			  
		    echo '['.$request->responseInfo["http_code"].'] >> ' . $request->getUrl() . "(".$request->responseInfo['total_time']." seconds)" . PHP_EOL;

		    $this->followed[] = $request->getUrl();

	    })->setSimultaneousLimit(20)->execute();

		echo "...done in " . (microtime(true) - $start) . PHP_EOL;

		echo "All results: " . PHP_EOL;

	}

	public function crawl()
	{
		do {
			$this->fetch();	
		} while ($this->crawling);
		
	}

}