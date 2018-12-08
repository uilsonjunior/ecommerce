<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\mailer;
use \Hcode\Model\User;


class Cart extends Model {

	const SESSION = "Cart";

	public static function getFromSession(){

		$cart = new Cart();

		if(isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[cart::SESSION]['idcart'] > 0){

			$cart->get((int)$_SESSION[cart::SESSION]['idcart']);

		}else{

			$cart->getFromSessionID();

			if ( !(int)$cart->getidcart() > 0 ){

				$data = [

					'dessessionid'=>session_id()

				];

				if (User::checkLogin(False)){

					$user = User::getFromSession();	

					$data['iduser'] = $user->getiduser();

				}

				$cart->setData($data);

				$cart->save();

				$cart->setToSession();

			}

		}

		return $cart;

	}

	public function setToSession(){

		$_SESSION[Cart::SESSION] = $this->getValues();

	}


	public function getFromSessionID(){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid",[
			":dessessionid"=>session_id()
		]);

		if(count($results) > 0){

			$this->setData($results[0]);
		
		}

	}


	public function get(int $idcart){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart",[
			":idcart"=>$idcart
		]);

		if(count($results) > 0){

			$this->setData($results[0]);
		
		}

	}

	public static function listAll(){

		$sql = new Sql();

		return $sql->select("SELECT * FROM tb_categories ORDER BY descategory");

		 
	}

	public function save()
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", array(
			":idcart"=>$this->getidcart(),
			":dessessionid"=>$this->getdessessionid(),
			":iduser"=>$this->getiduser(),
			":deszipcode"=>$this->getdeszipcode(),
			":vlfreight"=>$this->getvlfreight(),
			":nrdays"=>$this->getnrdays()
		));		

		$this->setData($results[0]);

		

	}


}

?>