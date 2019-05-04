<?php 

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;

$app->get('/', function() {
	
	$products = Product::listAll();	

	$page = new Page();

	$cart = Cart::getFromSession();

	$page->setTpl("index",[
		"products"=>Product::checkList($products)
	]);

});


$app->get("/categories/:idcategory", function($idcategory){

	$page = (isset($_GET["page"])) ? (int)$_GET["page"] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$page = new Page();

	$pages = [];

	for ($i=1; $i < $pagination["pages"] ; $i++) { 
		array_push($pages, [
			"link"=>"/categories/".$category->getidcategory()."?page=".$i,
			"page"=>$i
		]);
	}

	$page->setTpl("category",[
		"category"=>$category->getValues(),
		"products"=>$pagination["data"],
		"pages"=>$pages
	]);	

});

$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	$product->getFromUrl($desurl);

	$page = new Page();

	$page->setTpl("product-detail",[
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);

});

$app->get("/cart", function(){

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart",[
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'totais'=>$cart->getProductsTotals(),
		'error'=>Cart::getMsgError()
	]);

});

$app->get("/cart/:idproduct/add", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qts = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	$cart->addProduct($product, $qts);

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/minus", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/remove", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});

$app->post("/cart/freight", function(){

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;

});


$app->get("/login", function(){

	$page = new Page();

	$page->setTpl("login",[
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) 
							? $_SESSION['registerValues'] 
							: ['name'=>'','email'=>'','password'=>'','phone'=>'']
	]);

});

$app->post("/login", function(){

	try{	
		
		User::login($_POST['login'],$_POST['password']);
	
	}catch (Exception $e){

		User::setError($e->getMessage());

	}
	header("Location: /checkout");
	exit;

});

$app->get("/logout", function(){

	User::logout();

	header("Location: /login");
	exit;

});

$app->post("/register", function(){

	$_SESSION['registerValues'] = $_POST;

	if (
		!isset($_POST['name'])
		||
		$_POST['name'] === ''
	){
		User::setErrorRegister("Preencha o seu nome");
		header("Location: /login");
		exit;
	}
	if (
		!isset($_POST['email'])
		||
		$_POST['email'] === ''
	){
		User::setErrorRegister("Preencha o seu email");
		header("Location: /login");
		exit;
	}

	if (
		!isset($_POST['password'])
		||
		$_POST['password'] === ''
	){
		User::setErrorRegister("Preencha a sua senha");
		header("Location: /login");
		exit;
	}

	if (User::checkLoginExist($_POST['email'])){
		User::setErrorRegister("Endereço de e-mail já cadastrado");
		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']

	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	header("Location: /checkout");
	exit();

});

$app->get("/profile", function(){

	User::verifyLogin(false);

	$user = User::getFromSession();	

	$page = new Page();

	$page->setTpl("profile",[
		"user"=>$user->getValues(),
		"profileMsg"=>User::getSuccess(),
		"profileError"=>User::getError()
		]);


});

$app->post("/profile", function(){

	User::verifyLogin(false);

	$user = User::getFromSession();	

	if (
		!isset($_POST['desperson'])
		||
		$_POST['desperson'] === ''
	){
		User::setError("Preencha o seu nome");
		header("Location: /profile");
		exit;
	}

	if (
		!isset($_POST['desemail'])
		||
		$_POST['desperson'] === ''
	){
		User::setError("Preencha o seu e-mail");
		header("Location: /profile");
		exit;
	}	

	if ($_POST['desemail'] !== $user->getdesemail()){

		if (User::checkLoginExist($_POST['email'])){
			User::setErrorRegister("Endereço de e-mail já cadastrado");
			header("Location: /profile");
			exit;
		}

	}

	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();



	$user->setData($_POST);

	$user->update();

	User::setSuccess("Dados Alterados com Sucesso!");

	header("Location: /profile");
	exit;

});

$app->get("/profile/orders", function(){

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile-orders",[
		'orders'=>$user->getOrders()
	]);

});

$app->get("/profile/orders/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->getProductsTotals();

	$cart->getCalculateTotal((int)$order->getidcart());

	$cart->get((int)$order->getidcart());

	$page = new Page();

	$page->setTpl("profile-orders-detail",[
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);

});

?>