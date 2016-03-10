<?php namespace Crawler;

use Crawler\Rank;

class Reaver extends Rank 
{

	public $url;

	public function setUrl($url)
	{
		$this->url = is_array($url) ? $url[1] : $url;
	}

	public function crawl()
	{
		$ch_1 = curl_init($this->url);

		curl_setopt($ch_1, CURLOPT_RETURNTRANSFER, true);

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