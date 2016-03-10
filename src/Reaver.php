<?php namespace Crawler;

use Crawler\Rank;

class Reaver extends Rank 
{

	public $url;
	public $followed;
	public $indexed;
	public $agent;

	public function __construct()
	{
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";
		$this->agent = [
			"User-Agent: reaver-dirge-".uniqid(),
			"Accept-Language: en-us"
    	];
	}

	public function setUrl($url = '')
	{
		$this->url = is_array($url) ? $url[1] : $url;
	}

	public function fetch($url = '')
	{
		if(empty($url))
			$url = $this->url;

		$ch = curl_init($url);

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->agent);
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
		curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);

		$mh = curl_multi_init();

		curl_multi_add_handle($mh, $ch);

		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while ($running);

		$response = curl_multi_getcontent($ch);

		return $response;
	}

	public function init()
	{
		$response = $this->fetch();	
		var_dump($response);
	}

	public function crawl() 
	{
		$this->init();
	}

}