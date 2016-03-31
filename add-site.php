<?php 
require __DIR__.'/vendor/autoload.php';

use Crawler\Sites;
use Carbon\Carbon;

if(isset($_GET['drop-db']) && $_GET['drop-db'] == 'true') {
	shell_exec('php crawl --drop-db  &> /dev/null &');
	header('location: /?p=admin&site=dropped');
	exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {

	if(isset($_GET['indexAll']) && $_GET['indexAll'] == 'true') {
		$sites = Sites::orderBy('created_at', 'desc')->limit(10)->pluck('url');
		$i = 0;
		foreach($sites as $site) {

			shell_exec('php crawl '. $site . '  &> /dev/null &');
			if($i > 10) break;
		}

		header('location: /?p=admin&site=added');					
		exit;
	}

	$site = $_POST['site'];

	shell_exec('php crawl '. $site . '  &> /dev/null &');

	header('location: /?p=admin&site=added');
}

