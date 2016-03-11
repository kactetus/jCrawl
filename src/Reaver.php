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
	public $title;
	public $description;

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

	public function scrape($html)
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

		$title = $dom->getElementsByTagName('title')[0];
		$title = $title->nodeValue;
		$meta = $dom->getElementsByTagName('meta');
		$description = '';

		foreach($meta as $desc) {
			if($desc->hasAttribute('name') && $desc->getAttribute('name') == 'description') {
				$description = $desc->getAttribute('content');
				break;
			} else {
				$body = $dom->getElementsByTagName('body')[0];
				$body = isset($body->nodeValue) ? $body->nodeValue : '';
				$description = truncate($body, 1000);
			}
		}

		$this->title = $title;
		$this->description = $description;

		$this->links = is_array($this->links) ? array_unique($this->links) : [$this->links];
		$this->links = array_values($this->links);
	}

	public function index($html, $headers, $url)
	{
		$indexed = [
			'url' => $url,
			'title' => $this->title, 
			'description' => $this->description,
			'headers' => $headers,
			'site' => strip_tags($html)
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
			$this->url = $this->links[$i];
		}

		$results = array();

		$start = microtime(true);

		echo '['.date('Y-m-d h:i:s a').'] Fetching Seed url...'. PHP_EOL;

		$this->setCallback(function(Request $request, Curl $rollingCurl) use (&$results) {

		    $this->scrape($request->responseText);   

		    $this->index($request->responseText, $request->responseInfo, $request->getUrl());
			  
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