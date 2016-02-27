<?php namespace Crawler;

class Reaver 
{

	public $url = 'http://golectures.com';

	public function __construct()
	{
		set_time_limit(1000); 
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		//error_reporting(E_ALL & ~E_NOTICE);
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";
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
			'headers' => get_http_headers($this->url)
		];
	}

	public function get()
	{
		return fetch($this->url);
	}

	public function crawl()
	{
		$headers = $this->headers();
		$site = $this->get();
		var_dump($headers);
		var_dump($site);
	}
}