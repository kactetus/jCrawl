<?php namespace Crawler;

use \DOMDocument;
use Carbon\Carbon;

class Reaver extends DOMDocument
{
	public $url;
	public $links;
	public $followed = [];
	public $follow;
	public $ch;
	public $agent = ["User-Agent: reaver-dirge", "Accept-Language: en-us"];
	public $html;
     
	
	public function __construct()
	{
		parent::__construct("1.0", "UTF-8");

		$this->registerNodeClass('DOMNode', __NAMESPACE__ . '\Reaver');

		$this->preserveWhiteSpace = false;
		$this->strictErrorChecking = false;

		$this->url = getenv("SERVER");

		$this->follow = false;

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

	public function init()
	{
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
	    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($this->ch, CURLOPT_HEADER, 0);
	    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->agent);
	    curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
	    curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
	    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); 
	    curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	}

	public function fetch()
	{
		$this->html = curl_exec($this->ch);
		$this->scrape();
	}


	public function scrape()
	{
		$this->loadHTML( '<?xml encoding="UTF-8">' . $this->html,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$a = $this->getElementsByTagName('a');
		foreach($a as $link) {
			$a = url_to_absolute($this->url, $link->getAttribute('href'));
			$a = rtrim($a, '#');
			$a = rtrim($a, '/');
			$a = strtok($a, "?");
			$a = strtok($a, "#");
			// Load the links
			if(checkUrl($a) && !checkImage($a)) $this->links[] = $a; 
		}

		$title = $this->getElementsByTagName('title')[0];
		$title = !is_null($title) ? $title->nodeValue : $this->url;
		$meta = $this->getElementsByTagName('meta');
		$description = '';

		foreach($meta as $desc) {
			if($desc->hasAttribute('name') && $desc->getAttribute('name') == 'description') {
				$description = $desc->getAttribute('content');
				break;
			} else {
				$body = $this->getElementsByTagName('body')[0];
				$body = isset($body->nodeValue) ? $body->nodeValue : '';
				$description = truncate($body, 1000);
			}
		}

		$this->index($title, $description);
		$this->links = is_array($this->links) ? array_unique($this->links) : [$this->links];
		$this->links = array_values($this->links);
	}

	public function index($title, $description)
	{
		$indexed = [
			'url' => $this->url,
			'title' => $title, 
			'description' => $description,
			'site' => strip_tags($this->html)
		];
	}

	
	public function crawl()
	{
		$this->fetch();
	}

}