<?php
require __DIR__.'/../../vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__.'/../../');
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

$capsule = new Capsule;

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'reaver_db',
    'username'  => 'homestead',
    'password'  => 'secret',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
    'port'		=> 33060
]);

// Make this Capsule instance available globally via static methods
$capsule->setAsGlobal();

// Setup the Eloquent ORM
$capsule->bootEloquent();


if(!$capsule->schema()->hasTable('sites')) {
	$capsule->schema()->create('sites', function(Blueprint $table) {
		$table->increments('id');
		$table->string('url');
		$table->string('title');
		$table->longText('description');
		$table->longText('html');
		$table->timestamp('expires');
		$table->timestamps();
	});
}