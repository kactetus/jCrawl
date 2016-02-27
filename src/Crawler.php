<?php namespace Crawler;

use DOMDocument;
use Carbon\Carbon;
use MongoClient;
use Crawler\PR;

class Reaver {

	/**
	 * Starting URL, arrays of URLs already indexed
	 * As well as an array of links that we find on pages.
	 * @var [string, array, array]
	 */
	public $seed;
	public $indexed = [];
	public $found = [];
	public $log;
	public $m;

	/**
	 * Construct the crawler, implement crawler settings
	 */
	public function __construct()
	{
		set_time_limit(1000); 
		libxml_use_internal_errors(true) AND libxml_clear_errors();
		//error_reporting(E_ALL & ~E_NOTICE);
		print '['.date('Y-m-d h:i:s a').'] Initializing Reaver...'."\n";
		$this->log = fopen(__DIR__.'/../storage/logs/'.date('Y-m').'-log.log', 'w');

		$this->m = new \MongoClient(); // connect

		$db = $this->m->reaver_index;

		$index = $db->index;

		$data = [
			"url" => ['$ne' => null]
		];
		$this->seed = $index->find($data)->sort(['created_at' => -1])->limit(1);

		foreach($this->seed as $found)
			$this->seed = $found['url'];
	}

	/**
	 * Close out the crawler print some stats.
	 */
	public function __destruct()
	{
		print 'Stats: '."\n";
		print '----------------------------------------------------------------'."\n";
		print 'Crawled....'. count($this->indexed) . ' Pages'. "\n";
		print 'Found......'. count($this->found) . ' Links'. "\n";
		print '['.date('Y-m-d h:i:s a').'] Shutting Reaver Down...'."\n";
		var_dump($this->seed);
		fclose($this->log);
	}

	/**
	 * Method that implements two functions to
	 * snag header information from each url.
	 * @return [array] [headers]
	 */
	public function headers()
	{
		return [ 
			'code' => (int) get_http_response_code($this->seed),
			'headers' => get_http_headers($this->seed)
		];
	}

	/**
	 * Initalize the crawler, grab the headers
	 * and create a document to crawl.
	 */
	public function init()
	{

		$headers = $this->headers();

		// Useful for determining a redirect and setting
		// the seed to the Location of the redirect.
		if(isset($headers['headers']["Location"]) && checkUrl($headers['headers']['Location']))
			$this->seed = $headers['headers']['Location'];

		if(in_array($this->seed, $this->indexed)) 
			return false;

		$get = fetch($this->seed);

		// Setup the DOM.
		$dom = new DOMDocument('1.0', 'UTF-8');
		@$dom->loadHTML( '<?xml encoding="UTF-8">' . $get['site'],  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$dom->normalizeDocument();
		$dom->formatOutput=true;
		$dom->encoding = 'UTF-8';
		$dom->preserveWhiteSpace = false;
		
		$this->indexed[] = $this->seed;

		if(is_array($this->seed))
			$this->seed = $this->seed[0];

		// Print out individual stats, letting the CLI know
		// where we're at in the crawl.
		print '['.$headers['headers'][0].'] '. $this->seed .
			 ' ('.$headers['code'].') ('.$get['info']['total_time']. ' seconds)' ."\n";
			 
		fwrite($this->log, '['.$headers['headers'][0].'] '. $this->seed .
			   ' ('.$headers['code'].') ('.$get['info']['total_time']. ' seconds)' ."\n");

		$db = $this->m->reaver_index;

		$html = $dom->saveHTML().PHP_EOL.PHP_EOL;
		$html = mb_convert_encoding($html, 'ISO-8859-1', 'UTF-8');
		$html = utf8_encode($html);

		$rank = new PR;

		removeElementsByTagName('script', $dom);
		removeElementsByTagName('style', $dom);

	    $title = $dom->getElementsByTagName('title');
		$title = $title ? $title[0]->nodeValue : $this->seed;

		if(empty($title)) $title = $this->seed;

		$metas = $dom->getElementsByTagName('meta');
		$description = '';

		foreach ($metas as $meta) {
		  if (strtolower($meta->getAttribute('name')) == 'description') 
		    $description = $meta->getAttribute('content');
		}

		if(is_null($description) || empty($description)) 
			$description = $dom->getElementsByTagName('body')[0]->nodeValue;

		$description = strip_html_tags($description);

		$allImages = $dom->getElementsByTagName('img');

		foreach($allImages as $img) {

			$src = $img->getAttribute('src');
			$alt = $img->getAttribute('alt');
			if(!checkImage($src)) continue;
			if(!checkUrl($src))
				$src = url_to_absolute($this->seed, $src);

			if(!checkUrl($src)) continue;

			$images[] = [
				'src' => $img->getAttribute('src'), 
				'alt' => $img->getAttribute('alt')
			];
		}

		$data = [
			'title' => $title, 
			'description' => $description, 
			'body' =>  $dom->getElementsByTagName('body')[0]->nodeValue,
			'images' => $images
		];

		$site = [
			'url' => $this->seed, 
			'status' => $headers['headers'][0], 
			'expires' => date('Y-m-d H:i:s'), 
			'data' => $data, 
			'rank' => !empty($rank->get_google_pagerank($this->seed)) ? $rank->get_google_pagerank($this->seed) : 0,
			'created_at'=> date('Y-m-d H:i:s'), 
			'updated_at'=> date('Y-m-d H:i:s')
		];
		
		try {
			$db->index->update(['url' => $site['url']], $site, ['upsert' => true]);
		} catch ( MongoConnectionException $e )  {
		    echo '<p>There was an error</p>'."\n";
		    die($e->getMessage());
		}

			
		// Loop through each link checking to make sure they
		// are not either already indexed or already found
		// and make sure they are real urls.
		foreach($dom->getElementsByTagName('a') as $link) {
			$a = url_to_absolute($this->seed, $link->getAttribute('href'));
			if(is_null($this->found) || !in_array($a, $this->found) && checkUrl($a) && !checkImage($a)) {
				$this->found[] = $a;
			} 
		}

	}

	/**
	 * Follow any links that are found, store them
	 * then reset the starting url and the initialization
	 * to fetch the link.
	 */
	public function follow()
	{
		$links = $this->found;

		if(empty($links)) {
			$links[] = readline('I don\'t have any links to follow, please provide one: '."\n");
		}

		foreach($links as $link) {
			if(!in_array($link, $this->indexed) && checkUrl($link)) {
				$this->seed = $link;
				$this->init();
			}
		}
	}

	/**
	 * Crawl the intial seed, follow the links
	 * reset the crawler to crawl each new 
	 * page.
	 */
	public function crawl()
	{
		$this->init();
		$this->follow();
		$this->crawl();
	}

}