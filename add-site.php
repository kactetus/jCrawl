<?php 
require __DIR__.'/vendor/autoload.php';

use Crawler\Sites;

if(isset($_GET['drop-db']) && $_GET['drop-db'] == 'true') {
	shell_exec('php crawl --drop-db  &> /dev/null &');
	header('location: /?p=admin&site=dropped');
	exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	$site = $_POST['site'];

	if(isset($_GET['indexAll']) && $_GET['indexAll'] == 'true') {
		$sites = Sites::pluck('url')->limit(20);
		foreach($sites as $site) {
			shell_exec('php crawl '. $site . '  &> /dev/null &');

		}

		header('location: /?p=admin&site=added');					
		exit;
	}

	shell_exec('php crawl '. $site . '  &> /dev/null &');

	header('location: /?p=admin&site=added');
}

