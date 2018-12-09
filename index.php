<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;

//use \Hcode\DB; usada paenas pra testar nos códigos abaixo

$app = new Slim();

$app->config('debug', true);

require_once("functions.php");
require_once("site.php");
require_once("admin.php");
require_once("admin-categories.php");
require_once("admin-users.php");
require_once("admin-products.php");
require_once("site-forgot.php");
require_once("site-checkout.php");
require_once("site-order.php");

$app->run();

 ?>