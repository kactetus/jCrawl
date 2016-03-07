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
    public $response;

	public function __construct()
	{
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";

		$this->ch = curl_init();
	    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($this->ch, CURLOPT_HEADER, 0);
	    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->agent);
	    curl_setopt($this->ch, CURLOPT_TIMEOUT, 60);
	    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); 
	}

	public function __destruct()
	{
		print "\n\n".'Stats: '."\n";
		print '----------------------------------------------------------------'."\n";
		print 'Crawled....'. count($this->url) . ' Pages'. "\n";
		print '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'."\n";
	}

	public function setUrl($url)
	{
		$this->url = $url[1];
	    curl_setopt($this->ch, CURLOPT_URL, $this->url);
	    $this->response = curl_getinfo($this->ch);
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
		return curl_exec($this->ch);
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
		$this->links = array_unique($this->links);
		return $this->links;
	}


	public function init()
	{
		$headers 	= $this->headers();
		$response 	= $this->response;
		$links 		= $this->links();

		echo "[".$headers->status[0] ."] >> " . $this->url ."\n";

		$result = [
			'headers' => $headers,
			'result' => $response,
			'html' => $this->fetch(),
			'links' => $links
		];		

		$result = json($result);

		$this->followed[] = $this->url;
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
		$this->follow();
	}

}