<?php namespace Crawler;

use Crawler\Rank;

class Reaver extends Rank 
{

	public $url;
	public $links;
	public $followed;
	public $agent = [
	         "User-Agent: reaver-dirge",
	         "Accept-Language: en-us"
    ];

	public function __construct()
	{
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";

		/*$this->ch = curl_init();
	    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($this->ch, CURLOPT_HEADER, true);
	    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->agent);
	    curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
	    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); 
	    curl_setopt($this->ch, CURLOPT_AUTOREFERER, true); */
	}


	public function setUrl($url)
	{
		$this->url = is_array($url) ? $url[1] : $url;
	}

	public function fetch()
	{
		$ch = curl_init($this->url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HEADER, true);
	    curl_setopt($ch, CURLOPT_HTTPHEADER, $this->agent);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
	    curl_setopt($ch, CURLOPT_AUTOREFERER, true); 
			
		$response = curl_exec($ch);

		return $response;
	}	

	public function links($site)
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $site,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$a = $dom->getElementsByTagName('a');
		foreach($a as $link) {
			$a = url_to_absolute($this->url, $link->getAttribute('href'));
			$a = rtrim($a, '#');
			$a = rtrim($a, '/');
			// Load the links
			if(checkUrl($a) && !checkImage($a)) $this->links[] = $a; 
		}

		$this->links = is_array($this->links) ? array_unique($this->links) : [$this->links];
		return $this->links;
	}

	public function init()
	{
		$res = $this->fetch();
		$this->links($res);
	}

	public function crawl()
	{
		$ch_1 = curl_init($this->url);

		curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch_1, CURLOPT_HEADER, true);
	    curl_setopt($ch_1, CURLOPT_HTTPHEADER, $this->agent);
	    curl_setopt($ch_1, CURLOPT_TIMEOUT, 60);
	    curl_setopt($ch_1, CURLOPT_FOLLOWLOCATION, true); 
	    curl_setopt($ch_1, CURLOPT_AUTOREFERER, true); 

		$mh = curl_multi_init();

		curl_multi_add_handle($mh, $ch_1);

		$running = null;

		do {
			curl_multi_exec($mh, $running);
		} while ($running);

		$response_1 = curl_multi_getcontent($ch_1);
		var_dump($response_1);

	}

}