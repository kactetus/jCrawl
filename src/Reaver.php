<?php namespace Crawler;

use Crawler\Rank;
use \DOMDocument;

class Reaver extends Rank 
{
	public $url;
	public $links;
	public $followed = [];
	public $indexed;
	public $agent;
	public $crawling;
	public $mh;

	public function __construct()
	{
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		echo '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";
		$this->agent = [
			"User-Agent: reaver-dirge-".uniqid(),
			"Accept-Language: en-us"
    	];
    	$this->crawling = true;
    	$this->mh = curl_multi_init();
	}

	public function __destruct()
	{
		echo "\n\n".'Stats: '."\n";
		echo '----------------------------------------------------------------'."\n";
		echo 'Crawled....'. count($this->url) . ' Pages'. "\n";
		echo 'Found....'. count($this->links) . ' Links'. "\n";
		echo 'Indexed....'. count($this->links) . ' Pages'. "\n";
		echo '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'."\n";
	}

	public function setUrl($url = '')
	{
		$this->url = is_array($url) ? $url[1] : $url;
		$this->links[] = $this->url;
	}

	public function headers($url)
	{
		@$headers = get_headers($url);

		$code = substr($headers[0], 9, 3);

		$array = [
			'code' => $code, 
			'status' => $headers
		];

		return json($array, true);
	}

	public function index($html, $headers)
	{
		$indexed = [
			'headers' => $headers,
			'site' => $html
		];
		$this->indexed[] = json($indexed);
	}

	public function fetch()
	{
		for($i = 0; $i < count($this->links); $i++) {

			if(in_array($this->links[$i], $this->followed)) {
				unset($this->links[$i]);
				$this->links = array_values($this->links);
			}

			$this->url = $this->links[$i];

			$ch[$i] = curl_init($this->links[$i]);

			curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch[$i], CURLOPT_HEADER, 0);
			curl_setopt($ch[$i], CURLOPT_HTTPHEADER, $this->agent);
			curl_setopt($ch[$i], CURLOPT_HTTPGET, 1);
			curl_setopt($ch[$i], CURLOPT_TIMEOUT, 60);
			curl_setopt($ch[$i], CURLOPT_FOLLOWLOCATION, 1); 
			curl_setopt($ch[$i], CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

			curl_multi_add_handle($this->mh, $ch[$i]);

			$running = null;
			do {
				curl_multi_exec($this->mh, $running);
			} while ($running);

			$response[$i] = curl_multi_getcontent($ch[$i]);

			$this->followLinks($response[$i]);

			$headers = $this->headers($this->links[$i]);

			echo "[".$headers->status[0]. "] >> " .$this->links[$i] . "\n";

			$this->followed[] = $this->links[$i];

			$this->index('', $headers);

			if($i > 5) return $this->crawling = false; 
		}	
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

	public function init()
	{
		$this->fetch();	
	}

	public function crawl() 
	{
		do {
			$this->init();			
		} while($this->crawling);
	}

}