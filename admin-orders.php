<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

$app->get('/admin/orders', function(){

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";

	$search = trim($search);

	$pag = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){
		$pagination = Order::getPageSearch($search,$pag,3);
	}
	else{
		$pagination = Order::getPage($pag,3);
	}

	$pags = [];

	for ($i=1; $i < $pagination['pages']; $i++) { 
		array_push($pags, [
			'href'=>'/admin/orders?'.http_build_query([
					'page'=>$i,
					'search'=>$search
				]),
			'text'=>$i
			]);
	}

	$page = new PageAdmin();

	$page->setTpl("orders",	[
		"orders"=>$pagination['data'],
		"search"=>$search,
		"pages"=> $pags
	]);		

/*	$users = User::listAll();

	$page = new PageAdmin();

	$page->setTpl("orders", array(
		"orders"=>Order::listAll()
	));
*/
});

$app->get("/admin/orders/:idorder/status",function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$page = new PageAdmin();

	$page->setTpl("order-status", [
		'order'=>$order->getvalues(),
		'status'=>OrderStatus::listAll(),
		'msgSuccess'=>Order::getSuccess(),
		'msgError'=>Order::getError(),
	]);

});

$app->post("/admin/orders/:idorder/status",function($idorder){

	User::verifyLogin();

	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0 ){
		Order::setError("Informe o status atual");
		header("Location: /admin/orders/".$idorder."/status");
		exit;		
	}

	$order = new Order();

	$order->get((int)$idorder);

	$order->setidstatus((int)$_POST['idstatus']);

	$order->save();

	Order::SetSuccess("Status alterado com sucesso!");

	header("Location: /admin/orders/".$idorder."/status");
	exit;

});


$app->get("/admin/orders/:idorder/delete",function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$order->delete();

	header("Location: /admin/orders");
	exit;

});

$app->get("/admin/orders/:idorder",function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$cart = $order->getCart();

	$cart->getCalculateTotal((int)$order->getidorder());

	$page = new PageAdmin();

	$page->setTpl("order", [
		'order'=>$order->getvalues(),
		'cart'=>$cart->getvalues(),
		'products'=>$cart->getProducts()
	]);

});

?>
