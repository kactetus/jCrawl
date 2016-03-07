<?php namespace Crawler;

class Reaver 
{
	public $url;
	public $followed;
	public $links;
	public $agent = [
	         "User-Agent: reaver-dirge",
	         "Accept-Language: en-us"
    ];

    public $ch;

	public function __construct()
	{
		$this->ch = curl_init();
	     
	    // 2. set the options, including the url
	    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($this->ch, CURLOPT_HEADER, 0);
	    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->agent);
	    curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
	    curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
	    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); 
	    curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
	}

	public function setUrl($url)
	{
		$this->url = $url[1];
	}

	public function fetch()
	{
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
		return curl_exec($this->ch);
	}

	public function crawl()
	{
		$response =  [
			$this->fetch(),
			curl_getinfo($this->ch)
		];

		curl_close($this->ch);

		var_dump($response);
	}

}