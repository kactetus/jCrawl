<?php namespace Crawler;

use Crawler\Rank;

class Reaver extends Rank 
{

	public $url;
	public $followed;
	public $links;
	public $agent = [
	         "User-Agent: reaver-dirge",
	         "Accept-Language: en-us"
    ];

    public function __construct()
	{
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";
	}

	public function __destruct()
	{
		print "\n\n".'Stats: '."\n";
		print '----------------------------------------------------------------'."\n";
		print 'Crawled.... '. number_format(count($this->followed)) . ' Pages'. "\n";
		print 'Found.... '. number_format(count($this->links)) . ' Links'. "\n";
		print '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'."\n";
	}

	public function setUrl($url)
	{
		$this->url = is_array($url) ? $url[1] : $url;
	}

	public function headers()
	{
		@$headers = get_headers($this->url);

		$code = substr($headers[0], 9, 3);

		$array = [
			'code' => $code, 
			'status' => $headers
		];

		return json($array, true);
	}

	public function fetch()
	{
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->agent); 
		return curl_exec($ch);
	}

	public function links()
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML( '<?xml encoding="UTF-8">' . $this->fetch(),  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
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
		$headers 	= $this->headers();
		$links 		= $this->links();

		echo "[".$headers->status[0] ."] >> " . $this->url ." >> (0) \n";	
		
		$result = [
			'headers' => $headers,
			'html' => $this->fetch(),
		];		

		$result = json($result);

		$this->followed[] = $this->url;

		var_dump($result);
	}


	public function follow()
	{
		foreach($this->links as $link) {
			if(in_array($link, $this->followed)) {
				unset($this->links[$link]);
				continue;
			}
			$this->setUrl([0, $link]);
			$this->crawl();
		}
	}

	public function crawl()
	{
		$this->init();
		//$this->follow();
	}

}