<?php namespace Crawler;

use \DOMDocument;
use Carbon\Carbon;
use Crawler\Sites;

class Reaver extends DOMDocument
{
	public $url;
	public $links;
	public $followed = [];
	public $follow;
	public $ch;
	public $mh;
	public $agent = ["User-Agent: reaver-dirge", "Accept-Language: en-us"];
	public $html;
	public $title;
	public $description;
	public $indexed;
	
	public function __construct()
	{
		parent::__construct("1.0", "UTF-8");

		$this->registerNodeClass('DOMNode', __NAMESPACE__ . '\Reaver');

		$this->preserveWhiteSpace = false;
		$this->strictErrorChecking = false;

		$this->url = getenv("SERVER");

		$this->follow = false;

		$this->mh = curl_multi_init();

		libxml_use_internal_errors(true) AND libxml_clear_errors();
		echo '['.Carbon::now().'] Initializing Reaver...'. PHP_EOL;

	}

	public function __destruct()
	{
		echo "\n\n".'Stats: '. PHP_EOL;
		echo '----------------------------------------------------------------'. PHP_EOL;
		echo 'Found....'. count($this->links) . ' Links'.  PHP_EOL;
		echo 'Indexed....'. count($this->followed) . ' Pages'.  PHP_EOL;
		echo '----------------------------------------------------------------'. PHP_EOL;
		echo 'Results:'.PHP_EOL;
		file_put_contents('response.json', $this->indexed);
		echo '['.Carbon::now().'] Shutting Reaver Down...'. PHP_EOL;
	}

	public function setUrl($url)
	{
		$this->url = $url;
	}

	public function init()
	{
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $this->url);
	    curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->agent);
	    curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true); 
	}

	public function fetch()
	{
		$this->init();
		curl_multi_add_handle($this->mh, $this->ch);

		$running = null;

		do {
			curl_multi_select($this->mh);
			curl_multi_exec($this->mh, $running);
		} while ($running);

		$this->html = curl_multi_getcontent($this->ch);

		$this->scrape();

		$this->followed[] = $this->url;
	}


	public function scrape()
	{
		$this->loadHTML( '<?xml encoding="UTF-8">' . $this->html,  LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$a = $this->getElementsByTagName('a');
		foreach($a as $link) {
			$a = url_to_absolute($this->url, $link->getAttribute('href'));
			$a = rtrim($a, '#');
			$a = rtrim($a, '/');
			// Load the links
			if(checkUrl($a) && !checkImage($a)) $this->links[] = $a; 
		}

		$title = $this->getElementsByTagName('title')[0];
		$title = !is_null($title) ? $title->nodeValue : $this->url;
		$meta = $this->getElementsByTagName('meta');
		$description = '';

		foreach($meta as $desc) {
			if($desc->hasAttribute('name') && $desc->getAttribute('name') == 'description') {
				$description = $desc->getAttribute('content');
				break;
			} else {
				$body = $this->getElementsByTagName('body')[0];
				$body = isset($body->nodeValue) ? $body->nodeValue : '';
				$description = truncate($body, 1000);
			}
		}

		$this->links = is_array($this->links) ? array_unique($this->links) : [$this->links];
		$this->links = array_values($this->links);

		$this->title = $title;
		$this->description = $description;
		$this->index();


	public function fetch()
	{
		$dom = fetch($this->links[0]);
		$this->scrape($dom['html'], $this->links[0]);
		echo '['.Carbon::now().'] Found seed url >> '. $this->links[0] . PHP_EOL;
		echo '['.Carbon::now().'] Starting crawl... '.PHP_EOL;

		$this->followed[] = $this->links[0];

	}

	public function index()
	{
		
		$site = Sites::where('url', $this->url)->first();
		if(is_null($site)) $site = new Sites;		
		$site->url = $this->url;
		$site->title = $this->title;
		$site->description = $this->description;
		$site->html = preg_replace('/(\s)+/', ' ', strip_tags($this->html));
		$site->expires = Carbon::now()->addWeeks(2);

		$site->save();

		echo '['.Carbon::now().'] (200) >> '. $this->url .PHP_EOL;
	}
	
	public function run()
	{
		foreach($this->links as $link) {
			$this->url = $link;
			$this->fetch();
		}
	}
}