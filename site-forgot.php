<?php

use \Hcode\Model\User;
use \Hcode\Page;

$app->get("/forgot", function(){

	$page = new Page();

	$page->setTpl("forgot");

});

$app->post("/forgot", function(){

	$user = User::getForgot($_POST["email"],false); //false serve para dizer que não e administrador 

	header("Location: /forgot/send");
	exit;

});

$app->get("/forgot/send", function(){

	$page = new Page();

	$page->setTpl("forgot-sent");

});

$app->get("/forgot/reset", function(){

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));	


});

$app->post("/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);	

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, [
		"cost"=>12
	]);

	$user->setPassword($password);

	$page = new Page();

	$page->setTpl("forgot-reset-success");	

});

?>