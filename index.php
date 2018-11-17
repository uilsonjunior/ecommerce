<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
//use \Hcode\DB; usada paenas pra testar nos códigos abaixo

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
	
	//echo $_SERVER["DOCUMENT_ROOT"]."/udemy/projeto_ecommerce/views";
	
	$page = new Page();

	$page->setTpl("index");

	//$sql = new Hcode\DB\Sql();

	//$results = $sql->select("SELECT * FROM tb_users");

	//echo json_encode($results);

});

$app->run();

 ?>