<?php  

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;

$app->get("/admin/products", function(){

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";

	$pag = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){
		$pagination = Product::getPageSearch($search,$pag,3);
	}
	else{
		$pagination = Product::getPage($pag,3);
	}

	$pags = [];

	for ($i=1; $i < $pagination['pages']; $i++) { 
		array_push($pags, [
			'href'=>'/admin/products?'.http_build_query([
					'page'=>$i,
					'search'=>$search
				]),
			'text'=>$i
			]);
	}

	$page = new PageAdmin();

	$page->setTpl("products",	[
		"products"=>$pagination['data'],
		"search"=>$search,
		"pages"=> $pags
	]);		

});

$app->get("/admin/products/create", function(){

	User::verifyLogin();

	$product = Product::listAll();

	$page = new PageAdmin();

	$page->setTpl("products-create");

});

$app->post("/admin/products/create", function(){

	User::verifyLogin();

	$product = new Product();

	$product->setData($_POST);

	$product->save();

	header("Location: /admin/products");
	exit;

});

$app->get("/admin/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$page = new PageAdmin();

	$page->setTpl("products-update", [
		"product"=>$product->getValues()
	]);

});

$app->post("/admin/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$product->setData($_POST);

	$product->save();

	$product->setPhoto($_FILES["file"]);

	header("Location: /admin/products");
	exit;

});

$app->get("/admin/products/:idproduct/delete", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$product->delete();

	header("Location: /admin/products");
	exit;

});



?>