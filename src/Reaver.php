<?php namespace Crawler;

use Crawler\Rank;
use \DOMDocument;
use Crawler\Request;
use Crawler\Curl;
use Carbon\Carbon;


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

		if(!validUrl($this->url) || !checkUrl($this->url)) 
			die('Please use a valid url');

		$this->links[] = $this->url;
	}

	public function scrape($html, $url)
	{
		$this->url = $url;
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $html,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$a = $dom->getElementsByTagName('a');
		foreach($a as $link) {
			$a = url_to_absolute($this->url, $link->getAttribute('href'));
			$a = rtrim($a, '#');
			$a = rtrim($a, '/');
			$a = strtok($a, "?");
			$a = strtok($a, "#");
			// Load the links
			if(checkUrl($a) && !checkImage($a)) $this->links[] = $a; 
		}

		$title = $dom->getElementsByTagName('title')[0];
		$title = !is_null($title) ? $title->nodeValue : $this->url;
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

		$this->index($url, $title, $description, $html);
		$this->links = is_array($this->links) ? array_unique($this->links) : [$this->links];
		$this->links = array_values($this->links);
	}

	public function index($url, $title, $description, $html)
	{
		$indexed = [
			'url' => $url,
			'title' => $title, 
			'description' => $description,
			'site' => strip_tags($html)
		];
	}

	public function fetch()
	{
		$dom = fetch($this->links[0]);
		$this->scrape($dom['html'], $this->links[0]);
	}

	public function crawl()
	{
		for($i = 0; $i < count($this->links); $i++) {
			if(in_array($this->links[$i], $this->followed)) {
				unset($this->links[$i]);
				$this->links = array_values($this->links);
				continue;
			}
			$this->get($this->links[$i]);
		}

		echo '['.date('Y-m-d h:i:s a').'] Fetching Seed url...'. PHP_EOL;

		$this->setCallback(function(Request $request, Curl $rollingCurl) {

		    $this->scrape($request->responseText, $request->getUrl());   
			  
		    echo '['.$request->responseInfo["http_code"].'] >> ' . $request->getUrl() . "(".$request->responseInfo['total_time']." seconds)" . PHP_EOL;

		    $this->followed[] = $request->getUrl();

	    })->setSimultaneousLimit(20)->execute();

	}

}