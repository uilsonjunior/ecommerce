<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
	//echo "OK";
	$page = new Page();

	$page->setTpl("index");

});

$app->run();

 ?>