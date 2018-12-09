<?php

use \Hcode\Model\User;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Page;

$app->get("/checkout", function(){

	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address();

	if (isset($_GET['zipcode'])){

		$_GET['zipcode'] = $cart->getdeszipcode();

	}

	if (isset($_GET['zipcode'])){
		$address->loadFromCEP($_GET['zipcode']);
		$cart->setdeszipcode($_GET['zipcode']);
		$cart->save();
		$cart->updateFreight();
	}

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout", [
		"cart"=>$cart->getValues(),
		"address"=>$address->getValues(),
		"products"=>$cart->getProducts(),
		"error"=>$cart->getMsgError(),
		"totais"=>$cart->getProductsTotals()
	]);

});

$app->post("/checkout", function(){

	User::verifyLogin(false);

	if (
		!isset($_POST['zipcode'] )
		||
		$_POST['zipcode'] === ''
	){
		Cart::setMsgError("Informe o CEP.");
		header("Location: /checkout");
		exit;
	}

	if (
		!isset($_POST['desaddress'] )
		||
		$_POST['desaddress'] === ''
	){
		Cart::setMsgError("Informe o Endereço.");
		header("Location: /checkout");
		exit;
	}

	if (
		!isset($_POST['desdistrict'] )
		||
		$_POST['desdistrict'] === ''
	){
		Cart::setMsgError("Informe o Bairro.");
		header("Location: /checkout");
		exit;
	}

	if (
		!isset($_POST['descity'] )
		||
		$_POST['descity'] === ''
	){
		Cart::setMsgError("Informe a Cidade.");
		header("Location: /checkout");
		exit;
	}

	if (
		!isset($_POST['desstate'] )
		||
		$_POST['desstate'] === ''
	){
		Cart::setMsgError("Informe o Estado.");
		header("Location: /checkout");
		exit;
	}


	if (
		!isset($_POST['descountry'] )
		||
		$_POST['descountry'] === ''
	){
		Cart::setMsgError("Informe o País.");
		header("Location: /checkout");
		exit;
	}


	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	header("Location: /order");
	exit;

});

?>