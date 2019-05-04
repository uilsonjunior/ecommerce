<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\mailer;
use \Hcode\Model\User;


class Cart extends Model {

	const SESSION = "Cart";
	const SESSION_ERROR = 'CarError';

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

	public function addProduct(Product $product, $qts){

		$sql = new Sql();

		$setenca = "INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES(:idcart, :idproduct)";

		for ($i=1; $i < $qts; $i++) { 
			$setenca .=",(:idcart, :idproduct)";
		}

		$sql->query($setenca,[
			"idcart"=>$this->getidcart(),
			"idproduct"=>$product->getidproduct()
		]);

		$this->updateFreight();

	}

	public function removeProduct(Product $product, $all = false){

		$sql = new Sql();

		if ($all){

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL",[
			"idcart"=>$this->getidcart(),
			"idproduct"=>$product->getidproduct()
			]);

		}else{

			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1",[
			"idcart"=>$this->getidcart(),
			"idproduct"=>$product->getidproduct()

		]);

		}

		$this->updateFreight();

	}

	public function getProducts(){

		$sql = new Sql();

		$rows = $sql->select("
				SELECT b.idproduct, b.desproduct, b.desurl, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, 
						COUNT(*) as nrqtd, SUM(b.vlprice) AS vltotal
					FROM tb_cartsproducts a 
					INNER JOIN tb_products b
							ON a.idproduct = b.idproduct
					WHERE 
						a.idcart = :idcart AND a.dtremoved IS NULL 
						GROUP BY b.idproduct, b.desproduct, b.desurl, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight
						ORDER BY b.desproduct", [
							":idcart"=>$this->getidcart()
						]);
			

		return Product::checkList( $rows );

	}

	public function getProductsTotals(){

		$sql = new Sql();

		

		$results = $sql->select("
				SELECT SUM(a.vlprice) AS vlprice, SUM(a.vlwidth) AS vlwidth, SUM(a.vlweight) AS vlweight, 
					   SUM(a.vlheight) AS vlheight, SUM(a.vllength) AS vllength, COUNT(*) AS nrqtd
				FROM tb_products a
				INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct 
				WHERE b.idcart = :idcart AND b.dtremoved IS NULL
			", [
				":idcart"=>$this->getidcart()
			]);

		return $this->getidcart();

	/*	if(count($results) > 0){
			return $results[0];
		}else{
			return [];
		}
*/
	}

	public function getCalculateTotal($idorder){

		$sql = new Sql();

		$results = $sql->select("
			SELECT a.vltotal as vlsubtotal , b.vlfreight + a.vltotal as vltotal, b.vlfreight
					FROM tb_orders as a
					INNER JOIN tb_carts as b ON a.idcart = b.idcart
						WHERE a.idorder = :idorder", 
			[
				":idorder"=>$idorder 
			]);		
		if(count($results) > 0) $this->setData($results[0]);


	}

	public function getCartTotal($idOrder){

		$sql = new Sql();

		$results = $sql->select("
				SELECT SUM(d.vltotal) + SUM(c.vlfreight) AS total
				FROM  tb_carts as c
				INNER JOIN tb_orders         d ON c.idcart   = d.idcart
				WHERE c.idcart = :idcart 
						AND d.idorder = :idorder
			", [
				":idcart"=>$this->getidcart(),
				":idorder"=>$idOrder
			]);

		if(count($results) > 0){
			return $results;
		}else{
			return 0;
		}
	}

	public function setFreight($nrzipcode){

		$urlws = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/";

		$calcPrecoPrazo = "CalcPrecoPrazo?"; //? no final e para usar para envio de variaveis

		$nrzipcode = str_replace('-', '', $nrzipcode);

		$totals = $this->getProductsTotals();

		if ($totals['nrqtd'] > 0){

			if($totals['vllength'] < 16){
				$totals['vllength'] = 16;
			}

			if($totals['vlheight'] < 2){
				$totals['vlheight'] = 2;
			}

			if($totals['vlprice'] >= 10000){
				$totals['vlprice'] = 9999;
			}

			$qs = http_build_query([
				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010', // consultar tabela para maiores detalhes
				'sCepOrigem'=>'09853120', // CEP da HCode - usado de exemplo
				'sCepDestino'=>$nrzipcode, //CEP de Minha cidade
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'
			]);

			$xml = simplexml_load_file($urlws.$calcPrecoPrazo.$qs);

			$results = $xml->Servicos->cServico;

			if ($results->MsgErro != '' )	{
				Cart::setMsgError('Erro: '.$results->MsgErro);
			}else{
				Cart::clearMsgError();				
			}

			$this->setnrdays($results->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($results->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $results;

		}else{



		}

	}

	public static function formatValueToDecimal($value):float{

		$value = str_replace('.', '', $value);

		return str_replace(',', '.', $value);

	}

	public static function setMsgError($msg){

		$_SESSION[Cart::SESSION_ERROR] = $msg;

	}

	public static function getMsgError(){

		$msg = (isset($_SESSION[Cart::SESSION_ERROR]) ? $_SESSION[Cart::SESSION_ERROR] : "" );

		Cart::clearMsgError();

		return $msg;

	}

	public static function clearMsgError(){

		$_SESSION[Cart::SESSION_ERROR] = NULL;

	}

	public function updateFreight(){

		if($this->getdeszipcode() != '' ){

			$this->setFreight($this->getdeszipcode());

		}

	}

}
?>