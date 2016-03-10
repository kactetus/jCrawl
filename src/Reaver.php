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

    public $index;

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

	public function fetch() 
	{
		$ch = curl_init($this->url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_exec($ch);
	    $response = curl_multi_getcontent($ch);
	    return $response;
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
		$links 		= $this->links();

		echo "[] >> " . $this->url ." >> (0) \n";	
		
		$result = [
			'site' => $this->url,
			'html' => $this->fetch()
		];		

		$result = json($result, true);

		$this->followed[] = $this->url;
		$this->index[] = $result;
	}	

	public function follow()
	{
		$mh = curl_multi_init();
		$ch = [];
		$response = [];

		for($i = 0; $i < count($this->links); $i++) {
			$ch[$i] = curl_init($this->links[$i]);
			curl_multi_add_handle($mh, $ch[$i]);
			$running = null;
			do {
				curl_multi_exec($mh, $running);
			} while ($running);

			//$response[$i] = curl_multi_getcontent($ch[$i]);
 			//var_dump(curl_getinfo($ch[$i]));
		}

	}

	public function crawl()
	{
		$this->init();
		$this->follow();
	}	

}