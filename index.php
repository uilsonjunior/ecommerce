<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
//use \Hcode\DB; usada paenas pra testar nos códigos abaixo

$app = new Slim();

$app->config('debug', true);

$app->get('/', function() {
	
	$page = new Page();

	$page->setTpl("index");

	//$sql = new Hcode\DB\Sql();

	//$results = $sql->select("SELECT * FROM tb_users");

	//echo json_encode($results);

});

$app->get('/admin/', function() {
	
	//var_dump(User::verifyLogin());
	//var_dump($_SESSION);
	//var_dump((bool)$_SESSION[User::SESSION]["inadmin"]);
//
	if ( User::verifyLogin() == true ){

	$pageAdm = new PageAdmin();

	$pageAdm->setTpl("index");
	} else {
		header("Location: /admin/login/");
		exit;
	}
});

$app->get('/admin/login/', function() {
	
	//echo $_SERVER["DOCUMENT_ROOT"]."/udemy/projeto_ecommerce/views";
	
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	$page->setTpl("login");

});

$app->post('/admin/login/', function() {
	
	User::login($_POST["login"], $_POST["password"]);

	header("Location: /admin/");

	exit;

});

$app->get('/admin/logout/', function() {

	User::logout();

	header("Location: /admin/login");
	exit;

});

$app->run();

 ?>