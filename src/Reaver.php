<?php namespace Crawler;

use Crawler\Rank;

class Reaver 
{

	public $url;
	public $error;

	/**
	 * Setting up the crawler and displaying some nice
	 * messages to get you going in the morning. 
	 * This also set ups some error handling that
	 * helps avoid issues with the DOMDocument object.
	 */
	public function __construct()
	{
		set_time_limit(1000); 
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		//error_reporting(E_ALL & ~E_NOTICE);
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";
	}

	/**
	 * We're done.
	 */
	public function __destruct()
	{
		if(!is_null($this->error)) {
			print 'Crawl failed... '."\n";
			print $this->error . "\n";
			print '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'."\n";
			exit;
		}

		print 'Stats: '."\n";
		print '----------------------------------------------------------------'."\n";
		print 'Crawled....'. count($this->url) . ' Pages'. "\n";
		print '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'."\n";
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
			'status' => get_http_headers($this->url)
		];
	}

	/**
	 * Nothing fancy here, just fetching the website.
	 * @return [array] [returns an array of information that
	 * pulls the html and basic info about the call to the site]
	 */
	public function get()
	{
		return fetch($this->url);
	}

	/**
	 * Gather's links, TODO: implement nofollow compliance.
	 * @param  [html string] $site [Takes the html string response 
	 * from a cURL call]
	 * @return [array]       [Returns an array of links scraped from the page]
	 */
	public function links($site)
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $site,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$dom->normalizeDocument();
		$dom->formtOutput=true;
		$dom->encoding = 'UTF-8';
		$dom->preserveWhiteSpace = false;

		$a = $dom->getElementsByTagName('a');
		$links = [];

		foreach($a as $link) {
			$a = url_to_absolute($this->url, $link->getAttribute('href'));
			$a = rtrim($a, '#');
			$a = rtrim($a, '/');
			if(checkUrl($a) && !checkImage($a) && $a !== $this->url) {
				$links[] = $a;
			} 
		}

		return $links;
	}

	/**
	 * The backbone of the crawler, gather's the headers
	 * and grabs the HTML content of a site as well
	 * as the information about the fetch.
	 * @return [JSON] [displays the results in json format as well
	 * as writes the content to a json file. Content is set to override whatever
	 * was there previously]
	 */
	public function init()
	{	
		$headers = $this->headers();
		$response = $this->get();

		if($headers['code'] === 301) {
			print '['.$headers['status'][0].'] '.$this->url. "\n" .'Redirecting...'. "\n";

			$this->url = $response['info']['redirect_url'];

			$headers = $this->headers();
			$response = $this->get();

			print '['.$headers['status'][0].'] '.$this->url."\n";
		}

		$rank = new Rank;

		$result = [
			'url' => $this->url,
			'links' => $this->links($response['html']),
			'rank' => $rank->getRank($this->url),
			'headers' => $headers, 
			'site' => $response
		];

		$result = json_encode($result);

		file_put_contents('response.json', indent($result));//, FILE_APPEND | LOCK_EX);

		return indent($result);
	}

	/**
	 * Get to crawling
	 * @param  [string] $uri [Takes the string url entered from the cli]
	 * @return [method]      [Starts the init on the crawler to actually fetch the site.]
	 */
	public function crawl($uri)
	{
		// Check for too many arguments
		if(count($uri) > 2) {
			$this->error = 'Too many arguments';
			return dd('Too many arguments');
		}

		// Check if a valid url has been input
		if(!checkUrl($uri[1])) {
			$this->error = 'Please use the correct html format'."\n".'Example: http://www.example.com';
			return dd('Please use the correct html format'."\n".'Example: http://www.example.com');
		}

		$this->url = $uri[1];

		return $this->init();
	}
}